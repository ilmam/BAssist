<?php

namespace App\View\Concerns;

trait ResolvesThemeView
{
    protected function themeView(string $component): \Illuminate\Contracts\View\View
    {
        return ui_component_view($component);
    }
}
