<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\Tests\Fixtures\Livewire;

use AbdulrahmanDev22\FilamentIconPicker\Forms\Components\IconPicker;
use AbdulrahmanDev22\FilamentIconPicker\Tests\TestCase;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;

/**
 * Filament v3 flavour of the test form.
 */
class IconPickerFormV3 extends Component implements HasForms
{
    use InteractsWithForms;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public ?string $initialIcon = null;

    public ?string $storageFormat = null;

    public mixed $saved = null;

    public function mount(): void
    {
        $this->form->fill(['icon' => $this->initialIcon]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                IconPicker::make('icon')
                    ->customIconsPath(TestCase::fixturePath('icons'), 'icons')
                    ->storeAs($this->storageFormat ?? 'reference'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $this->saved = $this->form->getState();
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}
