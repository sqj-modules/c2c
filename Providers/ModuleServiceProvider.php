<?php

namespace SQJ\Modules\C2C\Providers;

use Caffeinated\Modules\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the module services.
     *
     * @return void
     * @throws \Caffeinated\Modules\Exceptions\ModuleNotFoundException
     */
    public function boot()
    {
        $this->loadTranslationsFrom(module_path('c2c', 'Resources/Lang', 'app'), 'c2c');
        $this->loadViewsFrom(module_path('c2c', 'Resources/Views', 'app'), 'c2c');
        $this->loadMigrationsFrom(module_path('c2c', 'Database/Migrations', 'app'), 'c2c');
        if(!Config::has('c2c'))
        {
            $this->loadConfigsFrom(module_path('c2c', 'Config', 'app'));
        }
        $this->loadFactoriesFrom(module_path('c2c', 'Database/Factories', 'app'));

        $this->publishes([
            module_path('c2c', 'Config/c2c.php') => config_path('c2c.php'),
        ]);
    }

    /**
     * Register the module services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);

        // 注册事件服务器
        $this->app->register(EventServiceProvider::class);
    }
}
