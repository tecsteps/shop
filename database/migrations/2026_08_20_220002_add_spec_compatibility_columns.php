<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->longText('description_html')->nullable();
        });
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->string('currency', 3)->nullable();
            $table->unsignedInteger('weight_g')->nullable();
            $table->string('status')->default('active')->index();
        });
        Schema::table('product_media', function (Blueprint $table): void {
            $table->string('type')->default('image');
            $table->string('storage_key')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('byte_size')->nullable();
            $table->string('checksum')->nullable();
        });
        Schema::table('shipping_rates', function (Blueprint $table): void {
            $table->foreignId('zone_id')->nullable()->constrained('shipping_zones')->nullOnDelete();
        });
        Schema::table('tax_settings', function (Blueprint $table): void {
            $table->string('provider')->default('none');
            $table->boolean('prices_include_tax')->default(false);
            $table->json('config_json')->nullable();
        });
        Schema::table('checkouts', function (Blueprint $table): void {
            $table->foreignId('shipping_method_id')->nullable()->constrained('shipping_rates')->nullOnDelete();
        });
        Schema::table('order_lines', function (Blueprint $table): void {
            $table->string('title_snapshot')->nullable();
            $table->string('sku_snapshot')->nullable();
        });
        Schema::table('refunds', function (Blueprint $table): void {
            $table->string('provider_refund_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table): void {
            $table->dropColumn('provider_refund_id');
        });
        Schema::table('order_lines', function (Blueprint $table): void {
            $table->dropColumn(['title_snapshot', 'sku_snapshot']);
        });
        Schema::table('checkouts', function (Blueprint $table): void {
            $table->dropForeign(['shipping_method_id']);
            $table->dropColumn('shipping_method_id');
        });
        Schema::table('tax_settings', function (Blueprint $table): void {
            $table->dropColumn(['provider', 'prices_include_tax', 'config_json']);
        });
        Schema::table('shipping_rates', function (Blueprint $table): void {
            $table->dropForeign(['zone_id']);
            $table->dropColumn('zone_id');
        });
        Schema::table('product_media', function (Blueprint $table): void {
            $table->dropColumn(['type', 'storage_key', 'mime_type', 'byte_size', 'checksum']);
        });
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropColumn(['currency', 'weight_g', 'status']);
        });
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('description_html');
        });
    }
};
