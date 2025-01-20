<?php

namespace Asciisd\Kashier\Facades;

use Illuminate\Support\Facades\Facade;

class Kashier extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'kashier';
    }
}
