<?php

namespace NWJeffM\Generators\Helpers;

trait GeneratorTrait {

    /**
     * Return the service directory from the configuration file
     *
     * @return string
     */
    protected function getServiceDirectoryFromConfig()
    {
        return substr(rtrim($this->getServiceDirectory(), '/\\'), 4);
    }

    /**
     * Return the repository directory from the configuration file
     *
     * @return string
     */
    protected function getRepositoryDirectoryFromConfig()
    {
        return substr(rtrim($this->getRepositoryDirectory(), '/\\'), 4);
    }

    /**
     * Return the trait directory from the configuration file
     *
     * @return $string
     */
    protected function getTraitDirectoryFromConfig()
    {
        return substr(rtrim($this->getTraitDirectory(), '/\\'), 4);
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

    /**
     * Return the service implements interface stub
     *
     * @return $stub
     */
    protected function getServiceImplementsInterfaceStub()
    {
        return __DIR__ . '/../stubs/service-implements-interface.stub';
    }

    /**
     * Create a directory
     *
     * @param  string $type
     * @return void
     */
	protected function createDirectory($type)
    {
        if(is_array($directories = $this->getDirOption(true))) {
            $function = 'get' . ucfirst($type) . 'Directory';
            $directory_array = [];
            $directory_paths = [];
            if($directories) {
                foreach($directories as $directory) {
                    array_push($directory_array, $directory);
                    array_push($directory_paths, implode('/', $directory_array));
                    if($directory_paths) {
                        foreach($directory_paths as $directory_path) {
                            if( ! $this->files->isDirectory($this->{$function}() . $directory_path)) {
                                $this->files->makeDirectory($this->{$function}() . $directory_path);
                            }
                        }
                    }
                }
            }
        }
    }

   

}