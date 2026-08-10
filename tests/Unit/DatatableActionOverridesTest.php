<?php

namespace Tests\Unit;

use App\View\Components\Datatable;
use ReflectionClass;
use Tests\TestCase;

class DatatableActionOverridesTest extends TestCase
{
    public function test_show_action_override_adds_split_menu_items(): void
    {
        $component = new Datatable(
            options: [
                'model' => 'Feature',
                'columns' => ['title'],
                'actionOverrides' => [
                    'show' => [
                        'menu' => true,
                        'menuItems' => [
                            [
                                'label' => 'View',
                                'link' => '/features/{id}',
                                'modalUrl' => '/features/modal/{id}/view',
                            ],
                            [
                                'label' => 'View raw',
                                'link' => '/features/{id}',
                                'modalUrl' => '/features/modal/{id}/raw',
                            ],
                        ],
                    ],
                ],
            ],
            defaultButtons: true,
            collapsedActions: false,
        );

        $buttons = $this->buttonsFrom($component);
        $show = collect($buttons)->firstWhere('action', 'show');

        $this->assertIsArray($show);
        $this->assertTrue($show['menu'] ?? false);
        $this->assertCount(2, $show['menuItems'] ?? []);
        $this->assertSame('View raw', $show['menuItems'][1]['label'] ?? null);
        $this->assertStringContainsString('/modal/{id}/raw', $show['menuItems'][1]['modalUrl'] ?? '');
    }

    public function test_collapsed_actions_strip_show_split_menu(): void
    {
        $component = new Datatable(
            options: [
                'model' => 'Feature',
                'columns' => ['title'],
                'actionOverrides' => [
                    'show' => [
                        'menu' => true,
                        'menuItems' => [
                            [
                                'label' => 'View raw',
                                'link' => '/features/{id}',
                                'modalUrl' => '/features/modal/{id}/raw',
                            ],
                        ],
                    ],
                ],
            ],
            defaultButtons: true,
            collapsedActions: true,
        );

        $buttons = $this->buttonsFrom($component);
        $show = collect($buttons)->firstWhere('action', 'show');

        $this->assertIsArray($show);
        $this->assertArrayNotHasKey('menu', $show);
        $this->assertArrayNotHasKey('menuItems', $show);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function buttonsFrom(Datatable $component): array
    {
        $ref = new ReflectionClass($component);
        $prop = $ref->getProperty('buttons');
        $prop->setAccessible(true);

        /** @var list<array<string, mixed>> $buttons */
        $buttons = $prop->getValue($component);

        return $buttons;
    }
}
