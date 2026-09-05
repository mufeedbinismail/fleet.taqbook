<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Page size
    |--------------------------------------------------------------------------
    |
    | The default number of rows a table page holds, and the largest a client is
    | allowed to ask for. A table may raise or lower either for itself.
    |
    */

    'per_page' => 25,

    'max_per_page' => 100,

    /*
    |--------------------------------------------------------------------------
    | Export
    |--------------------------------------------------------------------------
    |
    | 'chunk' is how many rows are read from the database at a time while an
    | export is written.
    |
    | 'xlsx_rows' caps the xlsx export. The format cannot be written row by row,
    | so the whole workbook is assembled in memory before any of it is sent;
    | past this many rows a request is refused and csv is offered instead.
    |
    */

    'export' => [

        'chunk' => 1000,

        'xlsx_rows' => 20000,

    ],

];
