<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();

            $table->boolean('active')->default(true)->index();
            $table->unsignedSmallInteger('sort')->default(0)->index();

            $table->jsonb('name');
            $table->jsonb('url')->nullable();
            $table->string('type', 50)->index();
            $table->jsonb('description')->nullable();

            $table->timestampTz('starts_at')->nullable()->index();
            $table->timestampTz('ends_at')->nullable();

            $table->string('country', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('address', 255)->nullable();

            $table->decimal('price', 10, 2)->nullable();
            $table->text('ticket_url')->nullable();

            $table->timestamps();
        });

        DB::statement("CREATE UNIQUE INDEX events_url_fr_unique ON events ((url->>'fr')) WHERE url->>'fr' IS NOT NULL");
        DB::statement("CREATE UNIQUE INDEX events_url_en_unique ON events ((url->>'en')) WHERE url->>'en' IS NOT NULL");
        DB::statement("CREATE UNIQUE INDEX events_url_ro_unique ON events ((url->>'ro')) WHERE url->>'ro' IS NOT NULL");
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
