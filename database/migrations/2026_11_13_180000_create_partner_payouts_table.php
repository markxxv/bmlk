<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->timestamp('paid_at');
            $table->string('note', 255)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_payouts');
    }
};
