## Laravel Repository Pattern Package
[![Packagist License](https://img.shields.io/badge/Licence-MIT-blue)](http://choosealicense.com/licenses/mit/)

This package is to implement the `Repository Pattern` with Laravel.
It includes the creation and register of a custom ServiceProvider.
It includes the binding in the ServiceProvider.
It includes the creation of Model, Interface & implementation, Service class and Controller.
Optional, includes creation and implementation of an Api Response Class.

> [!NOTE]
> This package is compatible with Laravel >= 9

> [!CAUTION]
> Use this package only if you required and plan to use a `Repository Pattern` based on Interface & Repository Class and dependency injection.

## Installation 📌

- Require the package using composer.
```bash
    composer require dev-danno/laravel-repository-pattern --dev
```

- Laravel uses Auto-Discovery, so it doesn't require to manually add the ServiceProvider.

- Publish the config file
```php
    php artisan vendor:publish --tag=repository-pattern-config
```

## Usage 🧰

> [!NOTE]
> When using `interface --repository` or `model --csir` commands, it will automatically create the custom ServiceProvider & register it.

- Api Response Class
```php
    php artisan make:response
```

- Interface with resources
```php
    php artisan make:interface
```

- Interface with resources, Repository class with resources and the corresponding binding.
```php
    php artisan make:interface --repository
```

- Model with base structure
```php
    php artisan make:model
```

- Model, Interface & Repository with resources, Service & Controller class with resources and Dependency Injection.
> [!NOTE]
> If `ApiResponseHelper` class exists, the controllers will include it in the responses.
```php
    php artisan make:model --csir
```


