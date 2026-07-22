<?php

namespace App\Support;

use App\Attributes\Form;
use App\Attributes\Hide;
use App\Attributes\InList;
use App\Attributes\ListForm;
use App\Attributes\Value;
use Illuminate\Support\Facades\Cache;
use ReflectionAttribute;
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
     * Form fields keyed by property name, values are Form/ListForm type args (e.g. ['text'] or ['select', 'Project']).
     *
     * @return array<string, array{0: string, 1?: string, quick_span: int}>
     */
    public function formFields(): array
    {
        return $this->schema()['form_fields'];
    }

    /**
     * Form fields that should appear as visible inputs on Quick Create.
     *
     * @return array<string, array{0: string, 1?: string, quick_span: int}>
     */
    public function quickCreateVisibleFormFields(): array
    {
        $meta = $this->quickCreateMeta();
        $fields = [];

        foreach ($this->formFields() as $name => $args) {
            if (($meta[$name]['hidden'] ?? false) === true) {
                continue;
            }

            $fields[$name] = $args;
        }

        return $fields;
    }

    /**
     * Hidden Quick Create fields mapped to their DTO property defaults.
     *
     * @return array<string, mixed>
     */
    public function quickCreateHiddenDefaults(?object $emptyDto = null): array
    {
        $meta = $this->quickCreateMeta();
        $defaults = [];
        $emptyDto ??= $this->className::from($this->className::empty());

        foreach ($this->formFields() as $name => $args) {
            if (($meta[$name]['hidden'] ?? false) !== true) {
                continue;
            }

            $defaults[$name] = $emptyDto->{$name} ?? null;
        }

        return $defaults;
    }

    /**
     * @return array<string, array{hidden: bool}>
     */
    public function quickCreateMeta(): array
    {
        return $this->schema()['quick_create'] ?? [];
    }

    /**
     * Dot-notation paths for detail/view display (all public props except Hide).
     * Nested Data collapses to `{relation}.{displayField}`; matching `*_id` FKs
     * are skipped when the relation property exists. Optional #[Value('…')]
     * overrides the nested display field only.
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
     * Dot-notation paths for properties marked with InList / ListForm (datatable columns).
     * Falls back to valueFieldPaths() when no list markers are present on the DTO.
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
     * Uses InList/ListForm fields; falls back to Value fields.
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
            return array_map(
                fn (string $path) => $this->displayKeyForPath($path, $withPrefix),
                $this->valueFieldPaths(withPrefix: true)
            );
        }

        $fields = [];

        foreach ($paths as $path) {
            $key = $this->displayKeyForPath($path, $withPrefix);
            $fields[$key] = $this->valueAtPath($dto, $path);
        }

        return $fields;
    }

    /**
     * Collapse relation.displayField paths (status.name) to the relation key (status)
     * so labels resolve to friendly names like "Status".
     */
    protected function displayKeyForPath(string $path, bool $withPrefix): string
    {
        if (str_contains($path, '.')) {
            $parts = explode('.', $path);
            $last = end($parts);

            if (self::isDisplayFieldName($last) && count($parts) >= 2) {
                return $withPrefix
                    ? implode('.', array_slice($parts, 0, -1))
                    : $parts[count($parts) - 2];
            }

            return $withPrefix ? $path : $last;
        }

        return $path;
    }

    /**
     * @return array{
     *     form_fields: array<string, array{0: string, 1?: string, quick_span: int}>,
     *     value_fields: list<string>,
     *     list_fields: list<string>,
     *     quick_create: array<string, array{hidden: bool, default: mixed, has_default: bool}>
     * }
     */
    public function schema(): array
    {
        if ($this->cacheEnabled()) {
            $cached = $this->cacheStore()->get($this->cacheKey());

            if (is_array($cached) && array_key_exists('quick_create', $cached)) {
                return $cached;
            }
        }

        $schema = [
            'form_fields' => self::discoverFormFields($this->className),
            'value_fields' => self::discoverValueFields($this->className),
            'list_fields'  => self::discoverListFields($this->className),
            'quick_create' => self::discoverQuickCreateMeta($this->className),
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
     * @return array<string, array{0: string, 1?: string, quick_span: int}>
     */
    protected static function discoverFormFields(string $class): array
    {
        $fields = [];
        $reflect = new ReflectionClass($class);

        foreach ($reflect->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $formAttrs = $property->getAttributes(Form::class);
            $listFormAttrs = $property->getAttributes(ListForm::class);

            if ($formAttrs !== []) {
                $fields[$property->getName()] = self::normalizeFormArgs($formAttrs[0]);
                continue;
            }

            if ($listFormAttrs !== []) {
                $fields[$property->getName()] = self::normalizeFormArgs($listFormAttrs[0]);
            }
        }

        return $fields;
    }

    /**
     * @return array<string, array{hidden: bool}>
     */
    protected static function discoverQuickCreateMeta(string $class): array
    {
        $meta = [];
        $reflect = new ReflectionClass($class);

        foreach ($reflect->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $attribute = $property->getAttributes(Form::class)[0]
                ?? $property->getAttributes(ListForm::class)[0]
                ?? null;

            if ($attribute === null) {
                continue;
            }

            $instance = $attribute->newInstance();
            if (! $instance->hideQuick) {
                continue;
            }

            $meta[$property->getName()] = [
                'hidden' => true,
            ];
        }

        return $meta;
    }

    /**
     * Discover properties for detail/view display (opt-out via Hide).
     *
     * Includes all public properties except those marked #[Hide]. Nested Data
     * relations contribute `{relation}.{displayField}` (override via optional
     * #[Value('code')] / #[Value(field: 'code')]). Matching `*_id` FKs are
     * skipped when the relation property exists.
     *
     * @return list<string>
     */
    protected static function discoverValueFields(string $class, string $prefix = ''): array
    {
        $fields = [];
        $reflect = new ReflectionClass($class);
        $relationNames = [];

        foreach ($reflect->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $nestedClass = self::nestedClassName($property);

            if ($nestedClass !== null && class_exists($nestedClass) && is_subclass_of($nestedClass, \Spatie\LaravelData\Data::class)) {
                $relationNames[] = $property->getName();
            }
        }

        foreach ($reflect->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->getAttributes(Hide::class) !== []) {
                continue;
            }

            $fieldName = $property->getName();
            $fullFieldName = ltrim($prefix.'.'.$fieldName, '.');
            $nestedClass = self::nestedClassName($property);
            $valueAttrs = $property->getAttributes(Value::class);
            $value = $valueAttrs !== [] ? $valueAttrs[0]->newInstance() : null;

            if ($nestedClass !== null && class_exists($nestedClass) && is_subclass_of($nestedClass, \Spatie\LaravelData\Data::class)) {
                $mainField = $value?->field ?? self::mainDisplayFieldName($nestedClass);

                if ($mainField !== null) {
                    $fields[] = $fullFieldName.'.'.$mainField;
                }

                continue;
            }

            if (str_ends_with($fieldName, '_id')) {
                $relationName = substr($fieldName, 0, -3);

                if (in_array($relationName, $relationNames, true)) {
                    continue;
                }
            }

            $fields[] = $fullFieldName;
        }

        return $fields;
    }

    /**
     * Prefer the entity "main" display field on a nested ViewData.
     */
    protected static function mainDisplayFieldName(string $class): ?string
    {
        $reflect = new ReflectionClass($class);

        foreach (['name', 'title', 'category', 'label'] as $candidate) {
            if ($reflect->hasProperty($candidate)) {
                return $candidate;
            }
        }

        foreach ($reflect->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (! self::propertyIsListed($property)) {
                continue;
            }

            if (self::nestedClassName($property) === null) {
                return $property->getName();
            }
        }

        return null;
    }

    protected static function isDisplayFieldName(string $name): bool
    {
        return in_array($name, ['name', 'title', 'category', 'label'], true);
    }

    /**
     * Discover properties for list/datatable display (InList or ListForm).
     * Returns empty array when none found; listFieldPaths() falls back to Value paths.
     *
     * @return list<string>
     */
    protected static function discoverListFields(string $class, string $prefix = ''): array
    {
        $fields = [];
        $reflect = new ReflectionClass($class);

        foreach ($reflect->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->getAttributes(Hide::class) !== []) {
                continue;
            }

            if (! self::propertyIsListed($property)) {
                continue;
            }

            $fieldName = $property->getName();
            $fullFieldName = ltrim($prefix.'.'.$fieldName, '.');
            $nestedClass = self::nestedClassName($property);

            if ($nestedClass !== null && class_exists($nestedClass) && is_subclass_of($nestedClass, \Spatie\LaravelData\Data::class)) {
                $valueAttrs = $property->getAttributes(Value::class);
                $override = $valueAttrs !== [] ? $valueAttrs[0]->newInstance()->field : null;
                $mainField = $override ?? self::mainDisplayFieldName($nestedClass);

                if ($mainField !== null) {
                    $fields[] = $fullFieldName.'.'.$mainField;
                }

                continue;
            }

            $fields[] = $fullFieldName;
        }

        return $fields;
    }

    /**
     * @param  ReflectionAttribute<Form|ListForm>  $attribute
     * @return array{0: string, 1?: string, quick_span: int}
     */
    protected static function normalizeFormArgs(ReflectionAttribute $attribute): array
    {
        $instance = $attribute->newInstance();
        $args = [$instance->type];

        if ($instance->model !== '') {
            $args[] = $instance->model;
        }

        $args['quick_span'] = self::clampQuickSpan($instance->quickSpan);

        return $args;
    }

    /**
     * Clamp Quick Create grid span to the 12-column range.
     */
    public static function clampQuickSpan(int $span): int
    {
        return max(1, min(12, $span));
    }

    protected static function propertyIsListed(ReflectionProperty $property): bool
    {
        return $property->getAttributes(InList::class) !== []
            || $property->getAttributes(ListForm::class) !== [];
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
