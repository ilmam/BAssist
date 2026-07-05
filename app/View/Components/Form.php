<?php

namespace App\View\Components;

use App\View\Concerns\ResolvesThemeView;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class Form extends Component
{
    use ResolvesThemeView;

    public $cancelRoute;

    public function __construct(
        public string $route,
        public object $dto,
        public array $fieldsArray = [],
        public string $id = 'form1',
        public string $verb = 'post',
        public string $model = '',
        public bool $inModal = false,
    ) {
        $this->cancelRoute = strtolower(Str::plural(Str::snake(class_basename($this->model ?: 'item')))).'.index';
    }

    public function render()
    {
        return $this->themeView('form');
    }
}
