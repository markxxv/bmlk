<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Spanish replaces Romanian: drop the old `ro` unique indexes and add `es` ones.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS products_slug_ro_unique');
        DB::statement('DROP INDEX IF EXISTS categories_slug_ro_unique');
        DB::statement('DROP INDEX IF EXISTS pages_slug_ro_unique');
        DB::statement('DROP INDEX IF EXISTS events_url_ro_unique');

        DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS products_slug_es_unique ON products ((slug->>'es')) WHERE (slug->>'es') IS NOT NULL AND (slug->>'es') <> ''");
        DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS categories_slug_es_unique ON categories ((slug->>'es')) WHERE (slug->>'es') IS NOT NULL AND (slug->>'es') <> ''");
        DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS pages_slug_es_unique ON pages ((slug->>'es')) WHERE (slug->>'es') IS NOT NULL AND (slug->>'es') <> '' AND deleted_at IS NULL");
        DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS events_url_es_unique ON events ((url->>'es')) WHERE url->>'es' IS NOT NULL");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS products_slug_es_unique');
        DB::statement('DROP INDEX IF EXISTS categories_slug_es_unique');
        DB::statement('DROP INDEX IF EXISTS pages_slug_es_unique');
        DB::statement('DROP INDEX IF EXISTS events_url_es_unique');
    }
};
