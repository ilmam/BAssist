<?php

namespace App\Http\Controllers\Api;

use App\Support\CrudEntityRegistry;
use App\Support\EntityAccess;
use Illuminate\Support\Str;

class CrudController extends BaseApiController
{
    public function callAction($method, $parameters)
    {
        $this->modelName = $this->resolveModelName();
        $this->modelRepository = CrudEntityRegistry::repository($this->modelName);

        EntityAccess::authorize(
            auth()->user(),
            $this->modelName,
            EntityAccess::abilityForControllerMethod($method)
        );

        return parent::callAction($method, $parameters);
    }

    protected function resolveModelName(): string
    {
        $name = request()->route()?->getName();

        if (! is_string($name) || $name === '') {
            abort(404);
        }

        $resource = explode('.', $name, 3)[1] ?? '';
        $model = Str::studly($resource);

        if (! array_key_exists($model, CrudEntityRegistry::all())) {
            abort(404);
        }

        return $model;
    }
}
