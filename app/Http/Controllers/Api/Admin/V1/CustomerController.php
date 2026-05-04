<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V1\CustomerResource;
use App\Models\Customer;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    public function index(Request $request, Store $store): AnonymousResourceCollection
    {
        $this->authorizeStore($request, $store);

        $validated = $request->validate([
            'query' => ['nullable', 'string', 'max:255'],
            'marketing_opt_in' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $customers = Customer::withoutGlobalScopes()
            ->withCount('orders')
            ->withSum('orders as total_spent_amount', 'total_amount')
            ->where('store_id', $store->getKey())
            ->when(data_get($validated, 'query'), function (Builder $query, string $search): void {
                $like = '%'.$search.'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query
                        ->where('email', 'like', $like)
                        ->orWhere('name', 'like', $like);
                });
            })
            ->when(array_key_exists('marketing_opt_in', $validated), fn (Builder $query) => $query->where('marketing_opt_in', (bool) $validated['marketing_opt_in']))
            ->latest('created_at')
            ->latest('id')
            ->paginate((int) data_get($validated, 'per_page', 25));

        return CustomerResource::collection($customers);
    }

    public function show(Request $request, Store $store, Customer $customer): CustomerResource
    {
        $this->authorizeStore($request, $store);
        $this->abortUnlessCustomerBelongsToStore($customer, $store);

        return CustomerResource::make($this->loadCustomer($customer));
    }

    private function authorizeStore(Request $request, Store $store): void
    {
        if (! $request->attributes->has('admin_api_oauth_token')) {
            abort_unless($request->user()?->stores()->whereKey($store->getKey())->exists(), 403);
        }

        app()->instance('current_store', $store);
    }

    private function abortUnlessCustomerBelongsToStore(Customer $customer, Store $store): void
    {
        abort_unless((int) $customer->store_id === $store->getKey(), 404);
    }

    private function loadCustomer(Customer $customer): Customer
    {
        return $customer->load([
            'addresses',
            'orders' => fn ($query) => $query->latest('placed_at')->limit(10),
        ])
            ->loadCount('orders')
            ->loadSum('orders as total_spent_amount', 'total_amount');
    }
}
