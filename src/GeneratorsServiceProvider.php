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
            __DIR__ . '/../config/generator.php' => config_path('generator.php')
        ], 'config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // $this->registerServiceGenerator();
        $this->registerRepositoryGenerator();
    }

    /**
     * Registersthe make:service command.
     */
    private function registerServiceGenerator()
    {
        $this->app->singleton('command.nwjeffm.service', function($app) {
            return $app['NWJeffM\Generators\Commands\MakeServiceCommand'];
        });

        $this->commands('command.nwjeffm.service');
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
