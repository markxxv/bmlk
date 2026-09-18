<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('category_id', 80);
            $table->boolean('active')->default(true)->index();
            $table->boolean('featured')->default(false)->index();
            $table->boolean('is_new')->default(false)->index();
            $table->string('sku', 120)->nullable()->unique();

            $table->jsonb('name');
            $table->string('slug', 190)->unique();
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

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')->references('id')->on('categories')->restrictOnDelete();
            $table->index(['category_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
