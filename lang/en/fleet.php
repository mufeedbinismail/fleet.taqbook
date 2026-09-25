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
            'last_reached' => 'Last reached',
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

        'delivery_outcome' => [
            'reached' => 'Reached',
            'unreachable' => 'Could not connect to its address',
            'refused' => 'Its address refused the message',
            'wrong_answer' => 'Its address answered, but not to this message',
        ],

        'hint' => [
            'alias' => 'What support will call this install: the customer, then which of theirs.',
            'alias_shape' => 'Letters and digits, separated by single hyphens, and never digits alone.',
            'number' => 'Assigned when the deployment is registered, and never changed.',
            'instance_created' => 'The day the install itself was stood up, which may be long before it reached this register.',
            'changed_at' => 'When it moved, not when you are recording it.',
            'no_address' => 'No address',
        ],

        'action' => [
            'new' => 'Register',
            'edit' => 'Edit',
            'rename' => 'Rename',
            'change_status' => 'Change status',
            'ping' => 'Ping',
            'menu' => 'Actions',
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
            'no_address' => 'This deployment has no address to reach it at.',
            'insecure_address' => 'Its address must be https://. Plain http:// is only pinged in development.',
            'no_signing_key' => 'This server holds no operational key or delegation. Run fleet:keypair:install first.',
            'delegation_expired' => 'This server\'s delegation ran out on :date. Run fleet:keypair:install with the root secret.',
        ],

        'notice' => [
            'registered' => 'Deployment registered',
            'saved' => 'Deployment saved',
            'renamed' => 'Deployment renamed',
            'status_changed' => 'Status changed',
            'removed' => 'Deployment moved to trash',
            'erased' => 'Deployment deleted permanently',
            'reached' => ':alias answered the ping',
        ],

        'footer' => [
            'total' => ':count deployments',
        ],
    ],

];
