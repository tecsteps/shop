<?php

use App\Support\Database\SqliteEnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('title');
            $table->string('handle');
            $table->text('description_html')->nullable();
            $table->string('type')->default('manual');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['store_id', 'handle'], 'idx_collections_store_handle');
            $table->index('store_id', 'idx_collections_store_id');
            $table->index(['store_id', 'status'], 'idx_collections_store_status');
        });

        SqliteEnumCheck::add('collections', 'type', ['manual', 'automated']);
        SqliteEnumCheck::add('collections', 'status', ['draft', 'active', 'archived']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
