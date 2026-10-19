<?php

use App\Foundation\Auth\Constant\Permission;
use App\Foundation\Framework\Facade\ClientData;

ClientData::registry()
    ->settings(['dimensionLevel'])
    ->permissions(['VIEW_INVENTORY_STATUS'])
    ->routes(['inventory.items.show', 'inventory.items.status', 'inventory.items.image'])
    ->translations([
        'inventory.item.detail.missing',
    ]);
?>

@extends('layout.app')
@section('title', $title)
@section('content')
<div x-data="itemsPage()" @record-browser:changed="loadStockStatus($event.detail)">
<x-ui.record-browser
    param="item"
    route="inventory.items.show"
    :selected="$selection->item?->value"
    :title="__('inventory.item.rail.title')"
    :rail-label="__('inventory.item.rail.label')"
    :detail-label="__('inventory.item.detail.label')"
    :filters-label="__('inventory.item.rail.filters')"
    :lookup-label="__('inventory.item.rail.lookup')"
    lookup-id="item-lookup"
    state-icon="image"
    :messages="[
        'none_title' => __('inventory.item.detail.none_title'),
        'none' => __('inventory.item.detail.none'),
        'missing_title' => __('inventory.item.detail.missing_title'),
        'missing' => 'inventory.item.detail.missing',
        'problem_title' => __('inventory.item.detail.problem_title'),
        'loading' => __('inventory.item.detail.loading'),
    ]"
>
    <x-slot:lookup>
        <x-ui.select
            id="item-lookup"
            name="item"
            data-lookup
            :channel="$lookup"
            :selected="$selection->item?->value"
            :placeholder="__('inventory.item.rail.lookup_placeholder')"
            :param-sources="[
                'include_inactive' => '#item-show-inactive',
                'category_id' => '#item-category',
                'item_source_type' => '#item-type',
            ]"
            x-record-browser:lookup
        />
    </x-slot>

    <x-slot:list>
        <x-ui.table
            :definition="$definition"
            :caption="__('inventory.item.rail.label')"
            route="inventory.items.list"
            :initial="$initial"
            row-key="stock_id"
            height="28rem"
            :resettable="false"
            compact
            fill
            x-record-browser:list
        >
            <x-slot:toolbar>
                <div class="grid w-full min-w-0 grid-cols-[minmax(0,1fr)] gap-2">
                    <x-ui.select
                        id="item-category"
                        name="category_id"
                        :channel="$categories"
                        :placeholder="__('inventory.item.filter.any_category')"
                        :aria-label="__('inventory.item.filter.category')"
                        x-effect="filterValue('category_id'), $el.__xSelect?.setValue(filterValue('category_id'), { silent: true })"
                        @change="setFilter('category_id', $event.target.value)"
                    />

                    <x-ui.select
                        id="item-type"
                        name="item_type"
                        :options="$types"
                        :placeholder="__('inventory.item.filter.any_type')"
                        :aria-label="__('inventory.item.filter.type')"
                        x-effect="filterValue('item_type'), $el.__xSelect?.setValue(filterValue('item_type'), { silent: true })"
                        @change="setFilter('item_type', $event.target.value)"
                    />

                    <div class="flex items-center justify-between gap-2 text-sm text-card-txt">
                        <span id="item-show-inactive-label">{{ __('inventory.item.rail.show_inactive') }}</span>
                        <x-ui.toggle
                            id="item-show-inactive"
                            name="show_inactive"
                            aria-labelledby="item-show-inactive-label"
                            :on="['label' => __('component.table.filter.yes')]"
                            :off="['label' => __('component.table.filter.no')]"
                            x-effect="filterValue('show_inactive'), $el.value = isInactiveIncluded(filterValue('show_inactive')) ? '1' : '0', $el.dispatchEvent(new Event('change'))"
                            @change="isInactiveIncluded(filterValue('show_inactive')) !== ($event.target.value === '1') && setFilter('show_inactive', $event.target.value === '1')"
                        />
                    </div>
                </div>
            </x-slot>

            <x-slot:cell_description>
                <button type="button" data-item-row :data-code="row.stock_id" x-record-browser:pick="row.stock_id">
                    <span class="block text-sm font-semibold" x-text="row.description"></span>
                    <span class="block text-xs text-card-txt">
                        <span x-text="row.stock_id + ' · ' + row.category_name"></span>
                        <x-ui.badge color="neutral" class="ms-1" x-show="row.inactive">{{ __('inventory.item.badge.inactive') }}</x-ui.badge>
                    </span>
                </button>
            </x-slot>
        </x-ui.table>
    </x-slot>

    <x-slot:detail>
        <header class="flex flex-wrap items-start gap-4 px-4 py-4 lg:px-6 lg:py-5">
            <div role="img" aria-label="{{ __('inventory.item.detail.image') }}"
                    class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-solid border-card-border bg-card-sunken-bg text-3xl text-card-txt">
                <img alt="" class="h-full w-full object-cover" x-show="detail.has_image"
                    :src="detail.has_image ? imageUrl(detail.stock_id) : ''">
                <span class="icon icon-image" aria-hidden="true" x-show="! detail.has_image"></span>
            </div>

            <div class="min-w-0 flex-1 basis-56">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="break-words text-xl font-semibold text-card-title-txt" data-selected-item x-text="detail.description"></h2>
                    <x-ui.badge color="success" x-show="! detail.inactive">{{ __('inventory.item.badge.active') }}</x-ui.badge>
                    <x-ui.badge color="neutral" x-show="detail.inactive">{{ __('inventory.item.badge.inactive') }}</x-ui.badge>
                </div>
                <p class="mt-1 text-sm text-card-txt"
                    x-text="[detail.stock_id, detail.type_label, detail.category_name, detail.unit_name].join(' · ')"></p>
                <p class="mt-2 text-sm text-card-txt" x-show="detail.long_description" x-text="detail.long_description"></p>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-ui.button variant="outline" icon="pencil" disabled>{{ __('inventory.item.detail.edit') }}</x-ui.button>
                <x-ui.button variant="outline" icon="copy" disabled>{{ __('inventory.item.detail.clone') }}</x-ui.button>
                <x-ui.button variant="danger" icon="trash" disabled>{{ __('inventory.item.detail.delete') }}</x-ui.button>
            </div>
        </header>

        <div class="grid gap-3 px-4 pb-4 sm:grid-cols-2 lg:px-6 lg:pb-5 xl:grid-cols-4">
            <x-ui.stat :label="__('inventory.item.stat.on_hand')" x-show="detail.is_stocked && canViewStatus">
                <span x-text="stock.onHand !== null ? App.figures.formatQuantity(stock.onHand, detail.unit_decimals) : '…'"></span>
                <x-slot:caption>{{ __('inventory.item.stat.on_hand_hint') }}</x-slot:caption>
            </x-ui.stat>

            <x-ui.stat :label="__('inventory.item.stat.average_cost')" x-show="detail.average_cost !== null">
                <span x-text="App.figures.formatPrice(detail.average_cost)"></span>
                <x-slot:caption>{{ __('inventory.item.stat.average_cost_hint') }}</x-slot:caption>
            </x-ui.stat>

            <x-ui.stat :label="__('inventory.item.stat.normal_price')">
                <span x-text="detail.normal_price === null ? @js(__('inventory.item.stat.no_price')) : App.figures.formatPrice(detail.normal_price)"></span>
                <x-slot:caption>{{ __('inventory.item.stat.normal_price_hint') }}</x-slot:caption>
            </x-ui.stat>

            <x-ui.stat :label="__('inventory.item.stat.markup')">
                <span x-text="detail.markup === null ? '—' : App.figures.formatPercent(detail.markup) + '%'"></span>
                <x-slot:caption>{{ __('inventory.item.stat.markup_hint') }}</x-slot:caption>
            </x-ui.stat>
        </div>

        <x-ui.tabs id="item-tabs" param="tab" fill :label="__('inventory.item.tabs')" :selected="$selection->tab->value">
            <x-ui.tab name="general" label="{{ __('inventory.item.tab.general') }}">
                @include('pages.inventory.items.tabs.general')
            </x-ui.tab>
            @can(Permission::MANAGE_SALE_PRICE)
                <x-ui.tab name="sales" label="{{ __('inventory.item.tab.sales') }}">
                    @include('pages.inventory.items.tabs.placeholder', ['label' => __('inventory.item.tab.sales')])
                </x-ui.tab>
            @endcan
        </x-ui.tabs>
    </x-slot>
</x-ui.record-browser>
</div>
@endsection

@pageScript('resources/js/pages/inventory/items.js')
