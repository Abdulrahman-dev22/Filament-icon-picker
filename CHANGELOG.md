# Changelog

All notable changes to `filament-icon-picker` are documented here. The format
follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the
project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.1.0] - 2026-09-22

### Added

- `DiskIconSet`: icon sets stored on any Laravel filesystem disk (`disk_icon_sets` config).
- `IconPicker::uploadable()`: an "Upload icon" action that sanitises an SVG, stores it in a set implementing `AcceptsUploads` and selects it.
- `SvgSanitizer` (replaceable via `SvgSanitizer::using()`) and `IconUploader`.
- `CustomIconSet` now accepts uploads as well.

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
