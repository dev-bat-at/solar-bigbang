<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('support_requests', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('id');
            }

            if (! Schema::hasColumn('support_requests', 'customer_phone')) {
                $table->string('customer_phone')->nullable()->after('customer_name');
                $table->index('customer_phone');
            }

            if (! Schema::hasColumn('support_requests', 'customer_email')) {
                $table->string('customer_email')->nullable()->after('customer_phone');
                $table->index('customer_email');
            }

            if (! Schema::hasColumn('support_requests', 'customer_address')) {
                $table->text('customer_address')->nullable()->after('customer_email');
            }

            if (! Schema::hasColumn('support_requests', 'request_type')) {
                $table->string('request_type', 50)->default('general_contact')->after('customer_address');
                $table->index('request_type');
            }

            if (! Schema::hasColumn('support_requests', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('request_type');
                $table->index('product_id');
            }

            if (! Schema::hasColumn('support_requests', 'system_type_id')) {
                $table->unsignedBigInteger('system_type_id')->nullable()->after('product_id');
                $table->index('system_type_id');
            }

            if (! Schema::hasColumn('support_requests', 'status')) {
                $table->string('status', 50)->default('new')->after('system_type_id');
                $table->index('status');
            }

            if (! Schema::hasColumn('support_requests', 'source')) {
                $table->string('source', 50)->default('admin_manual')->after('status');
                $table->index('source');
            }

            if (! Schema::hasColumn('support_requests', 'customer_message')) {
                $table->text('customer_message')->nullable()->after('source');
            }

            if (! Schema::hasColumn('support_requests', 'admin_note')) {
                $table->text('admin_note')->nullable()->after('customer_message');
            }

            if (! Schema::hasColumn('support_requests', 'request_payload')) {
                $table->json('request_payload')->nullable()->after('admin_note');
            }

            if (! Schema::hasColumn('support_requests', 'handled_at')) {
                $table->timestamp('handled_at')->nullable()->after('request_payload');
            }

            if (! Schema::hasColumn('support_requests', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('support_requests', function (Blueprint $table) {
            $columns = [
                'customer_name',
                'customer_phone',
                'customer_email',
                'customer_address',
                'request_type',
                'product_id',
                'system_type_id',
                'status',
                'source',
                'customer_message',
                'admin_note',
                'request_payload',
                'handled_at',
            ];

            $existingColumns = array_values(array_filter(
                $columns,
                fn (string $column): bool => Schema::hasColumn('support_requests', $column)
            ));

            if (Schema::hasColumn('support_requests', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            if ($existingColumns !== []) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
