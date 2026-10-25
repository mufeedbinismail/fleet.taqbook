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
