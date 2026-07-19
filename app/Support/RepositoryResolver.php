<?php

namespace App\Support;

use App\Repositories\BaseRepository;
use InvalidArgumentException;

/**
 * Single entry point for resolving repositories (data access layer).
 */
class RepositoryResolver
{
    /**
     * Instance entry point (injectable/mockable) that delegates to make().
     */
    public function for(string $modelName): BaseRepository
    {
        return self::make($modelName);
    }

    public static function make(string $modelName): BaseRepository
    {
        $repositoryClass = self::classFor($modelName);

        return new $repositoryClass;
    }

    public static function classFor(string $modelName): string
    {
        $repositoryClass = "App\\Repositories\\{$modelName}Repository";

        if (! class_exists($repositoryClass)) {
            throw new InvalidArgumentException("Repository [{$repositoryClass}] not found for model [{$modelName}].");
        }

        if (! is_subclass_of($repositoryClass, BaseRepository::class)) {
            throw new InvalidArgumentException("Repository [{$repositoryClass}] must extend BaseRepository.");
        }

        return $repositoryClass;
    }
}
