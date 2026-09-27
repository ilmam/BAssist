<?php

namespace App\Support;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Rejects write payloads that reference records outside the current tenant.
 *
 * `exists:` validation rules and mass assignment do not know about tenants, so a
 * crafted POST could attach a record to another tenant's project or parent.
 * This guard walks the payload and checks every foreign-key-looking value:
 *
 *  - `{name}_id` scalars and `{name}_ids` lists, at any depth (editor rows too)
 *  - the target model comes from the owning model's BelongsTo relation for that
 *    column, else from the key name (`primary_business_need_id` → BusinessNeed)
 *  - only tenant-owned targets (BelongsToTenant) are checked; lookups such as
 *    Status or Priority are shared and skipped
 *
 * The existence check runs through the target model's TenantScope, so "exists"
 * means "exists in this tenant".
 */
class TenantPayloadGuard
{
    /** @var array<class-string, array<string, class-string<Model>>> */
    protected static array $foreignKeyMaps = [];

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws ValidationException
     */
    public static function assertOwned(Model $model, array $payload): void
    {
        if (! Tenancy::enforced()) {
            return;
        }

        $errors = [];
        self::walk($model, $payload, '', $errors);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $errors
     */
    protected static function walk(Model $model, array $payload, string $prefix, array &$errors): void
    {
        foreach ($payload as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                if (is_string($key) && str_ends_with($key, '_ids')) {
                    $target = self::targetFor($model, substr($key, 0, -4).'_id');
                    foreach ($value as $id) {
                        if (! self::isOwned($target, $id)) {
                            $errors[$path] = __('validation.exists', ['attribute' => self::label($key)]);

                            break;
                        }
                    }

                    continue;
                }

                self::walk($model, $value, $path, $errors);

                continue;
            }

            if (! is_string($key) || ! str_ends_with($key, '_id')) {
                continue;
            }

            $target = self::targetFor($model, $key);
            if (! self::isOwned($target, $value)) {
                $errors[$path] = __('validation.exists', ['attribute' => self::label($key)]);
            }
        }
    }

    /**
     * @param  class-string<Model>|null  $target
     */
    protected static function isOwned(?string $target, mixed $id): bool
    {
        if ($target === null) {
            return true;
        }

        if ($id === null || $id === '' || ! is_numeric($id) || (int) $id <= 0) {
            return true;
        }

        return $target::query()->whereKey((int) $id)->exists();
    }

    /**
     * @return class-string<Model>|null tenant-owned model the column points at
     */
    protected static function targetFor(Model $model, string $column): ?string
    {
        $map = self::foreignKeyMap($model);
        $target = $map[$column] ?? self::guessModel($column);

        if ($target === null || ! in_array(BelongsToTenant::class, class_uses_recursive($target), true)) {
            return null;
        }

        return $target;
    }

    /**
     * @return class-string<Model>|null
     */
    protected static function guessModel(string $column): ?string
    {
        $base = preg_replace('/^primary_/', '', substr($column, 0, -3));
        $class = 'App\\Models\\'.Str::studly((string) $base);

        return class_exists($class) && is_subclass_of($class, Model::class) ? $class : null;
    }

    /**
     * Foreign key column → related model, from the model's BelongsTo methods.
     *
     * @return array<string, class-string<Model>>
     */
    protected static function foreignKeyMap(Model $model): array
    {
        $class = $model::class;

        if (isset(self::$foreignKeyMaps[$class])) {
            return self::$foreignKeyMaps[$class];
        }

        $map = [];

        foreach ((new \ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $type = $method->getReturnType();

            if ($method->getNumberOfParameters() > 0
                || $method->isStatic()
                || ! $type instanceof ReflectionNamedType
                || $type->getName() !== BelongsTo::class) {
                continue;
            }

            $relation = $model->{$method->getName()}();
            if ($relation instanceof BelongsTo) {
                $map[$relation->getForeignKeyName()] = $relation->getRelated()::class;
            }
        }

        return self::$foreignKeyMaps[$class] = $map;
    }

    protected static function label(string $key): string
    {
        return str_replace('_', ' ', preg_replace('/_ids?$/', '', $key) ?? $key);
    }
}
