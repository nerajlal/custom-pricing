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
        // Add price_rule_id column if it doesn't exist
        if (!Schema::hasColumn('point_redemptions', 'price_rule_id')) {
            Schema::table('point_redemptions', function (Blueprint $table) {
                $table->unsignedBigInteger('price_rule_id')->nullable()->after('coupon_code');
                $table->index('price_rule_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('point_redemptions', 'price_rule_id')) {
            Schema::table('point_redemptions', function (Blueprint $table) {
                $table->dropIndex(['price_rule_id']);
                $table->dropColumn('price_rule_id');
            });
        }
    }
};
