<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_users', 'covered_areas')) {
                $table->json('covered_areas')->nullable()->after('force_change_password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            if (Schema::hasColumn('admin_users', 'covered_areas')) {
                $table->dropColumn('covered_areas');
            }
        });
    }
};
