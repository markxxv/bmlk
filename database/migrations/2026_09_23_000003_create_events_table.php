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
            $table->boolean('confirmed')->default(false)->index();
            $table->unsignedSmallInteger('sort')->default(0)->index();

            $table->jsonb('name');
            $table->jsonb('url')->nullable();
            $table->jsonb('type');
            $table->jsonb('status')->nullable();
            $table->jsonb('description')->nullable();
            $table->jsonb('cta')->nullable();
            $table->jsonb('date_label')->nullable();

            $table->timestampTz('starts_at')->nullable()->index();
            $table->timestampTz('ends_at')->nullable();

            $table->jsonb('country')->nullable();
            $table->jsonb('city')->nullable();
            $table->jsonb('venue')->nullable();
            $table->jsonb('address')->nullable();
            $table->string('postal_code', 32)->nullable();

            $table->decimal('price', 10, 2)->nullable();
            $table->char('currency', 3)->default('EUR');

            $table->string('sales_mode', 20)->nullable()->index();
            $table->text('ticket_url')->nullable();

            $table->jsonb('meta_title')->nullable();
            $table->jsonb('meta_description')->nullable();

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
