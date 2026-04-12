<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_domains', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();
            $table->string('hostname')->unique('idx_store_domains_hostname');
            $table->string('type')->default('storefront');
            $table->boolean('is_primary')->default(false);
            $table->string('tls_mode')->default('managed');
            $table->timestamp('created_at')->nullable();

            $table->index('store_id', 'idx_store_domains_store_id');
            $table->index(['store_id', 'is_primary'], 'idx_store_domains_store_primary');
        });

        DB::statement("CREATE TRIGGER store_domains_type_check BEFORE INSERT ON store_domains FOR EACH ROW BEGIN SELECT CASE WHEN NEW.type NOT IN ('storefront','admin','api') THEN RAISE(ABORT, 'invalid type') END; END");
        DB::statement("CREATE TRIGGER store_domains_tls_check BEFORE INSERT ON store_domains FOR EACH ROW BEGIN SELECT CASE WHEN NEW.tls_mode NOT IN ('managed','bring_your_own') THEN RAISE(ABORT, 'invalid tls_mode') END; END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS store_domains_type_check');
        DB::statement('DROP TRIGGER IF EXISTS store_domains_tls_check');
        Schema::dropIfExists('store_domains');
    }
};
