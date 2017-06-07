<?php

namespace NWJeffM\Generators;

use Illuminate\Support\ServiceProvider;

class GeneratorsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/repository.php' => config_path('repository.php')
        ], 'config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerRepositoryGenerator();
    }

    /**
     * Register the make:repository command.
     */
    private function registerRepositoryGenerator()
    {
        $this->app->singleton('command.nwjeffm.repository', function($app) {
            return $app['NWJeffM\Generators\Commands\MakeRepositoryCommand'];
        });

        $this->commands('command.nwjeffm.repository');
    }
}
