<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\UserRepository;

use App\Repositories\Interfaces\SettingRepositoryInterface;
use App\Repositories\SettingRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(SettingRepositoryInterface::class, SettingRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
