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
     * The command signature
     *
     * @var string
     */
    protected $signature = 'make:repositories {name : The name of your Repository} {--interface= : Custom name of your Interface} {--trait= : Custom name of your Trait}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generator Repository files for your Service';

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
     * Execute the command
     */
    public function handle()
    {
    	$this->makeRepositories();
    	$this->composer->dumpAutoloads();
    }

    /**
     * Create the repository
     *
     * @return mixed
     */
    protected function makeRepositories()
    {
    	if($this->repositoryExists()) {
    		return $this->error("Repository already exists");
    	}

    	$this->createRepositoriesDirectory();
    	$this->addInterfaceFile();
    	$this->addTraitFile();
    }

    /**
     * Validate if repository already exists
     *
     * @return bool
     */
    public function repositoryExists()
    {
    	if($this->files->exists($this->getInterfacesDirectory() . '/' . $this->getInterfaceName() . '.php')
    		|| $this->files->exists($this->getTraitsDirectory() . '/' . $this->getTraitName() . '.php')) {
    		return true;
    	}

    	return false;
    }

    /**
     * Get the Repositories directory
     *
     * @return string
     */
    private function getRepositoriesDirectory()
    {
    	return base_path() . '/app/' . config('generator.repositories_directory');
    }

    /**
     * Get the Interface directory
     *
     * @return string
     */
    private function getInterfacesDirectory()
    {
    	return base_path() . '/app/' . config('generator.repositories_directory') . '/Interfaces';
    }

    /**
     * Get the Traits directory
     *
     * @return string
     */
    protected function getTraitsDirectory()
    {
    	return base_path() . '/app/' . config('generator.repositories_directory') . '/Traits';
    }

    /**
     * Create Repository directory
     */
    protected function createRepositoriesDirectory()
    {
    	if( ! $this->files->isDirectory($this->getRepositoriesDirectory())) {
    		$this->files->makeDirectory($this->getRepositoriesDirectory(), 0755);
    	}
    	$this->info($this->getAppNamespace());

    	if( ! $this->files->isDirectory($this->getInterfacesDirectory())) {
    		$this->files->makeDirectory($this->getInterfacesDirectory(), 0755);
    	}

    	if( ! $this->files->isDirectory($this->getTraitsDirectory())) {
    		$this->files->makeDirectory($this->getTraitsDirectory(), 0755);
    	}

        if( ! $this->files->isDirectory($this->getInterfacesDirectory() . '/Base')) {
            $this->files->makeDirectory($this->getInterfacesDirectory() . '/Base', 0755);
        }

        if( ! $this->files->exists($this->getInterfacesDirectory() . '/Base/BaseRepositoryInterface.php')) {
            $this->addBaseInterfaceFile();
        }
    }

    protected function getRepositoryName()
    {
    	return $this->argument('name');
    }

    /**
     * Return the Interface name
     *
     * @return mixed
     */
    protected function getInterfaceName()
    {
    	if( ! is_null($interface_name = $this->option('interface'))) {
    		return ucwords(Str::plural(Str::camel($interface_name))) . 'RepositoryInterface';
    	}

    	return ucwords(Str::plural(Str::camel($this->getRepositoryName()))) . 'RepositoryInterface';
    }

    protected function addBaseInterfaceFile()
    {
    	$this->files->put($this->getBaseInterfacePath(), $this->generateBaseInterfaceFile());
    }

    /**
     * Add new Interface Repository
     *
     * @return void
     */
    protected function addInterfaceFile()
    {
        $this->files->put($this->getInterfacePath(), $this->generateInterfaceFile());

        $this->info('Interface generated successfully');
    }

    protected function generateBaseInterfaceFile()
    {
        $stub = $this->files->get($this->getBaseInterfaceStub());

        $this->replaceNamespace($stub, config('generator.repositories_directory') . "\Interfaces\Base");

        return $stub;
    }

    /**
     * Generate Interface file
     *
     * @return void
     */
    public function generateInterfaceFile()
    {
        $stub = $this->files->get($this->getInterfaceStub());

        $this->replaceNamespace($stub, config('generator.repositories_directory') . "\Interfaces")
            ->replaceInterfaceDirectory($stub)
            ->replaceInterfaceClassName($stub);

        return $stub;
    }

    /**
     * Add new Trait Repository
     *
     * @return void
     */
    protected function addTraitFile()
    {
        $this->files->put($this->getTraitPath(), $this->generateTraitFile());

        $this->info('Trait generated successfully');
    }

    /**
     * Generate Trait file
     *
     * @return void
     */
    protected function generateTraitFile()
    {
        $stub = $this->files->get($this->getTraitStub());

        $this->replaceNamespace($stub, config('generator.repositories_directory') . "\Traits")
            ->replaceTraitClassName($stub);

        return $stub;
    }

    /**
     * Return Trait stub
     *
     * @return string
     */
    protected function getTraitStub()
    {
        return __DIR__ . '/../stubs/trait.stub';
    }

    /**
     * Return Interface stub
     *
     * @return string
     */
    protected function getInterfaceStub()
    {
        return __DIR__ . '/../stubs/interface.stub';
    }

    /**
     * Return Interface stub
     *
     * @return string
     */
    protected function getBaseInterfaceStub()
    {
        return __DIR__ . '/../stubs/base-interface.stub';
    }

    protected function replaceNamespace(&$stub, $directory = '')
    {
        $stub = str_replace('{{Namespace}}', $this->getAppNamespace() . $directory, $stub);

        return $this;
    }

    protected function replaceInterfaceDirectory(&$stub)
    {
        $stub = str_replace('{{InterfaceDirectory}}', config('generator.repositories_directory') . '\Interfaces', $stub);

        return $this;
    }

    /**
     * Replace the class name in the stub.
     *
     * @param  string $stub
     * @return $this
     */
    protected function replaceInterfaceClassName(&$stub)
    {
        $className = ucwords(Str::camel($this->getInterfaceName()));
        
        $stub = str_replace('{{InterfaceName}}', $className, $stub);
        
        return $this;
    }

    /**
     * Replace the class name in the stub.
     *
     * @param  string $stub
     * @return $this
     */
    protected function replaceTraitClassName(&$stub)
    {
        $className = ucwords(Str::camel($this->getTraitName()));
        
        $stub = str_replace('{{TraitName}}', $className, $stub);
        
        return $this;
    }

    /**
     * Return the Trait name
     *
     * @return mixed
     */
    protected function getTraitName()
    {
    	if( ! is_null($trait_name = $this->option('trait'))) {
    		return ucwords(Str::plural(Str::camel($trait_name))) . 'Trait';
    	}

    	return ucwords(Str::plural(Str::camel($this->getRepositoryName()))) . 'Trait';
    }

    /**
     * Get the trait path to where we should store the service
     *
     * @return string
     */
    protected function getTraitPath()
    {
        return $this->getTraitsDirectory() . '/' . $this->getTraitName() . '.php';
    }

    /**
     * Get the trait path to where we should store the service
     *
     * @return string
     */
    protected function getInterfacePath()
    {
        return $this->getInterfacesDirectory() . '/' . $this->getInterfaceName() . '.php';
    }

    /**
     * Get the trait path to where we should store the service
     *
     * @return string
     */
    protected function getBaseInterfacePath()
    {
        return $this->getInterfacesDirectory() . '/Base/BaseRepositoryInterface.php';
    }

	/**
     * Get the application namespace.
     *
     * @return string
     */
    protected function getAppNamespace()
    {
        return Container::getInstance()->getNamespace();
    }

}