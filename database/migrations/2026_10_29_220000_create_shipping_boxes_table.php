<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_boxes', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 120);

            // Stored in cm; the Filament form displays mm.
            $table->decimal('inner_length_cm', 8, 2)->nullable();
            $table->decimal('inner_width_cm', 8, 2)->nullable();
            $table->decimal('inner_height_cm', 8, 2)->nullable();
            $table->decimal('outer_length_cm', 8, 2)->nullable();
            $table->decimal('outer_width_cm', 8, 2)->nullable();
            $table->decimal('outer_height_cm', 8, 2)->nullable();

            $table->unsignedInteger('tare_weight_g')->nullable();
            $table->unsignedInteger('max_weight_g')->nullable();

            $table->boolean('active')->default(false)->index();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_boxes');
    }
};
