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
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_loyalty_account_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['earn', 'redeem', 'expire', 'adjust', 'bonus', 'refund']);
            $table->integer('points'); // Positive for earn, negative for redeem/expire
            $table->integer('balance_after');
            $table->string('description');
            $table->unsignedBigInteger('shopify_order_id')->nullable();
            $table->string('order_name')->nullable(); // e.g., #1001
            $table->decimal('order_amount', 10, 2)->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps();
            
            $table->index('shopify_order_id');
            $table->index('type');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_transactions');
    }
};
