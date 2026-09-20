<?php

return [

    'area' => [
        'title' => 'Fleet',
        'help' => 'Every client install we run',
    ],

    'section' => [
        'maintenance' => 'Maintenance',
    ],

    'deployment' => [
        'title' => 'Deployment Register',
        'new' => 'Register a deployment',
        'edit' => 'Edit deployment',
        'rename' => 'Rename deployment',
        'status_change' => 'Change status',
        'status_change_of' => 'Change status of :alias',

        'column' => [
            'number' => 'Number',
            'alias' => 'Alias',
            'customer' => 'Customer',
            'hosting' => 'Hosting',
            'status' => 'Status',
            'instance_created' => 'Created',
            'address' => 'Address',
            'actions' => '',
        ],

        'field' => [
            'customer' => 'Customer',
            'alias' => 'Alias',
            'hosting' => 'Hosting',
            'status' => 'Status',
            'instance_created' => 'Instance created',
            'changed_at' => 'Changed on',
            'address' => 'Address',
        ],

        'placeholder' => [
            'customer' => 'Pick a customer',
            'address' => 'https://example.com',
        ],

        'hosting' => [
            'cloud_ours' => 'Cloud - Ours',
            'cloud_theirs' => 'Cloud - Theirs',
            'local_on_premise' => 'Local - On Premise',
        ],

        'status' => [
            'registered' => 'Registered',
            'delivered' => 'Delivered',
            'live' => 'Live',
            'suspended' => 'Suspended',
            'retired' => 'Retired',
        ],

        'hint' => [
            'alias' => 'What support will call this install: the customer, then which of theirs.',
            'alias_shape' => 'Letters and digits, separated by single hyphens.',
            'number' => 'Assigned when the deployment is registered, and never changed.',
            'instance_created' => 'The day the install itself was stood up, which may be long before it reached this register.',
            'changed_at' => 'When it moved, not when you are recording it.',
        ],

        'action' => [
            'new' => 'Register',
            'edit' => 'Edit',
            'rename' => 'Rename',
            'change_status' => 'Change status',
            'remove' => 'Remove',
            'save' => 'Save',
            'create' => 'Register',
            'cancel' => 'Cancel',
        ],

        'remove' => [
            'title' => 'Remove :alias',
            'trash' => [
                'action' => 'Move to trash',
                'text' => 'It leaves the register. The record is kept, and its name stays reserved.',
            ],
            'erase' => [
                'action' => 'Delete permanently',
                'text' => 'The record is destroyed and its name is free to use again. Its number is not reissued.',
                'confirm_title' => 'Delete :alias permanently?',
                'confirm_text' => 'There is no record of it afterwards, and this cannot be undone.',
                'confirm_action' => 'Delete permanently',
            ],
        ],

        'error' => [
            'heading' => 'This deployment could not be saved',
            'same_status' => 'This deployment already stands at :status. Pick the status it moved to.',
            'alias_taken' => 'Another deployment is already called :alias, on the register or off it.',
        ],

        'notice' => [
            'registered' => 'Deployment registered',
            'saved' => 'Deployment saved',
            'renamed' => 'Deployment renamed',
            'status_changed' => 'Status changed',
            'removed' => 'Deployment moved to trash',
            'erased' => 'Deployment deleted permanently',
        ],

        'footer' => [
            'total' => ':count deployments',
        ],
    ],

];
