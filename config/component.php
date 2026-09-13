<?php

return [

    'table' => [

        /*
        |----------------------------------------------------------------------
        | Page size
        |----------------------------------------------------------------------
        |
        | The default number of rows a table page holds. A table may raise or
        | lower it for itself.
        |
        */

        'per_page' => 25,

        /*
        |----------------------------------------------------------------------
        | Largest page
        |----------------------------------------------------------------------
        |
        | The most rows a client is allowed to ask for on one page, whatever
        | the address says.
        |
        */

        'max_per_page' => 100,

        'export' => [

            /*
            |------------------------------------------------------------------
            | Read chunk
            |------------------------------------------------------------------
            |
            | How many rows are read from the database at a time while an
            | export is written.
            |
            */

            'chunk' => 1000,

            /*
            |------------------------------------------------------------------
            | Xlsx cap
            |------------------------------------------------------------------
            |
            | The format cannot be written row by row, so the whole workbook
            | is assembled in memory before any of it is sent; past this many
            | rows a request is refused and csv is offered instead.
            |
            */

            'xlsx_rows' => 20000,

        ],

    ],

    'select' => [

        /*
        |----------------------------------------------------------------------
        | Page size
        |----------------------------------------------------------------------
        |
        | The number of options one fetched page holds when the client names
        | no size of its own.
        |
        */

        'per_page' => 25,

        /*
        |----------------------------------------------------------------------
        | Largest page
        |----------------------------------------------------------------------
        |
        | The most options a client may ask for on one page, and the most
        | held values it may ask a verdict on at once.
        |
        */

        'max_per_page' => 100,

        /*
        |----------------------------------------------------------------------
        | Inline limit
        |----------------------------------------------------------------------
        |
        | The most rows a select's set may hold and still be listed in full.
        | Past this many the set is fetched from its route as it is searched.
        |
        */

        'inline_up_to' => 200,

    ],

];
