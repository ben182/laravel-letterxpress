<?php

namespace Ben182\Letterxpress;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Ben182\Letterxpress\Skeleton\SkeletonClass
 */
class LaravelLetterxpressFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'letterxpress';
    }
}
