{{--
    Render a value stored by the IconPicker anywhere in your Blade views:

        <x-filament-icon-picker::icon :icon="$category->icon" class="h-5 w-5" />
--}}
@props([
    'icon' => null,
])

{{ \AbdulrahmanDev22\FilamentIconPicker\Facades\FilamentIconPicker::render($icon, $attributes->getAttributes()) }}
