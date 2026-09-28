<?php

namespace Tests\Feature;

use App\Data\ChangeRequestData;
use App\Data\FunctionalRequirementData;
use App\Data\NonFunctionalRequirementData;
use App\Data\RiskData;
use App\Models\FunctionalRequirement;
use App\Models\Project;
use App\Models\Role;
use App\Models\RoleEntityPermission;
use App\Models\StakeholderNeed;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Workspace;
use App\Support\CrudEntityRegistry;
use App\Support\DtoMetadata;
use App\Support\RiskStatus;
use App\Support\Validation\EntityValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Fixtures\ValidationLevelsData;
use Tests\TestCase;

/**
 * Framework-level validation (docs/validation.md): every entity save is
 * validated from its edit DTO, with no per-page code.
 */
class FrameworkValidationTest extends TestCase
{
    use RefreshDatabase;

    protected Project $project;

    protected StakeholderNeed $need;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::query()->create(['name' => 'Acme', 'slug' => 'acme']);
        $workspace = Workspace::query()->create(['tenant_id' => $tenant->id, 'name' => 'Main', 'slug' => 'main']);
        $this->project = Project::query()->create(['workspace_id' => $workspace->id, 'name' => 'Portal', 'code' => 'POR']);
        $this->need = StakeholderNeed::query()->create(['project_id' => $this->project->id, 'title' => 'Track orders']);

        $role = Role::query()->create(['name' => 'Analyst', 'slug' => 'analyst']);
        foreach (['FunctionalRequirement', 'Risk'] as $entity) {
            RoleEntityPermission::query()->create([
                'role_id' => $role->id, 'entity' => $entity,
                'can_view' => true, 'can_create' => true, 'can_update' => true, 'can_delete' => true,
            ]);
        }

        $this->user = User::factory()->create([
            'role_id' => $role->id,
            'tenant_id' => $tenant->id,
            'workspace_id' => $workspace->id,
        ]);
    }

    // --- Web, AJAX and API saves all validate -------------------------------

    public function test_full_page_save_with_blank_required_fields_goes_back_with_field_errors(): void
    {
        $this->actingAs($this->user)
            ->from(route('functional_requirements.create'))
            ->post(route('functional_requirements.store'), $this->frPayload(['title' => '', 'statement' => '']))
            ->assertRedirect(route('functional_requirements.create'))
            ->assertSessionHasErrors(['title', 'statement'])
            ->assertSessionHasInput('acceptance_criteria', 'Given an order');

        $this->assertSame(0, FunctionalRequirement::query()->count());
    }

    public function test_modal_ajax_save_returns_422_with_field_errors(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('functional_requirements.store'), $this->frPayload(['title' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title'])
            ->assertJsonMissingValidationErrors(['statement']);
    }

    public function test_a_field_left_out_of_the_request_is_validated_as_empty(): void
    {
        $payload = $this->frPayload();
        unset($payload['title']);

        $this->actingAs($this->user)
            ->postJson(route('api.functional_requirement.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_valid_save_succeeds_and_update_is_validated_too(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('functional_requirements.store'), $this->frPayload())
            ->assertOk()
            ->assertJsonPath('success', true);

        $fr = FunctionalRequirement::query()->firstOrFail();

        $this->putJson(route('functional_requirements.update', $fr->id), $this->frPayload(['title' => str_repeat('x', 256)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);

        $this->assertSame('Show order status', $fr->fresh()->title);
    }

    // --- Inferred rules (level 0) --------------------------------------------

    public function test_one_of_requires_exactly_one_parent(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('functional_requirements.store'), $this->frPayload(['stakeholder_need_id' => null]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['stakeholder_need_id', 'change_request_id']);
    }

    public function test_select_from_a_fixed_list_rejects_unlisted_values(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('functional_requirements.store'), $this->frPayload(['priority_id' => 999999]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['priority_id']);

        $rules = $this->entityRules(RiskData::class);
        $this->assertContains('in:"'.implode('","', RiskStatus::values()).'"', $rules['status']);
    }

    public function test_readonly_fields_are_never_taken_from_input(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('functional_requirements.store'), $this->frPayload(['code' => 'HACKED-1']))
            ->assertOk();

        $this->assertNotSame('HACKED-1', FunctionalRequirement::query()->firstOrFail()->code);
    }

    // --- Browser side ---------------------------------------------------------

    public function test_form_shows_required_and_maxlength_hints_from_the_rules(): void
    {
        $html = $this->actingAs($this->user)->get(route('functional_requirements.create'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<input(?=[^>]*name="title")(?=[^>]*required)[^>]*>/', $html);
        $this->assertMatchesRegularExpression('/<input(?=[^>]*name="title")(?=[^>]*maxlength="255")[^>]*>/', $html);
        $this->assertMatchesRegularExpression('/<textarea(?=[^>]*name="statement")(?=[^>]*required)[^>]*>/', $html);
        $this->assertStringContainsString('data-field-error-for="title"', $html);
        $this->assertStringContainsString('data-form-errors', $html);
    }

    public function test_failed_full_page_save_redisplays_input_and_error_under_the_field(): void
    {
        $this->actingAs($this->user)
            ->from(route('functional_requirements.create'))
            ->followingRedirects()
            ->post(route('functional_requirements.store'), $this->frPayload(['title' => '', 'statement' => 'Typed but kept']))
            ->assertOk()
            ->assertSee('Typed but kept')
            ->assertSee(__('validation.required', ['attribute' => 'title']));
    }

    // --- Quick Create ---------------------------------------------------------

    public function test_required_fields_are_never_hidden_on_quick_create(): void
    {
        $this->assertArrayHasKey('statement', DtoMetadata::for(FunctionalRequirementData::class)->quickCreateVisibleFormFields());
        $this->assertArrayHasKey('description', DtoMetadata::for(NonFunctionalRequirementData::class)->quickCreateVisibleFormFields());
        $this->assertArrayHasKey('problem', DtoMetadata::for(ChangeRequestData::class)->quickCreateVisibleFormFields());

        // Optional fields marked hideQuick stay hidden.
        $this->assertArrayNotHasKey('acceptance_criteria', DtoMetadata::for(FunctionalRequirementData::class)->quickCreateVisibleFormFields());
    }

    public function test_no_entity_form_has_a_required_field_the_user_cannot_fill(): void
    {
        foreach (array_keys(CrudEntityRegistry::all()) as $model) {
            $dtoClass = CrudEntityRegistry::repository($model)->editDto;
            $metadata = DtoMetadata::for($dtoClass);
            $visible = array_keys($metadata->quickCreateVisibleFormFields());
            $hiddenDefaults = $metadata->quickCreateHiddenDefaults();

            foreach ($this->entityRules($dtoClass) as $field => $rules) {
                if (! in_array('required', $rules, true) || in_array($field, DtoMetadata::CONTEXT_FILLED_FIELDS, true)) {
                    continue;
                }

                $hidden = array_key_exists($field, $hiddenDefaults);
                $fillable = in_array($field, $visible, true)
                    || ($hidden && ! in_array($hiddenDefaults[$field], ['', null], true))
                    || (! $hidden && ! array_key_exists($field, $metadata->formFields()));

                $this->assertTrue($fillable, "{$model}: required field [{$field}] cannot be filled on Quick Create.");
            }
        }
    }

    // --- Every declaration level (fixture DTO) --------------------------------

    public function test_every_declaration_level_is_enforced(): void
    {
        $valid = [
            'name' => 'Checkout', 'code' => 'CHK', 'summary' => 'Short',
            'email' => 'a@b.c', 'starts_on' => '2026-01-01', 'ends_on' => '2026-02-01',
            'status' => RiskStatus::OPEN,
        ];

        $this->assertSame('Checkout', EntityValidator::validate(ValidationLevelsData::class, $valid)->name);

        $cases = [
            'level 0 inferred required' => [['name' => ''], 'name'],
            'level 0 listed option' => [['status' => 'bogus'], 'status'],
            'level 0 OneOf (both)' => [['phone' => '123'], 'email'],
            'level 0 OneOf (neither)' => [['email' => null], 'email'],
            'level 1 #[Rule] on a field' => [['code' => 'chk'], 'code'],
            'level 1 #[Max] override' => [['summary' => str_repeat('a', 21)], 'summary'],
            'level 2 rule class' => [['summary' => 'TBD later'], 'summary'],
            'level 3 rules() across fields' => [['ends_on' => '2025-12-31'], 'ends_on'],
            'level 4 after() hook' => [['status' => RiskStatus::CLOSED, 'ends_on' => null], 'status'],
        ];

        foreach ($cases as $label => [$override, $field]) {
            try {
                EntityValidator::validate(ValidationLevelsData::class, array_merge($valid, $override));
                $this->fail("{$label}: expected a validation error on [{$field}].");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey($field, $e->errors(), "{$label}: ".json_encode($e->errors()));
            }
        }
    }

    public function test_after_hook_does_not_run_while_other_rules_fail(): void
    {
        try {
            EntityValidator::validate(ValidationLevelsData::class, [
                'name' => '', 'email' => 'a@b.c', 'status' => RiskStatus::CLOSED,
            ]);
            $this->fail('Expected validation to fail.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('name', $e->errors());
            $this->assertArrayNotHasKey('status', $e->errors());
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function frPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Show order status',
            'project_id' => $this->project->id,
            'stakeholder_need_id' => $this->need->id,
            'change_request_id' => null,
            'statement' => 'The system shall show the order status.',
            'acceptance_criteria' => 'Given an order',
        ], $overrides);
    }
}
