<?php

namespace App\Livewire\Admin;

use App\Models\Store;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

abstract class AdminComponent extends Component
{
    public function boot(): void
    {
        if (request()->isMethod('GET') || ! app()->bound('current_store')) {
            return;
        }

        $store = app('current_store');
        $status = $store->status instanceof \BackedEnum ? $store->status->value : $store->status;
        abort_unless($status === 'active', 403, 'This store is suspended.');
    }

    protected function currentStore(): Store
    {
        abort_unless(app()->bound('current_store'), 404, 'Store not found.');

        /** @var Store $store */
        $store = app('current_store');

        return $store;
    }

    protected function adminUser(): User
    {
        /** @var User|null $user */
        $user = Auth::guard('web')->user();
        abort_unless($user, 401);

        return $user;
    }

    protected function authorizeAction(string $ability, mixed $arguments): void
    {
        Gate::forUser($this->adminUser())->authorize($ability, $arguments);
    }

    /** @param list<string> $roles */
    protected function requireRoles(array $roles): void
    {
        $role = $this->adminUser()->roleForStore($this->currentStore())?->value;

        throw_unless(in_array($role, $roles, true), AuthorizationException::class);
    }

    /** @param list<array{label: string, url?: string}> $breadcrumbs */
    protected function admin(View $view, string $title, array $breadcrumbs = []): View
    {
        $data = [
            'adminStore' => $this->currentStore(),
            'adminUser' => $this->adminUser(),
            'adminRole' => $this->adminUser()->roleForStore($this->currentStore())?->value,
            'breadcrumbs' => $breadcrumbs,
        ];

        return $view->with($data)->layout('admin.layouts.app', ['title' => $title, ...$data]);
    }

    protected function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('toast', type: $type, message: $message);
    }

    protected function enumValue(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
