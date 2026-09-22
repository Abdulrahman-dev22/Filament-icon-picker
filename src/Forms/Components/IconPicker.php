<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\Forms\Components;

use AbdulrahmanDev22\FilamentIconPicker\Support\CssColor;
use Closure;
use Filament\Forms\Components\Concerns\CanBeSearchable;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Filament\Support\Concerns\HasExtraAlpineAttributes;

/**
 * A searchable, tabbed grid of icons. Stores "set-key:icon-name".
 *
 * Everything that differs between Filament 3, 4 and 5 (field wrapper view,
 * state binding, asset pipeline) is reached through Filament's own stable
 * extension points, so this class and its view are shared by all versions.
 */
class IconPicker extends Field
{
    use CanBeSearchable;
    use Concerns\CanUploadIcons;
    use Concerns\HasGridColumns;
    use Concerns\HasIconSets;
    use Concerns\HasStorageFormat;
    use HasExtraAlpineAttributes;
    use HasPlaceholder;

    /**
     * @var view-string
     */
    protected string $view = 'filament-icon-picker::forms.components.icon-picker';

    protected bool|Closure $isDeselectable = true;

    protected string|Closure|null $gridMaxHeight = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchable();

        $this->registerActions([
            static fn (IconPicker $component) => $component->getUploadAction(),
        ]);

        // The picker always works with "set:name" internally; the stored
        // representation is chosen with storeAs*() and converted here.
        $this->afterStateHydrated(static function (IconPicker $component, mixed $state): void {
            $component->state($component->normalizeState($state));
        });

        $this->dehydrateStateUsing(static fn (IconPicker $component, mixed $state): string|array|null => $component->formatStateForStorage($state));

        $this->rule(static fn (IconPicker $component): Closure => static function (string $attribute, mixed $value, Closure $fail) use ($component): void {
            if (blank($value)) {
                return;
            }

            if (! $component->hasIcon($value)) {
                $fail(__('filament-icon-picker::icon-picker.validation.invalid'));
            }
        });
    }

    /**
     * Whether clicking the selected icon again clears the value.
     */
    public function deselectable(bool|Closure $condition = true): static
    {
        $this->isDeselectable = $condition;

        return $this;
    }

    public function isDeselectable(): bool
    {
        return (bool) $this->evaluate($this->isDeselectable);
    }

    /**
     * Any CSS length, e.g. `'24rem'`. The grid scrolls once it is taller.
     */
    public function gridMaxHeight(string|Closure|null $height): static
    {
        $this->gridMaxHeight = $height;

        return $this;
    }

    public function getGridMaxHeight(): ?string
    {
        return $this->evaluate($this->gridMaxHeight);
    }

    /**
     * Inline CSS custom properties that drive the grid layout.
     */
    public function getGridStyle(): string
    {
        $declarations = [];

        foreach ($this->getGridColumns() as $breakpoint => $count) {
            $declarations[] = "--fi-icon-picker-columns-{$breakpoint}: {$count};";
        }

        if (filled($maxHeight = $this->getGridMaxHeight())) {
            $declarations[] = "--fi-icon-picker-max-height: {$maxHeight};";
        }

        foreach ($this->getThemeColorDeclarations() as $property => $value) {
            $declarations[] = "{$property}: {$value};";
        }

        return implode(' ', $declarations);
    }

    /**
     * The panel's primary colour, resolved server-side because Filament 3 and
     * Filament 4/5 expose it to CSS in incompatible formats.
     *
     * @return array<string, string>
     */
    protected function getThemeColorDeclarations(): array
    {
        return array_filter([
            '--fi-icon-picker-primary-light' => CssColor::shade('primary', 600),
            '--fi-icon-picker-primary-dark' => CssColor::shade('primary', 400),
            '--fi-icon-picker-primary-soft-light' => CssColor::shade('primary', 50),
        ]);
    }
}
