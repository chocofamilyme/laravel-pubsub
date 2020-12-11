<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Listen for events
    |--------------------------------------------------------------------------
    |
    | Define event name and it's listeners. Please notice that one event name may have multiple listeners
    |
    | Example:
    |
    | listen => [
    |     'UserNotified' => [
    |         NotifyAboutDeviceChangeListener::class,
    |     ]
    | ],
    |
    */
    'listen' => [],

    /**
     * Define database tables for storing data (publishing events, incoming events, etc.)
     */
    'tables' => [
        'events' => 'pubsub_events',
    ],

    /**
     * If true, all incoming subscribe events will be recorded to tables->events table
     */
    'record_sub_events' => false
];
