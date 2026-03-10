<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('loyalty_settings', function (Blueprint $table) {
            $table->boolean('allow_all_customers')->default(false)->after('is_enabled');
        });

        Schema::table('customer_loyalty_accounts', function (Blueprint $table) {
            $table->boolean('is_enabled')->default(true)->after('customer_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loyalty_settings', function (Blueprint $table) {
            $table->dropColumn('allow_all_customers');
        });

        Schema::table('customer_loyalty_accounts', function (Blueprint $table) {
            $table->dropColumn('is_enabled');
        });
    }
};
