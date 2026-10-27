<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\Forms\Components\Concerns;

use Closure;

/**
 * Collapses the picker into a select-like trigger that opens the set tabs,
 * search box and icon grid in a Filament dropdown panel.
 */
trait CanBeDropdown
{
    protected bool|Closure $isDropdown = false;

    protected bool|Closure $shouldCloseOnSelect = false;

    /**
     * Show only the selected icon until the field is clicked, instead of
     * always rendering the grid inline.
     */
    public function dropdown(bool|Closure $condition = true): static
    {
        $this->isDropdown = $condition;

        return $this;
    }

    /**
     * Close the dropdown as soon as an icon is picked.
     */
    public function closeOnSelect(bool|Closure $condition = true): static
    {
        $this->shouldCloseOnSelect = $condition;

        return $this;
    }

    public function isDropdown(): bool
    {
        return (bool) $this->evaluate($this->isDropdown);
    }

    /**
     * Only applies in dropdown mode: an inline grid has nothing to close.
     */
    public function shouldCloseOnSelect(): bool
    {
        return $this->isDropdown() && (bool) $this->evaluate($this->shouldCloseOnSelect);
    }
}
