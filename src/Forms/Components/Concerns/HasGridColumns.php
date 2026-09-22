<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\Forms\Components\Concerns;

use Closure;
use InvalidArgumentException;

/**
 * Column configuration for the icon grid.
 *
 * Unlike Filament's own `columns()` (where an integer only applies from the
 * `lg` breakpoint), an integer here applies at every breakpoint, because an
 * icon grid rarely wants to collapse to a single column on small screens.
 */
trait HasGridColumns
{
    /**
     * @var array<string, int>|int|string|Closure|null
     */
    protected array|int|string|Closure|null $gridColumns = null;

    /**
     * The parameter type is the union of what Filament 3 and Filament 4/5
     * accept on `Component::columns()`, so this override stays compatible
     * with every supported version.
     *
     * @param  array<string, int>|int|string|Closure|null  $columns  e.g. `8` or `['default' => 4, 'md' => 6, 'xl' => 10]`
     */
    public function columns(array|int|string|Closure|null $columns = 2): static
    {
        $this->gridColumns = $columns;

        return $this;
    }

    /**
     * @return array<string, int> breakpoint => column count, always containing `default`
     */
    public function getGridColumns(): array
    {
        $columns = $this->evaluate($this->gridColumns) ?? $this->getDefaultGridColumns();

        if (is_int($columns) || is_string($columns)) {
            $columns = ['default' => (int) $columns];
        }

        $normalized = [];

        foreach ($columns as $breakpoint => $count) {
            if (! in_array($breakpoint, $this->getGridBreakpoints(), true)) {
                throw new InvalidArgumentException(sprintf('Unknown icon picker breakpoint [%s]. Use one of: %s.', $breakpoint, implode(', ', $this->getGridBreakpoints())));
            }

            $count = (int) $count;

            if ($count < 1) {
                throw new InvalidArgumentException('Icon picker column counts must be at least 1.');
            }

            $normalized[$breakpoint] = $count;
        }

        $normalized['default'] ??= 1;

        return $normalized;
    }

    /**
     * Columns used when none are configured. Constants are avoided here
     * because trait constants require PHP 8.2.
     *
     * @return array<string, int>
     */
    protected function getDefaultGridColumns(): array
    {
        return [
            'default' => 4,
            'sm' => 6,
            'lg' => 8,
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function getGridBreakpoints(): array
    {
        return ['default', 'sm', 'md', 'lg', 'xl', '2xl'];
    }
}
