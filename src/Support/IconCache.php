<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\Support;

use Closure;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;

/**
 * Optional persistent cache for resolved icon lists.
 *
 * Every key written is also recorded in an index so `flush()` can remove
 * them without relying on cache tags.
 */
class IconCache
{
    public function __construct(
        protected Application $app,
        protected Config $config,
        protected CacheFactory $cache,
    ) {}

    public function isEnabled(): bool
    {
        $enabled = $this->config->get('filament-icon-picker.cache.enabled');

        if ($enabled === null || $enabled === '') {
            return $this->app->isProduction();
        }

        return filter_var($enabled, FILTER_VALIDATE_BOOL);
    }

    /**
     * @param  Closure(): array<string, string>  $callback
     * @return array<string, string>
     */
    public function remember(string $identifier, Closure $callback): array
    {
        if (! $this->isEnabled()) {
            return $callback();
        }

        $key = $this->key($identifier);
        $store = $this->store();

        $icons = $store->get($key);

        if (is_array($icons)) {
            return $icons;
        }

        $icons = $callback();
        $ttl = $this->config->get('filament-icon-picker.cache.ttl');

        $ttl === null ? $store->forever($key, $icons) : $store->put($key, $icons, $ttl);

        $this->index([...$this->index(), $key]);

        return $icons;
    }

    public function forget(string $identifier): void
    {
        $key = $this->key($identifier);

        $this->store()->forget($key);
        $this->index(array_values(array_diff($this->index(), [$key])));
    }

    public function flush(): void
    {
        $store = $this->store();

        foreach ($this->index() as $key) {
            $store->forget($key);
        }

        $store->forget($this->indexKey());
    }

    protected function store(): Repository
    {
        return $this->cache->store($this->config->get('filament-icon-picker.cache.store'));
    }

    protected function key(string $identifier): string
    {
        return $this->prefix().'.icons.'.$identifier;
    }

    protected function indexKey(): string
    {
        return $this->prefix().'.keys';
    }

    protected function prefix(): string
    {
        return (string) $this->config->get('filament-icon-picker.cache.prefix', 'filament-icon-picker');
    }

    /**
     * Read or write the list of cache keys this class has written.
     *
     * @param  array<int, string>|null  $keys
     * @return array<int, string>
     */
    protected function index(?array $keys = null): array
    {
        $store = $this->store();

        if ($keys !== null) {
            $store->forever($this->indexKey(), array_values(array_unique($keys)));
        }

        return (array) $store->get($this->indexKey(), []);
    }
}
