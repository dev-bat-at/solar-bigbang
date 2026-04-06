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
            $table->foreignId('dealer_id')->nullable()->after('id')->constrained()->onDelete('set null');
            $table->text('address')->nullable()->after('phone');
            $table->string('lock_reason')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['dealer_id']);
            $table->dropColumn(['dealer_id', 'address', 'lock_reason']);
        });
    }
};
