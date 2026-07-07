<?php

namespace App\Console\Commands;

use App\Support\DtoMetadata;
use Illuminate\Console\Command;

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
