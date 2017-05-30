<?php

namespace NWJeffM\Generators\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;

class MakeRepositoryCommand extends Command
{

	/**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:repositories';

    /**
     * The command signature.
     *
     * @var string
     */
    protected $signature = 'make:repository {name : Repository to be created} {--dir= : Directory of the repository} {--trait : With trait}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new repository files for your services';

    /**
     * Meta information for the requested migration.
     *
     * @var array
     */
    protected $meta;

    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     * @param Composer $composer
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
        $this->composer = app()['composer'];
    }

    /**
     * Execute the command.
     */
    public function handle()
    {
    	$this->makeRepositories();
    	$this->composer->dumpAutoloads();
    }

    /**
     * Create the repository.
     *
     * @return mixed
     */
    protected function makeRepositories()
    {
    	if($this->isRepositoryExists()) {
    		return $this->error("Repository name already exists");
    	}

    	$this->createRepositoriesDirectory();
        $this->createInterfacesDirectory();
        $this->createBaseInterfaceFile();
        $this->createInterfaceFile();
        $this->createTraitsDirectory();
        if($this->option('trait')) {
            $this->createTraitFile();
        }
    }

    /**
     * Create base interface file
     *
     * @return void
     */
    protected function createBaseInterfaceFile()
    {
        if( ! $this->files->exists($this->getRepositoriesDirectory() . 'Interfaces/Base/BaseRepositoryInterface.php')) {
            $this->files->put(base_path() . '/' . $this->getRepositoriesDirectory() . 'Interfaces/Base/BaseRepositoryInterface.php', $this->generateBaseInterfaceFile());

            return $this->info('Base interface file generated.');
        }
    }

    /**
     * Generate base interface class using the stub
     *
     * @return void
     */
    protected function generateBaseInterfaceFile()
    {
        $stub = $this->files->get($this->getBaseInterfaceStub());

        $this->replaceNamespace($stub, $this->getBaseInterfaceNamespace());

        return $stub;
    }

    /**
     * Create interface file.
     *
     * @return void
     */
    protected function createInterfaceFile()
    {
        $this->files->put(base_path() . '/' . $this->getInterfacesDirectory() . $this->getInterfaceName() . '.php', $this->generateInterfaceFile());

        return $this->info('Interface file generated.');
    }

    /**
     * Generate interface class using the stub.
     *
     * @return void
     */
    protected function generateInterfaceFile()
    {
        $stub = $this->files->get($this->getInterfaceStub());

        $this->replaceNamespace($stub, $this->getInterfaceNamespace())
            ->replaceBaseInterfaceDirectory($stub)
            ->replaceInterfaceClassName($stub);

        return $stub;
    }

    /**
     * Create trait file
     *
     * @return void
     */
    protected function createTraitFile()
    {
        $this->files->put(base_path() . '/' . $this->getTraitsDirectory() . $this->getTraitName() . '.php', $this->generateTraitFile());

        return $this->info('Trait file generated.');
    }

    /**
     * Generate trait class using the stub.
     *
     * @return void
     */
    protected function generateTraitFile()
    {
        $stub = $this->files->get($this->getTraitStub());

        $this->replaceNamespace($stub, $this->getTraitNamespace())
            ->replaceTraitName($stub);

        return $stub;
    }

    /**
     * Get interface namespace
     *
     * @return sring
     */
    protected function getInterfaceNamespace()
    {
        return str_replace('/', '\\', ucwords($this->getInterfacesDirectory(), '/'));
    }

    /**
     * Get base interface namespace
     *
     * @return string
     */
    protected function getBaseInterfaceNamespace()
    {
        return rtrim(str_replace('/', '\\', ucwords($this->getRepositoriesDirectory(), '/')), '/\\') . '\Interfaces\Base';
    }

    /**
     * Get trait namespace
     *
     * @return string
     */
    protected function getTraitNamespace()
    {
        return str_replace('/', '\\', ucwords($this->getTraitsDirectory(), '/'));
    }

    /**
     * Replace the namespace in the stub.
     *
     * @param  string $stub
     * @param  string $directory
     * @return $this
     */
    protected function replaceNamespace(&$stub, $directory = '')
    {
        $stub = str_replace('{{Namespace}}', $this->trimTrailingSlash($directory), $stub);

        return $this;
    }

    /**
     * Replace interface directory from the stub
     *
     * @return string
     */
    protected function replaceInterfaceDirectory(&$stub)
    {
        $stub = str_replace('{{InterfaceDirectory}}', str_replace('/', '\\', ucwords($this->getInterfacesDirectory(), '/')), $stub);

        return $this;
    }

    /**
     * Replace base interface directory from the stub
     *
     * @return string
     */
    protected function replaceBaseInterfaceDirectory(&$stub)
    {
        $stub = rtrim(str_replace('{{BaseInterfaceDirectory}}', $this->getBaseInterfaceNamespace(), $stub));

        return $this;
    }

    /**
     * Replace the interface class name in the stub.
     *
     * @param  string $stub
     * @return $this
     */
    protected function replaceInterfaceClassName(&$stub)
    {
        $stub = str_replace('{{InterfaceClassName}}', ucwords(Str::camel($this->getInterfaceName())), $stub);
        
        return $this;
    }

    /**
     * Replace the trait class name in the stub.
     *
     * @param string $stub
     * @return $this
     */
    protected function replaceTraitName(&$stub)
    {
        $stub = str_replace('{{TraitName}}', ucwords(Str::camel($this->getTraitName())), $stub);

        return $this;
    }

    /**
     * create TraitDirectory
     *
     * @return void
     */
    protected function createTraitsDirectory()
    {
        if( ! $this->files->isDirectory($this->getRepositoriesDirectory() . 'Traits')) {
            $this->files->makeDirectory($this->getRepositoriesDirectory() . 'Traits');
        }

        if( ! $this->files->isDirectory($this->getTraitsDirectory())) {
            $this->files->makeDirectory($this->getTraitsDirectory());
        }
    }

    /**
     * Create interface repository directory
     *
     * @return void
     */
    protected function createInterfacesDirectory()
    {
        if( ! $this->files->isDirectory($this->getRepositoriesDirectory() . 'Interfaces')) {
            $this->files->makeDirectory($this->getRepositoriesDirectory() . 'Interfaces');
        }

        if( ! $this->files->isDirectory($this->getRepositoriesDirectory() . 'Interfaces/Base')) {
            $this->files->makeDirectory($this->getRepositoriesDirectory() . 'Interfaces/Base');
        }

        if( ! $this->files->isDirectory($this->getInterfacesDirectory())) {
            $this->files->makeDirectory($this->getInterfacesDirectory());
        }
    }

    /**
     * Create repositories directory
     *
     * @return void
     */
    protected function createRepositoriesDirectory()
    {
        if( ! $this->files->isDirectory($this->getRepositoriesDirectory())) {
            $this->files->makeDirectory($this->getRepositoriesDirectory());
        }
    }

    /**
     * Validate if repository already exists.
     *
     * @return bool
     */
    protected function isRepositoryExists()
    {
        if($this->isInterfaceExists() || $this->isTraitExists()) {
            return true;
        }

        return false;
    }

    /**
     * Validate if trait repository exists.
     *
     * @return bool
     */
    protected function isTraitExists()
    {
        return $this->files->exists($this->getTraitsDirectory() . $this->getTraitName() . '.php');
    }

    /**
     * Validate if interface repository exists.
     *
     * @return bool
     */
    protected function isInterfaceExists()
    {
        return $this->files->exists($this->getInterfacesDirectory() . $this->getInterfaceName() . '.php');
    }

    /**
     * Return interface name
     *
     * @return string
     */
    protected function getInterfaceName()
    {
        return Str::plural(Str::studly(strtolower($this->argument('name')))) . 'RepositoryInterface';
    }

    /**
     * Return trait name
     *
     * @return string
     */
    protected function getTraitName()
    {
        return Str::plural(Str::studly(strtolower($this->argument('name')))) . 'Trait';
    }

    /**
     * Return interface directory
     *
     * @return string
     */
    protected function getInterfacesDirectory()
    {
        return $this->getRepositoriesDirectory() . 'Interfaces/' .$this->returnDirOption();
    }

    /**
     * Return trait directory
     *
     * @return string
     */
    protected function getTraitsDirectory()
    {
        return $this->getRepositoriesDirectory() . 'Traits/' . $this->returnDirOption();
    }

    /**
     * Return base interface directory
     *
     * @return string
     */
    protected function getRepositoriesDirectory()
    {
        return 'app/' . (config('generator.repositories_directory') ? $this->trimTrailingSlash(ucfirst(config('generator.repositories_directory'))) . '/' : '');
    }

    /**
     * Return --dir option
     *
     * @return string
     */
    public function returnDirOption()
    {
        return ! is_null($this->option('dir')) ? Str::plural(Str::studly($this->option('dir'))) . '/' : '';
    }

    /**
     * Return the interface stub template.
     *
     * @return string
     */
    protected function getInterfaceStub()
    {
        return __DIR__ . '/../stubs/interface.stub';
    }


    /**
     * Return the base enterface stub template.
     *
     * @return string
     */
    protected function getBaseInterfaceStub()
    {
        return __DIR__ . '/../stubs/base-interface.stub';
    }

    /**
     * Return the trait stub template.
     *
     * @return string
     */
    protected function getTraitStub()
    {
        return __DIR__ . '/../stubs/trait.stub';
    }

    /**
     * Get the application's namespace.
     *
     * @return string
     */
    protected function getAppNamespace()
    {
        return Container::getInstance()->getNamespace();
    }

    /**
     * Remove trailing slash from the string
     *
     * @param string $string
     * @return string
     */
    private function trimTrailingSlash($string = null)
    {
        if( ! is_null($string)) {
            return rtrim($string, '/\\');
        }
    }

}