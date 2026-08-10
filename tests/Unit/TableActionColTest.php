<?php

namespace Tests\Unit;

use App\Helpers\Ui;
use Tests\TestCase;

class TableActionColTest extends TestCase
{
    public function test_collapsed_menu_renders_kt_menu_with_labels_and_icons(): void
    {
        $html = Ui::TableActionCol([
            ['action' => 'show', 'icon' => 'eye', 'link' => '/items/{id}', 'modalUrl' => '/items/modal/{id}/view'],
            ['action' => 'edit', 'icon' => 'pencil', 'link' => '/items/{id}/edit', 'modalUrl' => '/items/modal/{id}/edit'],
            ['action' => 'delete', 'icon' => 'trash', 'link' => '/items/{id}', 'modalUrl' => '/items/modal/{id}/delete'],
        ], collapsed: true);

        $this->assertStringContainsString('data-kt-menu="true"', $html);
        $this->assertStringContainsString('ki-dots-vertical', $html);
        $this->assertStringContainsString('ki-eye', $html);
        $this->assertStringContainsString('ki-pencil', $html);
        $this->assertStringContainsString('ki-trash', $html);
        $this->assertStringContainsString('View', $html);
        $this->assertStringContainsString('Edit', $html);
        $this->assertStringContainsString('Delete', $html);
        $this->assertStringContainsString('kt-menu-separator', $html);
        $this->assertStringContainsString('data-action="delete"', $html);
        $this->assertStringContainsString('js-open-modal', $html);
    }

    public function test_inline_actions_still_render_icon_buttons_by_default(): void
    {
        $html = Ui::TableActionCol([
            ['action' => 'show', 'icon' => 'eye', 'link' => '/items/{id}', 'text' => ''],
        ], collapsed: false);

        $this->assertStringContainsString('kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost', $html);
        $this->assertStringNotContainsString('data-kt-menu="true"', $html);
        $this->assertStringNotContainsString('ki-dots-vertical', $html);
    }

    public function test_show_split_menu_renders_custom_items_with_id_placeholders(): void
    {
        $html = Ui::TableActionCol([
            [
                'action' => 'show',
                'icon' => 'eye',
                'link' => '/features/{id}',
                'modalUrl' => '/features/modal/{id}/view',
                'text' => '',
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
        ], collapsed: false);

        $this->assertStringContainsString('data-kt-dropdown="true"', $html);
        $this->assertStringContainsString('action-split-btn', $html);
        $this->assertStringContainsString('View raw', $html);
        $this->assertStringContainsString('/features/modal/{id}/raw', $html);
        $this->assertStringContainsString('js-open-modal', $html);
        $this->assertStringContainsString('data-modal-url="/features/modal/{id}/raw"', $html);
    }
}
