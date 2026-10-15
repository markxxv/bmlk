<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('access_token', 48)->nullable()->unique();
            $table->string('payment_status', 20)->default('unpaid')->index();
            $table->string('stripe_session_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->unsignedInteger('payment_attempts')->default(0);
            $table->timestamp('paid_at')->nullable();
        });

        DB::table('orders')->whereNull('access_token')->orderBy('id')
            ->select('id')->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    DB::table('orders')->where('id', $order->id)
                        ->update(['access_token' => Str::random(48)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['access_token']);
            $table->dropIndex(['payment_status']);
            $table->dropColumn([
                'access_token', 'payment_status', 'stripe_session_id',
                'stripe_payment_intent_id', 'payment_attempts', 'paid_at',
            ]);
        });
    }
};
