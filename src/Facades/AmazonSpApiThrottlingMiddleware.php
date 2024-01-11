<?php

namespace EolabsIo\AmazonSpApiThrottlingMiddleware\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \EolabsIo\AmazonSpApiResponseParser\Skeleton\SkeletonClass
 */
class AmazonSpApiThrottlingMiddleware extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'amazon-sp-api-throttling-middleware';
    }
}
