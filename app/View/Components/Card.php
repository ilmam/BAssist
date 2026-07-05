<?php

namespace App\View\Components;

use App\View\Concerns\ResolvesThemeView;
use Illuminate\View\Component;

class Card extends Component
{
    use ResolvesThemeView;

    public function __construct(
        public string $id = 'card1',
        public string $title = '',
        public string $class = ''
    ) {}

    public function render()
    {
        return $this->themeView('card');
    }
}
