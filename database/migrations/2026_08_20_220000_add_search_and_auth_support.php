<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        DB::statement('CREATE VIRTUAL TABLE products_fts USING fts5(product_id UNINDEXED, store_id UNINDEXED, title, description, vendor, product_type, tags)');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS products_fts');
        Schema::dropIfExists('customer_password_reset_tokens');
        Schema::dropIfExists('personal_access_tokens');
    }
};
