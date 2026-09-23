<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();

            $table->boolean('active')->default(true)->index();
            $table->unsignedSmallInteger('sort')->default(0)->index();

            $table->jsonb('title');
            $table->jsonb('slug');
            $table->jsonb('body')->nullable();

            $table->jsonb('meta_title')->nullable();
            $table->jsonb('meta_description')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement("CREATE UNIQUE INDEX pages_slug_fr_unique ON pages ((slug->>'fr')) WHERE (slug->>'fr') IS NOT NULL AND (slug->>'fr') <> '' AND deleted_at IS NULL");
        DB::statement("CREATE UNIQUE INDEX pages_slug_en_unique ON pages ((slug->>'en')) WHERE (slug->>'en') IS NOT NULL AND (slug->>'en') <> '' AND deleted_at IS NULL");
        DB::statement("CREATE UNIQUE INDEX pages_slug_ro_unique ON pages ((slug->>'ro')) WHERE (slug->>'ro') IS NOT NULL AND (slug->>'ro') <> '' AND deleted_at IS NULL");
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
