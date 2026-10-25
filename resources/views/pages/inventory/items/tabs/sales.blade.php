{{--
    The item's sales prices: one per sales type and currency, added, changed and removed here.

    Sits inside the open item's pane and reads its `detail`; the header's price cards are refreshed after every change.
--}}
@php
    \App\Foundation\Framework\Facade\ClientData::registry()
        ->routes([
            'sale.prices.items.index',
            'sale.prices.items.suggestion',
            'sale.prices.store',
            'sale.prices.update',
            'sale.prices.destroy',
        ])
        ->translations([
            'sale.price.ui.remove.title',
            'sale.price.ui.remove.text',
            'sale.price.ui.remove.confirm',
        ]);

    // A panel drawn anywhere but inside the form would open behind the modal that hosts it.
    $panelParent = '[data-sale-price-editor]';
@endphp

<div x-data="itemSalePrices" x-cloak>
    <section class="rounded-xl border border-solid border-card-border p-4">
        <div class="mb-3 flex items-center justify-between gap-4">
            <h3 class="m-0 text-xs font-semibold uppercase tracking-wide text-card-txt">{{ __('sale.price.ui.heading') }}</h3>
            <x-ui.button type="button" variant="outline" data-add @click="open()" x-show="rows">
                {{ __('sale.price.ui.add') }}
            </x-ui.button>
        </div>

        <p class="m-0 text-sm text-card-txt" x-show="! rows && ! problem">{{ __('sale.price.ui.loading') }}</p>
        <p class="m-0 text-sm font-semibold text-error-accent" x-show="problem" x-text="problem"></p>

        <template x-if="rows">
            <div>
                <p class="m-0 text-sm text-card-txt" x-show="rows.length === 0">{{ __('sale.price.ui.empty') }}</p>

                <ul class="m-0 list-none p-0" x-show="rows.length > 0">
                    <template x-for="row in rows" :key="row.id">
                        <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 border-0 border-t border-solid border-card-divider py-3 text-sm text-card-title-txt">
                            <div class="flex min-w-0 items-baseline gap-3">
                                <span class="shrink-0 rounded-md bg-card-sunken-bg px-2 py-0.5 font-mono" x-text="row.currency"></span>
                                <span class="min-w-0 break-words font-semibold" x-text="row.sales_type"></span>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="tabular-nums font-semibold" x-text="shown(row.price)"></span>
                                <button type="button" data-change @click="open(row)"
                                        class="cursor-pointer border-0 bg-transparent p-0 text-sm text-card-txt transition hover:text-card-title-txt">
                                    {{ __('sale.price.ui.action.change') }}
                                </button>
                                <button type="button" data-remove @click="remove(row)"
                                        class="cursor-pointer border-0 bg-transparent p-0 text-sm text-error-accent transition hover:underline">
                                    {{ __('sale.price.ui.action.remove') }}
                                </button>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>
        </template>
    </section>

    <x-ui.modal name="sale-price-editor" size="sm" static>
        <x-slot:header>
            <span x-text="editingId === null ? @js(__('sale.price.ui.new')) : @js(__('sale.price.ui.edit'))"></span>
        </x-slot>

        <form data-sale-price-editor @submit.prevent="save()" novalidate class="grid grid-cols-[minmax(0,1fr)] gap-y-2">
            <div class="rounded-md border border-solid border-banner-error-border bg-banner-error-bg p-3 text-sm text-banner-error-txt"
                 data-error x-show="messages.length">
                <p class="m-0 font-semibold">{{ __('sale.price.ui.error_heading') }}</p>
                <ul class="m-0 mt-1 list-none p-0">
                    <template x-for="message in messages">
                        <li x-text="message"></li>
                    </template>
                </ul>
            </div>

            <label for="sale-price-sales-type" class="text-sm font-semibold text-card-title-txt">{{ __('sale.price.ui.sales_type') }}</label>
            <x-ui.select
                id="sale-price-sales-type"
                name="sales_type_id"
                class="w-full"
                :panel-parent="$panelParent"
                :channel="$salesTypes"
                :placeholder="__('sale.price.ui.placeholder')"
                x-model="form.sales_type_id"
                x-effect="$el.__xSelect?.setValue(form.sales_type_id, { silent: true })"
                @change="suggest()"
            />
            <p class="text-sm font-semibold text-error-accent" x-show="errorFor('sales_type_id')" x-text="errorFor('sales_type_id')"></p>

            <label for="sale-price-currency" class="text-sm font-semibold text-card-title-txt">{{ __('sale.price.ui.currency') }}</label>
            <x-ui.select
                id="sale-price-currency"
                name="currency"
                class="w-full"
                :panel-parent="$panelParent"
                :channel="$currencies"
                :placeholder="__('sale.price.ui.placeholder')"
                x-model="form.currency"
                x-effect="$el.__xSelect?.setValue(form.currency, { silent: true })"
                @change="suggest()"
            />
            <p class="text-sm font-semibold text-error-accent" x-show="errorFor('currency')" x-text="errorFor('currency')"></p>

            <label for="sale-price-price" class="text-sm font-semibold text-card-title-txt">{{ __('sale.price.ui.price') }}</label>
            <input type="text" id="sale-price-price" inputmode="decimal" autocomplete="off" required
                   x-model="form.price" @input="priceTyped = true"
                   class="field" :class="errorFor('price') ? 'border-error-accent' : 'border-field-border'">
            <p class="text-sm font-semibold text-error-accent" x-show="errorFor('price')" x-text="errorFor('price')"></p>
            <p class="text-xs text-card-txt" data-calculated x-show="source === 'markup'">{{ __('sale.price.ui.calculated') }}</p>

            <div class="mt-4 flex items-center gap-4">
                <x-ui.button type="submit" data-save>{{ __('sale.price.ui.save') }}</x-ui.button>
                <button type="button" data-cancel x-modal:close
                        class="cursor-pointer border-0 bg-transparent text-sm text-card-txt transition hover:text-card-title-txt">
                    {{ __('sale.price.ui.cancel') }}
                </button>
            </div>
        </form>
    </x-ui.modal>
</div>

@once
    @pageScript('resources/js/pages/inventory/item-sale-prices.js')
@endonce
