<?php

return [

    /**
     * Valid values: redis, memory
     */
    'adapter' => env('PROMETHEUS_ADAPTER'),

    'redis_connection' => env('PROMETHEUS_REDIS_CONNECTION', 'default')
];
