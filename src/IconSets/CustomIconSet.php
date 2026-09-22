<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\IconSets;

use BladeUI\Icons\Exceptions\CannotRegisterIconSet;
use BladeUI\Icons\Factory;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * An icon set built from a directory of `.svg` files. Dropping a new file in
 * the folder is enough to make it selectable.
 */
class CustomIconSet implements IconSet
{
    use Concerns\CachesIconList;

    protected string $path;

    protected ?bool $isRegisteredWithBladeIcons = null;

    public function __construct(
        string $path,
        protected string $key = 'custom',
        protected ?string $label = null,
    ) {
        if (! preg_match('/^[A-Za-z0-9_-]+$/', $key)) {
            throw new InvalidArgumentException("Icon set key [{$key}] may only contain letters, digits, dashes and underscores.");
        }

        $this->path = rtrim($path, '/\\');
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        return $this->label ?? __('filament-icon-picker::icon-picker.sets.custom');
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getIcons(): array
    {
        return $this->rememberIcons(fn (): array => $this->scan());
    }

    public function getIcon(string $name): string|Htmlable|null
    {
        $file = $this->getFilePath($name);

        if ($file === null || ! is_file($file)) {
            return null;
        }

        if ($this->registerWithBladeIcons()) {
            return "{$this->key}-{$name}";
        }

        return new HtmlString((string) file_get_contents($file));
    }

    /**
     * Expose the directory to Blade Icons so the stored value also works as a
     * plain icon name anywhere in the app (`->icon('custom-star')`,
     * `<x-filament::icon icon="custom-star" />`, `@svg('custom-star')`).
     *
     * Blade Icons prefixes cannot contain dashes and must be unique, so this
     * silently falls back to inline SVG rendering when registration is not
     * possible.
     */
    public function registerWithBladeIcons(?Factory $factory = null): bool
    {
        if ($this->isRegisteredWithBladeIcons !== null) {
            return $this->isRegisteredWithBladeIcons;
        }

        if (str_contains($this->key, '-') || ! is_dir($this->path)) {
            return $this->isRegisteredWithBladeIcons = false;
        }

        $factory ??= app(Factory::class);
        $existing = $factory->all()[$this->key] ?? null;

        if ($existing !== null) {
            return $this->isRegisteredWithBladeIcons = ($existing['prefix'] ?? null) === $this->key
                && in_array($this->path, $existing['paths'] ?? [], true);
        }

        try {
            $factory->add($this->key, ['path' => $this->path, 'prefix' => $this->key]);

            return $this->isRegisteredWithBladeIcons = true;
        } catch (CannotRegisterIconSet) {
            return $this->isRegisteredWithBladeIcons = false;
        }
    }

    /**
     * @return array<string, string>
     */
    protected function scan(): array
    {
        if (! is_dir($this->path)) {
            return [];
        }

        $icons = [];

        foreach (File::allFiles($this->path) as $file) {
            if (strtolower($file->getExtension()) !== 'svg') {
                continue;
            }

            $name = Str::of($file->getRelativePathname())
                ->beforeLast('.')
                ->replace(['\\', '/'], '.')
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

        return $this->path.'/'.str_replace('.', '/', $name).'.svg';
    }

    protected function getCacheIdentifier(): string
    {
        return "custom.{$this->key}.".md5($this->path);
    }
}
