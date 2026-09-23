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
        Schema::table('theme_files', function (Blueprint $table) {
            $table->string('storage_key')->nullable();
            $table->string('sha256')->nullable();
            $table->unsignedInteger('byte_size')->default(0);
        });

        DB::table('theme_files')->orderBy('id')->chunkById(100, function ($files): void {
            foreach ($files as $file) {
                DB::table('theme_files')->where('id', $file->id)->update([
                    'storage_key' => 'themes/'.$file->theme_id.'/'.$file->path,
                    'sha256' => hash('sha256', $file->content),
                    'byte_size' => strlen($file->content),
                ]);
            }
        });

        Schema::table('theme_files', function (Blueprint $table) {
            $table->string('storage_key')->nullable(false)->change();
            $table->string('sha256')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('theme_files', function (Blueprint $table) {
            $table->dropColumn(['storage_key', 'sha256', 'byte_size']);
        });
    }
};
