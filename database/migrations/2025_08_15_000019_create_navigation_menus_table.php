<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('navigation_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('handle');
            $table->string('title');
            $table->timestamps();

            $table->unique(['store_id', 'handle']);
            $table->index('store_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('navigation_menus');
    }
};
