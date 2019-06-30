<?php

namespace Ben182\LaravelLetterxpress;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Ben182\LaravelLetterxpress\Skeleton\SkeletonClass
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
        return 'laravel-letterxpress';
    }
}
