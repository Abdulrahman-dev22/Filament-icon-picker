<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\Support;

use InvalidArgumentException;
use Stringable;

/**
 * The value an IconPicker stores: "set-key:icon-name".
 *
 * Keeping the set key in the value means the same icon name in two different
 * sets never collides, and the value can be resolved back to a renderable
 * icon anywhere in the application.
 */
final class IconReference implements Stringable
{
    public const SEPARATOR = ':';

    public function __construct(
        public readonly string $set,
        public readonly string $name,
    ) {}

    public static function make(string $set, string $name): self
    {
        return new self($set, $name);
    }

    /**
     * Parse a stored value, returning null when it is blank or malformed.
     */
    public static function tryFrom(mixed $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        $position = strpos($value, self::SEPARATOR);

        if ($position === false || $position === 0 || $position === strlen($value) - 1) {
            return null;
        }

        return new self(substr($value, 0, $position), substr($value, $position + 1));
    }

    /**
     * Parse a stored value or throw when it is malformed.
     */
    public static function from(mixed $value): self
    {
        return self::tryFrom($value) ?? throw new InvalidArgumentException(
            sprintf('[%s] is not a valid icon reference. Expected "set%sname".', is_scalar($value) ? (string) $value : get_debug_type($value), self::SEPARATOR),
        );
    }

    public function toString(): string
    {
        return $this->set.self::SEPARATOR.$this->name;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
