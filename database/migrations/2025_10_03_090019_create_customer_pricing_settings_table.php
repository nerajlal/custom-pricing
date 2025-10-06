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
        Schema::create('customer_pricing_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->unsignedBigInteger('shopify_customer_id');
            $table->string('customer_email');
            $table->boolean('is_custom_pricing_enabled')->default(false);
            $table->timestamps();

            $table->unique(['store_id', 'shopify_customer_id'], 'unique_customer_per_store');
            $table->index('customer_email', 'idx_customer_email');
            $table->index('is_custom_pricing_enabled', 'idx_customer_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_pricing_settings');
    }
};
