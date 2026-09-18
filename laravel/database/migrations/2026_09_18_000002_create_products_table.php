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
            $table->unsignedInteger('sort_order')->default(0)->index();

            $table->string('status', 40)->default('active')->index();
            $table->string('product_type', 40)->default('physical')->index();
            $table->string('catalog_scope', 40)->default('main')->index();

            $table->string('source_key', 190)->nullable()->unique();
            $table->string('sku', 120)->nullable()->unique();
            $table->string('barcode', 120)->nullable()->unique();

            $table->jsonb('name');
            $table->jsonb('slug');
            $table->jsonb('description')->nullable();

            $table->jsonb('tags')->nullable();
            $table->jsonb('claims')->nullable();
            $table->jsonb('details')->nullable();
            $table->jsonb('size')->nullable();
            $table->jsonb('measurements')->nullable();
            $table->jsonb('options')->nullable();

            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('compare_at_price', 12, 2)->nullable();
            $table->boolean('tax_included')->nullable();

            $table->jsonb('source_data')->nullable();
            $table->jsonb('legacy_data')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'active', 'sort_order']);
            $table->index(['status', 'active']);
        });

        DB::statement('CREATE INDEX products_name_gin_idx ON products USING GIN (name)');
        DB::statement('CREATE INDEX products_slug_gin_idx ON products USING GIN (slug)');
        DB::statement('CREATE INDEX products_tags_gin_idx ON products USING GIN (tags)');
        DB::statement('CREATE INDEX products_claims_gin_idx ON products USING GIN (claims)');
        DB::statement('CREATE INDEX products_options_gin_idx ON products USING GIN (options)');
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
