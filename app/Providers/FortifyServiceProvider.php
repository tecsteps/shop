<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
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
        $this->configureActions();
    }

    /**
     * Configure Fortify actions.
     *
     * Admin login/logout is handled by the App\Livewire\Admin\Auth components,
     * not Fortify view routes (config/fortify.php `views` is false). Fortify
     * remains responsible for the password-reset flow on the `users` broker.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
    }
}
