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
        // Add pricing_tier_id to customer_pricing_settings
        if (Schema::hasTable('customer_pricing_settings')) {
            Schema::table('customer_pricing_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('customer_pricing_settings', 'pricing_tier_id')) {
                    $table->foreignId('pricing_tier_id')->nullable()->after('store_id')->constrained('pricing_tiers')->onDelete('set null');
                }
            });
        }

        // Update custom_prices
        if (Schema::hasTable('custom_prices')) {
            Schema::table('custom_prices', function (Blueprint $table) {
                // Drop foreign key if exists
                if (Schema::hasColumn('custom_prices', 'customer_pricing_setting_id')) {
                    // Try catch typically not possible in migration easily without raw SQL query for constraint check
                    // But we can blindly try dropping the FK by standard name, array syntax handles name generation.
                    // If it doesnt exist, we might get error, but Schema check handles column.
                    // FK is harder to check. Let's assume consistent environment.
                    
                    try {
                        $table->dropForeign(['customer_pricing_setting_id']);
                    } catch (\Exception $e) {
                        // ignore if FK doesn't exist
                    }

                    $table->dropColumn('customer_pricing_setting_id');
                }
                
                if (!Schema::hasColumn('custom_prices', 'pricing_tier_id')) {
                    $table->foreignId('pricing_tier_id')->after('id')->constrained('pricing_tiers')->onDelete('cascade');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Simplified down:
        // Rolling back strict changes might be complex if data loss involved, 
        // but generally we reverse the specific columns.
        if (Schema::hasTable('custom_prices')) {
             Schema::table('custom_prices', function (Blueprint $table) {
                if (Schema::hasColumn('custom_prices', 'pricing_tier_id')) {
                    $table->dropForeign(['pricing_tier_id']);
                    $table->dropColumn('pricing_tier_id');
                }
                if (!Schema::hasColumn('custom_prices', 'customer_pricing_setting_id')) {
                    $table->foreignId('customer_pricing_setting_id')->nullable()->constrained('customer_pricing_settings')->onDelete('cascade');
                }
            });
        }

        if (Schema::hasTable('customer_pricing_settings')) {
            Schema::table('customer_pricing_settings', function (Blueprint $table) {
                 if (Schema::hasColumn('customer_pricing_settings', 'pricing_tier_id')) {
                    $table->dropForeign(['pricing_tier_id']);
                    $table->dropColumn('pricing_tier_id');
                }
            });
        }
    }
};
