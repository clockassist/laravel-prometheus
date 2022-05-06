<?php

namespace Prometheus\Laravel;

use Prometheus\CollectorRegistry;

/**
 * @see CollectorRegistry
 */
class Facade extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        return 'prometheus';
    }
}
