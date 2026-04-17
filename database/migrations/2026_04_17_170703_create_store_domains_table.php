<?php

use App\Enums\StoreDomainType;
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
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('hostname')->unique('idx_store_domains_hostname');
            $table->string('type')->default(StoreDomainType::Storefront->value);
            $table->unsignedTinyInteger('is_primary')->default(0);
            $table->string('tls_mode')->default('managed');
            $table->timestamp('created_at')->nullable();

            $table->index('store_id', 'idx_store_domains_store_id');
            $table->index(['store_id', 'is_primary'], 'idx_store_domains_store_primary');
        });

        $types = collect(StoreDomainType::values())->map(fn (string $v): string => "'".$v."'")->implode(',');
        $tls = "'managed','bring_your_own'";

        DB::statement("CREATE TRIGGER store_domains_type_check_insert BEFORE INSERT ON store_domains FOR EACH ROW WHEN NEW.type NOT IN ({$types}) BEGIN SELECT RAISE(ABORT, 'invalid type'); END");
        DB::statement("CREATE TRIGGER store_domains_type_check_update BEFORE UPDATE ON store_domains FOR EACH ROW WHEN NEW.type NOT IN ({$types}) BEGIN SELECT RAISE(ABORT, 'invalid type'); END");
        DB::statement("CREATE TRIGGER store_domains_tls_check_insert BEFORE INSERT ON store_domains FOR EACH ROW WHEN NEW.tls_mode NOT IN ({$tls}) BEGIN SELECT RAISE(ABORT, 'invalid tls_mode'); END");
        DB::statement("CREATE TRIGGER store_domains_tls_check_update BEFORE UPDATE ON store_domains FOR EACH ROW WHEN NEW.tls_mode NOT IN ({$tls}) BEGIN SELECT RAISE(ABORT, 'invalid tls_mode'); END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS store_domains_type_check_insert');
        DB::statement('DROP TRIGGER IF EXISTS store_domains_type_check_update');
        DB::statement('DROP TRIGGER IF EXISTS store_domains_tls_check_insert');
        DB::statement('DROP TRIGGER IF EXISTS store_domains_tls_check_update');
        Schema::dropIfExists('store_domains');
    }
};
