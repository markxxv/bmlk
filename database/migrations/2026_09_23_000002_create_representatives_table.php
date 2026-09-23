<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('representatives', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(true)->index();
            $table->unsignedSmallInteger('sort')->default(0)->index();

            $table->string('name', 160);
            $table->jsonb('country');
            $table->jsonb('tag')->nullable();
            $table->jsonb('city')->nullable();
            $table->jsonb('cta')->nullable();

            $table->text('url')->nullable();
            $table->text('instagram')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('representatives');
    }
};
