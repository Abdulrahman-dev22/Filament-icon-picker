<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\Support;

use AbdulrahmanDev22\FilamentIconPicker\Exceptions\InvalidSvgException;
use AbdulrahmanDev22\FilamentIconPicker\IconSets\Contracts\AcceptsUploads;
use Illuminate\Support\Str;

/**
 * Turns an uploaded SVG into an icon of a set: sanitises the markup, derives
 * a safe unique name, stores it and refreshes the set's icon list.
 */
class IconUploader
{
    public function __construct(protected SvgSanitizer $sanitizer) {}

    /**
     * @param  string  $svg  raw file contents
     * @param  string|null  $name  requested icon name; falls back to the original file name
     * @param  int|null  $maxBytes  null = `filament-icon-picker.uploads.max_size` (KB)
     *
     * @throws InvalidSvgException
     */
    public function upload(AcceptsUploads $set, string $svg, ?string $name = null, ?string $originalFilename = null, bool $overwrite = false, ?int $maxBytes = null): IconReference
    {
        $maxBytes ??= ((int) config('filament-icon-picker.uploads.max_size', 256)) * 1024;

        if ($maxBytes > 0 && strlen($svg) > $maxBytes) {
            throw new InvalidSvgException(sprintf('The file is larger than %d KB.', (int) ceil($maxBytes / 1024)));
        }

        $svg = $this->sanitizer->sanitize($svg);

        $name = $this->slug($name) ?: $this->slug(pathinfo((string) $originalFilename, PATHINFO_FILENAME)) ?: 'icon';

        if (! $overwrite) {
            $name = $this->uniqueName($set, $name);
        }

        $set->storeIcon($name, $svg);

        if (method_exists($set, 'forgetCachedIcons')) {
            $set->forgetCachedIcons();
        }

        return IconReference::make($set->getKey(), $name);
    }

    protected function slug(?string $value): string
    {
        return Str::of((string) $value)->trim()->slug('-')->limit(64, '')->trim('-')->toString();
    }

    protected function uniqueName(AcceptsUploads $set, string $name): string
    {
        $existing = $set->getIcons();

        if (! array_key_exists($name, $existing)) {
            return $name;
        }

        for ($suffix = 2; $suffix < 1000; $suffix++) {
            if (! array_key_exists("{$name}-{$suffix}", $existing)) {
                return "{$name}-{$suffix}";
            }
        }

        return $name.'-'.Str::lower(Str::random(6));
    }
}
