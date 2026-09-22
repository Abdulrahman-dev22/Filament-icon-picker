@php
    use AbdulrahmanDev22\FilamentIconPicker\Support\IconReference;
    use AbdulrahmanDev22\FilamentIconPicker\Support\IconRenderer;

    $fieldWrapperView = $getFieldWrapperView();
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isSearchable = $isSearchable() && ! $isDisabled;
    $isDeselectable = $isDeselectable() && ! $isDisabled;
    $isUploadable = $isUploadable() && ! $isDisabled;
    $sets = $getIconSets();
    $placeholder = $getPlaceholder() ?? __('filament-icon-picker::icon-picker.placeholder');
    $livewireKey = $this->getId() . '.' . $statePath . '.' . $field::class;

    $iconsBySet = [];
    $searchIndex = [];

    foreach ($sets as $key => $set) {
        $iconsBySet[$key] = $set->getIcons();

        foreach ($iconsBySet[$key] as $name => $label) {
            $searchIndex[$key][$name] = mb_strtolower($name . ' ' . $label);
        }

        $searchIndex[$key] ??= [];
    }
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <div
        {{--
            Nothing inside x-data may depend on the field state: Livewire
            morphs changed attributes and Alpine would re-initialise the
            scope, orphaning the effects bound to the old one. The active tab
            is therefore derived from the state in init().
        --}}
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            search: '',
            activeSet: null,
            index: @js($searchIndex),
            visible: {},
            selectedHtml: '',
            deselectable: @js($isDeselectable),

            init() {
                this.activeSet = this.initialSet()
                this.filter()
                this.syncPreview()
                this.$watch('search', () => this.filter())
                this.$watch('state', () => this.syncPreview())
            },

            initialSet() {
                const sets = Object.keys(this.index)
                const separator = String(this.state ?? '').indexOf(':')

                if (separator > 0) {
                    const set = String(this.state).slice(0, separator)

                    if (sets.includes(set)) {
                        return set
                    }
                }

                return sets[0] ?? null
            },

            filter() {
                const query = this.search.trim().toLowerCase()
                const visible = {}

                for (const [set, icons] of Object.entries(this.index)) {
                    if (query === '') {
                        visible[set] = null
                        continue
                    }

                    const matches = {}

                    for (const [name, haystack] of Object.entries(icons)) {
                        if (haystack.includes(query)) {
                            matches[name] = true
                        }
                    }

                    visible[set] = matches
                }

                this.visible = visible
            },

            isVisible(set, name) {
                const matches = this.visible[set]

                return matches === null || matches === undefined || matches[name] === true
            },

            hasResults(set) {
                const matches = this.visible[set]

                return matches === null || matches === undefined || Object.keys(matches).length > 0
            },

            isSelected(value) {
                return this.state === value
            },

            select(value) {
                if (this.state === value) {
                    if (this.deselectable) {
                        this.state = null
                    }

                    return
                }

                this.state = value
            },

            clear() {
                this.state = null
            },

            showSet(set) {
                this.activeSet = set
            },

            syncPreview() {
                const option = this.state
                    ? this.$root.querySelector('[data-icon-picker-icon=' + JSON.stringify(String(this.state)) + ']')
                    : null

                this.selectedHtml = option ? option.innerHTML : ''
            },
        }"
        {{
            $getExtraAttributeBag()
                ->merge($getExtraAlpineAttributeBag()->getAttributes(), escape: false)
                ->class([
                    'fi-icon-picker',
                    'fi-disabled' => $isDisabled,
                ])
                ->style([$getGridStyle()])
        }}
    >
        <div class="fi-icon-picker-selection">
            <span
                x-cloak
                x-show="state"
                x-html="selectedHtml"
                class="fi-icon-picker-selection-preview"
                aria-hidden="true"
            ></span>

            <span
                x-text="state || @js($placeholder)"
                x-bind:class="{ 'fi-icon-picker-selection-placeholder': ! state }"
                class="fi-icon-picker-selection-label"
            >{{ $placeholder }}</span>

            @if ($isDeselectable)
                <button
                    type="button"
                    x-cloak
                    x-show="state"
                    x-on:click="clear()"
                    class="fi-icon-picker-clear"
                >
                    {{ __('filament-icon-picker::icon-picker.clear') }}
                </button>
            @endif

            @if ($isUploadable)
                <div class="fi-icon-picker-upload" wire:key="{{ $livewireKey }}.upload">
                    {{ $getAction('uploadIcon') }}
                </div>
            @endif
        </div>

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
    </div>
</x-dynamic-component>
