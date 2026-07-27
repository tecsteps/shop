<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Customer remember-me tokens (spec 06 §1.2 login with "remember"; the
     * model already hides remember_token per spec 06 §4.8).
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->text('remember_token')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn('remember_token');
        });
    }
};
