<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->boolean('active')->default(true)->index();
            $table->boolean('featured')->default(false)->index();
            $table->boolean('is_new')->default(false)->index();
            $table->string('sku', 120)->nullable()->unique();

            $table->jsonb('name');
            $table->jsonb('slug');
            $table->jsonb('tag')->nullable();
            $table->jsonb('description')->nullable();

            $table->jsonb('claims')->nullable();
            $table->jsonb('details')->nullable();
            $table->jsonb('size')->nullable();
            $table->jsonb('measurements')->nullable();
            $table->jsonb('options')->nullable();
            $table->jsonb('contents')->nullable();

            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('price_min', 12, 2)->nullable();
            $table->decimal('price_max', 12, 2)->nullable();
            $table->decimal('compare_at_price', 12, 2)->nullable();

            $table->jsonb('meta_title')->nullable();
            $table->jsonb('meta_description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'active']);
        });

        DB::statement("CREATE UNIQUE INDEX products_slug_fr_unique ON products ((slug->>'fr')) WHERE (slug->>'fr') IS NOT NULL AND (slug->>'fr') <> ''");
        DB::statement("CREATE UNIQUE INDEX products_slug_en_unique ON products ((slug->>'en')) WHERE (slug->>'en') IS NOT NULL AND (slug->>'en') <> ''");
        DB::statement("CREATE UNIQUE INDEX products_slug_ro_unique ON products ((slug->>'ro')) WHERE (slug->>'ro') IS NOT NULL AND (slug->>'ro') <> ''");
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
