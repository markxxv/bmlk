<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->boolean('active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('role', 40)->nullable()->index();
            $table->string('disk', 40)->nullable();
            $table->text('path');
            $table->text('source_ref')->nullable();
            $table->jsonb('alt')->nullable();
            $table->jsonb('data')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'active', 'sort_order']);
        });

        DB::statement('CREATE INDEX product_images_alt_gin_idx ON product_images USING GIN (alt)');
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
