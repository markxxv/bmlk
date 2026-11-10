<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            // Historic orders and any subsequently imported orders are excluded.
            // Only genuine new storefront checkouts opt in explicitly.
            $table->boolean('ratings_enabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('ratings_enabled');
        });
    }
};
