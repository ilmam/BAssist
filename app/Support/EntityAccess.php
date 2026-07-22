<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;

class EntityAccess
{
    public const SUPER_ADMIN_SLUG = 'super-admin';

    public const VIEW = 'view';

    public const CREATE = 'create';

    public const UPDATE = 'update';

    public const DELETE = 'delete';

    public static function can(?User $user, string $entity, string $ability): bool
    {
        if ($user === null) {
            return false;
        }

        $user->loadMissing('role.entityPermissions');

        if (self::isSuperAdmin($user)) {
            return true;
        }

        $permission = $user->role?->entityPermissions
            ->firstWhere('entity', $entity);

        if ($permission === null) {
            return false;
        }

        return match ($ability) {
            self::CREATE => $permission->can_create,
            self::UPDATE => $permission->can_update,
            self::DELETE => $permission->can_delete,
            default => $permission->can_view,
        };
    }

    public static function authorize(?User $user, string $entity, string $ability): void
    {
        if (! self::can($user, $entity, $ability)) {
            throw new AuthorizationException('This action is unauthorized.');
        }
    }

    public static function isSuperAdmin(?User $user): bool
    {
        return $user?->role?->slug === self::SUPER_ADMIN_SLUG;
    }

    public static function authorizeSuperAdmin(?User $user): void
    {
        if (! self::isSuperAdmin($user)) {
            throw new AuthorizationException('This action is unauthorized.');
        }
    }

    /**
     * @return list<string>
     */
    public static function entitiesFor(?User $user, ?string $ability = self::VIEW): array
    {
        if ($user === null) {
            return [];
        }

        if (self::isSuperAdmin($user)) {
            return array_keys(CrudEntityRegistry::all());
        }

        $entities = [];

        foreach ($user->role?->entityPermissions ?? [] as $permission) {
            if (self::can($user, $permission->entity, $ability ?? self::VIEW)) {
                $entities[] = $permission->entity;
            }
        }

        sort($entities);

        return $entities;
    }

    /**
     * @return array{entity: string, ability: string}|null
     */
    public static function resolveFromRoute(?Route $route): ?array
    {
        $name = $route?->getName();

        if (! is_string($name) || $name === '') {
            return null;
        }

        if (str_starts_with($name, 'api.')) {
            $parts = explode('.', $name, 3);
            $resource = $parts[1] ?? '';
            $action = $parts[2] ?? '';
            $entity = CrudEntityRegistry::modelFromResource($resource) ?? Str::studly($resource);
        } else {
            $parts = explode('.', $name, 2);
            $resource = $parts[0] ?? '';
            $action = $parts[1] ?? '';
            $entity = Str::studly(Str::singular($resource));
        }

        if (! array_key_exists($entity, CrudEntityRegistry::all())) {
            return null;
        }

        return [
            'entity' => $entity,
            'ability' => self::abilityForRouteAction($action),
        ];
    }

    public static function abilityForRouteAction(string $action): string
    {
        return match (strtolower($action)) {
            'create', 'store', 'modalcreate' => self::CREATE,
            'edit', 'update', 'modaledit' => self::UPDATE,
            'destroy', 'modaldelete', 'modalshow' => self::DELETE,
            default => self::VIEW,
        };
    }

    public static function abilityForControllerMethod(string $method): string
    {
        return match (strtolower($method)) {
            'create', 'store', 'modalcreate' => self::CREATE,
            'edit', 'update', 'modaledit' => self::UPDATE,
            'destroy', 'modaldelete', 'modalshow' => self::DELETE,
            default => self::VIEW,
        };
    }

    public static function abilityForTableAction(string $action): string
    {
        return match ($action) {
            'edit' => self::UPDATE,
            'delete' => self::DELETE,
            default => self::VIEW,
        };
    }
}
