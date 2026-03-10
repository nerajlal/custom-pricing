<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('gdpr_requests', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'data_request', 'customer_redact', 'shop_redact'
            $table->string('shop_domain');
            $table->string('customer_id')->nullable();
            $table->json('payload')->nullable();
            $table->enum('status', ['pending', 'processed', 'failed'])->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->index(['shop_domain', 'type']);
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('gdpr_requests');
    }
};
