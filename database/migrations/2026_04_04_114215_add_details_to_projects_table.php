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
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('system_type_id')->nullable()->constrained('system_types')->nullOnDelete();
            $table->string('address')->nullable();
            $table->date('completion_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['system_type_id']);
            $table->dropColumn(['system_type_id', 'address', 'completion_date']);
        });
    }
};
