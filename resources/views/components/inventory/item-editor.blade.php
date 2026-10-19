@use('App\Inventory\Enum\ItemAccount')

@props([
    'name' => 'item-editor',
])

{{--
    The item editor: one form that creates an item or changes one, and the only form there is in it.

        <x-inventory.item-editor name="item-editor" />

    Hosted by a modal, a page or a legacy screen alike, because it asks its host for nothing: its pick
    lists come from `ItemEditorComposer` and it talks only through its own handle and events.

        App.itemEditor('item-editor').create()      a blank item
        App.itemEditor('item-editor').edit('CODE')  an existing one
        item-editor:created | updated | cancelled | failed | status-changed | image-removed   bubbling from the root, `detail.item` the code
--}}

@php
    $label = fn (string $key) => __('inventory.item.editor.field.'.$key);

    // A panel drawn anywhere but inside the editor would open behind a modal that hosts it, because a
    // modal's top layer sits above everything outside it.
    $panelParent = '[data-item-editor][data-name="'.$name.'"]';
@endphp

<section data-item-editor data-name="{{ $name }}" x-data="itemEditor" x-cloak>
    <form data-form @submit.prevent="save()" novalidate class="grid grid-cols-[minmax(0,1fr)] gap-6">
        <div class="grid grid-cols-[minmax(0,1fr)] gap-6 lg:grid-cols-2 lg:items-start">
            <div class="grid min-w-0 grid-cols-[minmax(0,1fr)] gap-6">
            <fieldset class="min-w-0">
                <legend>{{ __('inventory.item.editor.section.identity') }}</legend>
                <div class="grid grid-cols-[minmax(0,1fr)] gap-y-2">
                    <span class="text-sm font-semibold text-card-title-txt">{{ $label('stock_id') }}</span>
                    <div>
                        <template x-if="isNew">
                            <div class="flex gap-2">
                                <input type="text" id="{{ $name }}-stock-id" data-code maxlength="20" required autofocus
                                       x-model="form.stock_id" class="field" aria-describedby="{{ $name }}-code-hint"
                                       :class="errorFor('stock_id') ? 'border-error-accent' : 'border-field-border'">
                                <x-ui.button type="button" variant="outline" icon="refresh" data-generate
                                             x-show="canGenerateBarcode" @click="generateCode()">
                                    {{ __('inventory.item.editor.action.generate') }}
                                </x-ui.button>
                            </div>
                        </template>
                        <template x-if="! isNew">
                            <p data-code-label class="py-2 font-semibold text-card-title-txt" x-text="form.stock_id"></p>
                        </template>
                    </div>
                    <p class="text-xs text-card-txt" id="{{ $name }}-code-hint" x-show="isNew">
                        {{ __('inventory.item.editor.code_hint') }}
                    </p>
                    <p class="text-sm font-semibold text-error-accent" x-show="errorFor('stock_id')" x-text="errorFor('stock_id')"></p>

                    <label for="{{ $name }}-description" class="text-sm font-semibold text-card-title-txt">{{ $label('description') }}</label>
                    <input type="text" id="{{ $name }}-description" maxlength="200" required x-model="form.description"
                           class="field" :class="errorFor('description') ? 'border-error-accent' : 'border-field-border'">
                    <p class="text-sm font-semibold text-error-accent" x-show="errorFor('description')" x-text="errorFor('description')"></p>

                    <label for="{{ $name }}-long-description" class="text-sm font-semibold text-card-title-txt">{{ $label('long_description') }}</label>
                    <textarea id="{{ $name }}-long-description" rows="2" maxlength="255" x-model="form.long_description"
                              class="field" :class="errorFor('long_description') ? 'border-error-accent' : 'border-field-border'"></textarea>
                    <p class="text-sm font-semibold text-error-accent" x-show="errorFor('long_description')" x-text="errorFor('long_description')"></p>

                    <label for="{{ $name }}-category" class="text-sm font-semibold text-card-title-txt">{{ $label('category') }}</label>
                    <x-ui.select
                        id="{{ $name }}-category"
                        name="category_id"
                        class="w-full"
                        :panel-parent="$panelParent"
                        :channel="$categories"
                        :placeholder="__('inventory.item.editor.placeholder.category')"
                        x-model="form.category_id"
                        x-effect="$el.__xSelect?.setValue(form.category_id, { silent: true })"
                        @change="categoryPicked()"
                    />
                    <p class="text-sm font-semibold text-error-accent" x-show="errorFor('category_id')" x-text="errorFor('category_id')"></p>

                <label for="{{ $name }}-type" class="text-sm font-semibold text-card-title-txt">{{ $label('type') }}</label>
                    <x-ui.select
                        id="{{ $name }}-type"
                        name="mb_flag"
                        class="w-full"
                        :panel-parent="$panelParent"
                        :options="$types"
                        x-model="form.mb_flag"
                        x-effect="$el.__xSelect?.setValue(form.mb_flag, { silent: true }); $el.__xSelect && ($el.__xSelect.disabled = locked)"
                    />
                    <p class="text-xs text-card-txt" x-show="locked">{{ __('inventory.item.editor.locked') }}</p>
                    <p class="text-sm font-semibold text-error-accent" x-show="errorFor('mb_flag')" x-text="errorFor('mb_flag')"></p>

                    <label for="{{ $name }}-units" class="text-sm font-semibold text-card-title-txt">{{ $label('units') }}</label>
                    <x-ui.select
                        id="{{ $name }}-units"
                        name="units"
                        class="w-full"
                        :panel-parent="$panelParent"
                        :channel="$units"
                        :placeholder="__('inventory.item.editor.placeholder.choose')"
                        x-model="form.units"
                        x-effect="$el.__xSelect?.setValue(form.units, { silent: true }); $el.__xSelect && ($el.__xSelect.disabled = locked)"
                    />
                    <p class="text-sm font-semibold text-error-accent" x-show="errorFor('units')" x-text="errorFor('units')"></p>

                    <label for="{{ $name }}-tax-type" class="text-sm font-semibold text-card-title-txt">{{ $label('tax_type') }}</label>
                    <x-ui.select
                        id="{{ $name }}-tax-type"
                        name="tax_type_id"
                        class="w-full"
                        :panel-parent="$panelParent"
                        :channel="$taxTypes"
                        :placeholder="__('inventory.item.editor.placeholder.choose')"
                        x-model="form.tax_type_id"
                        x-effect="$el.__xSelect?.setValue(form.tax_type_id, { silent: true })"
                    />
                    <p class="text-sm font-semibold text-error-accent" x-show="errorFor('tax_type_id')" x-text="errorFor('tax_type_id')"></p>

                </div>
            </fieldset>
            </div>

            <div class="grid min-w-0 grid-cols-[minmax(0,1fr)] gap-6">
            <fieldset class="min-w-0">
                <legend>{{ __('inventory.item.editor.section.rules') }}</legend>
                <div class="grid grid-cols-[minmax(0,1fr)] gap-y-2">
    
                    <div class="grid gap-2">
                        <label class="flex items-center gap-2 text-sm text-card-txt">
                            <input type="checkbox" class="tick" data-no-sale x-model="form.no_sale"> <span>{{ $label('no_sale') }}</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm text-card-txt">
                            <input type="checkbox" class="tick" data-no-purchase x-model="form.no_purchase"> <span>{{ $label('no_purchase') }}</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm text-card-txt">
                            <input type="checkbox" class="tick" data-editable x-model="form.editable"> <span>{{ $label('editable') }}</span>
                        </label>
                    </div>
                </div>
            </fieldset>

            <fieldset class="min-w-0">
                <legend>{{ __('inventory.item.editor.section.accounts') }}</legend>
                <div class="grid grid-cols-[minmax(0,1fr)] gap-y-2">
                    @foreach (ItemAccount::cases() as $account)
                        <template x-if="hasAccount('{{ $account->value }}')">
                            <div class="contents">
                                <label for="{{ $name }}-{{ $account->value }}" class="text-sm font-semibold text-card-title-txt">{{ $account->label() }}</label>
                                <x-ui.select
                                    id="{{ $name }}-{{ $account->value }}"
                                    name="{{ $account->value }}"
                                    class="w-full"
                                    :panel-parent="$panelParent"
                                    :channel="$accountLookup"
                                    :placeholder="__('inventory.item.editor.placeholder.account')"
                                    x-model="form.{{ $account->value }}"
                                    x-effect="$el.__xSelect?.setValue(form.{{ $account->value }}, { silent: true })"
                                />
                                <p class="text-sm font-semibold text-error-accent" x-show="errorFor('{{ $account->value }}')" x-text="errorFor('{{ $account->value }}')"></p>
                            </div>
                        </template>
                    @endforeach
                </div>
            </fieldset>

            @if ($dimensionLevel > 0)
                <fieldset class="min-w-0">
                    <legend>{{ __('inventory.item.editor.section.dimensions') }}</legend>
                    <div class="grid grid-cols-[minmax(0,1fr)] gap-y-2">
                        <label for="{{ $name }}-dimension" class="text-sm font-semibold text-card-title-txt">{{ $label('dimension') }}</label>
                        <x-ui.select
                            id="{{ $name }}-dimension"
                            name="dimension_id"
                            class="w-full"
                        :panel-parent="$panelParent"
                            :channel="$dimensions"
                            :placeholder="__('inventory.item.editor.placeholder.dimension')"
                            x-model="form.dimension_id"
                            x-effect="$el.__xSelect?.setValue(form.dimension_id, { silent: true })"
                        />

                        @if ($dimensionLevel > 1)
                            <label for="{{ $name }}-dimension2" class="text-sm font-semibold text-card-title-txt">{{ $label('dimension2') }}</label>
                            <x-ui.select
                                id="{{ $name }}-dimension2"
                                name="dimension2_id"
                                class="w-full"
                        :panel-parent="$panelParent"
                                :channel="$dimensions2"
                                :placeholder="__('inventory.item.editor.placeholder.dimension')"
                                x-model="form.dimension2_id"
                                x-effect="$el.__xSelect?.setValue(form.dimension2_id, { silent: true })"
                            />
                        @endif
                    </div>
                </fieldset>
            @endif

            <fieldset class="min-w-0">
                <legend>{{ __('inventory.item.editor.section.image') }}</legend>
                <div class="grid grid-cols-[minmax(0,1fr)] gap-y-2">
                    <label for="{{ $name }}-image" class="text-sm font-semibold text-card-title-txt">{{ $label('image') }}</label>
                    <div class="grid grid-cols-[minmax(0,1fr)] gap-2">
                        <img data-image-preview alt="" x-show="preview" :src="preview"
                             class="h-24 w-24 rounded-xl border border-solid border-card-border object-cover">
                        <input type="file" id="{{ $name }}-image" data-image accept="image/jpeg,image/png,image/gif"
                               x-ref="file" @change="pickImage($event.target.files[0] ?? null)" class="w-full text-sm text-card-txt">
                        <p class="text-xs text-card-txt">{{ __('inventory.item.editor.image_hint', ['max' => $maxImageKb]) }}</p>
                        <p class="text-sm font-semibold text-error-accent" x-show="errorFor('image')" x-text="errorFor('image')"></p>

                        {{-- Its own request, apart from saving: the picture is gone at once and the form is left as it is. --}}
                        <div>
                            <x-ui.button type="button" variant="outline" icon="trash" data-remove-image
                                         x-show="hasImage && ! file" @click="removeImage()">
                                {{ __('inventory.item.editor.action.remove_image') }}
                            </x-ui.button>
                        </div>
                    </div>
                </div>
            </fieldset>

            </div>
        </div>

        <div data-error x-show="messages.length">
            <div class="rounded-lg border border-banner-error-border bg-banner-error-bg px-4 py-3 text-sm text-banner-error-txt">
                <div class="flex items-center gap-2 font-semibold">
                    <span class="icon icon-warning"></span>
                    <span class="grow">{{ __('inventory.item.editor.error_heading') }}</span>
                </div>
                <ul class="mt-1 list-disc ps-6">
                    <template x-for="message in messages" :key="message">
                        <li x-text="message"></li>
                    </template>
                </ul>
            </div>
        </div>

        {{-- Inside the form so Enter submits it; what Cancel means is the host's to say. --}}
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.button type="submit" data-save icon="button-ok">
                <span x-text="isNew ? @js(__('inventory.item.editor.action.create')) : @js(__('inventory.item.editor.action.save'))"></span>
            </x-ui.button>

            {{-- Its own request, apart from saving: it changes what is stored now and leaves the form as it is. --}}
            <template x-if="! isNew">
                <div class="flex items-center gap-2">
                    <x-ui.button type="button" variant="danger" data-activation x-show="! form.inactive"
                                 @click="toggleActivation()">{{ __('inventory.item.editor.action.deactivate') }}</x-ui.button>
                    <x-ui.button type="button" variant="success" data-activation x-show="form.inactive"
                                 @click="toggleActivation()">{{ __('inventory.item.editor.action.activate') }}</x-ui.button>
                    <x-ui.badge color="neutral" x-show="form.inactive">{{ __('inventory.item.badge.inactive') }}</x-ui.badge>
                </div>
            </template>

            <button type="button" data-cancel @click="cancel()"
                    class="ms-auto cursor-pointer border-0 bg-transparent text-sm text-card-txt transition hover:text-card-title-txt">
                {{ __('inventory.item.editor.action.cancel') }}
            </button>
        </div>
    </form>
</section>

@once
    @pageScript('resources/js/pages/inventory/item-editor.js')
@endonce
