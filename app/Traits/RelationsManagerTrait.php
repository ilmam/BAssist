<?php

namespace App\Traits;

use App\Attributes\Relation;
use ReflectionNamedType;

trait RelationsManagerTrait
{
    /**
     * @var array<class-string, array<string, list<string>>>
     */
    protected static $relationsList = [];

    protected static $relationClasses = [
        'HasOne',
        'HasMany',
        'BelongsTo',
        'BelongsToMany',
    ];

    public static function getAllRelations($type = null): array
    {
        $class = static::class;

        if (! isset(self::$relationsList[$class])) {
            self::$relationsList[$class] = self::discoverRelations();
        }

        $relations = self::$relationsList[$class];

        return $type ? ($relations[$type] ?? []) : $relations;
    }

    /**
     * @return array<string, list<string>>
     */
    protected static function discoverRelations(): array
    {
        $relations = [];
        $reflect = new \ReflectionClass(static::class);

        foreach ($reflect->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (! $method->hasReturnType()) {
                continue;
            }

            $returnType = $method->getReturnType();
            if (! $returnType instanceof ReflectionNamedType) {
                continue;
            }

            $foundRelation = self::checkRelation($returnType->getName());
            if (! $foundRelation) {
                continue;
            }

            $attributes = $method->getAttributes(Relation::class);
            if (count($attributes) > 0) {
                $relations[$foundRelation][] = $method->getName();
            }
        }

        return $relations;
    }

    protected static function checkRelation(string $methodReturnType, string $relationNamespace = 'Illuminate\\Database\\Eloquent\\Relations\\'): string|false
    {
        foreach (self::$relationClasses as $relation) {
            if ($methodReturnType === $relationNamespace.$relation) {
                return $relation;
            }
        }

        return false;
    }
}
