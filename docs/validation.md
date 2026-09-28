# Validation — how saves are checked

Every save is validated by the framework from the entity's **edit DTO** (`app/Data/{Model}Data.php`). That covers full-page forms, modals, Quick Create, Alt+S save-in-place and the JSON API. You do not write a Form Request, and you do not write validation per page.

For an ordinary field you write **nothing**: the rule is read from the property you already declared. You only declare what is genuinely special, at the lowest level that fits.

```
Property + #[Form]  ─┐
#[OneOf]            ─┤
#[Max] / #[Rule]    ─┼─►  EntityValidator  ─►  save   (or field errors back to the form)
rules()             ─┤
after()             ─┘
```

Check what an entity enforces at any time:

```bash
php artisan entity:rules FunctionalRequirement
```

It prints every field, its rules, and which level declared them.

---

## The levels at a glance

| Level | Your case | Where you declare it |
|-------|-----------|----------------------|
| **0** | Normal fields: required, type, length, valid option, related record | Nothing: inferred from the property and its `#[Form]` |
| **0** | "Exactly one of these fields" | `#[OneOf(...)]` on the class |
| **1** | One field needs an extra or different rule | `#[Rule(...)]`, `#[Max(...)]`, … on the property |
| **2** | A reusable rule of your own | A rule class, used through `#[Rule(new YourRule)]` |
| **3** | A rule that relates several fields | `rules()`, only those lines |
| **4** | A business check that needs the database | `after()` hook |

Each level **adds to** the ones below it; nothing needs repeating. Use the lowest level that fits.

---

## Level 0 — inferred (write nothing)

Rules come from the PHP property and its `#[Form]` / `#[ListForm]` attribute:

| You declared | Rule the framework adds |
|--------------|-------------------------|
| `public string $title = ''` | `required`, `string` |
| `public ?string $notes = null` | `nullable`, `string` |
| `public int $sort_order = 0` / `public bool $is_outline` | `required`, `numeric` / `boolean` |
| `public array $elements = []` | `present`, `array` (an empty list is valid) |
| `#[Form('text')]` on a string | `max:255` |
| `#[Form('textarea')]`, `#[Form('code')]` | no length limit |
| `#[Form('select', 'Project')]` (a model) | the id must exist **in your tenant** (`RecordExists`) |
| `#[Form('select', 'RiskStatus')]` (an `App\Support` list) | must be one of `RiskStatus::values()` |
| `#[Form(..., readonly: true)]` | never taken from input (`exclude`) |

The rule for selects follows the form builder's own convention. If `App\Support\{Name}` exists with `values()` or `selectOptions()`, it is a fixed list. Otherwise it is a related `App\Models\{Name}` record.

### "Exactly one of": `#[OneOf]`

```php
use App\Attributes\OneOf;

#[OneOf('stakeholder_need_id', 'change_request_id')]
class FunctionalRequirementData extends BaseData
```

At least one of the fields must be filled in, and never two together. Keep the fields nullable on the DTO. The attribute is repeatable if a class has two independent groups.

### Missing fields count as empty

A declared form field that is missing from the request is validated as empty. So an API call that leaves out `title` is rejected, rather than saving `''`. The exceptions are controls that submit nothing when empty: checkbox, attachments, file, image and dropzone.

### Quick Create never hides a required field

A field marked `hideQuick: true` is still shown on Quick Create when the user must fill it in. That applies when its PHP type is not nullable and its default is empty (`''`, `0`, `null`). Otherwise the form could never be saved. `project_id` and `workspace_id` are exempt, because the framework fills them from the selected project and workspace.

---

## Level 1 — an extra rule on one field

Add a validation attribute next to `#[Form]`. The inferred rules stay; yours is added.

```php
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Rule;

#[Form('text'), Rule('regex:/^[A-Z]{2,5}$/')]    // project code: 2–5 capitals
public string $code = '',

#[Form('text'), Rule('email')]
public ?string $contact_email = null,

#[Form('text', hideQuick: true), Max(2000)]      // replaces the default max:255
public ?string $success_measure = null,
```

`#[Rule]` accepts any [Laravel rule](https://laravel.com/docs/validation#available-validation-rules) as a string. Spatie also ships dedicated attributes (`Min`, `Max`, `Email`, `Url`, `Regex`, `In`, `After`, `Date`, …) in `Spatie\LaravelData\Attributes\Validation`.

An explicit `#[Max]` replaces the inferred `max:255`; it is not added on top.

Real examples: `BusinessObjectiveData` (`Max(2000)`), `StatusData` / `PriorityData` (`Min(0)`), `StateFlowData` (`Max(255)`, `Max(1000)`).

---

## Level 2 — your own reusable rule

When no built-in rule fits and you need it in more than one place, write a Laravel rule class and use it through `#[Rule]`:

```php
// app/Rules/NoPlaceholderText.php
class NoPlaceholderText implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && preg_match('/\b(TBD|TODO)\b/i', $value)) {
            $fail('The :attribute still contains placeholder text.');
        }
    }
}
```

```php
#[Form('textarea'), Rule(new NoPlaceholderText)]
public ?string $acceptance_criteria = null,
```

The framework's own `App\Rules\RecordExists` is an example of this level: a tenant-aware "the id exists".

---

## Level 3 — rules across several fields: `rules()`

Keep a `rules()` method **only** for rules that relate fields, or for nested editor rows. It is merged on top of everything above (`BaseData` carries `#[MergeValidationRules]`), so list only the special lines.

```php
public static function rules(): array
{
    return [
        'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        'target'  => ['required_if:measure_type,numeric', 'numeric'],
    ];
}
```

Nested rows, e.g. a diagram editor, use dot paths:

```php
'elements.*.lane'  => ['nullable', 'string', 'max:255'],
'elements.*.type'  => ['nullable', 'string', Rule::in([...SwimlaneMermaidGenerator::TYPES, ''])],
'elements.*.stakeholder_need_id' => ['nullable', 'integer', new RecordExists(StakeholderNeed::class)],
```

Use `new RecordExists(Model::class)` rather than `exists:table,id` for ids, so the check respects tenancy.

Real examples: `SwimlaneFlowData`, `StateFlowData`, `ArchitectureData`, `DataDictionaryData`.

---

## Level 4 — business checks: `after()`

For rules that need the database or real domain logic. It runs **only when every other rule passed**, so the input is already well-formed.

```php
use Illuminate\Validation\Validator;

public static function after(Validator $validator, array $input): void
{
    // "A risk can't be closed while it has no linked requirement"
    if ($input['status'] === RiskStatus::CLOSED && empty($input['subject_id'])) {
        $validator->errors()->add('status', 'Link the risk to a requirement before closing it.');
    }
}
```

Add the error to the field it concerns and it appears under that field, like any other error.

---

## What the user sees

| Save path | On invalid input |
|-----------|------------------|
| Full-page form | Back to the form, with what they typed kept and each message under its field |
| Modal, Quick Create, Alt+S | Stays open (HTTP 422); messages appear under their fields |
| JSON API | HTTP 422 with `{ "message": …, "errors": { "field": ["…"] } }` |

Messages that do not belong to a visible field (for example `elements.3.lane`) are listed in a summary at the top of the form.

The same rules also give the browser a head start: `required` and `maxlength` are added to text, textarea and number inputs. These hints are only a convenience; the server is always the authority. `required` is left off enhanced controls (code editors, searchable selects) because they hide their native element.

Error display is generic:
- **Server side:** `pages/partials/form-field-error.blade.php` under each field and `pages/partials/form-errors-summary.blade.php` at the top of the form, in both themes.
- **Client side:** `window.bassistShowFormErrors(form, payload)` in `resources/js/form-safety.js`.

If you build a custom form Blade, include those two partials to get the same behaviour.

---

## Where it lives

| Piece | File |
|-------|------|
| Inference (level 0) | `app/Support/Validation/FormRuleInferrer.php`, registered in `config/data.php` → `rule_inferrers` |
| `#[OneOf]` | `app/Attributes/OneOf.php` |
| Merge + `after()` wiring | `app/Data/BaseData.php` |
| Enforcement (every save) | `app/Support/Validation/EntityValidator.php`, called by `BaseController` and `Api\BaseApiController` |
| Tenant-aware exists | `app/Rules/RecordExists.php` |
| Browser hints | `app/Support/Validation/ValidationHints.php` |
| Rule report | `php artisan entity:rules {Model}` |
| Tests | `tests/Feature/FrameworkValidationTest.php`, with a fixture DTO using every level: `tests/Fixtures/ValidationLevelsData.php` |

Tenancy adds one more check on every save, independent of these levels: referenced ids must belong to your tenant. See [tenancy.md](tenancy.md).
