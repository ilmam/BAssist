<?php

namespace App\View\Components;

use App\Support\CrudEntityRegistry;
use App\Support\HelpRegistry;
use App\View\Concerns\ResolvesThemeView;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;

class HelpTrigger extends Component
{
    use ResolvesThemeView;

    public ?string $url = null;

    public function __construct(
        public ?string $model = null,
        public ?string $topic = null,
    ) {
        $this->url = $this->resolveUrl();
    }

    public function shouldRender(): bool
    {
        return is_string($this->url) && $this->url !== '';
    }

    public function render()
    {
        return $this->themeView('help-trigger');
    }

    protected function resolveUrl(): ?string
    {
        if (is_string($this->model) && $this->model !== '') {
            $model = class_basename($this->model);

            if (! HelpRegistry::existsForModel($model)) {
                return null;
            }

            $routeName = model_route_name($model, 'help');

            return Route::has($routeName) ? route($routeName) : null;
        }

        if (is_string($this->topic) && $this->topic !== '') {
            $key = HelpRegistry::normalizeKey($this->topic);

            if (! HelpRegistry::exists($key)) {
                return null;
            }

            // Prefer hub/topic route; fall back to CRUD resource route when the topic is a resource name.
            if (Route::has($key.'.help')) {
                return route($key.'.help');
            }

            $model = CrudEntityRegistry::modelFromResource($key);

            if ($model !== null && Route::has(model_route_name($model, 'help'))) {
                return model_route($model, 'help');
            }
        }

        return null;
    }
}
