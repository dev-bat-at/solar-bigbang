<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_types', function (Blueprint $table) {
            if (! Schema::hasColumn('system_types', 'name_vi')) {
                $table->string('name_vi')->nullable()->after('name');
            }

            if (! Schema::hasColumn('system_types', 'name_en')) {
                $table->string('name_en')->nullable()->after('name_vi');
            }

            if (! Schema::hasColumn('system_types', 'description_vi')) {
                $table->text('description_vi')->nullable()->after('description');
            }

            if (! Schema::hasColumn('system_types', 'description_en')) {
                $table->text('description_en')->nullable()->after('description_vi');
            }
        });

        DB::table('system_types')
            ->select(['id', 'name', 'description'])
            ->orderBy('id')
            ->get()
            ->each(function (object $row): void {
                DB::table('system_types')
                    ->where('id', $row->id)
                    ->update([
                        'name_vi' => $row->name,
                        'name_en' => $row->name,
                        'description_vi' => $row->description,
                        'description_en' => $row->description,
                    ]);
            });

        Schema::table('product_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('product_categories', 'name_vi')) {
                $table->string('name_vi')->nullable()->after('name');
            }

            if (! Schema::hasColumn('product_categories', 'name_en')) {
                $table->string('name_en')->nullable()->after('name_vi');
            }
        });

        DB::table('product_categories')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->get()
            ->each(function (object $row): void {
                DB::table('product_categories')
                    ->where('id', $row->id)
                    ->update([
                        'name_vi' => $row->name,
                        'name_en' => $row->name,
                    ]);
            });

        Schema::table('tags', function (Blueprint $table) {
            if (! Schema::hasColumn('tags', 'name_vi')) {
                $table->string('name_vi')->nullable()->after('name');
            }

            if (! Schema::hasColumn('tags', 'name_en')) {
                $table->string('name_en')->nullable()->after('name_vi');
            }
        });

        DB::table('tags')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->get()
            ->each(function (object $row): void {
                DB::table('tags')
                    ->where('id', $row->id)
                    ->update([
                        'name_vi' => $row->name,
                        'name_en' => $row->name,
                    ]);
            });

        Schema::table('posts', function (Blueprint $table) {
            if (! Schema::hasColumn('posts', 'tag_id')) {
                $table->foreignId('tag_id')->nullable()->after('author_id')->constrained('tags')->nullOnDelete();
            }
        });

        if (Schema::hasTable('post_tag')) {
            DB::table('posts')
                ->select('id')
                ->orderBy('id')
                ->get()
                ->each(function (object $post): void {
                    $tagId = DB::table('post_tag')
                        ->where('post_id', $post->id)
                        ->orderBy('id')
                        ->value('tag_id');

                    if ($tagId) {
                        DB::table('posts')
                            ->where('id', $post->id)
                            ->update(['tag_id' => $tagId]);
                    }
                });

            Schema::dropIfExists('post_tag');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('post_tag')) {
            Schema::create('post_tag', function (Blueprint $table) {
                $table->id();
                $table->foreignId('post_id')->constrained()->cascadeOnDelete();
                $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
            });

            DB::table('posts')
                ->whereNotNull('tag_id')
                ->select(['id', 'tag_id'])
                ->orderBy('id')
                ->get()
                ->each(function (object $post): void {
                    DB::table('post_tag')->insert([
                        'post_id' => $post->id,
                        'tag_id' => $post->tag_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
        }

        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'tag_id')) {
                $table->dropConstrainedForeignId('tag_id');
            }
        });

        Schema::table('tags', function (Blueprint $table) {
            $columns = array_filter(
                ['name_vi', 'name_en'],
                fn (string $column): bool => Schema::hasColumn('tags', $column),
            );

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $columns = array_filter(
                ['name_vi', 'name_en'],
                fn (string $column): bool => Schema::hasColumn('product_categories', $column),
            );

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('system_types', function (Blueprint $table) {
            $columns = array_filter(
                ['name_vi', 'name_en', 'description_vi', 'description_en'],
                fn (string $column): bool => Schema::hasColumn('system_types', $column),
            );

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
