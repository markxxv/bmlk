<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Existing names are brand names: copy the same value into every locale.
        if (DB::getDriverName() === 'sqlite') {
            // SQLite stores JSON as text; no column type change is needed.
            DB::statement("UPDATE courses SET name = json_object('fr', name, 'en', name, 'es', name)");

            return;
        }

        DB::statement("ALTER TABLE courses ALTER COLUMN name TYPE jsonb USING jsonb_build_object('fr', name, 'en', name, 'es', name)");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("UPDATE courses SET name = COALESCE(json_extract(name, '$.fr'), '')");

            return;
        }

        DB::statement("ALTER TABLE courses ALTER COLUMN name TYPE varchar(255) USING COALESCE(name->>'fr', '')");
    }
};
