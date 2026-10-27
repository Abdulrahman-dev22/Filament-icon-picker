@php
    $fieldWrapperView = $getFieldWrapperView();
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isSearchable = $isSearchable() && ! $isDisabled;
    $isDeselectable = $isDeselectable() && ! $isDisabled;
    $isUploadable = $isUploadable() && ! $isDisabled;
    $isDropdown = $isDropdown();
    $shouldCloseOnSelect = $shouldCloseOnSelect();
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
            closeOnSelect: @js($shouldCloseOnSelect),

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
                if (this.state !== value) {
                    this.state = value
                } else if (this.deselectable) {
                    this.state = null
                }

                if (this.closeOnSelect) {
                    this.closeDropdown()
                }
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

            {{--
                The dropdown is Filament's own <x-filament::dropdown>. Its
                Alpine data differs between Filament 3, 4 and 5, but every
                version positions the panel with the same floating UI plugin,
                which shows and hides it through `style.display`, and exposes
                close(). Only those common parts are relied upon here.
            --}}
            setUpDropdownPanel(panel) {
                {{--
                    Filament 4 and 5 cap every dropdown panel at 14rem with an
                    !important rule inside a cascade layer, which no plain
                    stylesheet can override, and Filament 3 makes it 100vw
                    wide. Inline !important styles win in all three and let the
                    panel match the field's width, like Filament's own Select.
                --}}
                panel.style.setProperty('width', '100%', 'important')
                panel.style.setProperty('max-width', 'none', 'important')

                let wasOpen = false

                new MutationObserver(() => {
                    const isOpen = panel.style.display === 'block'

                    if (isOpen && ! wasOpen) {
                        {{-- Wait for the click that opened the panel to finish moving focus. --}}
                        requestAnimationFrame(() => panel.querySelector('.fi-icon-picker-search-input')?.focus())
                    }

                    wasOpen = isOpen
                }).observe(panel, { attributeFilter: ['style'] })
            },

            closeDropdown() {
                {{-- Called from inside the panel, $root is the dropdown's own x-data element. --}}
                const dropdown = this.$el.closest('.fi-icon-picker')?.querySelector('.fi-icon-picker-dropdown')

                if (! dropdown) {
                    return
                }

                window.Alpine.$data(dropdown).close()
                dropdown.querySelector('.fi-icon-picker-dropdown-trigger')?.focus()
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
        <div
            @class([
                'fi-icon-picker-selection',
                'fi-has-dropdown' => $isDropdown,
            ])
        >
            @if ($isDropdown)
                {{--
                    Clear and upload stay outside the trigger, so clicking them
                    never toggles the panel on any Filament version.
                --}}
                <x-filament::dropdown
                    :menu="false"
                    placement="bottom-start"
                    width="fi-icon-picker-dropdown-panel"
                    wire:key="{{ $livewireKey }}.dropdown"
                    class="fi-icon-picker-dropdown"
                >
                    <x-slot name="trigger">
                        <button
                            type="button"
                            @disabled($isDisabled)
                            class="fi-icon-picker-dropdown-trigger"
                        >
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

                            <x-filament::icon
                                icon="heroicon-m-chevron-down"
                                class="fi-icon-picker-dropdown-chevron"
                            />
                        </button>
                    </x-slot>

                    <div
                        x-init="setUpDropdownPanel($el.parentElement)"
                        {{--
                            The panel closes on Escape during the capture phase.
                            Stopping the event here keeps it from also reaching
                            a Filament 3 modal, which closes on any Escape that
                            bubbles up to the window.
                        --}}
                        x-on:keydown.escape.stop
                        class="fi-icon-picker-panel"
                    >
                        @include('filament-icon-picker::forms.components.icon-picker.browser')
                    </div>
                </x-filament::dropdown>
            @else
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
            @endif

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

        @unless ($isDropdown)
            @include('filament-icon-picker::forms.components.icon-picker.browser')
        @endunless
    </div>
</x-dynamic-component>
