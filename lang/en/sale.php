<?php

return [

    'price' => [
        'attribute' => [
            'stock_id' => 'item',
            'sales_type' => 'sales type',
            'currency' => 'currency',
            'price' => 'price',
        ],

        'notice' => [
            'created' => 'The new price has been added.',
            'updated' => 'This price has been updated.',
            'deleted' => 'This price has been removed.',
        ],

        'ui' => [
            'heading' => 'Sales prices',
            'add' => 'Add price',
            'edit' => 'Change price',
            'new' => 'New price',
            'empty' => 'There are no prices set up for this item.',
            'loading' => 'Loading prices…',
            'currency' => 'Currency',
            'sales_type' => 'Sales type',
            'price' => 'Price',
            'placeholder' => 'Choose',
            'calculated' => 'The price is calculated.',
            'error_heading' => 'The price could not be saved',
            'save' => 'Save',
            'cancel' => 'Cancel',

            'action' => [
                'change' => 'Change',
                'remove' => 'Remove',
            ],

            'remove' => [
                'title' => 'Remove this price?',
                'text' => 'The item will no longer sell at this price for the sales type and currency.',
                'confirm' => 'Yes, remove it',
            ],
        ],

        'error' => [
            'duplicate' => 'The sales pricing for this item, sales type and currency has already been added.',
            'no_sales_type' => 'The sales type does not exist.',
            'no_currency' => 'The currency does not exist.',
            'price_missing' => 'The price no longer exists.',
            'too_fine' => 'The price has more decimal places than the currency allows.',
        ],
    ],

    'customer' => [
        'picker' => [
            'inactive' => 'No longer trading',
        ],
    ],

];
