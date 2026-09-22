<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\IconSets;

use AbdulrahmanDev22\FilamentIconPicker\IconSets\Contracts\AcceptsUploads;
use BladeUI\Icons\Exceptions\CannotRegisterIconSet;
use BladeUI\Icons\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * An icon set stored on a Laravel filesystem disk (local, public, S3, GCS…),
 * so uploaded icons survive deployments and are shared between instances.
 */
class DiskIconSet implements AcceptsUploads
{
    use Concerns\CachesIconList;

    protected string $directory;

    protected ?bool $isRegisteredWithBladeIcons = null;

    public function __construct(
        protected ?string $disk = null,
        string $directory = 'icon-picker',
        protected string $key = 'uploads',
        protected ?string $label = null,
    ) {
        if (! preg_match('/^[A-Za-z0-9_-]+$/', $key)) {
            throw new InvalidArgumentException("Icon set key [{$key}] may only contain letters, digits, dashes and underscores.");
        }

        $this->directory = trim($directory, '/');
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        return $this->label ?? __('filament-icon-picker::icon-picker.sets.uploads');
    }

    public function getDisk(): string
    {
        return $this->disk
            ?? config('filament-icon-picker.uploads.disk')
            ?? config('filament.default_filesystem_disk')
            ?? config('filesystems.default', 'local');
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    public function getIcons(): array
    {
        return $this->rememberIcons(fn (): array => $this->scan());
    }

    public function getIcon(string $name): string|Htmlable|null
    {
        $path = $this->getFilePath($name);

        if ($path === null || ! $this->filesystem()->exists($path)) {
            return null;
        }

        if ($this->registerWithBladeIcons()) {
            return "{$this->key}-{$name}";
        }

        return new HtmlString((string) $this->filesystem()->get($path));
    }

    public function storeIcon(string $name, string $svg): void
    {
        $path = $this->getFilePath($name) ?? throw new InvalidArgumentException("Invalid icon name [{$name}].");

        $this->filesystem()->put($path, $svg);

        $this->memoizedIcons = null;
    }

    public function deleteIcon(string $name): void
    {
        if ($path = $this->getFilePath($name)) {
            $this->filesystem()->delete($path);
        }

        $this->memoizedIcons = null;
    }

    /**
     * Register the disk directory with Blade Icons (which supports disks),
     * so "uploads:star" also works as the plain icon name "uploads-star".
     */
    public function registerWithBladeIcons(?Factory $factory = null): bool
    {
        if ($this->isRegisteredWithBladeIcons !== null) {
            return $this->isRegisteredWithBladeIcons;
        }

        if (str_contains($this->key, '-') || $this->directory === '') {
            return $this->isRegisteredWithBladeIcons = false;
        }

        $factory ??= app(Factory::class);
        $existing = $factory->all()[$this->key] ?? null;

        if ($existing !== null) {
            return $this->isRegisteredWithBladeIcons = ($existing['prefix'] ?? null) === $this->key
                && ($existing['disk'] ?? null) === $this->getDisk()
                && in_array($this->directory, $existing['paths'] ?? [], true);
        }

        try {
            $factory->add($this->key, ['path' => $this->directory, 'prefix' => $this->key, 'disk' => $this->getDisk()]);

            return $this->isRegisteredWithBladeIcons = true;
        } catch (CannotRegisterIconSet) {
            return $this->isRegisteredWithBladeIcons = false;
        }
    }

    protected function filesystem(): Filesystem
    {
        return Storage::disk($this->getDisk());
    }

    /**
     * @return array<string, string>
     */
    protected function scan(): array
    {
        $icons = [];
        $prefix = $this->directory === '' ? '' : $this->directory.'/';

        foreach ($this->filesystem()->allFiles($this->directory) as $file) {
            if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'svg') {
                continue;
            }

            $name = Str::of($file)
                ->after($prefix)
                ->beforeLast('.')
                ->replace('/', '.')
                ->toString();

            $icons[$name] = Str::headline(str_replace(['.', '_', '-'], ' ', $name));
        }

        ksort($icons, SORT_NATURAL);

        return $icons;
    }

    protected function getFilePath(string $name): ?string
    {
        if ($name === '' || str_contains($name, '..') || str_contains($name, '/') || str_contains($name, '\\')) {
            return null;
        }

        return ($this->directory === '' ? '' : $this->directory.'/').str_replace('.', '/', $name).'.svg';
    }

    protected function getCacheIdentifier(): string
    {
        return "disk.{$this->key}.".md5($this->getDisk().'|'.$this->directory);
    }
}
