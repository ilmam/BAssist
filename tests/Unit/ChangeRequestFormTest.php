<?php

namespace Tests\Unit;

use App\Data\ChangeRequestData;
use App\Http\Controllers\ChangeRequestController;
use App\Models\ChangeRequest;
use App\Support\ChangeRequestAffectedType;
use App\Support\ChangeRequestImpact;
use App\Support\ChangeRequestStatus;
use App\Support\CrudEntityRegistry;
use App\Support\EntityFormBuilder;
use Illuminate\Http\Request;
use Tests\TestCase;

class ChangeRequestFormTest extends TestCase
{
    public function test_entity_is_registered_for_crud(): void
    {
        $this->assertContains('ChangeRequest', array_keys(CrudEntityRegistry::all()));
    }

    public function test_entity_number_prefix_is_cr(): void
    {
        $method = new \ReflectionMethod(ChangeRequest::class, 'entityNumberPrefix');

        $this->assertSame('CR', $method->invoke(null));
    }

    public function test_form_uses_type_and_item_subject_fields(): void
    {
        $fields = (new EntityFormBuilder)->fields(ChangeRequestData::class);

        $this->assertSame('text', $fields['requestor']['type'] ?? null);
        $this->assertSame('select', $fields['impact_level']['type'] ?? null);
        $this->assertSame('select', $fields['affected_type']['type'] ?? null);
        $this->assertSame('select', $fields['affected_id']['type'] ?? null);
        $this->assertArrayNotHasKey('business_need_id', $fields);
        $this->assertArrayNotHasKey('feature_id', $fields);
        $this->assertEqualsCanonicalizing(
            ChangeRequestAffectedType::values(),
            array_keys($fields['affected_type']['list'] ?? [])
        );
        $this->assertEqualsCanonicalizing(
            ChangeRequestImpact::values(),
            array_keys($fields['impact_level']['list'] ?? [])
        );
    }

    public function test_validation_requires_core_intake_fields(): void
    {
        $rules = ChangeRequestData::rules();

        $this->assertContains('required', $rules['problem']);
        $this->assertContains('required', $rules['proposed_change']);
        $this->assertContains('required', $rules['requestor']);
        $this->assertContains('required', $rules['impact_level']);
    }

    public function test_review_statuses_require_affected_subject(): void
    {
        $this->assertContains(ChangeRequestStatus::UNDER_REVIEW, ChangeRequestStatus::requiresAffected());
        $this->assertNotContains(ChangeRequestStatus::DRAFT, ChangeRequestStatus::requiresAffected());
    }

    public function test_create_form_prefills_affected_subject_from_query(): void
    {
        $request = Request::create('/change_requests/modal/create', 'GET', [
            'project_id' => 5,
            'affected_type' => ChangeRequestAffectedType::BUSINESS_OBJECTIVE,
            'affected_id' => 12,
        ]);
        $this->app->instance('request', $request);

        $controller = app(ChangeRequestController::class);
        $method = new \ReflectionMethod(ChangeRequestController::class, 'applyStickyContextDefaults');
        $method->setAccessible(true);

        $dto = $method->invoke(
            $controller,
            ChangeRequestData::from(ChangeRequestData::empty())
        );

        $this->assertSame(5, $dto->project_id);
        $this->assertSame(ChangeRequestAffectedType::BUSINESS_OBJECTIVE, $dto->affected_type);
        $this->assertSame(12, $dto->affected_id);
    }
}
