<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\Support;

use AbdulrahmanDev22\FilamentIconPicker\Exceptions\InvalidSvgException;
use Closure;
use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMProcessingInstruction;

/**
 * Makes an uploaded SVG safe to render inline: scripts, event handlers,
 * embedded documents and external references are removed, the rest is kept.
 *
 * Replace it entirely with `SvgSanitizer::using(fn (string $svg): string => …)`
 * (for example to plug in enshrined/svg-sanitize).
 */
class SvgSanitizer
{
    protected static ?Closure $using = null;

    /**
     * @var array<int, string>
     */
    public const REMOVED_ELEMENTS = ['script', 'foreignObject', 'iframe', 'object', 'embed', 'audio', 'video', 'handler', 'listener'];

    /**
     * @var array<int, string>
     */
    public const HREF_ATTRIBUTES = ['href', 'xlink:href', 'src'];

    /**
     * @var array<int, string>
     */
    public const UNSAFE_STYLE_TOKENS = ['url(', '@import', 'expression(', 'javascript:', 'behavior:', '-moz-binding'];

    public static function using(?Closure $callback): void
    {
        static::$using = $callback;
    }

    /**
     * @throws InvalidSvgException when the markup is not an SVG document
     */
    public function sanitize(string $svg): string
    {
        if (static::$using) {
            return (static::$using)($svg);
        }

        $svg = trim($svg);

        if ($svg === '' || ! str_contains($svg, '<svg')) {
            throw new InvalidSvgException('The file does not contain an <svg> element.');
        }

        $previous = libxml_use_internal_errors(true);

        $document = new DOMDocument;
        $document->preserveWhiteSpace = false;

        $loaded = $document->loadXML($svg, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_NOCDATA);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->documentElement;

        if (! $loaded || ! $root instanceof DOMElement || strtolower($root->localName ?? '') !== 'svg') {
            throw new InvalidSvgException('The file is not well-formed SVG.');
        }

        if ($document->doctype) {
            $document->removeChild($document->doctype);
        }

        foreach (iterator_to_array($document->childNodes) as $node) {
            if ($node instanceof DOMProcessingInstruction) {
                $document->removeChild($node);
            }
        }

        $this->clean($root);

        $output = $document->saveXML($root);

        if (! is_string($output) || $output === '') {
            throw new InvalidSvgException('The SVG could not be serialised.');
        }

        return $output;
    }

    protected function clean(DOMElement $element): void
    {
        foreach (iterator_to_array($element->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                if ($child instanceof DOMProcessingInstruction) {
                    $element->removeChild($child);
                }

                continue;
            }

            if (in_array($child->localName, self::REMOVED_ELEMENTS, true)) {
                $element->removeChild($child);

                continue;
            }

            if (strtolower($child->localName ?? '') === 'style' && $this->hasUnsafeStyle($child->textContent)) {
                $element->removeChild($child);

                continue;
            }

            $this->clean($child);
        }

        /** @var DOMAttr $attribute */
        foreach (iterator_to_array($element->attributes ?? []) as $attribute) {
            $name = strtolower($attribute->nodeName);
            $value = trim($attribute->nodeValue ?? '');

            if (str_starts_with($name, 'on')) {
                $element->removeAttributeNode($attribute);

                continue;
            }

            if (in_array($name, self::HREF_ATTRIBUTES, true) && ! $this->isSafeReference($value)) {
                $element->removeAttributeNode($attribute);

                continue;
            }

            if ($name === 'style' && $this->hasUnsafeStyle($value)) {
                $element->removeAttributeNode($attribute);
            }
        }
    }

    protected function isSafeReference(string $value): bool
    {
        if ($value === '' || str_starts_with($value, '#')) {
            return true;
        }

        return (bool) preg_match('/^data:image\/(png|jpe?g|gif|webp);base64,/i', $value);
    }

    protected function hasUnsafeStyle(string $css): bool
    {
        $css = strtolower(preg_replace('/\s+/', '', $css) ?? '');

        foreach (self::UNSAFE_STYLE_TOKENS as $token) {
            if (str_contains($css, str_replace(' ', '', $token))) {
                return true;
            }
        }

        return false;
    }
}
