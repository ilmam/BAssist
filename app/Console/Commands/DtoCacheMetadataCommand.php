<?php

namespace App\Console\Commands;

use App\Support\DtoMetadata;
use Illuminate\Console\Command;

/**
 * dto:cache-metadata - pre-compute and cache DTO attribute metadata.
 *
 * Schema is driven by PHP attributes on Data / ViewData classes:
 *   - #[InList] / #[ListForm] -> datatable / list columns
 *   - detail/value            -> all public props except #[Hide] (optional #[Value('…')] nested override)
 *   - #[Form] / #[ListForm]   -> form controls (hideQuick / quickSpan for Quick Create)
 *
 * @see docs/attributes.md
 * @see \App\Support\DtoMetadata
 * @see \App\Console\Commands\DtoClearMetadataCommand
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
