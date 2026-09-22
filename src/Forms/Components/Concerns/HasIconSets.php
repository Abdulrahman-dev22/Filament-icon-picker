<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\Forms\Components\Concerns;

use AbdulrahmanDev22\FilamentIconPicker\IconSets\CustomIconSet;
use AbdulrahmanDev22\FilamentIconPicker\IconSets\IconSet;
use AbdulrahmanDev22\FilamentIconPicker\IconSets\IconSetRegistry;
use AbdulrahmanDev22\FilamentIconPicker\Support\IconReference;
use Closure;

trait HasIconSets
{
    /**
     * @var array<int, array<int, IconSet|class-string<IconSet>>|Closure>
     */
    protected array $fieldIconSets = [];

    /**
     * @var array<int, string|Closure>
     */
    protected array $excludedIconSetKeys = [];

    protected bool|Closure $shouldIncludeGlobalIconSets = true;

    /**
     * @var array<string, IconSet>|null
     */
    protected ?array $cachedIconSets = null;

    /**
     * Add icon sets to this field. A set whose key matches a globally
     * registered one replaces it for this field only. Pass `merge: false`
     * to use only the given sets and ignore the global registry.
     *
     * @param  array<int, IconSet|class-string<IconSet>>|Closure  $sets
     */
    public function icons(array|Closure $sets, bool $merge = true): static
    {
        $this->fieldIconSets[] = $sets;

        if (! $merge) {
            $this->shouldIncludeGlobalIconSets = false;
        }

        $this->cachedIconSets = null;

        return $this;
    }

    /**
     * Add a tab listing every `.svg` file in the given directory. Call it more
     * than once with different keys to get several custom tabs.
     */
    public function customIconsPath(string|Closure $path, string $key = 'custom', string|Closure|null $label = null): static
    {
        $this->fieldIconSets[] = fn (): array => [
            new CustomIconSet($this->evaluate($path), $key, $this->evaluate($label)),
        ];

        $this->cachedIconSets = null;

        return $this;
    }

    /**
     * Hide one or more globally registered sets from this field.
     */
    public function withoutIconSet(string|Closure ...$keys): static
    {
        $this->excludedIconSetKeys = [...$this->excludedIconSetKeys, ...$keys];
        $this->cachedIconSets = null;

        return $this;
    }

    public function withoutHeroicons(): static
    {
        return $this->withoutIconSet('heroicons');
    }

    /**
     * Ignore the global registry entirely and only use sets added to this field.
     */
    public function withoutGlobalIconSets(bool|Closure $condition = true): static
    {
        $this->shouldIncludeGlobalIconSets = fn (): bool => ! $this->evaluate($condition);
        $this->cachedIconSets = null;

        return $this;
    }

    /**
     * The sets shown as tabs, in display order.
     *
     * @return array<string, IconSet>
     */
    public function getIconSets(): array
    {
        if ($this->cachedIconSets !== null) {
            return $this->cachedIconSets;
        }

        $registry = app(IconSetRegistry::class);

        $sets = $this->evaluate($this->shouldIncludeGlobalIconSets) ? $registry->all() : [];

        foreach ($this->fieldIconSets as $candidates) {
            foreach ((array) $this->evaluate($candidates) as $set) {
                $set = $registry->resolve($set);

                $sets[$set->getKey()] = $set;
            }
        }

        foreach ($this->excludedIconSetKeys as $key) {
            unset($sets[$this->evaluate($key)]);
        }

        return $this->cachedIconSets = $sets;
    }

    public function getIconSet(string $key): ?IconSet
    {
        return $this->getIconSets()[$key] ?? null;
    }

    /**
     * Whether a stored value points at an icon this field can offer.
     */
    public function hasIcon(mixed $value): bool
    {
        $reference = IconReference::tryFrom($value);

        if ($reference === null) {
            return false;
        }

        $set = $this->getIconSet($reference->set);

        return $set !== null && array_key_exists($reference->name, $set->getIcons());
    }
}
