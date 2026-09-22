<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\IconSets\Contracts;

use AbdulrahmanDev22\FilamentIconPicker\IconSets\IconSet;

/**
 * An icon set that new icons can be written to at runtime (by the field's
 * "Upload icon" action or by your own code). The markup passed in has already
 * been sanitised by the IconUploader.
 */
interface AcceptsUploads extends IconSet
{
    /**
     * Persist the SVG markup under the given icon name (no extension).
     */
    public function storeIcon(string $name, string $svg): void;

    public function deleteIcon(string $name): void;
}
