<?php

namespace NWJeffM\Generators\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use NWJeffM\Generators\Helpers\GeneratorTrait;

class MakeRepositoryCommand extends Command
{

    use GeneratorTrait;

    /**
     * The console command signature
     *
     * @var string
     */
    protected $signature = 'make:repository {name : The name of the service}
        {--dir= : Create the service (and interface) file inside a directory}
        {--interface= : Create a service with custom interface file name}
        {--trait= : Create a service with custom trait file name}
        {--i : Create a service with interface file}
        {--t : Create a service with trait file}';

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
        if($config = $this->configFileMissing()) {
            return $this->error($config);
        }

        $name = trim($this->argument('name'));

        $this->createService($name);

        if($this->option('i') || $this->option('interface')) {
            $this->createInterface($this->option('interface') ?: $name);
        }

        if($this->option('t') || $this->option('trait')) {
            $this->createTrait($this->option('trait') ?: $name);
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
        $name = $this->configureName('service', $name);

        if($this->serviceNameExists($name) === true) {
            return $this->error('Service already exists');
        }

        $this->createServiceDirectory();

        $this->createBaseServiceAbstract();

        $this->createServiceFile($name, $this->option('i') || $this->option('interface') ? true : false);
    }

    /**
     * Create interface
     *
     * @param  string $interface
     * @return void
     */
    protected function createInterface($interface)
    {
        if($this->repositoryNameExists($interface) === true) {
            return $this->error('Interface already exists');
        }

        $this->createInterfaceDirectory();

        $this->createBaseInterfaceFile();

        $this->createInterfaceFile($interface);
    }

    /**
     * Create trait
     *
     * @param string $trait
     * @return void
     */
    protected function createTrait($trait)
    {
        if($this->traitNameExists($trait) === true) {
            return $this->error('Trait already exists');
        }

        $this->createTraitDirectory();

        $this->createTraitFile($trait);
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
    protected function createServiceFile($name, $with_interface)
    {
        $dir_option = rtrim(str_replace('/', '\\', $this->getDirOption()), '/\\');
        $dir_option = $dir_option ? '\\' . $dir_option : '';
        $classname = $this->configureName('service', $name);

        if($with_interface === true) {
            $interface = trim($this->option('interface'));
            $interface = $this->configureName('repository', $interface ?: $name);

            $stub = str_replace(
                ['{{Namespace}}', '{{BaseServiceAbstractDirectory}}', '{{InterfaceDirectory}}', '{{InterfaceName}}', '{{ClassName}}'],
                [$this->getServiceDirectoryFromConfig() . $dir_option, $this->getServiceDirectoryFromConfig(),
                $this->getRepositoryDirectoryFromConfig() . $dir_option, $interface, $classname],
                $this->files->get($this->getServiceImplementsInterfaceStub())
            );
        } else {
            $stub = str_replace(
                ['{{Namespace}}', '{{BaseServiceAbstractDirectory}}', '{{ClassName}}'],
                [$this->getServiceDirectoryFromConfig() . $dir_option, $this->getServiceDirectoryFromConfig(), $classname],
                $this->files->get($this->getServiceStub())
            );
        }

        $this->files->put(base_path() . '/' . $this->getServiceDirectory() . $this->getDirOption() . $classname . '.php', $stub);
    }

    /**
     * Create the interface file
     *
     * @param  string $interface
     * @return void
     */
    protected function createInterfaceFile($interface)
    {
        $dir_option = rtrim(str_replace('/', '\\', $this->getDirOption()), '/\\');
        $dir_option = $dir_option ? '\\' . $dir_option : '';
        $interface = $this->configureName('repository', $interface);
        dd($interface);

        $stub = str_replace(
            ['{{Namespace}}', '{{BaseInterfaceDirectory}}', '{{InterfaceClassName}}'],
            [$this->getRepositoryDirectoryFromConfig() . $dir_option, $this->getRepositoryDirectoryFromConfig(), $interface],
            $this->files->get(__DIR__ . '/../stubs/interface.stub')
        );

        $this->files->put(base_path() . '/' . $this->getRepositoryDirectory() . $this->getDirOption() . $interface . '.php', $stub);
    }

    /**
     * Create the trait file
     *
     * @param  string $trait
     * @return void
     */
    protected function createTraitFile($trait)
    {
        $dir_option = rtrim(str_replace('/', '\\', $this->getDirOption()), '/\\');
        $dir_option = $dir_option ? '\\' . $dir_option : '';
        $trait = $this->configureName('trait', $trait);

        $stub = str_replace(
            ['{{Namespace}}', '{{TraitName}}'],
            [$this->getTraitDirectoryFromConfig() . $dir_option, $trait],
            $this->files->get(__DIR__ . '/../stubs/trait.stub')
        );

        $this->files->put(base_path() . '/' . $this->getTraitDirectory() . $this->getDirOption() . $trait . '.php', $stub);
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
     * Validate if trait already exists
     *
     * @param  string $trait
     * @return bool
     */
    protected function traitNameExists($trait)
    {
        if($this->files->exists($this->getTraitDirectory() . $this->getDirOption() . $trait . '.php')) {
            return true;
        }

        return false;
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
     * Return the full trait directory
     *
     * @return string
     */
    protected function getTraitDirectory()
    {
        if($config = $this->configFileMissing()) {
            return $this->error($config);
        }

        return 'app/' . rtrim($this->getConfigTraitDirectory(), '/\\') . '/';
    }

    /**
     * Return the service directory from config file
     *
     * @return string
     */
    protected function getConfigServiceDirectory()
    {
        return $this->laravel['config']['repository']['service_directory'];
    }

    /**
     * Return the repository directory from config file
     *
     * @return string
     */
    protected function getConfigRepositoryDirectory()
    {
        return $this->laravel['config']['repository']['repository_directory'];
    }

    /**
     * Return the trait ddirectory from the config file
     *
     * @return string
     */
    protected function getConfigTraitDirectory()
    {
        return $this->laravel['config']['repository']['trait_directory'];
    }

    /**
     * Return the directory option
     *
     * @return string|null
     */
    protected function getDirOption($return_array = false)
    {
        if($dir = $this->option('dir')) {
            $directories = explode('/', $dir);
            if(is_array($directories) && $directories) {
                $directories = array_map(function($directory) {
                    return ucfirst(str::plural(strtolower($directory)));
                }, $directories);

                return $return_array === true ? $directories : rtrim(implode('/', $directories), '/\\') . '/';
            }
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
     * Configure the name according to user preferred settings
     *
     * @param  string $type
     * @param  string $name
     * @return string
     */
    protected function configureName($type, $name)
    {
        $config = $this->laravel['config']['repository'];

        if($config["{$type}_to_plural"] === true) {
            $name = Str::plural($name);
        }

        if($config["case_sensitive"] === false) {
            $name = Str::studly(strtolower($name));
        }

        return $name . $config["{$type}_append"];
    }

     /**
     * Create trait directory
     *
     * @return void
     */
    protected function createTraitDirectory()
    {
        if( ! $this->files->isDirectory($this->getTraitDirectory())) {
            $this->files->makeDirectory($this->getTraitDirectory());
        }

        $this->createDirectory('trait');
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

        $this->createDirectory('repository');
    }

    /**
     * Create service directory
     *
     * @return void
     */
    protected function createServiceDirectory()
    {
        if( ! $this->files->isDirectory($this->getServiceDirectory())) {
            $this->files->makeDirectory($this->getServiceDirectory());
        }

        $this->createDirectory('service');
    }

}