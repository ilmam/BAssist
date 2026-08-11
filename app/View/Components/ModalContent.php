<?php

namespace App\View\Components;

use App\View\Concerns\ResolvesThemeView;
use Illuminate\View\Component;

class ModalContent extends Component
{
    use ResolvesThemeView;

    public function __construct(
        public string $title = '',
        /** Dialog chrome: sm | lg | full | fullscreen | end (aliases: md, xl, fs, sheet, modal-fullscreen). */
        public string $size = 'full'
    ) {}

    public function render()
    {
        return $this->themeView('modal-content');
    }
}
