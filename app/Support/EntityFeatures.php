<?php

namespace App\Support;

use App\Attributes\Approvable;
use App\Attributes\Commentable;
use App\Attributes\Tracked;
use ReflectionClass;

/**
 * Which optional framework features an entity opted into, declared with a PHP
 * attribute on the model (same idea as #[Attachable]):
 *
 *   #[Commentable]  comment threads     #[Tracked]  history     #[Approvable]  review
 *
 * No list to maintain anywhere else: services, routes, the details view and the
 * observer registration all ask this class.
 */
class EntityFeatures
{
    /** @var array<class-string, list<string>>|null */
    protected static ?array $cache = null;

    public static function commentable(string $model): bool
    {
        return self::has($model, Commentable::class);
    }

    public static function tracked(string $model): bool
    {
        return self::has($model, Tracked::class);
    }

    public static function approvable(string $model): bool
    {
        return self::has($model, Approvable::class);
    }

    /** @return list<string> model basenames carrying the attribute */
    public static function models(string $attribute): array
    {
        return self::map()[$attribute] ?? [];
    }

    public static function has(string $model, string $attribute): bool
    {
        return in_array(class_basename($model), self::models($attribute), true);
    }

    public static function flush(): void
    {
        self::$cache = null;
    }

    /** @return array<class-string, list<string>> */
    protected static function map(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $map = [Commentable::class => [], Tracked::class => [], Approvable::class => []];

        foreach (glob(app_path('Models').'/*.php') ?: [] as $file) {
            $name = basename($file, '.php');
            $class = 'App\\Models\\'.$name;
            if (! class_exists($class)) {
                continue;
            }
            $reflection = new ReflectionClass($class);
            foreach (array_keys($map) as $attribute) {
                if ($reflection->getAttributes($attribute) !== []) {
                    $map[$attribute][] = $name;
                }
            }
        }

        return self::$cache = $map;
    }
}
