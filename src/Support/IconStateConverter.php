<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\Support;

use AbdulrahmanDev22\FilamentIconPicker\IconSets\IconSet;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Route;

/**
 * Converts between the picker's internal IconReference and the configured
 * storage format, in both directions.
 */
final class IconStateConverter
{
    /**
     * @param  array<string, IconSet>  $sets
     */
    public function __construct(protected array $sets) {}

    /**
     * @param  array<string, IconSet>  $sets
     */
    public static function for(array $sets): self
    {
        return new self($sets);
    }

    /**
     * Normalise whatever is stored (any format) to an IconReference, or null
     * when it is blank or cannot be matched to a known icon.
     */
    public function toReference(mixed $stored): ?IconReference
    {
        if ($stored instanceof IconReference) {
            return $this->exists($stored) ? $stored : null;
        }

        if (is_array($stored)) {
            return $this->referenceFromArray($stored);
        }

        if (! is_string($stored) || trim($stored) === '') {
            return null;
        }

        $stored = trim($stored);

        if (str_starts_with($stored, '{')) {
            $decoded = json_decode($stored, true);

            return is_array($decoded) ? $this->referenceFromArray($decoded) : null;
        }

        if (str_starts_with($stored, '<')) {
            return $this->referenceFromSvg($stored);
        }

        if (($reference = IconReference::tryFrom($stored)) && $this->exists($reference)) {
            return $reference;
        }

        return $this->referenceFromBladeIcon($stored);
    }

    /**
     * Render an IconReference in the requested storage format.
     *
     * @return string|array<string, mixed>|null
     */
    public function fromReference(?IconReference $reference, IconStorageFormat $format): string|array|null
    {
        if ($reference === null || ! $this->exists($reference)) {
            return null;
        }

        return match ($format) {
            IconStorageFormat::Reference => $reference->toString(),
            IconStorageFormat::BladeIcon => $this->bladeIcon($reference),
            IconStorageFormat::Array => $this->toArray($reference),
            IconStorageFormat::Json => json_encode($this->toArray($reference), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: null,
            IconStorageFormat::Svg => $this->svg($reference),
        };
    }

    /**
     * Everything an API client may want to know about an icon.
     *
     * @return array{set: string, name: string, label: string|null, blade_icon: string|null, svg: string|null, url: string|null}
     */
    public function toArray(IconReference $reference): array
    {
        $set = $this->sets[$reference->set] ?? null;

        return [
            'set' => $reference->set,
            'name' => $reference->name,
            'label' => $set?->getIcons()[$reference->name] ?? null,
            'blade_icon' => $this->bladeIcon($reference),
            'svg' => $this->svg($reference),
            'url' => self::url($reference),
        ];
    }

    /**
     * Blade Icons name for the reference, when the set exposes one.
     */
    public function bladeIcon(IconReference $reference): ?string
    {
        $icon = ($this->sets[$reference->set] ?? null)?->getIcon($reference->name);

        return is_string($icon) ? $icon : null;
    }

    public function svg(IconReference $reference): ?string
    {
        $icon = ($this->sets[$reference->set] ?? null)?->getIcon($reference->name);

        return IconRenderer::render($icon)?->toHtml();
    }

    /**
     * Public URL of the icon when the package route is enabled.
     */
    public static function url(IconReference $reference): ?string
    {
        if (! Route::has('filament-icon-picker.icon')) {
            return null;
        }

        return route('filament-icon-picker.icon', ['set' => $reference->set, 'name' => $reference->name]);
    }

    public function exists(IconReference $reference): bool
    {
        $set = $this->sets[$reference->set] ?? null;

        return $set !== null && array_key_exists($reference->name, $set->getIcons());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function referenceFromArray(array $data): ?IconReference
    {
        if (isset($data['set'], $data['name']) && is_string($data['set']) && is_string($data['name'])) {
            $reference = IconReference::make($data['set'], $data['name']);

            return $this->exists($reference) ? $reference : null;
        }

        foreach (['reference', 'icon', 'blade_icon', 'svg'] as $key) {
            if (isset($data[$key]) && is_string($data[$key]) && ($reference = $this->toReference($data[$key]))) {
                return $reference;
            }
        }

        return null;
    }

    protected function referenceFromBladeIcon(string $bladeIcon): ?IconReference
    {
        foreach ($this->sets as $key => $set) {
            foreach (array_keys($set->getIcons()) as $name) {
                if ($set->getIcon((string) $name) === $bladeIcon) {
                    return IconReference::make($key, (string) $name);
                }
            }
        }

        return null;
    }

    protected function referenceFromSvg(string $svg): ?IconReference
    {
        $needle = self::normalizeSvg($svg);

        foreach ($this->sets as $key => $set) {
            foreach (array_keys($set->getIcons()) as $name) {
                $candidate = $set->getIcon((string) $name);

                if ($candidate instanceof Htmlable) {
                    $candidate = $candidate->toHtml();
                } elseif (is_string($candidate)) {
                    $candidate = IconRenderer::render($candidate)?->toHtml();
                }

                if (is_string($candidate) && self::normalizeSvg($candidate) === $needle) {
                    return IconReference::make($key, (string) $name);
                }
            }
        }

        return null;
    }

    private static function normalizeSvg(string $svg): string
    {
        return (string) preg_replace('/\s+/', ' ', trim($svg));
    }
}
