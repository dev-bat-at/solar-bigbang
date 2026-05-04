<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_types', function (Blueprint $table) {
            if (! Schema::hasColumn('system_types', 'show_calculation_formula')) {
                $table->boolean('show_calculation_formula')
                    ->default(false)
                    ->after('quote_is_active');
            }

            if (! Schema::hasColumn('system_types', 'quote_request_fields')) {
                $table->json('quote_request_fields')
                    ->nullable()
                    ->after('quote_recommendations');
            }
        });
    }

    public function down(): void
    {
        Schema::table('system_types', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['show_calculation_formula', 'quote_request_fields'],
                fn (string $column): bool => Schema::hasColumn('system_types', $column)
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
