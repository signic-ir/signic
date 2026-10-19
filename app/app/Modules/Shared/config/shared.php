<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default role permissions
    |--------------------------------------------------------------------------
    |
    | The permissions a role holds as soon as it exists. The roles are matched
    | by name and a permission is only granted once it exists, so the mapping
    | may be declared before the permissions themselves are created.
    |
    | The super admin is not listed here: it inherits the permissions of every
    | other role and therefore holds all of them.
    |
    */

    'role_permissions' => [
        'event_organizer' => ['attendee.create', 'attendee.view'],
        'exhibitor' => ['exhibitor.lead.create', 'exhibitor.lead.view'],
        'staff' => ['turnstile.operate', 'attendee.view'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Super admin
    |--------------------------------------------------------------------------
    |
    | The role that inherits the permissions of every other role. It passes
    | every ability check of the platform.
    |
    */

    'super_admin' => 'super_admin',

];
