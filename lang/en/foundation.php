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
        ],

        // Names the validator substitutes into its own messages, so they read mid-sentence and
        // lowercase rather than as headings.
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

    'toggle' => [
        'on' => 'On',
        'off' => 'Off',
    ],

    'skin' => [
        'label' => 'Skin',
        'system' => 'Auto',
        'light' => 'Light',
        'dark' => 'Dark',
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
    ],
];
