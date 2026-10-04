<?php

return [

    /*
    | The generator writes PHP files into the codebase, so it is enabled only
    | in the local environment unless CRUD_GENERATOR_ENABLED says otherwise.
    */
    'enabled' => (bool) env('CRUD_GENERATOR_ENABLED', env('APP_ENV', 'production') === 'local'),

    'base_path' => env('CRUD_GENERATOR_BASE_PATH', base_path()),

    'stubs_path' => base_path('stubs/crud-generator'),

    'manifest' => 'database/crud-generator/manifest.json',

    'artisan_environments' => ['local', 'staging'],

];
