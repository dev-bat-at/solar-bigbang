<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('slug')->unique();
            $table->string('name_vi');
            $table->string('name_en');
            $table->string('tagline_vi')->nullable();
            $table->string('tagline_en')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->boolean('is_best_seller')->default(false)->index();
            $table->json('images')->nullable();
            $table->unsignedBigInteger('price')->default(0);
            $table->string('price_unit_vi', 100);
            $table->string('price_unit_en', 100);
            $table->string('power', 100);
            $table->string('efficiency', 100);
            $table->string('warranty', 100);
            $table->json('specifications')->nullable();
            $table->longText('description_vi');
            $table->longText('description_en');
            $table->json('documents')->nullable();
            $table->json('faqs')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
