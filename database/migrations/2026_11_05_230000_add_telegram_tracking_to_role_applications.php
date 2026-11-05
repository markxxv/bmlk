<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_applications', function (Blueprint $table): void {
            $table->timestamp('telegram_first_sent_at')->nullable();
            $table->timestamp('telegram_last_sent_at')->nullable();
            $table->timestamp('telegram_claimed_at')->nullable();
            $table->timestamp('telegram_reminder_queued_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('role_applications', function (Blueprint $table): void {
            $table->dropColumn([
                'telegram_first_sent_at',
                'telegram_last_sent_at',
                'telegram_claimed_at',
                'telegram_reminder_queued_at',
            ]);
        });
    }
};
