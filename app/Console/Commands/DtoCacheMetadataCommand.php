<?php

namespace App\Console\Commands;

use App\Support\DtoMetadata;
use Illuminate\Console\Command;

/**
 * dto:cache-metadata — pre-compute and cache DTO attribute metadata.
 * =====================================================================
 *
 * The CRUD UI is driven by PHP attributes on the Data classes
 * (App\Data\*Data and *ViewData):
 *   - #[ListPropertyAttribute]  → datatable / list columns
 *   - #[ValuePropertyAttribute] → detail & modal field values
 *   - #[FormFieldAttribute]     → form controls
 *
 * Reflecting over these attributes on every request is wasteful, so
 * App\Support\DtoMetadata caches the resolved schema. This command warms
 * that cache ahead of time (e.g. during deployment) so the first request
 * does not pay the reflection cost.
 *
 * ---------------------------------------------------------------------
 * Options
 * ---------------------------------------------------------------------
 *   --class=   Fully-qualified DTO class name to cache in isolation,
 *              e.g. "App\Data\CountryData". When omitted, every Data class
 *              in the configured discovery directories is warmed.
 *
 * ---------------------------------------------------------------------
 * Behaviour
 * ---------------------------------------------------------------------
 *   - With --class: validates the class exists, then caches just that
 *     schema. Fails if the class cannot be found.
 *   - Without --class: discovers all Data classes, warms them, and lists
 *     what was cached. Warns (but succeeds) when none are found.
 *
 * ---------------------------------------------------------------------
 * Examples
 * ---------------------------------------------------------------------
 *   php artisan dto:cache-metadata
 *   php artisan dto:cache-metadata --class="App\Data\CountryData"
 *
 * Run dto:clear-metadata after changing DTO attributes to invalidate the
 * cache.
 *
 * @see \App\Support\DtoMetadata                         Resolves & stores the schema.
 * @see \App\Console\Commands\DtoClearMetadataCommand    Inverse operation.
 */
class DtoCacheMetadataCommand extends Command
{
    protected $signature = 'dto:cache-metadata
                            {--class= : Cache metadata for a single DTO class (FQCN)}';

    protected $description = 'Discover and cache DTO attribute metadata for forms, datatables, and detail views';

    public function handle(): int
    {
        $class = $this->option('class');

        if ($class) {
            if (! class_exists($class)) {
                $this->error("Class [{$class}] does not exist.");

                return self::FAILURE;
            }

            DtoMetadata::for($class)->schema();
            $this->info("Cached metadata for [{$class}].");

            return self::SUCCESS;
        }

        $classes = DtoMetadata::discoverClasses();
        $count = DtoMetadata::warm();

        if ($count === 0) {
            $this->warn('No Data classes found in configured directories.');

            return self::SUCCESS;
        }

        $this->info("Cached metadata for {$count} DTO class(es):");
        foreach ($classes as $dtoClass) {
            $this->line("  - {$dtoClass}");
        }

        $this->newLine();
        $this->comment('To clear this cache after changing DTO attributes, run: php artisan dto:clear-metadata');

        return self::SUCCESS;
    }
}
