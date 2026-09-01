<?php

namespace App\Support;

use App\Attributes\Attachable;
use App\Models\Concerns\HasAttachments;
use ReflectionClass;

class AttachableSupport
{
    /**
     * @param  class-string|string  $model
     */
    public static function enabled(string $model): bool
    {
        $class = self::modelClass($model);
        if ($class === null) {
            return false;
        }

        if (! in_array(HasAttachments::class, class_uses_recursive($class), true)) {
            return false;
        }

        return (new ReflectionClass($class))->getAttributes(Attachable::class) !== [];
    }

    /**
     * @return class-string|null
     */
    public static function modelClass(string $model): ?string
    {
        $class = str_contains($model, '\\') ? $model : 'App\\Models\\'.$model;

        return class_exists($class) ? $class : null;
    }
}
