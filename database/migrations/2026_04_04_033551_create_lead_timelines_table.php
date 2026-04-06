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
        Schema::create('lead_timelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->onDelete('cascade');
            $table->string('event_type')->default('system_event'); // system_event, admin_action, dealer_action
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->text('content')->nullable();
            $table->json('payload')->nullable();
            $table->morphs('actor'); // actor_id, actor_type
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_timelines');
    }
};
