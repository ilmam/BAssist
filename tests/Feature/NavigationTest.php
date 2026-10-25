<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Role;
use App\Models\RoleEntityPermission;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Workspace;
use App\Support\NavTreeBuilder;
use App\Support\ProjectContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menu speed (docs/navigation.md): cached tree, folders of only the active project in
 * the page, the rest loaded on demand, and menu links that swap the page.
 */
class NavigationTest extends TestCase
{
    use RefreshDatabase;

    protected Workspace $workspace;

    protected Project $active;

    protected Project $other;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $tenant = Tenant::query()->create(['name' => 'Acme', 'slug' => 'acme']);
        $this->workspace = Workspace::query()->create(['tenant_id' => $tenant->id, 'name' => 'Main', 'slug' => 'main']);
        $this->active = Project::query()->create(['workspace_id' => $this->workspace->id, 'name' => 'Portal', 'code' => 'POR']);
        $this->other = Project::query()->create(['workspace_id' => $this->workspace->id, 'name' => 'Intranet', 'code' => 'INT']);

        $role = Role::query()->create(['name' => 'Analyst', 'slug' => 'analyst']);
        foreach (['Workspace', 'Project', 'BusinessNeed', 'Risk'] as $entity) {
            RoleEntityPermission::query()->create([
                'role_id' => $role->id, 'entity' => $entity,
                'can_view' => true, 'can_create' => true, 'can_update' => true, 'can_delete' => true,
            ]);
        }

        $this->user = User::factory()->create(['role_id' => $role->id, 'tenant_id' => $tenant->id, 'workspace_id' => $this->workspace->id]);
        $this->actingAs($this->user);
    }

    /** @return list<array<string, mixed>> */
    protected function projectNodes(): array
    {
        $nodes = [];
        $walk = function (array $items) use (&$walk, &$nodes): void {
            foreach ($items as $item) {
                if (($item['type'] ?? null) === 'project') {
                    $nodes[$item['label']] = $item;
                }
                $walk($item['children'] ?? []);
            }
        };
        $walk(app(NavTreeBuilder::class)->build());

        return $nodes;
    }

    public function test_only_the_active_project_carries_its_folders_the_rest_load_on_demand(): void
    {
        app(ProjectContext::class)->set($this->active->id);

        $nodes = $this->projectNodes();

        $this->assertNotEmpty($nodes['Portal']['children']);
        $this->assertArrayNotHasKey('lazy_url', $nodes['Portal']);

        $this->assertSame([], $nodes['Intranet']['children']);
        $this->assertSame(route('nav.project', $this->other), $nodes['Intranet']['lazy_url']);
    }

    public function test_the_folders_endpoint_returns_the_menu_items_of_that_project(): void
    {
        $this->get(route('nav.project', $this->other))
            ->assertOk()
            ->assertSee('nav-phase-folder', false)
            ->assertSee('Business Needs')
            ->assertSee('project_id='.$this->other->id, false);
    }

    public function test_a_project_of_another_tenant_is_not_available_through_the_endpoint(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Other', 'slug' => 'other']);
        $workspace = Workspace::query()->create(['tenant_id' => $tenant->id, 'name' => 'Else', 'slug' => 'else']);
        $foreign = Project::query()->withoutGlobalScopes()->create(['workspace_id' => $workspace->id, 'name' => 'Secret', 'code' => 'SEC']);

        $this->get(route('nav.project', $foreign))->assertNotFound();
    }

    public function test_the_tree_is_cached_and_follows_a_renamed_project(): void
    {
        $this->assertArrayHasKey('Intranet', $this->projectNodes());

        $queries = 0;
        \DB::listen(function () use (&$queries): void {
            $queries++;
        });
        $this->projectNodes();
        $cachedQueries = $queries;

        // The cached tree costs a few cheap stamp queries, not a rebuild of every project.
        $this->assertLessThan(15, $cachedQueries);

        $this->other->forceFill(['name' => 'Staff portal', 'updated_at' => now()->addMinute()])->save();

        $nodes = $this->projectNodes();
        $this->assertArrayHasKey('Staff portal', $nodes);
        $this->assertArrayNotHasKey('Intranet', $nodes);
    }

    public function test_menu_links_swap_the_page_except_for_pages_that_keep_a_full_reload(): void
    {
        $this->assertTrue(nav_item_swaps(['route' => 'business_needs.index']));
        $this->assertTrue(nav_item_swaps(['route' => 'projects.dashboard']));
        $this->assertFalse(nav_item_swaps(['route' => 'swimlane_flows.index']));
        $this->assertFalse(nav_item_swaps(['route' => 'traceability.index']));
        $this->assertFalse(nav_item_swaps(['route' => 'projects.export']));
        $this->assertFalse(nav_item_swaps(['route' => '']));
    }

    public function test_a_navigate_request_does_not_rebuild_the_sidebar(): void
    {
        $this->get(route('business_needs.index'))->assertOk()->assertSee('id="sidebar_menu"', false)->assertSee('wire:navigate', false);

        $this->get(route('business_needs.index'), ['X-Livewire-Navigate' => ''])
            ->assertOk()
            ->assertDontSee('id="sidebar_menu"', false)
            ->assertSee('sidebar-placeholder', false);
    }
}
