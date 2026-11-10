<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->timestamp('delivered_at')->nullable()->index();
            $table->timestamp('review_request_sent_at')->nullable()->index();
        });

        Schema::create('review_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });

        Schema::create('review_email_opt_outs', function (Blueprint $table): void {
            $table->id();
            $table->char('email_hash', 64)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_email_opt_outs');
        Schema::dropIfExists('review_access_tokens');

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['delivered_at', 'review_request_sent_at']);
        });
    }
};
