<?php

namespace ComposerTest\Provider;

use Illuminate\Support\ServiceProvider;

class ComposerTestServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/composer_test.php' => config_path('permission.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../database/migrations/create_test_table.php' => database_path('migrations')
        ], 'migrations');
    }

    public function register()
    {

    }
}
