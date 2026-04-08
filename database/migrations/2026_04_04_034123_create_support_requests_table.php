<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_requests', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('customer_phone')->index();
            $table->string('customer_email')->nullable()->index();
            $table->text('customer_address')->nullable();
            $table->string('request_type', 50)->default('general_contact')->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('system_type_id')->nullable()->index();
            $table->string('status', 50)->default('new')->index();
            $table->string('source', 50)->default('admin_manual')->index();
            $table->text('customer_message')->nullable();
            $table->text('admin_note')->nullable();
            $table->json('request_payload')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_requests');
    }
};
