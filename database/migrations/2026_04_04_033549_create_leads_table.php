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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('dealer_id')->nullable()->constrained()->onDelete('set null');
            $table->string('status')->default('new'); // new, assigned, contacting, quoted, negotiating, won, lost, expired, reopened
            $table->string('source')->nullable();
            $table->string('province_name')->nullable();
            $table->decimal('estimated_value', 15, 2)->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
