# Changelog

All notable changes to `filament-icon-picker` are documented here. The format
follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the
project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Fixed

- Custom SVGs are rendered as authored. The picker no longer forces `fill: currentColor` on every
  icon, which painted outline icons (root `fill="none"` with stroked paths) as solid shapes and
  overrode hard-coded brand colours. Only icons without any root `fill` attribute inherit the text
  colour.
- Grid cells are capped at `--fi-icon-picker-option-max-size` (`5rem`), so `columns(1)` or a narrow
  screen no longer stretches each icon into a huge square.
- A per-breakpoint `columns([...])` array without a `default` entry now falls back to `4` columns
  on small screens instead of `1`.

## [1.0.0] - 2026-09-22

### Added

- `IconPicker` form field with a searchable, tabbed icon grid.
- Bundled Heroicons set (configurable styles) and directory based custom icon sets.
- `IconSet` contract and `IconSetRegistry` for registering additional sets.
- `FilamentIconPicker` facade and `<x-filament-icon-picker::icon>` component to render stored values.
- Optional persistent caching of icon lists and `filament-icon-picker:clear` command.
- Storage formats (`storeAsReference()`, `storeAsBladeIcon()`, `storeAsArray()`, `storeAsJson()`, `storeAsSvg()`) with a global default.
- `FilamentIconPicker::toArray()` / `url()` and an optional `{set}/{name}.svg` route for API clients.
- Support for Filament 3, 4 and 5.
