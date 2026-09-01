<?php

namespace App\View\Components;

use App\View\Concerns\ResolvesThemeView;
use Illuminate\View\Component;

class FormCardBody extends Component
{
    use ResolvesThemeView;

    public function render()
    {
        return $this->themeView('form-card-body');
    }
}
