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
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->text('name');
            $table->text('handle')->unique();
            $table->text('status')->default('active');
            $table->text('default_currency')->default('USD');
            $table->text('default_locale')->default('en');
            $table->text('timezone')->default('UTC');
            $table->timestamps();

            $table->index('organization_id');
            $table->index('status');
        });

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_store_status_insert
            BEFORE INSERT ON stores
            BEGIN
                SELECT CASE
                    WHEN NEW.status NOT IN ('active', 'suspended')
                    THEN RAISE(ABORT, 'Invalid store status')
                END;
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_store_status_update
            BEFORE UPDATE ON stores
            BEGIN
                SELECT CASE
                    WHEN NEW.status NOT IN ('active', 'suspended')
                    THEN RAISE(ABORT, 'Invalid store status')
                END;
            END;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS check_store_status_insert');
        DB::statement('DROP TRIGGER IF EXISTS check_store_status_update');
        Schema::dropIfExists('stores');
    }
};
