<?php

namespace App\Traits;

use App\Attributes\RelationAttribute;
use ReflectionNamedType;

trait RelationsManagerTrait
{
    protected static $relationsList = [];

    protected static $relationsInitialized = false;

    protected static $relationClasses = [
        'HasOne',
        'HasMany',
        'BelongsTo',
        'BelongsToMany',
    ];

    public static function getAllRelations($type = null): array
    {
        if (! self::$relationsInitialized) {
            self::initAllRelations();
        }

        return $type ? (self::$relationsList[$type] ?? []) : self::$relationsList;
    }

    protected static function initAllRelations(): void
    {
        self::$relationsInitialized = true;
        self::$relationsList = [];

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

            $attributes = $method->getAttributes(RelationAttribute::class);
            if (count($attributes) > 0) {
                self::$relationsList[$foundRelation][] = $method->getName();
            }
        }
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
