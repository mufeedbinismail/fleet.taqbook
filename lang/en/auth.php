<?php

/*
|--------------------------------------------------------------------------
| Authentication Language Lines
|--------------------------------------------------------------------------
|
| The following language lines are used during authentication for various
| messages that we need to display to the user. You are free to modify
| these language lines according to your application's requirements.
|
*/

return [
    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :minutes minutes.',

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
            'reserved_name' => 'Role names starting with ":prefix" are reserved by the system.',
            'name_format' => 'A role name may use only letters, digits, spaces and - & _, and cannot end with a separator.',
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
            'reserved_login' => 'Logins starting with ":prefix" are reserved by the system.',
            'login_format' => 'A login may use only letters, digits, spaces and - & _, and cannot end with a separator.',
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
];
