<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('request_key')->unique();
            $table->string('stripe_refund_id')->nullable()->unique();
            $table->string('type', 16);
            $table->unsignedInteger('item_index')->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->unsignedInteger('amount_cents');
            $table->string('status', 24)->default('initiated');
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_refunds');
    }
};
