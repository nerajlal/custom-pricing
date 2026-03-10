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
        Schema::create('point_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_loyalty_account_id')->constrained()->onDelete('cascade');
            $table->integer('points_used');
            $table->decimal('discount_amount', 10, 2);
            $table->string('coupon_code')->unique();
            $table->boolean('is_used')->default(false);
            $table->unsignedBigInteger('shopify_order_id')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index('coupon_code');
            $table->index('is_used');
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_redemptions');
    }
};
