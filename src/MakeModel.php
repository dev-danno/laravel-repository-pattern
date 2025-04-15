<?php

namespace DevDanno\LaravelRepositoryPattern;

use Illuminate\Console\GeneratorCommand;

class MakeModel extends GeneratorCommand
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
    protected $signature = 'make:model
        {name : Create a model}
        {--csir : Generate Controller, Service, Interface and Repository with resources}';

    // Considerar agregar option para generar swagger y se tendría que agregar en required en composer.json
    // protected $signature = 'make:model
    //     {name : Create a model}
    //     {--csir : Generate Controller, Service, Interface and Repository with resources}
    //     {--swagger : Generate swagger documentation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new model and optional the controller, service, interface and repository class and resources';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Model';


    /**
     * @return string
     */
    protected function getStub(): string
    {
        return self::STUB_PATH . 'model.stub';
    }

    /**
     * @return string
     */
    protected function getInterfaceStub(): string
    {
        return self::STUB_PATH . 'model.interface.stub';
    }

    /**
     * @return string
     */
    protected function getInterfaceRepositoryStub(): string
    {
        return self::STUB_PATH . 'model.interface.repository.stub';
    }

    /**
     * @return string
     */
    protected function getServiceStub(): string
    {
        return self::STUB_PATH . 'model.service.stub';
    }

    /**
     * @return string
     */
    protected function getControllerStub(): string
    {
        return self::STUB_PATH . 'model.controller.stub';
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
        $hasOptions = $this->option('csir');

        $this->files->put(
            $path,
            $this->sortImports(
                $this->buildModel($name)
            )
        );
        $message = $this->type;

        // If the option exists
        if ($hasOptions) {
            // Load paths from config file
            $paths = config('laravel-repository-pattern.paths');
            // Interface
            $interfaceName = $this->getNameInput() . 'Interface.php';
            $interfacePath = app_path($paths['interface']) . '/' . $interfaceName;
            $this->makeDirectory($interfacePath);
            $this->files->put(
                $interfacePath,
                $this->sortImports(
                    $this->buildInterface($this->getNameInput())
                )
            );
            // Repository
            $repositoryName = $this->getNameInput() . 'Repository.php';
            $repositoryPath = app_path($paths['repository']) . '/' . $repositoryName;
            $this->makeDirectory($repositoryPath);
            $this->files->put(
                $repositoryPath,
                $this->sortImports(
                    $this->buildInterfaceRepositoryClass($this->getNameInput())
                )
            );
            // Service
            $serviceName = $this->getNameInput() . 'Service.php';
            $servicePath = app_path($paths['service']) . '/' . $serviceName;
            $this->makeDirectory($servicePath);
            $this->files->put(
                $servicePath,
                $this->sortImports(
                    $this->buildServiceClass($this->getNameInput())
                )
            );
            // Controller
            $controllerName = $this->getNameInput() . 'Controller.php';
            $controllerPath = app_path($paths['controller']) . '/' . $controllerName;
            $this->makeDirectory($controllerPath);
            $this->files->put(
                $controllerPath,
                $this->sortImports(
                    $this->buildControllerClass($this->getNameInput())
                )
            );

            $message .= ' with Interface, Repository, Service and Controller with resources';

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
    protected function buildModel(string $name): string
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);
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
    protected function buildInterface(string $name): string
    {
        $stub = $this->files->get($this->getInterfaceStub());

        return $this->replaceCustomNamespace($stub, "interface")->replaceClass($stub, $name);
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

        return $this->replaceCustomNamespace($stub, "repository")->replaceCustomVariables($stub)->replaceClass($stub, $name);
    }

    /**
     * Build the optional classes with the given name.
     *
     * @param string $name
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildServiceClass(string $name): string
    {
        $stub = $this->files->get($this->getServiceStub());

        return $this->replaceCustomNamespace($stub, "service")->replaceCustomVariables($stub)->replaceClass($stub, $name);
    }

    /**
     * Build the optional classes with the given name.
     *
     * @param string $name
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildControllerClass(string $name): string
    {
        $stub = $this->files->get($this->getControllerStub());

        return $this->replaceCustomNamespace($stub, "controller")->replaceCustomVariables($stub)->replaceClass($stub, $name);
    }

    /**
     * Register binding in custom ServiceProvider
     *
     * @param string $name
     * @return void
     */
    protected function registerBinding(string $name): void
    {
        $interfaceNamespace = $this->getNamespaceFromConfigPath('interface');
        $repositoryNamespace = $this->getNamespaceFromConfigPath('repository');
        $interface = "{$interfaceNamespace}\\{$name}Interface";
        $implementation = "{$repositoryNamespace}\\{$name}Repository";
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
        return $rootNamespace . '\Models';
    }

    /**
     * Replace the namespace for the given stub based on the config file.
     *
     * @param  string  $stub
     * @param  string  $type
     * @return $this
     */
    protected function replaceCustomNamespace(string &$stub, string $type): self
    {
        $searches = [
            ['DummyNamespace', 'DummyRootNamespace', 'NamespacedDummyUserModel'],
            ['{{ namespace }}', '{{ rootNamespace }}', '{{ namespacedUserModel }}'],
            ['{{namespace}}', '{{rootNamespace}}', '{{namespacedUserModel}}'],
        ];
        $namespace = $this->getNamespaceFromConfigPath($type);
        foreach ($searches as $search) {
            $stub = str_replace($search, $namespace, $stub);
        }

        return $this;
    }

    /**
     * Replace the namespace for the given stub based on the variable type.
     *
     * @param  string  $stub
     * @return $this
     */
    protected function replaceCustomVariables(string &$stub): self
    {
        $serviceNamespace = $this->getNamespaceFromConfigPath('service');
        $interfaceNamespace = $this->getNamespaceFromConfigPath('interface');
        $apiHelperNamespace = class_exists('App\\Classes\\ApiResponseHelper') ? 'use App\\Classes\\ApiResponseHelper;' : '';
        $apiHelperReturn = $this->getApiHelperReturn('$data', 'Records obtain successfully', 200);
        $apiFindReturn = $this->getApiHelperReturn('$find', 'Record obtain successfully', 200);
        $apiSavedReturn = $this->getApiHelperReturn('$saved', 'Record created successfully', 201);
        $apiUpdatedReturn = $this->getApiHelperReturn('$updated', 'Record updated successfully', 200);
        $apiDeleteReturn = $this->getApiHelperReturn('$deleted', 'Record deleted successfully', 200);
        $exceptionOnlyBlock = $this->getExceptionCatches([['type' => '\Exception', 'message' => 'Error: ', 'code' => 500]]);
        $exceptionsBlock = $this->getExceptionCatches([
            ['type' => 'ModelNotFoundException', 'message' => 'Model not found: ', 'code' => 404],
            ['type' => '\Exception', 'message' => 'Error: ', 'code' => 500],
        ]);
        $replacements = [
            '{{ serviceNamespace }}' => $serviceNamespace,
            '{{ interfaceNamespace }}' => $interfaceNamespace,
            '{{ apiHelperImport }}' => $apiHelperNamespace,
            '{{ apiHelperReturn }}' => $apiHelperReturn,
            '{{ apiFindReturn }}' => $apiFindReturn,
            '{{ apiSavedReturn }}' => $apiSavedReturn,
            '{{ apiUpdatedReturn }}' => $apiUpdatedReturn,
            '{{ apiDeleteReturn }}' => $apiDeleteReturn,
            '{{ exceptionOnlyBlock }}' => $exceptionOnlyBlock,
            '{{ exceptionsBlock }}' => $exceptionsBlock,
        ];
        foreach ($replacements as $key => $value) {
            $stub = str_replace($key, $value, $stub);
        }
        return $this;
    }

    /**
     * Set the return api response only if ApiResponseHelper exists.
     *
     * @param  string  $variable
     * @param  string  $message
     * @param  int $statusCode
     * @return string
     */
    protected function getApiHelperReturn(string $variable, string $message, int $statusCode): string
    {
        if (class_exists('App\\Classes\\ApiResponseHelper')) return "return ApiResponseHelper::sendResponse({$variable}, '{$message}', {$statusCode});";
        return "return {$variable};";
    }

    /**
     * Set the exceptions api response only if ApiResponseHelper exists.
     *
     * @param  array  $exceptions
     * @return string
     */
    protected function getExceptionCatches(array $exceptions): string
    {
        $useApiHelper = class_exists('App\\Classes\\ApiResponseHelper');
        $result = '';

        foreach ($exceptions as $e) {
            $message = $e['message'] ?? 'Error: ';
            $code = $e['code'] ?? 500;
            $type = $e['type'] ?? '\Exception';

            $response = $useApiHelper
                ? "return ApiResponseHelper::throw(\$e, \"$message\" . \$e->getMessage(), $code);"
                : "return response()->json(['error' => \"$message\" . \$e->getMessage()], $code);";

            $result .= <<<PHP
            catch ($type \$e) {
                $response
            }

            PHP;
        }

        return trim($result);
    }

    /**
     * Get the namespace based on the config file.
     *
     * @param  string  $key
     * @return string
     */
    protected function getNamespaceFromConfigPath(string $key): string
    {
        $path = config("laravel-repository-pattern.paths.$key");
        // Example: 'Http/Controllers' -> 'App\Http\Controllers'
        return 'App\\' . str_replace('/', '\\', trim($path, '/'));
    }
}
