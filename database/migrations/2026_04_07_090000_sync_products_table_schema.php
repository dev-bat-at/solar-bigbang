<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'code')) {
                $table->string('code', 100)->nullable()->unique()->after('id');
            }

            if (!Schema::hasColumn('products', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('code');
            }

            if (!Schema::hasColumn('products', 'name_vi')) {
                $table->string('name_vi')->nullable()->after('slug');
            }

            if (!Schema::hasColumn('products', 'name_en')) {
                $table->string('name_en')->nullable()->after('name_vi');
            }

            if (!Schema::hasColumn('products', 'tagline_vi')) {
                $table->string('tagline_vi')->nullable()->after('name_en');
            }

            if (!Schema::hasColumn('products', 'tagline_en')) {
                $table->string('tagline_en')->nullable()->after('tagline_vi');
            }

            if (!Schema::hasColumn('products', 'status')) {
                $table->string('status', 20)->default('draft')->index()->after('tagline_en');
            }

            if (!Schema::hasColumn('products', 'is_best_seller')) {
                $table->boolean('is_best_seller')->default(false)->index()->after('status');
            }

            if (!Schema::hasColumn('products', 'images')) {
                $table->json('images')->nullable()->after('is_best_seller');
            }

            if (!Schema::hasColumn('products', 'price')) {
                $table->unsignedBigInteger('price')->default(0)->after('images');
            }

            if (!Schema::hasColumn('products', 'price_unit_vi')) {
                $table->string('price_unit_vi', 100)->nullable()->after('price');
            }

            if (!Schema::hasColumn('products', 'price_unit_en')) {
                $table->string('price_unit_en', 100)->nullable()->after('price_unit_vi');
            }

            if (!Schema::hasColumn('products', 'power')) {
                $table->string('power', 100)->nullable()->after('price_unit_en');
            }

            if (!Schema::hasColumn('products', 'efficiency')) {
                $table->string('efficiency', 100)->nullable()->after('power');
            }

            if (!Schema::hasColumn('products', 'warranty')) {
                $table->string('warranty', 100)->nullable()->after('efficiency');
            }

            if (!Schema::hasColumn('products', 'specifications')) {
                $table->json('specifications')->nullable()->after('warranty');
            }

            if (!Schema::hasColumn('products', 'description_vi')) {
                $table->longText('description_vi')->nullable()->after('specifications');
            }

            if (!Schema::hasColumn('products', 'description_en')) {
                $table->longText('description_en')->nullable()->after('description_vi');
            }

            if (!Schema::hasColumn('products', 'documents')) {
                $table->json('documents')->nullable()->after('description_en');
            }

            if (!Schema::hasColumn('products', 'faqs')) {
                $table->json('faqs')->nullable()->after('documents');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columns = [
                'code',
                'slug',
                'name_vi',
                'name_en',
                'tagline_vi',
                'tagline_en',
                'status',
                'is_best_seller',
                'images',
                'price',
                'price_unit_vi',
                'price_unit_en',
                'power',
                'efficiency',
                'warranty',
                'specifications',
                'description_vi',
                'description_en',
                'documents',
                'faqs',
            ];

            $existingColumns = array_values(array_filter(
                $columns,
                fn (string $column): bool => Schema::hasColumn('products', $column)
            ));

            if ($existingColumns !== []) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
