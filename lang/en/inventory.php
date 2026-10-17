<?php

return [
    'item' => [
        'title' => 'Items',

        'rail' => [
            'label' => 'Item list',
            'title' => 'All items',
            'search' => 'Search items',
            'filters' => 'Filters',
            'lookup' => 'Item',
            'lookup_placeholder' => 'Find an item',
            'show_inactive' => 'Show inactive',
        ],

        'column' => [
            'item' => 'Item',
            'on_hand' => 'On hand',
        ],

        'filter' => [
            'inactive' => 'Show inactive',
            'category' => 'Category',
            'type' => 'Type',
            'any_category' => 'All categories',
            'any_type' => 'All types',
        ],

        'badge' => [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ],

        'detail' => [
            'label' => 'Item details',
            'none_title' => 'No item selected',
            'none' => 'Pick an item from the list to see its details here.',
            'missing_title' => 'Item not found',
            'problem_title' => 'The item could not be loaded',
            'missing' => 'No item has the code :recordId.',
            'loading' => 'Loading the item.',
            'image' => 'No image',
            'edit' => 'Edit',
            'clone' => 'Clone',
            'delete' => 'Delete',
        ],

        'stat' => [
            'on_hand' => 'On hand',
            'average_cost' => 'Average cost',
            'normal_price' => 'Normal price',
            'markup' => 'Markup',
            'on_hand_hint' => 'Sum of all quantities up to today across all locations',
            'average_cost_hint' => 'Moving average of what a unit cost, recalculated as stock is received',
            'normal_price_hint' => 'Selling price for the base sales type, in the home currency',
            'no_price' => 'No price',
            'markup_hint' => 'How far the normal price sits above the average cost, as a percentage of the cost',
        ],

        'account' => [
            'sales_account' => 'Sales account',
            'cogs_account' => 'Cost of goods sold account',
            'inventory_account' => 'Inventory account',
            'adjustment_account' => 'Inventory adjustments account',
            'wip_account' => 'Work in progress account',
        ],

        'general' => [
            'item' => 'Identity',
            'accounts' => 'GL accounts',
            'rules' => 'Item rules',
            'stock' => 'Stock status',
            'name' => 'Name',
            'description' => 'Description',
            'category' => 'Category',
            'tax_type' => 'Item tax type',
            'type' => 'Item type',
            'unit' => 'Unit of measure',
            'dimension' => 'Dimension',
            'dimension2' => 'Dimension 2',
            'editable' => 'Editable description',
            'no_sale' => 'Excluded from sales',
            'no_purchase' => 'Excluded from purchases',
            'on' => 'On',
            'off' => 'Off',
            'editable_hint' => 'Staff can reword the description on documents',
            'no_sale_hint' => 'Hidden on quotes, orders and invoices',
            'no_purchase_hint' => 'Hidden on purchase orders and bills',
            'none' => 'None',
            'location' => 'Location',
            'on_hand' => 'On hand',
            'service_demand' => 'A service has no stock holding; only what is on outstanding sales orders is shown.',
            'demand' => 'Demand',
            'no_locations' => 'This item has no stock locations.',
        ],

        'source_type' => [
            'manufactured' => 'Manufactured',
            'bought' => 'Bought',
            'service' => 'Service',
            'fixed_asset' => 'Fixed asset',
        ],

        'tabs' => 'Item sections',

        'tab' => [
            'general' => 'General',
            'sales' => 'Sales',
            'purchasing' => 'Purchasing',
            'costs' => 'Costs',
            'reorder' => 'Reorder',
            'transactions' => 'Transactions',
            'status' => 'Status',
            'attachments' => 'Attachments',
        ],
    ],
];
