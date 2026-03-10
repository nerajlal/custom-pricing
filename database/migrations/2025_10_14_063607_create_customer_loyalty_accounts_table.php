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
        Schema::create('customer_loyalty_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('shopify_customer_id');
            $table->string('customer_email');
            $table->integer('total_points_earned')->default(0);
            $table->integer('current_points_balance')->default(0);
            $table->integer('points_redeemed')->default(0);
            $table->foreignId('current_tier_id')->nullable()->constrained('loyalty_tiers')->nullOnDelete();
            $table->date('birthday')->nullable();
            $table->boolean('birthday_bonus_claimed_this_year')->default(false);
            $table->timestamps();
            
            $table->unique(['store_id', 'shopify_customer_id']);
            $table->index('customer_email');
            $table->index('current_points_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_loyalty_accounts');
    }
};
