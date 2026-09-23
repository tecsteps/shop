<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('analytics_events', 'properties_json')) {
            Schema::table('analytics_events', function (Blueprint $table): void {
                $table->json('properties_json')->default('{}');
            });
        }

        if (! Schema::hasColumn('analytics_events', 'occurred_at')) {
            Schema::table('analytics_events', function (Blueprint $table): void {
                $table->timestamp('occurred_at')->nullable();
            });
        }

        if (! Schema::hasColumn('analytics_daily', 'checkout_completed_count')) {
            Schema::table('analytics_daily', function (Blueprint $table): void {
                $table->unsignedInteger('checkout_completed_count')->default(0);
            });
        }

        DB::table('analytics_events')
            ->whereNotNull('payload')
            ->update(['properties_json' => DB::raw('payload')]);
        DB::table('analytics_events')
            ->whereNull('occurred_at')
            ->update(['occurred_at' => DB::raw('created_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('analytics_daily', 'checkout_completed_count')) {
            Schema::table('analytics_daily', function (Blueprint $table): void {
                $table->dropColumn('checkout_completed_count');
            });
        }

        if (Schema::hasColumn('analytics_events', 'occurred_at')) {
            Schema::table('analytics_events', function (Blueprint $table): void {
                $table->dropColumn('occurred_at');
            });
        }

        if (Schema::hasColumn('analytics_events', 'properties_json')) {
            Schema::table('analytics_events', function (Blueprint $table): void {
                $table->dropColumn('properties_json');
            });
        }
    }
};
