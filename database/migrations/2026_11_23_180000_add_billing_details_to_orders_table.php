<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('billing_type', 16)->default('individual');
            $table->boolean('billing_same_as_shipping')->default(true);
            $table->string('billing_company')->nullable();
            $table->string('billing_vat', 40)->nullable();
            $table->text('billing_address')->nullable();
            $table->string('billing_zip', 30)->nullable();
            $table->string('billing_city', 120)->nullable();
            $table->char('billing_country_code', 2)->nullable();
            $table->string('billing_country', 150)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'billing_type', 'billing_same_as_shipping', 'billing_company', 'billing_vat',
                'billing_address', 'billing_zip', 'billing_city', 'billing_country_code', 'billing_country',
            ]);
        });
    }
};
