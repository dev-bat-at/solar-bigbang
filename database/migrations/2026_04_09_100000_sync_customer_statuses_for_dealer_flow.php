<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('customers')
            ->where('status', 'active')
            ->update(['status' => 'new', 'lock_reason' => null]);

        DB::table('customers')
            ->whereIn('status', ['inactive', 'locked'])
            ->update(['status' => 'processing']);
    }

    public function down(): void
    {
        DB::table('customers')
            ->where('status', 'new')
            ->update(['status' => 'active']);

        DB::table('customers')
            ->where('status', 'processing')
            ->update(['status' => 'inactive']);

        DB::table('customers')
            ->where('status', 'completed')
            ->update(['status' => 'active']);
    }
};
