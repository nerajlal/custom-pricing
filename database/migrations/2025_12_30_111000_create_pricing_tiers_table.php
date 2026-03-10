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
        // Drop it if it exists to clean up partial/failed previous runs
        if (Schema::hasTable('pricing_tiers')) {
            // Need to disable foreign key checks to drop if there are existing references (though unlikely yet)
            Schema::disableForeignKeyConstraints();
            Schema::drop('pricing_tiers');
            Schema::enableForeignKeyConstraints();
        }

        Schema::create('pricing_tiers', function (Blueprint $table) {
            $table->id();
            // Ensure stores table exists (fixed by previous migration)
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_tiers');
    }
};
