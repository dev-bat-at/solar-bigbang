<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'province_id')) {
                $table->foreignId('province_id')
                    ->nullable()
                    ->after('dealer_id')
                    ->constrained('provinces')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'province_id')) {
                $table->dropForeign(['province_id']);
                $table->dropColumn('province_id');
            }
        });
    }
};
