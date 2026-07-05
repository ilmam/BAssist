<?php

namespace App\View\Components;

use App\View\Concerns\ResolvesThemeView;
use Illuminate\View\Component;

class Alert extends Component
{
    use ResolvesThemeView;

    public function render()
    {
        return $this->themeView('alert');
    }
}
