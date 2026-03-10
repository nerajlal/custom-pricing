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
        Schema::create('loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Bronze, Silver, Gold, Platinum
            $table->integer('min_points_required')->default(0);
            $table->integer('points_multiplier')->default(100); // 100 = 1x, 150 = 1.5x
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->string('color')->default('#gray'); // For UI display
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_tiers');
    }
};
