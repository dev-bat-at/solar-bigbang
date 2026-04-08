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
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'province_id')) {
                $table->dropForeign(['province_id']);
                $table->dropColumn('province_id');
            }
            if (!Schema::hasColumn('customers', 'system_type_id')) {
                $table->unsignedBigInteger('system_type_id')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('customers', 'contact_time')) {
                $table->string('contact_time')->nullable()->after('system_type_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Revert changes if needed
        });
    }
};
