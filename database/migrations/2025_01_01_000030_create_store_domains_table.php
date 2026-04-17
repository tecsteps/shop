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
        Schema::create('store_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->text('hostname')->unique();
            $table->text('type')->default('storefront');
            $table->integer('is_primary')->default(0);
            $table->text('tls_mode')->default('managed');
            $table->timestamp('created_at')->nullable();

            $table->index('store_id');
            $table->index(['store_id', 'is_primary']);
        });

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_store_domain_type_insert
            BEFORE INSERT ON store_domains
            BEGIN
                SELECT CASE
                    WHEN NEW.type NOT IN ('storefront', 'admin', 'api')
                    THEN RAISE(ABORT, 'Invalid store domain type')
                END;
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_store_domain_type_update
            BEFORE UPDATE ON store_domains
            BEGIN
                SELECT CASE
                    WHEN NEW.type NOT IN ('storefront', 'admin', 'api')
                    THEN RAISE(ABORT, 'Invalid store domain type')
                END;
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_store_domain_tls_mode_insert
            BEFORE INSERT ON store_domains
            BEGIN
                SELECT CASE
                    WHEN NEW.tls_mode NOT IN ('managed', 'bring_your_own')
                    THEN RAISE(ABORT, 'Invalid TLS mode')
                END;
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_store_domain_tls_mode_update
            BEFORE UPDATE ON store_domains
            BEGIN
                SELECT CASE
                    WHEN NEW.tls_mode NOT IN ('managed', 'bring_your_own')
                    THEN RAISE(ABORT, 'Invalid TLS mode')
                END;
            END;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS check_store_domain_type_insert');
        DB::statement('DROP TRIGGER IF EXISTS check_store_domain_type_update');
        DB::statement('DROP TRIGGER IF EXISTS check_store_domain_tls_mode_insert');
        DB::statement('DROP TRIGGER IF EXISTS check_store_domain_tls_mode_update');
        Schema::dropIfExists('store_domains');
    }
};
