<?php

namespace DevDanno\LaravelRepositoryPattern;

use Illuminate\Support\ServiceProvider;

/**
 * This file is part of the Laravel Repository Pattern package.
 *
 * @author JcB <> (C)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class LRepositoryPatternServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/laravel-repository-pattern.php' => config_path('laravel-repository-pattern.php'),
        ], 'repository-pattern-config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/laravel-repository-pattern.php',
            'laravel-repository-pattern'
        );

        $this->commands(MakeInterface::class);
        $this->commands(MakeModel::class);
        $this->commands(MakeResponse::class);
    }
}
