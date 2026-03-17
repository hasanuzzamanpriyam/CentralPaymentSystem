<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Merchant credentials will be handled inside projects and project_gateways now
        Schema::dropIfExists('merchant_credentials');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We don't really construct the old table on rollback here, 
        // as this architectural change is permanent for Phase 8.
    }
};
