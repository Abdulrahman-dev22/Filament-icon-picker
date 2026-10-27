{{--
    The set tabs, search box and icon grid. Included by the field view, so it
    shares its variables and its Alpine scope.
--}}

@php
    use AbdulrahmanDev22\FilamentIconPicker\Support\IconReference;
    use AbdulrahmanDev22\FilamentIconPicker\Support\IconRenderer;
@endphp

@if (count($sets) > 1)
    <div class="fi-icon-picker-tabs" role="tablist">
        @foreach ($sets as $key => $set)
            <button
                type="button"
                role="tab"
                x-on:click="showSet(@js($key))"
                x-bind:class="{ 'fi-active': activeSet === @js($key) }"
                x-bind:aria-selected="activeSet === @js($key)"
                class="fi-icon-picker-tab"
            >
                <span>{{ $set->getLabel() }}</span>
                <span class="fi-icon-picker-tab-count">{{ count($iconsBySet[$key]) }}</span>
            </button>
        @endforeach
    </div>
@endif

@if ($isSearchable && count($sets))
    {{--
        A plain <input> is used on purpose: a Blade echo inside an
        attribute name (x-model.debounce.{{ ... }}) prevents Blade from
        compiling a <x-filament::input> component tag.
    --}}
    <x-filament::input.wrapper
        inline-prefix
        prefix-icon="heroicon-m-magnifying-glass"
        class="fi-icon-picker-search"
    >
        <input
            type="search"
            autocomplete="off"
            placeholder="{{ $getSearchPrompt() }}"
            x-model.debounce.{{ $getSearchDebounce() }}ms="search"
            class="fi-input fi-icon-picker-search-input"
        />
    </x-filament::input.wrapper>
@endif

@forelse ($sets as $key => $set)
    <div
        x-show="activeSet === @js($key)"
        @if (! $loop->first) x-cloak @endif
        wire:key="{{ $livewireKey }}.sets.{{ $key }}"
        role="tabpanel"
        class="fi-icon-picker-set"
    >
        @if (count($iconsBySet[$key]))
            <div
                x-show="hasResults(@js($key))"
                class="fi-icon-picker-grid"
            >
                @foreach ($iconsBySet[$key] as $name => $label)
                    @php
                        $value = IconReference::make($key, (string) $name)->toString();
                        $iconHtml = IconRenderer::render($set->getIcon((string) $name), ['class' => 'fi-icon-picker-icon']);
                    @endphp

                    @continue($iconHtml === null)

                    <button
                        type="button"
                        data-icon-picker-icon="{{ $value }}"
                        title="{{ $label }} ({{ $name }})"
                        aria-label="{{ $label }}"
                        x-show="isVisible(@js($key), @js((string) $name))"
                        x-bind:class="{ 'fi-selected': isSelected(@js($value)) }"
                        x-bind:aria-pressed="isSelected(@js($value))"
                        x-on:click="select(@js($value))"
                        @disabled($isDisabled)
                        class="fi-icon-picker-option"
                    >
                        {{ $iconHtml }}
                    </button>
                @endforeach
            </div>

            @if ($isSearchable)
                <div
                    x-cloak
                    x-show="! hasResults(@js($key))"
                    class="fi-icon-picker-empty"
                >
                    {{ $getNoSearchResultsMessage() }}
                </div>
            @endif
        @else
            <div class="fi-icon-picker-empty">
                {{ __('filament-icon-picker::icon-picker.empty_set') }}
            </div>
        @endif
    </div>
@empty
    <div class="fi-icon-picker-empty">
        {{ __('filament-icon-picker::icon-picker.no_sets') }}
    </div>
@endforelse
