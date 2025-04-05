<?php

namespace App\Providers;

use URL;
use Livewire\Livewire;
use App\Services\AccountService;
use Filament\Resources\Resource;
use App\Contracts\AccountHandler;
use Spatie\Health\Facades\Health;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Spatie\CpuLoadHealthCheck\CpuLoadCheck;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Checks\Checks\DatabaseConnectionCountCheck;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

class AppServiceProvider extends ServiceProvider
{

    public array $bindings = [
        AccountHandler::class => AccountService::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        Resource::scopeToTenant(false);
        if (App::environment('production', 'development'))
    {
        URL::forceScheme('https');
    }

        // Health::checks([
        //     OptimizedAppCheck::new(),
        //     DebugModeCheck::new(),
        //     EnvironmentCheck::new(),
        //     UsedDiskSpaceCheck::new(),
        //     DatabaseCheck::new(),
        //     QueueCheck::new(),
        //     CpuLoadCheck::new()
        //         ->failWhenLoadIsHigherInTheLast5Minutes(2.0)
        //         ->failWhenLoadIsHigherInTheLast15Minutes(1.5),
        //     DatabaseConnectionCountCheck::new()
        //         ->failWhenMoreConnectionsThan(100),
        //     CacheCheck::new(),
        // ]);

        \Livewire::setUpdateRoute(function ($handle) {
            $path = config('app.path').'/livewire/update';
            return \Route::post($path, $handle)->middleware('web');
        });
        \URL::forceRootUrl(config('app.url'));
        //\URL::forceScheme(config('app.scheme','http'));// added name to route
        Table::configureUsing(function (Table $table): void {
         $table
        
        ->paginationPageOptions([10, 25, 50]);
});
    }
}
