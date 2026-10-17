@use('App\Inventory\Enum\ItemAccount')
<div class="md:columns-2 md:gap-3">
    <section class="break-inside-avoid rounded-xl border border-solid border-card-border p-4 mb-3">
        <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-card-txt">{{ __('inventory.item.general.item') }}</h3>
        <dl class="m-0 grid grid-cols-2 gap-x-4">
            <div class="col-span-2 min-w-0 border-0 border-t border-solid border-card-divider py-3">
                <dt class="text-sm text-card-txt">{{ __('inventory.item.general.name') }}</dt>
                <dd class="m-0 mt-0.5 break-words text-base font-semibold text-card-title-txt" x-text="detail.description"></dd>
            </div>
            <div class="col-span-2 min-w-0 border-0 border-t border-solid border-card-divider py-3">
                <dt class="text-sm text-card-txt">{{ __('inventory.item.general.description') }}</dt>
                <dd class="m-0 mt-0.5 break-words text-sm font-medium text-card-title-txt" x-text="detail.long_description || @js(__('inventory.item.general.none'))"></dd>
            </div>
            <div class="min-w-0 border-0 border-t border-solid border-card-divider py-3">
                <dt class="text-sm text-card-txt">{{ __('inventory.item.general.category') }}</dt>
                <dd class="m-0 mt-0.5 break-words text-sm font-medium text-card-title-txt" x-text="detail.category_name"></dd>
            </div>
            <div class="min-w-0 border-0 border-t border-solid border-card-divider py-3">
                <dt class="text-sm text-card-txt">{{ __('inventory.item.general.type') }}</dt>
                <dd class="m-0 mt-0.5 break-words text-sm font-medium text-card-title-txt" x-text="detail.type_label"></dd>
            </div>
            <div class="min-w-0 border-0 border-t border-solid border-card-divider py-3">
                <dt class="text-sm text-card-txt">{{ __('inventory.item.general.unit') }}</dt>
                <dd class="m-0 mt-0.5 break-words text-sm font-medium text-card-title-txt" x-text="detail.unit_name"></dd>
            </div>
            <div class="min-w-0 border-0 border-t border-solid border-card-divider py-3">
                <dt class="text-sm text-card-txt">{{ __('inventory.item.general.tax_type') }}</dt>
                <dd class="m-0 mt-0.5 break-words text-sm font-medium text-card-title-txt" x-text="detail.tax_type_name"></dd>
            </div>
        </dl>
    </section>

    <section class="break-inside-avoid rounded-xl border border-solid border-card-border p-4 mb-3">
        <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-card-txt">{{ __('inventory.item.general.rules') }}</h3>
        <dl class="m-0">
            <div class="flex items-center justify-between gap-4 border-0 border-t border-solid border-card-divider py-3">
                <div class="min-w-0">
                    <dt class="text-sm font-semibold text-card-title-txt">{{ __('inventory.item.general.editable') }}</dt>
                    <dd class="m-0 mt-0.5 text-sm text-card-txt">{{ __('inventory.item.general.editable_hint') }}</dd>
                </div>
                <span class="shrink-0 rounded-lg bg-card-sunken-bg px-3 py-1 text-sm font-semibold"
                      :class="detail.editable ? 'text-card-title-txt' : 'text-card-txt'"
                      x-text="detail.editable ? @js(__('inventory.item.general.on')) : @js(__('inventory.item.general.off'))"></span>
            </div>
            <div class="flex items-center justify-between gap-4 border-0 border-t border-solid border-card-divider py-3">
                <div class="min-w-0">
                    <dt class="text-sm font-semibold text-card-title-txt">{{ __('inventory.item.general.no_sale') }}</dt>
                    <dd class="m-0 mt-0.5 text-sm text-card-txt">{{ __('inventory.item.general.no_sale_hint') }}</dd>
                </div>
                <span class="shrink-0 rounded-lg bg-card-sunken-bg px-3 py-1 text-sm font-semibold"
                      :class="detail.no_sale ? 'text-card-title-txt' : 'text-card-txt'"
                      x-text="detail.no_sale ? @js(__('inventory.item.general.on')) : @js(__('inventory.item.general.off'))"></span>
            </div>
            <div class="flex items-center justify-between gap-4 border-0 border-t border-solid border-card-divider py-3">
                <div class="min-w-0">
                    <dt class="text-sm font-semibold text-card-title-txt">{{ __('inventory.item.general.no_purchase') }}</dt>
                    <dd class="m-0 mt-0.5 text-sm text-card-txt">{{ __('inventory.item.general.no_purchase_hint') }}</dd>
                </div>
                <span class="shrink-0 rounded-lg bg-card-sunken-bg px-3 py-1 text-sm font-semibold"
                      :class="detail.no_purchase ? 'text-card-title-txt' : 'text-card-txt'"
                      x-text="detail.no_purchase ? @js(__('inventory.item.general.on')) : @js(__('inventory.item.general.off'))"></span>
            </div>
        </dl>
    </section>

    <section class="break-inside-avoid rounded-xl border border-solid border-card-border p-4 mb-3">
        <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-card-txt">{{ __('inventory.item.general.accounts') }}</h3>
        <dl class="m-0">
            <div class="min-w-0 border-0 border-t border-solid border-card-divider py-3" x-show="detail.sales_account">
                <dt class="text-sm text-card-txt">{{ ItemAccount::Sales->label() }}</dt>
                <dd class="m-0 mt-1 flex min-w-0 items-center gap-2">
                    <span class="shrink-0 rounded-md bg-card-sunken-bg px-2 py-0.5 font-mono text-sm text-card-title-txt" x-text="detail.sales_account"></span>
                    <span class="min-w-0 break-words text-sm font-medium text-card-title-txt" x-text="detail.sales_account_name"></span>
                </dd>
            </div>
            <div class="min-w-0 border-0 border-t border-solid border-card-divider py-3" x-show="detail.is_stocked && detail.inventory_account">
                <dt class="text-sm text-card-txt">{{ ItemAccount::Inventory->label() }}</dt>
                <dd class="m-0 mt-1 flex min-w-0 items-center gap-2">
                    <span class="shrink-0 rounded-md bg-card-sunken-bg px-2 py-0.5 font-mono text-sm text-card-title-txt" x-text="detail.inventory_account"></span>
                    <span class="min-w-0 break-words text-sm font-medium text-card-title-txt" x-text="detail.inventory_account_name"></span>
                </dd>
            </div>
            <div class="min-w-0 border-0 border-t border-solid border-card-divider py-3" x-show="detail.cogs_account">
                <dt class="text-sm text-card-txt">{{ ItemAccount::Cogs->label() }}</dt>
                <dd class="m-0 mt-1 flex min-w-0 items-center gap-2">
                    <span class="shrink-0 rounded-md bg-card-sunken-bg px-2 py-0.5 font-mono text-sm text-card-title-txt" x-text="detail.cogs_account"></span>
                    <span class="min-w-0 break-words text-sm font-medium text-card-title-txt" x-text="detail.cogs_account_name"></span>
                </dd>
            </div>
            <div class="min-w-0 border-0 border-t border-solid border-card-divider py-3" x-show="detail.is_stocked && detail.adjustment_account">
                <dt class="text-sm text-card-txt">{{ ItemAccount::Adjustment->label() }}</dt>
                <dd class="m-0 mt-1 flex min-w-0 items-center gap-2">
                    <span class="shrink-0 rounded-md bg-card-sunken-bg px-2 py-0.5 font-mono text-sm text-card-title-txt" x-text="detail.adjustment_account"></span>
                    <span class="min-w-0 break-words text-sm font-medium text-card-title-txt" x-text="detail.adjustment_account_name"></span>
                </dd>
            </div>
            <div class="min-w-0 border-0 border-t border-solid border-card-divider py-3" x-show="detail.is_manufactured && detail.wip_account">
                <dt class="text-sm text-card-txt">{{ ItemAccount::Wip->label() }}</dt>
                <dd class="m-0 mt-1 flex min-w-0 items-center gap-2">
                    <span class="shrink-0 rounded-md bg-card-sunken-bg px-2 py-0.5 font-mono text-sm text-card-title-txt" x-text="detail.wip_account"></span>
                    <span class="min-w-0 break-words text-sm font-medium text-card-title-txt" x-text="detail.wip_account_name"></span>
                </dd>
            </div>
            <div class="min-w-0 border-0 border-t border-solid border-card-divider py-3" x-show="dimensionLevel >= 1">
                <dt class="text-sm text-card-txt">{{ __('inventory.item.general.dimension') }}</dt>
                <dd class="m-0 mt-0.5 text-sm font-medium text-card-title-txt" x-text="detail.dimension?.name_formatted ?? @js(__('inventory.item.general.none'))"></dd>
            </div>
            <div class="min-w-0 border-0 border-t border-solid border-card-divider py-3" x-show="dimensionLevel >= 2">
                <dt class="text-sm text-card-txt">{{ __('inventory.item.general.dimension2') }}</dt>
                <dd class="m-0 mt-0.5 text-sm font-medium text-card-title-txt" x-text="detail.dimension2?.name_formatted ?? @js(__('inventory.item.general.none'))"></dd>
            </div>
        </dl>
    </section>

    <section class="break-inside-avoid rounded-xl border border-solid border-card-border p-4 mb-3" x-show="canViewStatus">
        <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-card-txt">{{ __('inventory.item.general.stock') }}</h3>
        <p class="m-0 text-sm text-card-txt" x-show="! stock.rows && ! stock.problem">{{ __('inventory.item.detail.loading') }}</p>
        <p class="m-0 text-sm text-card-txt" x-show="stock.problem" x-text="stock.problem"></p>
        <template x-if="stock.rows">
            <div>
                <p class="m-0 text-sm text-card-txt" x-show="! detail.is_stocked">{{ __('inventory.item.general.service_demand') }}</p>
                <p class="m-0 text-sm text-card-txt" x-show="stock.rows.length === 0">{{ __('inventory.item.general.no_locations') }}</p>
                <div class="flex items-center justify-between gap-4 pb-2 text-xs font-semibold uppercase tracking-wide text-card-txt" x-show="detail.is_stocked && stock.rows.length > 0">
                    <span>{{ __('inventory.item.general.location') }}</span>
                    <span>{{ __('inventory.item.general.on_hand') }}</span>
                </div>
                <ul class="m-0 list-none p-0">
                    <template x-for="place in stock.rows" :key="place.loc_code">
                        <li class="border-0 border-t border-solid border-card-divider py-3 text-sm text-card-title-txt">
                            <div class="flex items-center justify-between gap-4">
                                <span class="min-w-0 break-words font-semibold" x-text="place.location_name"></span>
                                <span class="tabular-nums" x-show="detail.is_stocked" x-text="App.figures.formatQuantity(place.on_hand, place.unit_decimals)"></span>
                                <span class="flex items-baseline gap-2 tabular-nums" x-show="! detail.is_stocked">
                                    <span class="text-card-txt">{{ __('inventory.item.general.demand') }}</span>
                                    <span x-text="App.figures.formatQuantity(place.demand, place.unit_decimals)"></span>
                                </span>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>
        </template>
    </section>
</div>
