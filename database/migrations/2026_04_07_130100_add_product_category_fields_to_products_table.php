<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'product_category_id')) {
                $table->foreignId('product_category_id')
                    ->nullable()
                    ->after('slug')
                    ->constrained('product_categories')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('products', 'product_subcategory_id')) {
                $table->foreignId('product_subcategory_id')
                    ->nullable()
                    ->after('product_category_id')
                    ->constrained('product_categories')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'product_subcategory_id')) {
                $table->dropForeign(['product_subcategory_id']);
                $table->dropColumn('product_subcategory_id');
            }

            if (Schema::hasColumn('products', 'product_category_id')) {
                $table->dropForeign(['product_category_id']);
                $table->dropColumn('product_category_id');
            }
        });
    }
};
