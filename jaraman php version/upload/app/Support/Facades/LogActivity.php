<?php

namespace App\Support\Facades;

use Illuminate\Support\Facades\Facade;

class LogActivity extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'activityLog';
    }
}
