<?php

namespace App\View\Components;

use App\View\Concerns\ResolvesThemeView;
use Illuminate\View\Component;

class ModalDismiss extends Component
{
    use ResolvesThemeView;

    public function __construct(
        public string $text = 'Close'
    ) {}

    public function render()
    {
        return $this->themeView('modal-dismiss');
    }
}
