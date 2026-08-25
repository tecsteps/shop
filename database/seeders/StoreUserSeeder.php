<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreUserSeeder extends Seeder
{
    /**
     * Link platform users to stores with their roles.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $assignments = [
                ['email' => 'admin@acme.test', 'store' => 'acme-fashion', 'role' => 'owner'],
                ['email' => 'staff@acme.test', 'store' => 'acme-fashion', 'role' => 'staff'],
                ['email' => 'support@acme.test', 'store' => 'acme-fashion', 'role' => 'support'],
                ['email' => 'manager@acme.test', 'store' => 'acme-fashion', 'role' => 'admin'],
                ['email' => 'admin@example.com', 'store' => 'acme-fashion', 'role' => 'owner'],
                ['email' => 'admin2@acme.test', 'store' => 'acme-electronics', 'role' => 'owner'],
            ];

            foreach ($assignments as $assignment) {
                $user = User::where('email', $assignment['email'])->firstOrFail();
                $store = Store::where('handle', $assignment['store'])->firstOrFail();

                StoreUser::updateOrCreate(
                    ['store_id' => $store->id, 'user_id' => $user->id],
                    ['role' => $assignment['role']],
                );
            }
        });
    }
}
