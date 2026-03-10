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
        Schema::create('loyalty_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id'); // FIXED: Use unsignedBigInteger
            $table->boolean('is_enabled')->default(true);
            $table->integer('points_per_dollar')->default(10);
            $table->integer('points_value_cents')->default(10);
            $table->integer('min_points_redemption')->default(100);
            $table->integer('points_expiry_days')->nullable();
            $table->boolean('signup_bonus_enabled')->default(true);
            $table->integer('signup_bonus_points')->default(100);
            $table->boolean('birthday_bonus_enabled')->default(true);
            $table->integer('birthday_bonus_points')->default(200);
            $table->timestamps();
            

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_settings');
    }
};
