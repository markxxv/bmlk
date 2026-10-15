<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('payment_failed_at')->nullable();
            $table->timestamp('paid_email_sent_at')->nullable();
            $table->timestamp('failed_email_sent_at')->nullable();
            $table->timestamp('failed_reminder_sent_at')->nullable();
        });

        // No unexpected emails to old orders when the cron job is first enabled.
        DB::table('orders')->where('payment_status', 'paid')->update([
            'paid_email_sent_at' => now(),
        ]);

        DB::table('orders')->where('payment_status', 'failed')->update([
            'payment_failed_at' => DB::raw('COALESCE(updated_at, created_at)'),
            'failed_email_sent_at' => now(),
            'failed_reminder_sent_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_failed_at',
                'paid_email_sent_at',
                'failed_email_sent_at',
                'failed_reminder_sent_at',
            ]);
        });
    }
};
