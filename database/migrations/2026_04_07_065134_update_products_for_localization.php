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
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_price_contact')->default(false)->after('price');
            $table->string('warranty_vi')->nullable()->after('efficiency');
            $table->string('warranty_en')->nullable()->after('warranty_vi');
            $table->json('specifications_vi')->nullable()->after('specifications');
            $table->json('specifications_en')->nullable()->after('specifications_vi');
            $table->json('documents_vi')->nullable()->after('documents');
            $table->json('documents_en')->nullable()->after('documents_vi');
            $table->json('faqs_vi')->nullable()->after('faqs');
            $table->json('faqs_en')->nullable()->after('faqs_vi');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'is_price_contact',
                'warranty_vi',
                'warranty_en',
                'specifications_vi',
                'specifications_en',
                'documents_vi',
                'documents_en',
                'faqs_vi',
                'faqs_en',
            ]);
        });
    }
};
