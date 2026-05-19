<?php

return [

    'name'  => 'Core',
    'alias' => 'Core',

    /*
    |--------------------------------------------------------------------------
    | Localization
    |--------------------------------------------------------------------------
    |
    | The locales the platform supports out of the box. The SetLocale
    | middleware reads the Accept-Language header and falls back to
    | `default_locale` when the requested locale is missing or invalid.
    |
    */

    'default_locale'    => env('CORE_DEFAULT_LOCALE', 'en'),
    'fallback_locale'   => env('CORE_FALLBACK_LOCALE', 'en'),
    'supported_locales' => ['ar', 'en'],

    /*
    |--------------------------------------------------------------------------
    | Tenant Scoping
    |--------------------------------------------------------------------------
    |
    | The column every tenant-scoped table uses. Centralized here so jobs,
    | repositories and the ScopeTenant middleware all agree on the same key.
    |
    */

    'tenant' => [
        'column'        => 'tenant_id',
        'container_key' => 'tenant_id',
        'guard'         => 'api',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'default_per_page' => (int) env('CORE_DEFAULT_PER_PAGE', 15),
        'max_per_page'     => (int) env('CORE_MAX_PER_PAGE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | UUID
    |--------------------------------------------------------------------------
    |
    | All primary keys in the project are CHAR(36) UUIDs. The version is
    | exposed here so the HasUuid trait (and seeders) can switch between
    | random/v4 and ordered/v7 UUIDs without code changes.
    |
    */

    'uuid' => [
        'version' => env('CORE_UUID_VERSION', 'v4'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queues — see CURSOR_RULES.md §14
    |--------------------------------------------------------------------------
    |
    | Every queued job in the project must use one of these queue names and
    | obey its timeout. Centralized so jobs reference `config('core.queues.reports.name')`
    | instead of hard-coding strings.
    |
    */

    'queues' => [
        'default'       => ['name' => 'default',       'timeout' => 60],
        'notifications' => ['name' => 'notifications', 'timeout' => 120],
        'reports'       => ['name' => 'reports',       'timeout' => 300],
        'imports'       => ['name' => 'imports',       'timeout' => 300],
        'exports'       => ['name' => 'exports',       'timeout' => 300],
        'billing'       => ['name' => 'billing',       'timeout' => 120],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Both central and tenant tables live in the same physical database, so
    | both keys point at the default Laravel connection. They're aliased here
    | so models can refer to the role rather than the connection name and
    | the deployment can split them later without touching model code.
    |
    */

    'connections' => [
        'central' => env('DB_CONNECTION_CENTRAL', env('DB_CONNECTION', 'mysql')),
        'tenant'  => env('DB_CONNECTION_TENANT',  env('DB_CONNECTION', 'mysql')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Files — column map for the unified `media_files` table
    |--------------------------------------------------------------------------
    |
    | The actual schema in rakeeza-v5.sql uses `model_type` / `model_id` /
    | `disk` / `file_path` / `file_size` / `collection`. Code should read
    | these names from here instead of hard-coding them, so a future schema
    | rename only has to be applied in one place.
    |
    */

    'media' => [
        'table'         => 'media_files',
        'morph_type'    => 'model_type',
        'morph_id'      => 'model_id',
        'disk_column'   => 'disk',
        'path_column'   => 'file_path',
        'size_column'   => 'file_size',
        'collection'    => 'collection',
        'default_disk'  => env('CORE_MEDIA_DEFAULT_DISK', 'public'),
        'allowed_owner_types' => [
            'contact', 'product', 'purchase_order', 'sales_invoice',
            'payment', 'expense', 'employee', 'payslip', 'journal_entry',
            'stock_adjustment', 'user', 'tenant',
            'order', 'order_proof', 'message', 'delivery_agent', 'shipping_company',
        ],

        /*
        | Map short owner keys (stored in model_type) to Eloquent model classes
        | so MediaFile::model() can resolve the owner. Modules may merge more
        | entries via their ServiceProvider::boot().
        */
        'owner_model_map' => [
            'user' => \App\Models\User::class,
        ],
    ],

];
