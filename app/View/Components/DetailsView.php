<?php

namespace App\View\Components;

use App\View\Concerns\ResolvesThemeView;
use Illuminate\View\Component;

class DetailsView extends Component
{
    use ResolvesThemeView;

    public function __construct(
        public string $model,
        public object $dto,
        public array $fields
    ) {}

    public function render()
    {
        return $this->themeView('details-view');
    }
}
