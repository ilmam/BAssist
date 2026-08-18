<?php

namespace Tests\Unit;

use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

class RespondAfterMutationTest extends TestCase
{
    public function test_full_page_save_redirects_to_edit_with_status_flash(): void
    {
        $controller = $this->makeController('Risk');
        $request = Request::create('/risks/7', 'PUT');

        $response = $this->invokeRespondAfterMutation($controller, $request, (object) ['id' => 7]);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertTrue($response->isRedirect(route('risks.edit', 7)));
        $this->assertSame(__('ui.record_saved'), $response->getSession()->get('status'));
    }

    public function test_full_page_create_redirects_to_edit_of_new_id(): void
    {
        $controller = $this->makeController('SwimlaneFlow');
        $request = Request::create('/swimlane_flows', 'POST');

        $response = $this->invokeRespondAfterMutation($controller, $request, ['id' => 42, 'title' => 'Flow']);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertTrue($response->isRedirect(route('swimlane_flows.edit', 42)));
    }

    public function test_destroy_without_record_redirects_to_index(): void
    {
        $controller = $this->makeController('Risk');
        $request = Request::create('/risks/7', 'DELETE');

        $response = $this->invokeRespondAfterMutation($controller, $request, null);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertTrue($response->isRedirect(route('risks.index')));
    }

    public function test_ajax_modal_save_returns_json_and_does_not_redirect(): void
    {
        $controller = $this->makeController('Risk');
        $request = Request::create('/risks/7', 'PUT', [], [], [], [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $response = $this->invokeRespondAfterMutation($controller, $request, [
            'id' => 7,
            'title' => 'Saved risk',
        ]);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $payload = $response->getData(true);
        $this->assertTrue($payload['success']);
        $this->assertSame(7, $payload['record']['id']);
    }

    public function test_ajax_payload_carries_urls_so_alt_s_can_save_in_place(): void
    {
        $controller = $this->makeController('SwimlaneFlow');
        $request = Request::create('/swimlane_flows', 'POST', [], [], [], [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $response = $this->invokeRespondAfterMutation($controller, $request, ['id' => 42, 'title' => 'Flow']);

        $payload = $response->getData(true);

        // A create form uses these to become an edit form, so a second Alt+S
        // updates record 42 instead of inserting a duplicate.
        $this->assertSame(route('swimlane_flows.update', 42), $payload['record']['update_url']);
        $this->assertSame(route('swimlane_flows.edit', 42), $payload['record']['edit_url']);
    }

    private function makeController(string $modelName): BaseController
    {
        $controller = new class extends BaseController {};
        $controller->modelName = $modelName;

        return $controller;
    }

    private function invokeRespondAfterMutation(BaseController $controller, Request $request, mixed $record): mixed
    {
        $method = new \ReflectionMethod(BaseController::class, 'respondAfterMutation');

        return $method->invoke($controller, $request, $record);
    }
}
