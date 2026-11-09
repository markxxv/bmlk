<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_refunds', function (Blueprint $table): void {
            // A single Stripe refund can contain multiple purchased product lines.
            $table->json('items')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('order_refunds', function (Blueprint $table): void {
            $table->dropColumn('items');
        });
    }
};
