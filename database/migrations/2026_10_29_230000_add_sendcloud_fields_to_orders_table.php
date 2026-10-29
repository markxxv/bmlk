<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('shipping_country_code', 2)->nullable();
            $table->string('shipping_option_code', 190)->nullable();
            $table->unsignedBigInteger('shipping_contract_id')->nullable();
            $table->string('shipping_option_name', 190)->nullable();
            $table->decimal('sendcloud_rate', 10, 2)->nullable();
            $table->json('shipping_parcel')->nullable();
            $table->unsignedBigInteger('service_point_id')->nullable();
            $table->string('service_point_name')->nullable();
            $table->string('service_point_address')->nullable();
            $table->string('sendcloud_order_id')->nullable()->index();
            $table->string('sendcloud_parcel_id')->nullable()->index();
            $table->string('sendcloud_shipment_id')->nullable();
            $table->string('tracking_url', 1000)->nullable();
            $table->string('sendcloud_status')->nullable();
            $table->unsignedBigInteger('sendcloud_event_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'shipping_country_code', 'shipping_option_code', 'shipping_contract_id',
                'shipping_option_name', 'sendcloud_rate', 'shipping_parcel',
                'service_point_id', 'service_point_name', 'service_point_address',
                'sendcloud_order_id', 'sendcloud_parcel_id', 'sendcloud_shipment_id',
                'tracking_url', 'sendcloud_status', 'sendcloud_event_at',
            ]);
        });
    }
};
