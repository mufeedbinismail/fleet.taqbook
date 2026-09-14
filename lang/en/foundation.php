<?php

return [
    'http' => [
        'expired' => 'This page has been open too long and its security token has lapsed. Reload the page and try again.',
        'offline' => 'The server could not be reached. Check your connection and try again.',
        'failed' => 'The request could not be completed.',
    ],
    'role' => [
        'title' => 'Access Setup',
        'new' => 'New role',
        'details' => 'Role details',
        'own' => 'You hold this role',

        'picker' => [
            'label' => 'Editing role',
            'inactive' => 'inactive',
            'show_inactive' => 'Show inactive',
        ],

        'field' => [
            'name' => 'Role name',
            'status' => 'Current status',
            'active' => 'Active',
            'inactive' => 'Inactive',
        ],

        'permission' => [
            'heading' => 'Permissions',
            'filter' => 'Filter permissions',
            'filter_placeholder' => 'Filter permissions…',
            'expand_all' => 'Expand all',
            'collapse_all' => 'Collapse all',
            'clear' => 'Clear',
            'toggle_group' => 'Toggle all in :group',
            'self_lock' => 'Required for your own role',
            'empty' => 'No permission matches your filter.',
        ],

        'action' => [
            'save' => 'Save role',
            'create' => 'Create role',
            'clone' => 'Clone this role',
            'delete' => 'Delete',
            'cancel' => 'Cancel',
            'dismiss' => 'Dismiss',
        ],

        'notice' => [
            'created' => 'New security role has been added.',
            'updated' => 'Security role has been updated.',
            'deleted' => 'Security role has been successfully deleted.',
        ],

        'error' => [
            'heading' => 'This role could not be saved',
            'duplicate_name' => 'A role with this name already exists.',
            'lockout' => 'You hold this role, so you cannot remove its access to this screen.',
            'assigned' => 'This role is currently assigned to some users and cannot be deleted.',
            'reserved' => 'This role is reserved by the system and cannot be changed or deleted.',
            'reserved_permission' => 'One of these permissions is reserved by the system and cannot be granted.',
        ],

        // Substituted into validation messages, so they read mid-sentence and lowercase.
        'attribute' => [
            'name' => 'role name',
            'permissions' => 'permissions',
        ],

        'delete' => [
            'title' => 'Delete this role?',
            'text' => 'Every permission granted to :role will be removed. This cannot be undone.',
            'confirm' => 'Yes, delete',
        ],
    ],

    'user' => [
        'title' => 'User Accounts Setup',
        'new' => 'New user',
        'edit' => 'Edit user',

        'column' => [
            'login' => 'User login',
            'real_name' => 'Full name',
            'phone' => 'Phone',
            'email' => 'E-mail',
            'last_visit' => 'Last visit',
            'role' => 'Access level',
            'actions' => '',
        ],

        'section' => [
            'sign_in' => 'Sign in',
            'person' => 'Person',
            'access' => 'Access and workspace',
        ],

        'field' => [
            'login' => 'User login',
            'password' => 'Password',
            'password_hint' => 'Enter a new password to change it, leave empty to keep the current one.',
            'real_name' => 'Full name',
            'phone' => 'Telephone no.',
            'email' => 'Email address',
            'role' => 'Access level',
            'pos' => "User's POS",
        ],

        'filter' => [
            'show_inactive' => 'Show inactive',
            'inactive' => 'Inactive',
        ],

        'badge' => [
            'inactive' => 'inactive',
        ],

        'action' => [
            'new' => 'New user',
            'edit' => 'Edit',
            'save' => 'Save user',
            'create' => 'Create user',
            'cancel' => 'Cancel',
            'delete' => 'Delete',
            'deactivate' => 'Deactivate',
            'activate' => 'Reactivate',
        ],

        'notice' => [
            'created' => 'A new user has been added.',
            'updated' => 'The selected user has been updated.',
            'deleted' => 'The user has been deleted.',
            'activated' => 'The user has been reactivated.',
            'deactivated' => 'The user has been deactivated.',
        ],

        'error' => [
            'heading' => 'This user could not be saved',
            'duplicate_login' => 'Another account already uses this login.',
            'self_removal' => 'You cannot delete your own account.',
            'self_deactivation' => 'You cannot deactivate your own account.',
            'inactive_edit' => 'An inactive account cannot be edited. Reactivate it first.',
            'has_history' => 'This user has posted transactions, so the account can only be deactivated.',
            'no_history' => 'This user has never posted a transaction, so the account is deleted rather than deactivated.',
            'reserved' => 'This account is reserved by the system and cannot be changed, deactivated or deleted.',
            'reserved_role' => 'This access level is reserved by the system and cannot be assigned.',
        ],

        // Names the validator substitutes into its own messages, so they read mid-sentence and
        // lowercase rather than as headings.
        'attribute' => [
            'login' => 'user login',
            'password' => 'password',
            'real_name' => 'full name',
            'role' => 'access level',
            'pos' => 'point of sale',
        ],

        'password' => [
            'error' => [
                'contains_login' => 'The password cannot contain the user login.',
            ],
        ],

        'delete' => [
            'title' => 'Delete this user?',
            'text' => 'The account for :user will be removed. This cannot be undone.',
            'confirm' => 'Yes, delete',
        ],

        'footer' => [
            'total' => ':count logins',
        ],
    ],

    'toggle' => [
        'on' => 'On',
        'off' => 'Off',

        'error' => [
            'not_yes_or_no' => 'This is neither yes nor no.',
        ],
    ],

    'skin' => [
        'label' => 'Skin',
        'system' => 'Auto',
        'light' => 'Light',
        'dark' => 'Dark',
    ],

    'date' => [
        'today' => 'Today',
        'clear' => 'Clear',

        'error' => [
            'not_a_date' => 'This is not a date that can be read.',
            'before_earliest' => 'This is earlier than the days offered here.',
            'after_latest' => 'This is later than the days offered here.',
        ],

        'range' => [
            'from' => 'From',
            'to' => 'To',

            'error' => [
                'not_a_range' => 'This asks for a period, and one was not given.',
                'needs_both_ends' => 'This period needs both a start and an end.',
                'too_long' => 'This period is longer than the :limit days offered here.',
            ],
        ],
    ],

    'modal' => [
        'close' => 'Close',
    ],

    'select' => [
        'empty' => 'No results',
        'searching' => 'Searching…',
        'failed' => 'The list could not be loaded.',
        'retry' => 'Retry',
        'more' => 'Refine your search to see more',
        'tooShort' => 'Keep typing to search',
        'remove' => 'Remove :label',
        'clear' => 'Clear',

        'error' => [
            'not_a_choice' => 'This is not one of the choices offered.',
            'not_one_value' => 'This holds one value, and several were given.',
        ],
    ],

    'text' => [
        'error' => [
            'not_one_value' => 'This holds one value, and several were given.',
        ],
    ],

    'table' => [
        /*
            Several keys below say what looks like the same thing twice, deliberately: the same
            count is a label among controls in the pager, a whole sentence where the rows would have
            been, and a whole sentence again read aloud after a draw. Where two differ only by a
            full stop, the full stop is the difference.
        */
        'search' => 'Search',
        'search_placeholder' => 'Search…',
        'column_search' => 'Filter :column',

        'filter' => [
            // Each stands after the name of the column it narrows, so the two boxes of a range are
            // told apart by more than tab order.
            'range' => [
                'from' => 'From',
                'to' => 'To',
            ],

            'any' => 'Any',
            'yes' => 'Yes',
            'no' => 'No',
            'remove' => 'Remove filter :filter',
        ],
        'caption' => [
            // Said once where a reader arrives rather than in every column that offers it.
            'sortable' => 'Columns whose heading is a button can be sorted by that column.',
        ],
        // A pair, drawn where the rows would have been: one for a set narrowed down to nothing, one
        // for a set that was always empty.
        'empty' => 'Nothing matches what you are looking for.',
        'blank' => 'There is nothing here yet.',

        'clear' => 'Clear filters',

        'export' => [
            'csv' => 'CSV',
            'xlsx' => 'Excel',
        ],

        // Fragments rather than sentences, each being a label sitting among controls.
        'page' => [
            'summary' => ':from-:to of :total',
            'empty' => 'No results',
            'size' => 'Rows per page',
            'first' => 'First page',
            'previous' => 'Previous page',
            'next' => 'Next page',
            'last' => 'Last page',
        ],

        'sort' => [
            // The position of this column within a multi-column sort.
            'position' => 'Sort priority :position',
        ],

        // Said aloud after a draw settles. Whole sentences with placeholders rather than words
        // joined at runtime, the ordering of a sentence being a language's business.
        'announce' => [
            // :columns is one or more of the two below, joined by :then.
            'sorted' => 'Sorted by :columns.',
            'unsorted' => 'Sorting removed.',
            'ascending' => ':column ascending',
            'descending' => ':column descending',
            // What stands between one sort key and the next one down.
            'then' => ', then ',
            'results' => 'Showing :from-:to of :total.',
            'empty' => 'No results.',
        ],

        'error' => [
            'export_too_large' => 'This export is too large for an Excel file, which holds at most :limit rows. Export it as CSV instead.',
        ],
    ],
];
