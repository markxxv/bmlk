<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_tracking_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('tracking_number', 255);
            $table->char('event_key', 64);
            $table->timestamp('event_at');
            $table->string('event_type', 32)->default('carrier');
            $table->string('phase', 64)->nullable();
            $table->string('exception', 64)->nullable();
            $table->string('sub_status', 100)->nullable();
            $table->string('status_type', 32)->nullable();
            $table->text('description')->nullable();
            $table->string('location', 255)->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'event_key']);
            $table->index(['order_id', 'event_at']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('sendcloud_tracking_phase', 64)->nullable();
            $table->timestamp('sendcloud_tracking_synced_at')->nullable();
            $table->date('sendcloud_expected_delivery_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'sendcloud_tracking_phase',
                'sendcloud_tracking_synced_at',
                'sendcloud_expected_delivery_date',
            ]);
        });

        Schema::dropIfExists('order_tracking_events');
    }
};
