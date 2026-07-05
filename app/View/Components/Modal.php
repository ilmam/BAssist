<?php

namespace App\View\Components;

use App\View\Concerns\ResolvesThemeView;
use Illuminate\View\Component;

class Modal extends Component
{
    use ResolvesThemeView;

    public function __construct(
        public string $id = 'mianModal',
        public string $title = '',
        public string $size = ''
    ) {}

    public function render()
    {
        return $this->themeView('modal');
    }
}
