<?php

namespace App\Support;

use App\Attributes\FormFieldAttribute;
use App\Attributes\HidePropertyAttribute;
use App\Attributes\ListPropertyAttribute;
use App\Attributes\ValuePropertyAttribute;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

class DtoMetadata
{
    private const MANIFEST_KEY = 'dto-metadata:manifest';

    private function __construct(
        private readonly string $className,
    ) {}

    public static function for(string|object $class): self
    {
        $className = is_object($class) ? $class::class : $class;

        if (! class_exists($className)) {
            throw new \InvalidArgumentException("DTO class [{$className}] does not exist.");
        }

        return new self($className);
    }

    public function className(): string
    {
        return $this->className;
    }

    /**
     * Form fields keyed by property name, values are FormFieldAttribute constructor args.
     *
     * @return array<string, array<int, mixed>>
     */
    public function formFields(): array
    {
        return $this->schema()['form_fields'];
    }

    /**
     * Dot-notation paths for properties marked with ValuePropertyAttribute (detail/view pages).
     * Properties marked with HidePropertyAttribute are excluded.
     *
     * @return list<string>
     */
    public function valueFieldPaths(bool $withPrefix = true): array
    {
        $paths = $this->schema()['value_fields'];

        if ($withPrefix) {
            return $paths;
        }

        return array_map(
            static fn (string $path) => str_contains($path, '.') ? substr($path, strrpos($path, '.') + 1) : $path,
            $paths
        );
    }

    /**
     * Dot-notation paths for properties marked with ListPropertyAttribute (datatable columns).
     * Falls back to valueFieldPaths() when no ListPropertyAttribute is present on the DTO.
     * Properties marked with HidePropertyAttribute are excluded.
     *
     * @return list<string>
     */
    public function listFieldPaths(bool $withPrefix = true): array
    {
        $paths = $this->schema()['list_fields'];

        if ($paths === []) {
            $paths = $this->schema()['value_fields'];
        }

        if ($withPrefix) {
            return $paths;
        }

        return array_map(
            static fn (string $path) => str_contains($path, '.') ? substr($path, strrpos($path, '.') + 1) : $path,
            $paths
        );
    }

    /**
     * Column headers for list/datatable views (includes id when present).
     * Uses ListPropertyAttribute fields; falls back to ValuePropertyAttribute fields.
     *
     * @return list<string>
     */
    public function listColumns(bool $withPrefix = true): array
    {
        $columns = [];

        if ($this->hasPublicProperty('id')) {
            $columns[] = 'id';
        }

        return array_merge($columns, $this->listFieldPaths($withPrefix));
    }

    /**
     * Extract runtime values from a DTO instance using cached field paths.
     *
     * @return array<string, mixed>|list<string>
     */
    public function extractValues(object $dto, bool $onlyHeaders = false, bool $withPrefix = true): array
    {
        $paths = $this->valueFieldPaths(withPrefix: true);

        if ($onlyHeaders) {
            return $this->valueFieldPaths($withPrefix);
        }

        $fields = [];

        foreach ($paths as $path) {
            $key = $withPrefix ? $path : (str_contains($path, '.') ? substr($path, strrpos($path, '.') + 1) : $path);
            $fields[$key] = $this->valueAtPath($dto, $path);
        }

        return $fields;
    }

    /**
     * @return array{form_fields: array<string, array<int, mixed>>, value_fields: list<string>, list_fields: list<string>}
     */
    public function schema(): array
    {
        if ($this->cacheEnabled()) {
            $cached = $this->cacheStore()->get($this->cacheKey());

            if (is_array($cached)) {
                return $cached;
            }
        }

        $schema = [
            'form_fields' => self::discoverFormFields($this->className),
            'value_fields' => self::discoverValueFields($this->className),
            'list_fields'  => self::discoverListFields($this->className),
        ];

        if ($this->cacheEnabled()) {
            $this->cacheStore()->put($this->cacheKey(), $schema, config('dto-metadata.cache.duration'));
            self::registerCachedClass($this->className);
        }

        return $schema;
    }

    public static function warm(?array $directories = null): int
    {
        $count = 0;

        foreach (self::discoverClasses($directories) as $class) {
            self::for($class)->schema();
            $count++;
        }

        return $count;
    }

    public static function clear(?string $class = null): void
    {
        $store = Cache::store(config('dto-metadata.cache.store'));
        $prefix = config('dto-metadata.cache.prefix', 'dto-metadata');

        if ($class !== null) {
            $store->forget("{$prefix}:{$class}");
            self::removeFromManifest($class);

            return;
        }

        $manifest = $store->get(self::MANIFEST_KEY, []);

        foreach ($manifest as $cachedClass) {
            $store->forget("{$prefix}:{$cachedClass}");
        }

        $store->forget(self::MANIFEST_KEY);
    }

    /**
     * @return list<string>
     */
    public static function discoverClasses(?array $directories = null): array
    {
        $directories ??= config('dto-metadata.directories', [app_path('Data')]);
        $classes = [];

        foreach ($directories as $directory) {
            foreach (glob($directory.'/*.php') ?: [] as $file) {
                $baseName = basename($file, '.php');

                if ($baseName === 'BaseData') {
                    continue;
                }

                $class = 'App\\Data\\'.$baseName;

                if (class_exists($class) && is_subclass_of($class, \Spatie\LaravelData\Data::class)) {
                    $classes[] = $class;
                }
            }
        }

        sort($classes);

        return array_values(array_unique($classes));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected static function discoverFormFields(string $class): array
    {
        $fields = [];
        $reflect = new ReflectionClass($class);

        foreach ($reflect->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            foreach ($property->getAttributes(FormFieldAttribute::class) as $attribute) {
                $fields[$property->getName()] = $attribute->getArguments();
            }
        }

        return $fields;
    }

    /**
     * Discover properties for detail/view display (ValuePropertyAttribute).
     * Properties with HidePropertyAttribute are excluded.
     *
     * @return list<string>
     */
    protected static function discoverValueFields(string $class, string $prefix = ''): array
    {
        $fields = [];
        $reflect = new ReflectionClass($class);

        foreach ($reflect->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->getAttributes(HidePropertyAttribute::class) !== []) {
                continue;
            }

            $fieldName = $property->getName();
            $fullFieldName = ltrim($prefix.'.'.$fieldName, '.');
            $nestedClass = self::nestedClassName($property);

            if ($nestedClass !== null && class_exists($nestedClass) && is_subclass_of($nestedClass, \Spatie\LaravelData\Data::class)) {
                $fields = array_merge($fields, self::discoverValueFields($nestedClass, $fullFieldName));

                continue;
            }

            foreach ($property->getAttributes(ValuePropertyAttribute::class) as $attribute) {
                $fields[] = $fullFieldName;
            }
        }

        return $fields;
    }

    /**
     * Discover properties for list/datatable display (ListPropertyAttribute).
     * Properties with HidePropertyAttribute are excluded.
     * Returns empty array when no ListPropertyAttribute is found; listFieldPaths() handles the fallback.
     *
     * @return list<string>
     */
    protected static function discoverListFields(string $class, string $prefix = ''): array
    {
        $fields = [];
        $reflect = new ReflectionClass($class);

        foreach ($reflect->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->getAttributes(HidePropertyAttribute::class) !== []) {
                continue;
            }

            $fieldName = $property->getName();
            $fullFieldName = ltrim($prefix.'.'.$fieldName, '.');
            $nestedClass = self::nestedClassName($property);

            if ($nestedClass !== null && class_exists($nestedClass) && is_subclass_of($nestedClass, \Spatie\LaravelData\Data::class)) {
                $fields = array_merge($fields, self::discoverListFields($nestedClass, $fullFieldName));

                continue;
            }

            foreach ($property->getAttributes(ListPropertyAttribute::class) as $attribute) {
                $fields[] = $fullFieldName;
            }
        }

        return $fields;
    }

    protected static function nestedClassName(ReflectionProperty $property): ?string
    {
        $type = $property->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        return $type->getName();
    }

    protected function hasPublicProperty(string $name): bool
    {
        return (new ReflectionClass($this->className))->hasProperty($name);
    }

    protected function valueAtPath(object $object, string $path): mixed
    {
        $current = $object;

        foreach (explode('.', $path) as $segment) {
            if (! is_object($current)) {
                return null;
            }

            $current = $current->{$segment} ?? null;
        }

        return $current;
    }

    protected function cacheEnabled(): bool
    {
        return (bool) config('dto-metadata.enabled', false);
    }

    protected function cacheKey(): string
    {
        return config('dto-metadata.cache.prefix', 'dto-metadata').':'.$this->className;
    }

    protected function cacheStore(): \Illuminate\Contracts\Cache\Repository
    {
        return Cache::store(config('dto-metadata.cache.store'));
    }

    protected static function registerCachedClass(string $class): void
    {
        $store = Cache::store(config('dto-metadata.cache.store'));
        $manifest = $store->get(self::MANIFEST_KEY, []);

        if (! in_array($class, $manifest, true)) {
            $manifest[] = $class;
            $store->forever(self::MANIFEST_KEY, $manifest);
        }
    }

    protected static function removeFromManifest(string $class): void
    {
        $store = Cache::store(config('dto-metadata.cache.store'));
        $manifest = array_values(array_filter(
            $store->get(self::MANIFEST_KEY, []),
            static fn (string $cachedClass) => $cachedClass !== $class
        ));

        if ($manifest === []) {
            $store->forget(self::MANIFEST_KEY);
        } else {
            $store->forever(self::MANIFEST_KEY, $manifest);
        }
    }
}
