<?php

namespace App\Support;

use App\Attributes\RoutableAttribute;
use App\Repositories\BaseRepository;
use Illuminate\Support\Str;
use ReflectionClass;

class CrudEntityRegistry
{
    public static function all(): array
    {
        return self::applyConfig(self::discover());
    }

    public static function resourceName(string $model): string
    {
        return Str::plural(Str::snake($model));
    }

    public static function modelFromResource(string $resource): ?string
    {
        foreach (self::all() as $model => $options) {
            if (self::resourceName($model) === $resource) {
                return $model;
            }
        }

        return null;
    }

    public static function repository(string $model): BaseRepository
    {
        if (! array_key_exists($model, self::all())) {
            abort(404);
        }

        return RepositoryResolver::make($model);
    }

    protected static function discover(): array
    {
        $entities = [];
        $repositoryPath = app_path('Repositories');

        foreach (glob($repositoryPath.'/*Repository.php') ?: [] as $file) {
            $baseName = basename($file, '.php');

            if ($baseName === 'BaseRepository') {
                continue;
            }

            $model = str_replace('Repository', '', $baseName);
            $repositoryClass = "App\\Repositories\\{$baseName}";
            $modelClass = "App\\Models\\{$model}";

            if (! class_exists($repositoryClass) || ! class_exists($modelClass)) {
                continue;
            }

            if (! is_subclass_of($repositoryClass, BaseRepository::class)) {
                continue;
            }

            if (! self::isRoutable($modelClass)) {
                continue;
            }

            $entities[$model] = array_merge(self::defaults(), [
                'repository' => $repositoryClass,
            ]);
        }

        ksort($entities);

        return $entities;
    }

    protected static function isRoutable(string $modelClass): bool
    {
        $attributes = (new ReflectionClass($modelClass))->getAttributes(RoutableAttribute::class);

        if ($attributes === []) {
            return false;
        }

        return $attributes[0]->newInstance()->enabled;
    }

    protected static function defaults(): array
    {
        return [
            'modal_actions' => ['view', 'edit', 'delete'],
            'nav' => false,
            'home' => false,
        ];
    }

    protected static function applyConfig(array $entities): array
    {
        foreach (config('crud.exclude', []) as $model) {
            unset($entities[$model]);
        }

        foreach (config('crud.models', []) as $model => $options) {
            if ($options['disabled'] ?? false) {
                unset($entities[$model]);

                continue;
            }

            if (! isset($entities[$model])) {
                continue;
            }

            $entities[$model] = array_replace_recursive($entities[$model], $options);
        }

        return $entities;
    }
}
