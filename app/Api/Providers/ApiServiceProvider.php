<?php
namespace App\Api\Providers;

use Illuminate\Support\ServiceProvider;

class ApiServiceProvider extends ServiceProvider{
    function register(){
        $this->app->register(RouteServiceProvider::class);
    }
    function boot(){

    }
}