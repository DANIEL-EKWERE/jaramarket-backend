<?php

namespace App\Providers;

use App\Macros\HttpMacro;
use App\Macros\ResponseMacro;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class MacroServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        Response::mixin(new ResponseMacro());
        Http::mixin(new HttpMacro());
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        
    }
}
