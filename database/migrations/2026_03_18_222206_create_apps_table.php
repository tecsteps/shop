<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apps', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->text('status')->default('active');
            $table->timestamps();

            $table->index('status', 'idx_apps_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apps');
    }
};
