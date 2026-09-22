<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\IconSets;

use AbdulrahmanDev22\FilamentIconPicker\Support\IconCache;
use AbdulrahmanDev22\FilamentIconPicker\Support\IconReference;
use AbdulrahmanDev22\FilamentIconPicker\Support\IconRenderer;
use AbdulrahmanDev22\FilamentIconPicker\Support\IconStateConverter;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;

/**
 * Holds the globally available icon sets. Every IconPicker field starts from
 * this list and may add, override or exclude sets for itself.
 */
class IconSetRegistry
{
    /**
     * @var array<string, IconSet>
     */
    protected array $sets = [];

    public function __construct(protected Container $container) {}

    /**
     * Build the registry from the package configuration.
     *
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(Container $container, array $config): static
    {
        $registry = new static($container);

        if ($config['register_heroicons'] ?? true) {
            $registry->register(new HeroiconsIconSet(
                styles: $config['heroicons']['styles'] ?? null,
                label: $config['heroicons']['label'] ?? null,
            ));
        }

        foreach ($config['custom_icon_sets'] ?? [] as $key => $options) {
            $options = is_string($options) ? ['path' => $options] : $options;

            $registry->register(new CustomIconSet(
                path: $options['path'],
                key: (string) ($options['key'] ?? $key),
                label: $options['label'] ?? null,
            ));
        }

        foreach ($config['icon_sets'] ?? [] as $set) {
            $registry->register($set);
        }

        return $registry;
    }

    /**
     * @param  IconSet|class-string<IconSet>  ...$sets
     */
    public function register(IconSet|string ...$sets): static
    {
        foreach ($sets as $set) {
            $set = $this->resolve($set);

            $this->sets[$set->getKey()] = $set;
        }

        return $this;
    }

    public function forget(string $key): static
    {
        unset($this->sets[$key]);

        return $this;
    }

    public function has(string $key): bool
    {
        return isset($this->sets[$key]);
    }

    public function get(string $key): ?IconSet
    {
        return $this->sets[$key] ?? null;
    }

    /**
     * @return array<string, IconSet>
     */
    public function all(): array
    {
        return $this->sets;
    }

    /**
     * Instantiate a set from a class name (through the container) and make
     * sure it is a valid IconSet.
     *
     * @param  IconSet|class-string<IconSet>  $set
     */
    public function resolve(IconSet|string $set): IconSet
    {
        if (is_string($set)) {
            $set = $this->container->make($set);
        }

        if (! $set instanceof IconSet) {
            throw new InvalidArgumentException(sprintf('Icon sets must implement [%s], [%s] given.', IconSet::class, get_debug_type($set)));
        }

        if (str_contains($set->getKey(), IconReference::SEPARATOR)) {
            throw new InvalidArgumentException(sprintf('Icon set key [%s] may not contain "%s".', $set->getKey(), IconReference::SEPARATOR));
        }

        return $set;
    }

    /**
     * Find the set a stored value ("set:name") belongs to.
     */
    public function findSet(IconReference|string|null $icon): ?IconSet
    {
        $reference = IconReference::tryFrom($icon);

        return $reference ? $this->get($reference->set) : null;
    }

    /**
     * Turn a stored value into something Filament can render: a Blade Icons
     * name or an Htmlable. Handy for `IconColumn::make()->icon(fn ($state) => ...)`.
     */
    public function resolveIcon(IconReference|string|null $icon): string|Htmlable|null
    {
        $reference = IconReference::tryFrom($icon);

        if ($reference === null) {
            return null;
        }

        return $this->get($reference->set)?->getIcon($reference->name);
    }

    /**
     * Render a stored value as inline SVG markup.
     *
     * @param  array<string, mixed>  $attributes  HTML attributes to place on the `<svg>` element.
     */
    public function render(IconReference|string|null $icon, array $attributes = []): ?Htmlable
    {
        return IconRenderer::render($this->resolveIcon($icon), $attributes);
    }

    /**
     * Everything an API client needs for a stored value: set, name, label,
     * blade_icon, inline svg and (when the route is enabled) a URL.
     *
     * @return array{set: string, name: string, label: string|null, blade_icon: string|null, svg: string|null, url: string|null}|null
     */
    public function toArray(IconReference|string|array|null $icon): ?array
    {
        $converter = IconStateConverter::for($this->sets);
        $reference = $converter->toReference($icon);

        return $reference ? $converter->toArray($reference) : null;
    }

    /**
     * Public URL of the icon served by the package route, or null when the
     * route is disabled or the icon is unknown.
     */
    public function url(IconReference|string|array|null $icon): ?string
    {
        return $this->toArray($icon)['url'] ?? null;
    }

    /**
     * Forget every memoised and persisted icon list.
     */
    public function clearCache(): void
    {
        foreach ($this->sets as $set) {
            if (method_exists($set, 'forgetCachedIcons')) {
                $set->forgetCachedIcons();
            }
        }

        $this->container->make(IconCache::class)->flush();
    }
}
