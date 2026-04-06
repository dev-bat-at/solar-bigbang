<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_types', function (Blueprint $table) {
            if (! Schema::hasColumn('system_types', 'description')) {
                $table->text('description')->nullable()->after('slug');
            }

            if (! Schema::hasColumn('system_types', 'quote_formula_type')) {
                $table->string('quote_formula_type')->nullable()->after('description');
            }

            if (! Schema::hasColumn('system_types', 'quote_is_active')) {
                $table->boolean('quote_is_active')->default(false)->after('quote_formula_type');
            }

            if (! Schema::hasColumn('system_types', 'quote_settings')) {
                $table->json('quote_settings')->nullable()->after('quote_is_active');
            }

            if (! Schema::hasColumn('system_types', 'quote_price_tiers')) {
                $table->json('quote_price_tiers')->nullable()->after('quote_settings');
            }

            if (! Schema::hasColumn('system_types', 'quote_recommendations')) {
                $table->json('quote_recommendations')->nullable()->after('quote_price_tiers');
            }
        });
    }

    public function down(): void
    {
        Schema::table('system_types', function (Blueprint $table) {
            $columns = [
                'description',
                'quote_formula_type',
                'quote_is_active',
                'quote_settings',
                'quote_price_tiers',
                'quote_recommendations',
            ];

            $existingColumns = array_filter(
                $columns,
                fn (string $column): bool => Schema::hasColumn('system_types', $column),
            );

            if ($existingColumns !== []) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
