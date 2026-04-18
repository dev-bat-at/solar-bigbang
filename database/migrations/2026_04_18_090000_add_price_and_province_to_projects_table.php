<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->nullable()->after('capacity');
            $table->foreignId('province_id')
                ->nullable()
                ->after('system_type_id')
                ->constrained('provinces')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropColumn(['price', 'province_id']);
        });
    }
};
