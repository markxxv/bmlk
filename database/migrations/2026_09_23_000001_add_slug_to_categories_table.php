<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->jsonb('slug')->nullable();
        });

        $used = ['fr' => [], 'en' => [], 'ro' => []];

        $categories = DB::table('categories')
            ->orderBy('id')
            ->get(['id', 'name']);

        foreach ($categories as $category) {
            $names = is_array($category->name)
                ? $category->name
                : json_decode((string) $category->name, true);

            $fallback = $names['fr'] ?? reset($names) ?: "category-{$category->id}";
            $slugs = [];

            foreach (array_keys($used) as $locale) {
                $base = Str::slug((string) ($names[$locale] ?? $fallback));

                if ($base === '') {
                    $base = "category-{$category->id}";
                }

                $slug = $base;
                $suffix = 2;

                while (isset($used[$locale][$slug])) {
                    $slug = $base.'-'.$suffix++;
                }

                $used[$locale][$slug] = true;
                $slugs[$locale] = $slug;
            }

            DB::statement(
                'UPDATE categories SET slug = ?::jsonb WHERE id = ?',
                [json_encode($slugs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $category->id]
            );
        }

        DB::statement("CREATE UNIQUE INDEX categories_slug_fr_unique ON categories ((slug->>'fr')) WHERE (slug->>'fr') IS NOT NULL AND (slug->>'fr') <> ''");
        DB::statement("CREATE UNIQUE INDEX categories_slug_en_unique ON categories ((slug->>'en')) WHERE (slug->>'en') IS NOT NULL AND (slug->>'en') <> ''");
        DB::statement("CREATE UNIQUE INDEX categories_slug_ro_unique ON categories ((slug->>'ro')) WHERE (slug->>'ro') IS NOT NULL AND (slug->>'ro') <> ''");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS categories_slug_fr_unique');
        DB::statement('DROP INDEX IF EXISTS categories_slug_en_unique');
        DB::statement('DROP INDEX IF EXISTS categories_slug_ro_unique');

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
