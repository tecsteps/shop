<?php

use App\Support\Database\SqliteEnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_settings', function (Blueprint $table) {
            $table->foreignId('store_id')->primary()->constrained('stores')->cascadeOnDelete();
            $table->string('mode')->default('manual');
            $table->string('provider')->default('none');
            $table->integer('prices_include_tax')->default(0);
            $table->text('config_json')->default('{}');
        });

        SqliteEnumCheck::add('tax_settings', 'mode', ['manual', 'provider']);
        SqliteEnumCheck::add('tax_settings', 'provider', ['stripe_tax', 'none']);
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_settings');
    }
};
