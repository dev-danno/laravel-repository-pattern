<?php

namespace DevDanno\LaravelRepositoryPattern;

use Illuminate\Console\GeneratorCommand;

class MakeInterface extends GeneratorCommand
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
    protected $signature = 'make:interface {name : Create a interface} {--repository : Create interface with repository class and resources}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new interface and optional repository class and resources';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Interface';


    /**
     * @return string
     */
    protected function getStub(): string
    {
        return self::STUB_PATH . 'interface.stub';
    }

    /**
     * @return string
     */
    protected function getInterfaceRepositoryStub(): string
    {
        return self::STUB_PATH . 'interface.repository.stub';
    }

    /**
     * @return string
     */
    protected function getRepositoryStub(): string
    {
        return self::STUB_PATH . 'repository.stub';
    }

    /**
     * @return string
     */
    protected function getRepositoryProviderStub(): string
    {
        return self::STUB_PATH . 'repository-provider.stub';
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
        if ($this->isReservedName($this->getNameInput())) {
            $this->error('The name "' . $this->getNameInput() . '" is reserved by PHP.');

            return false;
        }

        $name = $this->qualifyClass($this->getNameInput());

        $path = $this->getPath($name);

        if ((! $this->hasOption('force') || ! $this->option('force')) && $this->alreadyExists($this->getNameInput())) {
            $this->error($this->type . ' already exists!');
            return false;
        }
        $this->makeDirectory($path);
        $hasRepository = $this->option('repository');

        $this->files->put(
            $path,
            $this->sortImports(
                $this->buildInterface($name, $hasRepository)
            )
        );
        $message = $this->type;

        // If the option for repository exists
        if ($hasRepository) {
            $repositoryName = $this->getNameInput() . 'Repository.php';
            $repositoryPath = str_replace($this->getNameInput() . '.php', '../Repositories/', $path);

            $this->makeDirectory($repositoryPath . $repositoryName);

            $this->files->put(
                $repositoryPath . $repositoryName,
                $this->sortImports(
                    $this->buildInterfaceRepositoryClass($this->getNameInput())
                )
            );

            $message .= ' and Repository with resources';

            $this->registerBinding($this->getNameInput());
        }
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
    protected function buildInterface(string $name, $hasRepository): string
    {
        $stub = $this->files->get(
            $hasRepository ? $this->getStub() : $this->getStub()
        );

        return $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);
    }

    /**
     * Build the optional classes with the given name.
     *
     * @param string $name
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildInterfaceRepositoryClass(string $name): string
    {
        $stub = $this->files->get($this->getInterfaceRepositoryStub());

        return $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);
    }

    /**
     * Register binding in custom ServiceProvider
     *
     * @param string $name
     * @return void
     */
    protected function registerBinding(string $name): void
    {
        $interface = "App\\Interfaces\\{$name}";
        $implementation = "App\\Repositories\\{$name}Repository";
        $providerPath = app_path('Providers/RepositoryServiceProvider.php');

        if (!file_exists($providerPath)) {
            $stub = file_get_contents($this->getRepositoryProviderStub());
            file_put_contents($providerPath, $stub);
            $this->info("RepositoryServiceProvider.php created.");
            $this->registerCustomProvider();
        }

        $content = file_get_contents($providerPath);
        $binding = "\$this->app->bind(\\{$interface}::class, \\{$implementation}::class);";

        if (str_contains($content, $binding)) {
            $this->warn("Binding already exists in RepositoryServiceProvider.");
            return;
        }
        // Replace marker: binding + marker
        $updatedContent = str_replace(
            '// bindings',
            "{$binding}\n        // bindings",
            $content
        );

        file_put_contents($providerPath, $updatedContent);
        $this->info("Binding added to RepositoryServiceProvider.");
    }

    /**
     * Register custom service provider
     *
     * @return void
     */
    protected function registerCustomProvider(): void
    {
        $laravelVersion = app()->version();
        $providerClass = "App\\Providers\\RepositoryServiceProvider::class";
        // Laravel version 11 or greater, add in bootstrap/providers.php
        if (version_compare($laravelVersion, '11.0', '>=')) {
            $configPath = base_path('bootstrap/providers.php');
            if (!file_exists($configPath)) {
                $this->warn("bootstrap/providers.php not found.");
                return;
            }
            $content = file_get_contents($configPath);
            // Already register
            if (str_contains($content, $providerClass)) {
                $this->warn("RepositoryServiceProvider is already registered in bootstrap/providers.php.");
                return;
            }
            // Find line return
            $arrayStartPos = strpos($content, 'return [');
            if ($arrayStartPos === false) {
                $this->error("Could not find the 'return [' in bootstrap/providers.php.");
                return;
            }
            // Search array closing
            $arrayEndPos = strpos($content, '];', $arrayStartPos);
            if ($arrayEndPos === false) {
                $this->error("Could not find the closing '];' in bootstrap/providers.php.");
                return;
            }
            // Extract array and add new provider
            $beforeArray = substr($content, 0, $arrayEndPos);
            $afterArray = substr($content, $arrayEndPos);
            // Add provider at the end
            $newContent = $beforeArray . "\n    {$providerClass}," . "\n" . $afterArray;
            // Write new content in file
            file_put_contents($configPath, $newContent);
            $this->info("RepositoryServiceProvider registered in bootstrap/providers.php.");
        } else {
            // Laravel version < 11, add in config/app.php
            $configPath = config_path('app.php');
            if (!file_exists($configPath)) {
                $this->warn("config/app.php not found.");
                return;
            }
            $content = file_get_contents($configPath);
            if (str_contains($content, $providerClass)) {
                $this->warn("RepositoryServiceProvider is already registered in config/app.php.");
                return;
            }
            // Search providers array
            $pattern = '/(\'providers\'\s*=>\s*\[)(.*?)(^\s*\],)/ms';
            if (preg_match($pattern, $content, $matches)) {
                $providersContent = $matches[1];
                // Add provider at the end
                $newProvidersContent = rtrim($providersContent) . "\n        {$providerClass},";
                $newContent = str_replace($matches[1], $newProvidersContent, $content);
                file_put_contents($configPath, $newContent);
                $this->info("RepositoryServiceProvider registered in config/app.php.");
            } else {
                $this->error("Could not find the providers array in config/app.php.");
            }
        }
    }

    /**
     * @param $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Interfaces';
    }
}
