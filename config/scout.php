<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel Scout Configuration
    |--------------------------------------------------------------------------
    |
    | Scout provides a driver-based approach to full-text search. This file
    | configures the Meilisearch driver and queue-friendly defaults.
    |
    */

    'driver' => env('SCOUT_DRIVER', 'meilisearch'),

    /*
    |--------------------------------------------------------------------------
    | Index Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix applied to all Scout indexes. Useful for multi-tenant or
    | multi-environment setups sharing the same Meilisearch instance.
    |
    */

    'prefix' => env('SCOUT_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Scout can sync index updates to the queue for better performance.
    | When using Horizon, this dispatches index jobs asynchronously.
    |
    */

    'queue' => (bool) env('SCOUT_QUEUE', true),

    /*
    |--------------------------------------------------------------------------
    | After Commit
    |--------------------------------------------------------------------------
    |
    | When set to true, scout will dispatch queued indexing operations
    | only after the database transaction has been committed.
    |
    */

    'after_commit' => true,

    /*
    |--------------------------------------------------------------------------
    | Chunk Settings
    |--------------------------------------------------------------------------
    |
    | When importing large datasets, Scout chunks records to stay
    | within memory limits.
    |
    */

    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    |
    | When enabled, Scout automatically removes soft-deleted models
    | from the search index.
    |
    */

    'soft_delete' => false,

    /*
    |--------------------------------------------------------------------------
    | Meilisearch Driver Configuration
    |--------------------------------------------------------------------------
    |
    | Connection details for the Meilisearch engine. The host and key
    | should match the values in your compose.yaml or production setup.
    |
    */

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://127.0.0.1:7700'),
        'key' => env('MEILISEARCH_KEY', null),
        'index-settings' => [
            App\Models\Event::class => [
                'filterableAttributes' => [
                    'organizer_id',
                    'category_id',
                    'venue_city',
                    'starts_at',
                    'starts_at_date',
                ],
                'searchableAttributes' => [
                    'title',
                    'description',
                ],
            ],
        ],
    ],

];
