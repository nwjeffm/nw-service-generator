<?php

namespace NWJeffM\Generators\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;

class MakeRepositoryCommand extends Command
{
    /**
     * The console command signature
     *
     * @var string
     */
    protected $signature = 'make:repository {name : The name of the service.}
        {--i : Create with interface.}
        {--interface= : The interface name to be created.}
        {--dir= : Directory to store the service (and interface).}';

    /**
     * The console command description
     *
     * @var string
     */
    protected $description = 'Create a new repository';

    /**
     * The filesystem instance
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * Create a new repository install command instance
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command
     *
     * @return void
     */
    public function fire()
    {
        $name = trim($this->argument('name'));
        $name = Str::studly($name);
        $name = Str::plural($name);

        if($config = $this->configFileMissing()) {
            return $this->error($config);
        }

        $this->createService($name);

        if($this->option('i') || $this->option('interface')) {
            $interface = trim($this->option('interface') ?: null);
            $interface = Str::studly($interface);
            $interface = Str::plural($interface);

            $this->createInterface($interface ?: $name);
        }
    }

    /**
     * Create the service
     *
     * @param string $name
     * @return void
     */
    protected function createService($name)
    {
        $this->createServiceDirectory();

        if($this->serviceNameExists($name) === true) {
            return $this->error('Service already exists.');
        }

        $this->createDir();

        $this->createBaseServiceAbstract();

        $this->createServiceFile($name);
    }

    /**
     * Create interface
     *
     * @param string $name
     * @return void
     */
    protected function createInterface($interface)
    {
        $this->createInterfaceDirectory();

        if($this->repositoryNameExists($interface) === true) {
            return $this->error('Interface already exists.');
        }

        $this->createBaseInterfaceFile();

        $this->createInterfaceFile($interface);
    }

    /**
     * Create interface directory
     *
     * @return void
     */
    protected function createInterfaceDirectory()
    {
        if( ! $this->files->isDirectory($this->getRepositoryDirectory())) {
            $this->files->makeDirectory($this->getRepositoryDirectory());
        }

        if( ! $this->files->isDirectory($this->getRepositoryDirectory() . $this->getDirOption())) {
            $this->files->makeDirectory($this->getRepositoryDirectory() . $this->getDirOption());
        }
    }

    /**
     * Create base service abstract class file
     *
     * @return void
     */
    protected function createBaseServiceAbstract()
    {
        if( ! $this->files->exists($this->getServiceDirectory() . 'BaseServiceAbstract.php')) {
            $stub = str_replace(
                ['{{ServiceDirectory}}'],
                $this->getServiceDirectoryFromConfig(),
                $this->files->get(__DIR__ . '/../stubs/base-abstract.stub')
            );

            $this->files->put(base_path() . '/' . $this->getServiceDirectory() . 'BaseServiceAbstract.php', $stub);
        }
    }

    /**
     * Create base interface file
     *
     * @return void
     */
    protected function createBaseInterfaceFile()
    {
        if( ! $this->files->exists($this->getRepositoryDirectory() . 'BaseRepositoryInterface.php')) {
            $stub = str_replace(
                ['{{Namespace}}'],
                $this->getRepositoryDirectoryFromConfig(),
                $this->files->get(__DIR__ . '/../stubs/base-interface.stub')
            );

            $this->files->put(base_path() . '/' . $this->getRepositoryDirectory() . 'BaseRepositoryInterface.php', $stub);
        }
    }

    /**
     * Create the service class file
     *
     * @return void
     */
    protected function createServiceFile($name)
    {
        $dir_option = rtrim(str_replace('/', '\\', $this->getDirOption()), '/\\');
        $dir_option = $dir_option ? '\\' . $dir_option : '';

        $stub = str_replace(
            ['{{Namespace}}', '{{BaseServiceAbstractDirectory}}','{{ClassName}}'],
            [$this->getServiceDirectoryFromConfig() . $dir_option, $this->getServiceDirectoryFromConfig(), $name],
            $this->files->get(__DIR__ . '/../stubs/service.stub')
        );

        $this->files->put(base_path() . '/' . $this->getServiceDirectory() . $this->getDirOption() . $name . '.php', $stub);
    }

    /**
     * Create the interface class file
     *
     * @param  string $interface
     * @return void
     */
    protected function createInterfaceFile($interface)
    {
        $dir_option = rtrim(str_replace('/', '\\', $this->getDirOption()), '/\\');
        $dir_option = $dir_option ? '\\' . $dir_option : '';

        $stub = str_replace(
            ['{{Namespace}}', '{{BaseInterfaceDirectory}}','{{InterfaceClassName}}'],
            [$this->getRepositoryDirectoryFromConfig() . $dir_option, $this->getRepositoryDirectoryFromConfig(), $interface],
            $this->files->get(__DIR__ . '/../stubs/interface.stub')
        );

        $this->files->put(base_path() . '/' . $this->getRepositoryDirectory() . $this->getDirOption() . $interface . '.php', $stub);
    }

    /**
     * Validate if service already exists
     *
     * @param  string $name
     * @return bool
     */
    protected function serviceNameExists($name)
    {
        if($this->files->exists($this->getServiceDirectory() . $this->getDirOption() . $name . '.php')) {
            return true;
        }

        return false;
    }

    /**
     * Validate if repository interface already exists
     *
     * @param  string $interface
     * @return bool
     */
    protected function repositoryNameExists($interface)
    {
        if($this->files->exists($this->getRepositoryDirectory() . $this->getDirOption() . $interface . 'php')) {
            return true;
        }

        return false;
    }

    /**
     * Create the service directory
     *
     * @return void
     */
    protected function createServiceDirectory()
    {
        if( ! $this->files->isDirectory($this->getServiceDirectory())) {
            $this->files->makeDirectory($this->getServiceDirectory());

            return $this->info($this->getServiceDirectoryFromConfig() . ' directory created.');
        }
    }

    protected function getServiceDirectoryFromConfig()
    {
        return substr(rtrim($this->getServiceDirectory(), '/\\'), 4);
    }

    protected function getRepositoryDirectoryFromConfig()
    {
        return substr(rtrim($this->getRepositoryDirectory(), '/\\'), 4);
    }

    /**
     * Return the full service directory
     *
     * @return string
     */
    protected function getServiceDirectory()
    {
        if($config = $this->configFileMissing()) {
            return $this->error($config);
        }

        return 'app/' . rtrim($this->getConfigServiceDirectory(), '/\\') . '/';
    }

    /**
     * Return the full repository directory
     *
     * @return string
     */
    protected function getRepositoryDirectory()
    {
        if($config = $this->configFileMissing()) {
            return $this->error($config);
        }

        return 'app/' . rtrim($this->getConfigRepositoryDirectory(), '/\\') . '/';
    }

    /**
     * Return the service directory from config file
     *
     * @return string
     */
    private function getConfigServiceDirectory()
    {
        return $this->laravel['config']['repository']['services_directory'];
    }

    /**
     * Return the repository directory from config file
     *
     * @return string
     */
    private function getConfigRepositoryDirectory()
    {
        return $this->laravel['config']['repository']['repositories_directory'];
    }

    /**
     * Create directory from --dir option
     *
     * @return void
     */
    protected function createDir()
    {
        if($this->option('dir') === false) {
            return;
        }

        if($this->files->isDirectory($this->getServiceDirectory() . $this->getDirOption()) === false) {
            $this->files->makeDirectory($this->getServiceDirectory() . $this->getDirOption());

            return $this->info(rtrim($this->getDirOption(), '/\\') . ' directory created.');
        }
    }

    /**
     * Return the directory option
     *
     * @return string|null
     */
    protected function getDirOption()
    {
        if($this->option('dir')) {
            return rtrim(ucfirst(Str::plural($this->option('dir'))), '/\\') . '/';
        }
    }

    /**
     * Validate if config file exist
     *
     * @return string|null
     */
    protected function configFileMissing()
    {
        if($this->getConfigServiceDirectory() === null) {
            return 'Repository config is missing.';
        }
    }

    /**
     * Return the service stub
     *
     * @return $stub
     */
    protected function getServiceStub()
    {
        return __DIR__ . '/../stubs/service.stub';
    }

}