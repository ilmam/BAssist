<?php

namespace App\Console\Commands;

use App\Support\DtoMetadata;
use Illuminate\Console\Command;

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
