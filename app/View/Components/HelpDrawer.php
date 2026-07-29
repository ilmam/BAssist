<?php

namespace App\View\Components;

use App\View\Concerns\ResolvesThemeView;
use Illuminate\View\Component;

class HelpDrawer extends Component
{
    use ResolvesThemeView;

    public function __construct(
        public string $id = 'helpDrawer'
    ) {}

    public function render()
    {
        return $this->themeView('help-drawer');
    }
}
