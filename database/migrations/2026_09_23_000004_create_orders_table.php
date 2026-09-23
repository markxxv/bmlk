<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->string('order_number')->nullable()->unique();

            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('phone', 50);
            $table->string('email')->nullable();

            $table->string('country', 120);
            $table->string('zip', 30)->nullable();
            $table->string('city', 120);
            $table->text('address');
            $table->text('message')->nullable();

            $table->jsonb('items');

            $table->decimal('subtotal', 10, 2);
            $table->decimal('delivery_cost', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);

            $table->string('status', 30)->default('pending')->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
