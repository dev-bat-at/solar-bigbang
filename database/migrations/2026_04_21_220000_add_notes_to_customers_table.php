<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('customers', 'notes')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->text('notes')->nullable()->after('contact_time');
            });
        }

        if (! Schema::hasTable('lead_timelines') || ! Schema::hasTable('leads')) {
            return;
        }

        DB::table('lead_timelines')
            ->select(['id', 'lead_id', 'payload'])
            ->orderBy('id')
            ->chunkById(100, function ($timelines): void {
                foreach ($timelines as $timeline) {
                    $payload = is_array($timeline->payload)
                        ? $timeline->payload
                        : json_decode((string) $timeline->payload, true);

                    if (! is_array($payload)) {
                        continue;
                    }

                    $notes = data_get($payload, 'notes');

                    if (! is_string($notes) || trim($notes) === '') {
                        continue;
                    }

                    $customerId = DB::table('leads')
                        ->where('id', $timeline->lead_id)
                        ->value('customer_id');

                    if ($customerId === null) {
                        continue;
                    }

                    DB::table('customers')
                        ->where('id', $customerId)
                        ->where(function ($query): void {
                            $query->whereNull('notes')
                                ->orWhere('notes', '');
                        })
                        ->update([
                            'notes' => trim($notes),
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('customers', 'notes')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
