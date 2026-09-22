<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\Forms\Components\Concerns;

use AbdulrahmanDev22\FilamentIconPicker\Support\IconReference;
use AbdulrahmanDev22\FilamentIconPicker\Support\IconStateConverter;
use AbdulrahmanDev22\FilamentIconPicker\Support\IconStorageFormat;
use Closure;

trait HasStorageFormat
{
    protected IconStorageFormat|string|Closure|null $storageFormat = null;

    /**
     * How the selected icon is written to the model. Defaults to the
     * `filament-icon-picker.storage_format` config value ("reference").
     */
    public function storeAs(IconStorageFormat|string|Closure $format): static
    {
        $this->storageFormat = $format;

        return $this;
    }

    /** Store "brand:star". */
    public function storeAsReference(): static
    {
        return $this->storeAs(IconStorageFormat::Reference);
    }

    /** Store "heroicon-o-academic-cap" / "brand-star", ready for `->icon()`. */
    public function storeAsBladeIcon(): static
    {
        return $this->storeAs(IconStorageFormat::BladeIcon);
    }

    /** Store an array (set, name, label, blade_icon, svg, url) – use a JSON column with an array cast. */
    public function storeAsArray(): static
    {
        return $this->storeAs(IconStorageFormat::Array);
    }

    /** Store the same structure as `storeAsArray()` JSON-encoded into a string column. */
    public function storeAsJson(): static
    {
        return $this->storeAs(IconStorageFormat::Json);
    }

    /** Store the raw "<svg>" markup. */
    public function storeAsSvg(): static
    {
        return $this->storeAs(IconStorageFormat::Svg);
    }

    public function getStorageFormat(): IconStorageFormat
    {
        $format = $this->evaluate($this->storageFormat)
            ?? config('filament-icon-picker.storage_format', IconStorageFormat::Reference->value);

        return IconStorageFormat::fromValue($format);
    }

    public function getStateConverter(): IconStateConverter
    {
        return IconStateConverter::for($this->getIconSets());
    }

    /**
     * Stored value (any format) => internal "set:name" string, or null.
     */
    public function normalizeState(mixed $stored): ?string
    {
        if (is_string($stored) && IconReference::tryFrom($stored) && $this->getStateConverter()->exists(IconReference::from($stored))) {
            return $stored;
        }

        return $this->getStateConverter()->toReference($stored)?->toString();
    }

    /**
     * Internal "set:name" string => value in the configured storage format.
     *
     * @return string|array<string, mixed>|null
     */
    public function formatStateForStorage(mixed $state): string|array|null
    {
        return $this->getStateConverter()->fromReference(IconReference::tryFrom($state), $this->getStorageFormat());
    }
}
