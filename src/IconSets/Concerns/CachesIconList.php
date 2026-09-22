<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\IconSets\Concerns;

use AbdulrahmanDev22\FilamentIconPicker\Support\IconCache;
use Closure;

/**
 * Per-request memoisation plus an optional persistent cache layer for icon
 * sets that discover their icons by scanning the filesystem.
 */
trait CachesIconList
{
    /**
     * @var array<string, string>|null
     */
    protected ?array $memoizedIcons = null;

    /**
     * Identifier that uniquely describes the scanned source (path, styles…).
     */
    abstract protected function getCacheIdentifier(): string;

    /**
     * @param  Closure(): array<string, string>  $callback
     * @return array<string, string>
     */
    protected function rememberIcons(Closure $callback): array
    {
        if ($this->memoizedIcons !== null) {
            return $this->memoizedIcons;
        }

        return $this->memoizedIcons = app(IconCache::class)->remember($this->getCacheIdentifier(), $callback);
    }

    public function forgetCachedIcons(): void
    {
        $this->memoizedIcons = null;

        app(IconCache::class)->forget($this->getCacheIdentifier());
    }
}
