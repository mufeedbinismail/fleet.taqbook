<?php

return [
    'item' => [
        'title' => 'Items',
        'noun' => 'item',

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
            'new' => 'New item',
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

        'notice' => [
            'created' => 'The item has been added.',
            'updated' => 'The item has been updated.',
            'image_deleted' => 'The image has been removed.',
            'deleted' => 'The item has been deleted.',
        ],

        'error' => [
            'code_taken' => 'This code is already assigned to an item, a sales kit or a code alias.',
            'no_category' => 'The category does not exist.',
            'no_tax_type' => 'The item tax type does not exist.',
            'no_unit' => 'The unit of measure does not exist.',
            'account_required' => 'The :account is required for this item type.',
            'no_account' => 'The :account is not a GL account.',
            'account_inapplicable' => 'The :account does not apply to this item type and must be left empty.',
            'locked_type' => 'The item type cannot be changed while the item is in use.',
            'locked_unit' => 'The unit of measure cannot be changed while the item is in use.',
            'in_use' => 'The item cannot be deleted because it is in use.',
            'item_missing' => 'The item no longer exists.',
            'barcode_disabled' => 'Barcode generation is switched off for items.',
            'image_type' => 'The image must be a JPEG, PNG or GIF picture.',
            'image_size' => 'The image may not be larger than :max KB.',
        ],

        'attribute' => [
            'stock_id' => 'item code',
            'description' => 'name',
            'long_description' => 'description',
            'category' => 'category',
            'tax_type' => 'item tax type',
            'units' => 'unit of measure',
            'type' => 'item type',
            'sales_account' => 'sales account',
            'cogs_account' => 'cost of goods sold account',
            'inventory_account' => 'inventory account',
            'adjustment_account' => 'inventory adjustments account',
            'wip_account' => 'work in progress account',
            'image' => 'image',
        ],

        'delete' => [
            'title' => 'Delete this item?',
            'text' => 'The item is removed with its locations, prices and purchasing data. This cannot be undone.',
            'confirm' => 'Yes, delete it',
            'in_use' => 'Stock, an order, a bill of materials, a kit or a code alias refers to this item, so it cannot be deleted.',
        ],

        'editor' => [
            'title_new' => 'New item',
            'title_edit' => 'Edit item',
            'locked' => 'Fixed while the item is in use.',
            'locked_account' => 'This account does not apply to the selected item type.',
            'code_hint' => 'Letters, digits, hyphens and underscores; it cannot be changed afterwards.',

            'section' => [
                'identity' => 'Identity',
                'rules' => 'Item rules',
                'accounts' => 'GL accounts',
                'dimensions' => 'Dimensions',
                'image' => 'Image',
            ],

            'field' => [
                'stock_id' => 'Item code',
                'description' => 'Name',
                'long_description' => 'Description',
                'category' => 'Category',
                'type' => 'Item type',
                'units' => 'Unit of measure',
                'tax_type' => 'Item tax type',
                'no_sale' => 'Not for sale',
                'no_purchase' => 'Not purchasable',
                'editable' => 'Description can be edited on documents',
                'dimension' => 'Dimension 1',
                'dimension2' => 'Dimension 2',
                'image' => 'Picture',
            ],

            'placeholder' => [
                'choose' => 'Choose…',
                'category' => 'Choose a category',
                'account' => 'Choose an account',
                'dimension' => 'None',
            ],

            'action' => [
                'create' => 'Add item',
                'save' => 'Save changes',
                'cancel' => 'Cancel',
                'generate' => 'Generate',
                'remove_image' => 'Remove the picture',
                'activate' => 'Activate',
                'deactivate' => 'Deactivate',
            ],

            'remove_image' => [
                'title' => 'Remove the picture?',
                'text' => 'The picture of this item is deleted now, apart from saving. This cannot be undone.',
                'confirm' => 'Yes, remove it',
            ],

            'image_hint' => 'JPEG, PNG or GIF, up to :max KB.',
            'error_heading' => 'The item could not be saved',
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
