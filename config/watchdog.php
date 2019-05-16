<?php

return [

    'ignore_route' => [

        'GET|HEAD@auth/login',
        'GET|HEAD@auth/register',
        'GET|HEAD@auth/forget/password',
        'POST@auth/login',
        'POST@auth/register',
        'POST@auth/forget/password'

    ],

    /**
     * Relation Account Model
     */
    'account' => [

        'model' => 'App\Models\User\Account',

        'name' => 'nickname'

    ],

    /**
     * Super Admin Role ID.
     * Super Admin will ignore gate check, and allow access all route.
     */
    'super_admin_id' => 1,

];
