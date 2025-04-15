<?php

namespace DevDanno\LaravelRepositoryPattern;

use Illuminate\Console\GeneratorCommand;

class MakeResponse extends GeneratorCommand
{
    /**
     * STUB_PATH.
     */
    const STUB_PATH = __DIR__ . '/Stubs/';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:response';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a class for the API Response';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Api Response Class';


    /**
     * @return string
     */
    protected function getStub(): string
    {
        return self::STUB_PATH . 'responseapi.stub';
    }

    /**
     * Execute the console command.
     *
     * @return bool|null
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @see \Illuminate\Console\GeneratorCommand
     *
     */
    public function handle()
    {
        if ($this->isReservedName("ApiResponseHelper")) {
            $this->error('The name ApiResponseHelper is reserved by PHP.');
            return false;
        }

        $name = $this->qualifyClass("ApiResponseHelper");

        $path = $this->getPath($name);

        if ((! $this->hasOption('force') || ! $this->option('force')) && $this->alreadyExists("ApiResponseHelper")) {
            $this->error($this->type . ' already exists!');
            return false;
        }
        $this->makeDirectory($path);

        $this->files->put(
            $path,
            $this->sortImports(
                $this->buildResponseApiClass($name)
            )
        );
        $message = $this->type;
        $this->info($message . ' created successfully.');
    }

    /**
     * Build the class with the given name.
     *
     * @param string $name
     * @param $hasRepository
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildResponseApiClass(string $name): string
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);
    }

    /**
     * @param $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Classes';
    }
}
