<?php

return [

    /*
    |--------------------------------------------------------------------------
    | DTO metadata cache
    |--------------------------------------------------------------------------
    |
    | Forms, datatables, and detail views discover field metadata from PHP
    | attributes on Data classes (FormFieldAttribute, ValuePropertyAttribute).
    | That discovery is cached here so production requests avoid reflection.
    |
    | Uses Laravel's cache store (file by default). Redis is not required.
    | Schema is app-wide — not stored in session (metadata is the same for
    | every user).
    |
    | See docs/dto-metadata.md for usage and how to clear the cache.
    |
    */

    'enabled' => env('DTO_METADATA_CACHE_ENABLED', env('APP_ENV') === 'production'),

    'directories' => [
        app_path('Data'),
    ],

    'cache' => [
        'store' => env('CACHE_STORE', env('CACHE_DRIVER', 'file')),
        'prefix' => 'dto-metadata',
        'duration' => null,
    ],

];
