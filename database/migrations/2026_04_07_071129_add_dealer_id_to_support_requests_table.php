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
        Schema::table('support_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('dealer_id')->nullable()->after('system_type_id');
            $table->index('dealer_id');
        });
    }

    public function down(): void
    {
        Schema::table('support_requests', function (Blueprint $table) {
            $table->dropColumn('dealer_id');
        });
    }
};
