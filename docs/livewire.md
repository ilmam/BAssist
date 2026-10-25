# Livewire forms and pop-ups

**When do I care?** When you change how create / edit forms, the delete confirmation or the view pop-up behave, or you add a field type that must work inside the forms.

Create and edit forms (page and pop-up) run as the `App\Livewire\EntityForm` component. Everything the user sees is unchanged: the same `<x-form>` field controls, the same themes, the same layout. What changes is how the form talks to the server.

| | Classic form | Livewire form |
|---|---|---|
| Leaving a field | nothing | the field is checked against the DTO's rules and its error appears under it |
| Save (page) | full POST + redirect | in place; a new record continues on its edit page |
| Save (pop-up) | `fetch` + JSON | component action; the pop-up closes and the list reloads (`entity-saved`) |
| Alt+S | `fetch` + JSON | the same component action |
| Rules | `EntityValidator` | the same `EntityValidator` |

## How it fits together

| Piece | Where |
|---|---|
| Component (state, `validateField()`, `save()`, authorization) | `app/Livewire/EntityForm.php` |
| View — renders `<x-form :livewire="true">` | `resources/views/livewire/entity-form.blade.php` |
| Bridge — reads the form like a browser would and calls the component | `resources/js/livewire-forms.js` |
| Chooses component or classic form | `pages/generic/form.blade.php`, `pages/modals/form.blade.php` via `EntityForm::supports($fields)` |
| After a pop-up save | `entity-saved` listener in the theme template (reloads the list, closes the pop-up) |

Field values are **not** bound with `wire:model`. The bridge sends the form's values on Save and the changed field on leave, so every control (native inputs, KTSelect, the code editor) works unchanged. Add a new control the usual way; it needs nothing Livewire-specific as long as it is a real form input with a `name`.

## Rules to keep

- **Authorization is in the component.** Livewire's update route does not pass through `entity.access`, so `mount()` and `save()` call `EntityAccess::authorize()`. `model`, `recordId` and `inModal` are `#[Locked]`.
- **Do not store option lists or field definitions in public properties.** They are re-derived in `render()`; public properties travel to the browser and can be edited there.
- **Entities with their own controller stay classic.** The component saves through the repository, so it would skip a custom controller's `store()` / `update()`. `supports()` returns false when `config/crud.php` names a `controller` for the entity.
- **Entities with file fields stay classic.** `EntityForm::supports()` returns false when a form has `file`, `image`, `dropzone`, `attachments` or `tree` fields (they need a multipart request).
- **Livewire re-renders morph the form.** Every validation round trip re-renders it. Widgets that keep state in their own DOM (KTSelect, the code editor, tree) are wrapped in `wire:ignore` by `form-field-cells` / the Metronic 8 form, otherwise they lose their content (the code editor went blank when this was missing). A new widget of that kind must be added to that list (`code`, `select`, `kt-select`, `tree`).
- **Switch off:** `UI_LIVEWIRE_FORMS=false` (`config/ui.php` → `livewire_forms`) uses the classic form everywhere.

## Delete confirmation and view pop-up

| Component | What it adds |
|---|---|
| `App\Livewire\EntityDelete` (`livewire/entity-delete.blade.php`) | Delete through the repository without a fetch; a record someone else already deleted, or one other records still refer to, is explained in the pop-up instead of a browser alert. Dispatches `entity-saved` (the page closes the pop-up and reloads the list). |
| `App\Livewire\EntityView` (`livewire/entity-view.blade.php`) | The details body of the view pop-up, kept current: it polls every 20 s and re-renders **only when the record's `updated_at` changed** (or after `entity-saved`), so sections the reader opened or closed are not reset. Says so when the record was deleted while open. |

The table stays DataTables; only these two pop-ups and the forms are Livewire. Both pop-ups are used through `pages/modals/delete.blade.php` and `pages/modals/view.blade.php`, switched by `EntityDelete::supports()` / `EntityView::supports()`: `UI_LIVEWIRE_DIALOGS=false` (`ui.livewire_dialogs`) or an entity with its own controller keeps the classic pop-up. Texts are in `lang/en/livewire.php`.

## Tests

`tests/Feature/EntityFormLivewireTest.php` and `EntityDialogsLivewireTest.php` (component actions, permissions, switch-off) and `tests/Feature/CrudScreensTest.php` (the screens render with the component). Selects and the code editor were checked by hand in the browser (Metronic 9): values survive validation round trips and save.
