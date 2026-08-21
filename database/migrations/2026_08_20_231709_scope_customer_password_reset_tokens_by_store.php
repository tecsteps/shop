<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customer_password_reset_tokens')) {
            return;
        }

        Schema::create('customer_password_reset_tokens_scoped', function (Blueprint $table): void {
            $table->unsignedBigInteger('store_id')->nullable();
            $table->string('email');
            $table->string('token');
            $table->timestamp('created_at')->nullable();
            $table->primary(['store_id', 'email']);
            $table->index('email');
        });

        DB::table('customer_password_reset_tokens')->get()->each(function (object $token): void {
            DB::table('customer_password_reset_tokens_scoped')->insert((array) $token);
        });

        Schema::drop('customer_password_reset_tokens');
        Schema::rename('customer_password_reset_tokens_scoped', 'customer_password_reset_tokens');
    }

    public function down(): void
    {
        if (! Schema::hasTable('customer_password_reset_tokens')) {
            return;
        }

        Schema::create('customer_password_reset_tokens_legacy', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        DB::table('customer_password_reset_tokens')->select(['email', 'token', 'created_at'])->orderBy('created_at')->get()->each(function (object $token): void {
            DB::table('customer_password_reset_tokens_legacy')->insertOrIgnore((array) $token);
        });

        Schema::drop('customer_password_reset_tokens');
        Schema::rename('customer_password_reset_tokens_legacy', 'customer_password_reset_tokens');
    }
};
