<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\Commands;

use AbdulrahmanDev22\FilamentIconPicker\IconSets\IconSetRegistry;
use Illuminate\Console\Command;

class ClearIconCacheCommand extends Command
{
    protected $signature = 'filament-icon-picker:clear';

    protected $description = 'Clear the cached icon lists of the Filament icon picker.';

    public function handle(IconSetRegistry $registry): int
    {
        $registry->clearCache();

        $this->components->info('Filament icon picker cache cleared.');

        return self::SUCCESS;
    }
}
