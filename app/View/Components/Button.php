<?php

namespace App\View\Components;

use App\View\Concerns\ResolvesThemeView;
use Illuminate\View\Component;

class Button extends Component
{
    use ResolvesThemeView;

    public function __construct(
        public string $type = 'button',
        public string $text = '',
        public string $icon = '',
        public string $iconOnly = '',
        public string $color = 'light',
        public string $activeColor = 'primary',
        public string $size = 'md',
        public string $width = '',
        public string $height = '',
        public string $class = '',
        public string $href = '',
        public string $onclick = ''
    ) {}

    public function render()
    {
        return $this->themeView('button');
    }
}
