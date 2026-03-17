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
        Schema::create('project_gateways', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('gateway_name'); // stripe, bkash, sslcommerz
            $table->text('credentials')->nullable(); // stored as encrypted JSON
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            // A project can only configure a specific gateway once
            $table->unique(['project_id', 'gateway_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_gateways');
    }
};
