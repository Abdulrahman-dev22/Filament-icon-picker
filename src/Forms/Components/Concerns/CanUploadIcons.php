<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\Forms\Components\Concerns;

use AbdulrahmanDev22\FilamentIconPicker\Exceptions\InvalidSvgException;
use AbdulrahmanDev22\FilamentIconPicker\Forms\Components\IconPicker;
use AbdulrahmanDev22\FilamentIconPicker\IconSets\Contracts\AcceptsUploads;
use AbdulrahmanDev22\FilamentIconPicker\IconSets\DiskIconSet;
use AbdulrahmanDev22\FilamentIconPicker\Support\IconUploader;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Actions\Action as FormComponentAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Lets the person filling the form add a new SVG to one of the field's icon
 * sets through an "Upload icon" modal. The target set must implement
 * AcceptsUploads (DiskIconSet or CustomIconSet).
 */
trait CanUploadIcons
{
    protected bool|Closure $isUploadable = false;

    protected string|Closure|null $uploadIconSetKey = null;

    protected int|Closure|null $uploadMaxSize = null;

    protected ?Closure $modifyUploadActionUsing = null;

    /**
     * Show the upload button. Pass a closure to authorise it, e.g.
     * `->uploadable(fn () => auth()->user()->can('upload icons'))`.
     *
     * @param  string|Closure|null  $set  key of the set to upload into; null = the first disk-backed set. Folder sets (CustomIconSet) only receive uploads when named explicitly.
     */
    public function uploadable(bool|Closure $condition = true, string|Closure|null $set = null): static
    {
        $this->isUploadable = $condition;

        if ($set !== null) {
            $this->uploadIconSetKey = $set;
        }

        return $this;
    }

    public function uploadIconsTo(string|Closure|null $setKey): static
    {
        $this->uploadIconSetKey = $setKey;

        return $this;
    }

    /**
     * Maximum file size in kilobytes. Defaults to `filament-icon-picker.uploads.max_size`.
     */
    public function uploadMaxSize(int|Closure|null $kilobytes): static
    {
        $this->uploadMaxSize = $kilobytes;

        return $this;
    }

    /**
     * Customise the upload action (label, icon, modal, authorisation…).
     * Filament 3 passes a `Filament\Forms\Components\Actions\Action`,
     * Filament 4/5 a `Filament\Actions\Action`.
     *
     * @param  Closure(Action|FormComponentAction $action): (Action|FormComponentAction|null)  $callback
     */
    public function uploadAction(?Closure $callback): static
    {
        $this->modifyUploadActionUsing = $callback;

        return $this;
    }

    public function isUploadable(): bool
    {
        return (bool) $this->evaluate($this->isUploadable) && $this->getUploadIconSet() !== null;
    }

    public function getUploadIconSet(): ?AcceptsUploads
    {
        $sets = $this->getIconSets();
        $key = $this->evaluate($this->uploadIconSetKey) ?? config('filament-icon-picker.uploads.set');

        if (filled($key)) {
            $set = $sets[$key] ?? null;

            return $set instanceof AcceptsUploads ? $set : null;
        }

        // Without an explicit target, prefer a disk-backed set: a folder set
        // (e.g. resources/svg shipped with the app) should only receive
        // uploads when the developer names it.
        foreach ($sets as $set) {
            if ($set instanceof DiskIconSet) {
                return $set;
            }
        }

        return null;
    }

    public function getUploadMaxSize(): int
    {
        return (int) ($this->evaluate($this->uploadMaxSize) ?? config('filament-icon-picker.uploads.max_size', 256));
    }

    public function getUploadAction(): Action|FormComponentAction
    {
        $action = static::newUploadAction()
            ->label(__('filament-icon-picker::icon-picker.upload.label'))
            ->icon('heroicon-m-arrow-up-tray')
            ->link()
            ->size('sm')
            ->modalHeading(__('filament-icon-picker::icon-picker.upload.heading'))
            ->modalSubmitActionLabel(__('filament-icon-picker::icon-picker.upload.submit'))
            ->modalWidth('md')
            ->form([
                FileUpload::make('file')
                    ->label(__('filament-icon-picker::icon-picker.upload.file'))
                    ->helperText(__('filament-icon-picker::icon-picker.upload.file_help', ['size' => $this->getUploadMaxSize()]))
                    ->acceptedFileTypes(['image/svg+xml', 'image/svg', 'text/xml', 'application/xml', 'text/plain'])
                    ->maxSize($this->getUploadMaxSize())
                    ->storeFiles(false)
                    ->previewable(false)
                    ->required(),
                TextInput::make('name')
                    ->label(__('filament-icon-picker::icon-picker.upload.name'))
                    ->helperText(__('filament-icon-picker::icon-picker.upload.name_help'))
                    ->maxLength(64)
                    ->regex('/^[\pL\pN _-]+$/u'),
            ])
            ->action(function (array $data, IconPicker $component, mixed $action): void {
                $component->handleIconUpload($data, $action);
            })
            ->visible(fn (): bool => $this->isUploadable());

        if ($this->modifyUploadActionUsing) {
            $action = $this->evaluate($this->modifyUploadActionUsing, ['action' => $action]) ?? $action;
        }

        return $action;
    }

    /**
     * Form component actions are `Filament\Forms\Components\Actions\Action`
     * on Filament 3 and `Filament\Actions\Action` on 4/5; the field must not
     * care which, so the concrete class is looked up at runtime.
     */
    protected static function newUploadAction(): Action|FormComponentAction
    {
        $class = class_exists(FormComponentAction::class) && ! is_a(FormComponentAction::class, Action::class, true)
            ? FormComponentAction::class
            : Action::class;

        return $class::make('uploadIcon');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function handleIconUpload(array $data, Action|FormComponentAction $action): void
    {
        $set = $this->getUploadIconSet();

        if ($set === null) {
            $this->notifyUploadFailure(__('filament-icon-picker::icon-picker.upload.no_set'));
            $action->halt();

            return;
        }

        $file = $data['file'] ?? null;
        $file = is_array($file) ? reset($file) : $file;

        [$contents, $originalName] = $this->readUploadedSvg($file);

        if ($contents === null) {
            $this->notifyUploadFailure(__('filament-icon-picker::icon-picker.upload.invalid'));
            $action->halt();

            return;
        }

        try {
            $reference = app(IconUploader::class)->upload(
                set: $set,
                svg: $contents,
                name: $data['name'] ?? null,
                originalFilename: $originalName,
                maxBytes: $this->getUploadMaxSize() * 1024,
            );
        } catch (InvalidSvgException $exception) {
            $this->notifyUploadFailure(__('filament-icon-picker::icon-picker.upload.invalid'), $exception->getMessage());
            $action->halt();

            return;
        }

        $this->state($reference->toString());
        $this->callAfterStateUpdated();

        Notification::make()
            ->title(__('filament-icon-picker::icon-picker.upload.success', ['name' => $reference->name]))
            ->success()
            ->send();
    }

    /**
     * @return array{0: string|null, 1: string|null} [contents, original file name]
     */
    protected function readUploadedSvg(mixed $file): array
    {
        if ($file instanceof TemporaryUploadedFile) {
            return [(string) $file->get(), $file->getClientOriginalName()];
        }

        if ($file instanceof UploadedFile) {
            $path = $file->getRealPath();

            return [is_string($path) && is_file($path) ? (string) file_get_contents($path) : null, $file->getClientOriginalName()];
        }

        if (is_string($file) && str_contains($file, '<svg')) {
            return [$file, null];
        }

        return [null, null];
    }

    protected function notifyUploadFailure(string $title, ?string $body = null): void
    {
        Notification::make()
            ->title($title)
            ->body($body)
            ->danger()
            ->send();
    }
}
