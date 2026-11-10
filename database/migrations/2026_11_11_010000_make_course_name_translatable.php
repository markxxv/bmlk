<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Existing names are brand names: copy the same value into every locale.
        DB::statement("ALTER TABLE courses ALTER COLUMN name TYPE jsonb USING jsonb_build_object('fr', name, 'en', name, 'es', name)");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE courses ALTER COLUMN name TYPE varchar(255) USING COALESCE(name->>'fr', '')");
    }
};
