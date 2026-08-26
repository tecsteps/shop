<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('checkout_id')->nullable()->after('id')->constrained('checkouts')->nullOnDelete();
            $table->unique('checkout_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['checkout_id']);
            $table->dropConstrainedForeignId('checkout_id');
        });
    }
};
