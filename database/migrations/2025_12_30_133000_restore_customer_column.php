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
        Schema::table('custom_prices', function (Blueprint $table) {
            if (!Schema::hasColumn('custom_prices', 'customer_pricing_setting_id')) {
                $table->unsignedBigInteger('customer_pricing_setting_id')->nullable()->after('pricing_tier_id');
                $table->foreign('customer_pricing_setting_id')->references('id')->on('customer_pricing_settings')->onDelete('cascade');
            }
            
            // Make pricing_tier_id nullable if it isn't already (it might be required currently)
            $table->unsignedBigInteger('pricing_tier_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_prices', function (Blueprint $table) {
            // We don't strictly want to reverse this as we want to keep the column moving forward,
            // but for correctness:
            $table->dropForeign(['customer_pricing_setting_id']);
            $table->dropColumn('customer_pricing_setting_id');
            // Revert pricing_tier_id to nullable? Or required? 
            // Better to leave it nullable in down for safety.
        });
    }
};
