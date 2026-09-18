<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->boolean('active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('code', 80)->unique();
            $table->jsonb('name');
            $table->jsonb('slug');
            $table->jsonb('description')->nullable();
            $table->jsonb('meta_title')->nullable();
            $table->jsonb('meta_description')->nullable();
            $table->jsonb('data')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('CREATE INDEX categories_name_gin_idx ON categories USING GIN (name)');
        DB::statement('CREATE INDEX categories_slug_gin_idx ON categories USING GIN (slug)');
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
