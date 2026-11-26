<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            // Existing and imported orders must never be mailed automatically.
            $table->boolean('invoice_email_enabled')->default(false);
            $table->timestamp('invoice_email_sent_at')->nullable();
            $table->timestamp('invoice_email_attempted_at')->nullable();
            $table->index(['invoice_email_enabled', 'invoice_email_sent_at', 'status'], 'orders_invoice_email_due_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_invoice_email_due_idx');
            $table->dropColumn(['invoice_email_enabled', 'invoice_email_sent_at', 'invoice_email_attempted_at']);
        });
    }
};
