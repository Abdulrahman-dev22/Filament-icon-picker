<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\IconSets;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\Factory;
use Illuminate\Support\Str;
use ReflectionClass;

/**
 * The bundled icon set. Heroicons are a hard dependency of every supported
 * Filament version, so this set needs no configuration.
 */
class HeroiconsIconSet implements IconSet
{
    use Concerns\CachesIconList;

    public const KEY = 'heroicons';

    public const BLADE_PREFIX = 'heroicon';

    /**
     * @var array<string, string>
     */
    public const STYLES = [
        'o' => 'Outline',
        's' => 'Solid',
        'm' => 'Mini',
        'c' => 'Micro',
    ];

    /**
     * @param  array<int, string>|null  $styles  Heroicon variants to list (o, s, m, c). Null = config default.
     */
    public function __construct(
        protected ?array $styles = null,
        protected ?string $label = null,
    ) {}

    public function getKey(): string
    {
        return self::KEY;
    }

    public function getLabel(): string
    {
        return $this->label ?? config('filament-icon-picker.heroicons.label', 'Heroicons');
    }

    /**
     * @return array<int, string>
     */
    public function getStyles(): array
    {
        $styles = $this->styles ?? config('filament-icon-picker.heroicons.styles', ['o']);

        return array_values(array_intersect((array) $styles, array_keys(self::STYLES)));
    }

    public function getIcons(): array
    {
        return $this->rememberIcons(fn (): array => $this->scan());
    }

    public function getIcon(string $name): ?string
    {
        if (! preg_match('/^[osmc]-[a-z0-9-]+$/', $name)) {
            return null;
        }

        foreach ($this->getPaths() as $path) {
            if (is_file("{$path}/{$name}.svg")) {
                return self::BLADE_PREFIX."-{$name}";
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    protected function scan(): array
    {
        $styles = $this->getStyles();
        $icons = [];

        foreach ($this->getPaths() as $path) {
            foreach (glob("{$path}/*.svg") ?: [] as $file) {
                $name = basename($file, '.svg');
                $style = substr($name, 0, 1);

                if (! in_array($style, $styles, true)) {
                    continue;
                }

                $icons[$name] = $this->labelFor($name, count($styles) > 1);
            }
        }

        uksort($icons, fn (string $a, string $b): int => [array_search($a[0], $styles, true), substr($a, 2)] <=> [array_search($b[0], $styles, true), substr($b, 2)]);

        return $icons;
    }

    protected function labelFor(string $name, bool $withStyle): string
    {
        $label = Str::headline(substr($name, 2));

        return $withStyle ? "{$label} (".self::STYLES[$name[0]].')' : $label;
    }

    /**
     * Directories that hold the Heroicon SVG files, as registered with Blade Icons.
     *
     * @return array<int, string>
     */
    protected function getPaths(): array
    {
        $paths = app(Factory::class)->all()[self::KEY]['paths'] ?? [];

        if ($paths !== []) {
            return $paths;
        }

        $providerFile = (new ReflectionClass(BladeHeroiconsServiceProvider::class))->getFileName();

        return [realpath(dirname((string) $providerFile).'/../resources/svg') ?: ''];
    }

    protected function getCacheIdentifier(): string
    {
        return self::KEY.'.'.implode('', $this->getStyles());
    }
}
