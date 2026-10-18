<?php

namespace App\Providers;

use App\Support\Dates;
use Filament\Tables\Table;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Authenticate::redirectUsing(fn ($request) => '/login');
        RedirectIfAuthenticated::redirectUsing(fn ($request) => '/bookings');

        Table::configureUsing(fn (Table $table) => $table->defaultDateDisplayFormat(Dates::SHORT));

        // The Playwright suite (e2e/) serves the built assets in public/build,
        // even while `bun run dev` has left public/hot behind for the Docker
        // stack. Pointing Vite at a hot file that never exists does that.
        if ($this->app->environment('e2e')) {
            Vite::useHotFile(storage_path('framework/e2e-never-hot'));
        }
    }
}
