<?php

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
        Schema::create('custom_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_pricing_setting_id')->constrained('customer_pricing_settings')->onDelete('cascade');
            $table->unsignedBigInteger('shopify_product_id');
            $table->unsignedBigInteger('shopify_variant_id');
            $table->string('product_title');
            $table->string('variant_title')->nullable();
            $table->decimal('original_price', 10, 2);
            $table->decimal('custom_price', 10, 2);
            $table->timestamps();

            $table->unique(['customer_pricing_setting_id', 'shopify_variant_id'], 'unique_variant_per_customer');
            $table->index(['shopify_product_id', 'shopify_variant_id'], 'idx_product_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_prices');
    }
};
