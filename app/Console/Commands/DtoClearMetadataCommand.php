<?php

namespace App\Console\Commands;

use App\Support\DtoMetadata;
use Illuminate\Console\Command;

/**
 * dto:clear-metadata — invalidate cached DTO attribute metadata.
 * =====================================================================
 *
 * The inverse of dto:cache-metadata. The DTO schema cache (list columns,
 * detail values and form fields resolved from PHP attributes) is stale
 * whenever a Data class's attributes change — a new #[FormFieldAttribute],
 * a renamed property, a changed form type, etc. Run this command to drop
 * the cached entries so the next request (or the next
 * dto:cache-metadata run) rebuilds them from the current attributes.
 *
 * ---------------------------------------------------------------------
 * Options
 * ---------------------------------------------------------------------
 *   --class=   Fully-qualified DTO class name to clear in isolation,
 *              e.g. "App\Data\CountryData". When omitted, ALL cached DTO
 *              metadata entries are cleared.
 *
 * ---------------------------------------------------------------------
 * Examples
 * ---------------------------------------------------------------------
 *   php artisan dto:clear-metadata
 *   php artisan dto:clear-metadata --class="App\Data\CountryData"
 *
 * @see \App\Support\DtoMetadata                         Owns the cache store.
 * @see \App\Console\Commands\DtoCacheMetadataCommand    Warms the cache.
 */
class DtoClearMetadataCommand extends Command
{
    protected $signature = 'dto:clear-metadata
                            {--class= : Clear metadata cache for a single DTO class (FQCN)}';

    protected $description = 'Clear cached DTO attribute metadata';

    public function handle(): int
    {
        $class = $this->option('class');

        if ($class) {
            if (! class_exists($class)) {
                $this->error("Class [{$class}] does not exist.");

                return self::FAILURE;
            }

            DtoMetadata::clear($class);
            $this->info("Cleared metadata cache for [{$class}].");

            return self::SUCCESS;
        }

        DtoMetadata::clear();
        $this->info('Cleared all DTO metadata cache entries.');

        return self::SUCCESS;
    }
}
