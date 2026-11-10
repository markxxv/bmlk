<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('mode', 10)->default('online'); // online | live
            $table->string('name');
            $table->jsonb('tag')->nullable();
            $table->jsonb('description')->nullable();
            $table->jsonb('points')->nullable();
            $table->jsonb('programme')->nullable();   // per-locale: facts, about, goals, modules, included
            $table->jsonb('info_pdf')->nullable();    // per-locale path on the public disk
            $table->decimal('price', 10, 2)->nullable();
            $table->string('image')->nullable();      // file in public/img/formations
            $table->string('telegram_channel_url', 2048)->nullable(); // private: learning materials
            $table->string('telegram_chat_url', 2048)->nullable();    // private: community chat
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('course_purchases', function (Blueprint $table): void {
            $table->id();
            // Guests can buy: the account is attached once payment is confirmed.
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('email')->nullable();
            $table->string('access_token', 64);              // private thanks-page link
            $table->string('onboarding_token_hash', 64)->nullable(); // browser-only proof for auto sign-in
            $table->boolean('account_created')->default(false);     // passwordless account made by this purchase
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending'); // pending | paid | failed | expired | refunded
            $table->unsignedInteger('amount_cents')->default(0);
            $table->string('currency', 3)->default('eur');
            $table->string('locale', 5)->default('fr');
            $table->string('stripe_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->boolean('granted_manually')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // A customer can own a given course only once.
        DB::statement("CREATE UNIQUE INDEX course_purchases_one_paid_per_user_course ON course_purchases (user_id, course_id) WHERE status = 'paid'");
    }

    public function down(): void
    {
        Schema::dropIfExists('course_purchases');
        Schema::dropIfExists('courses');
    }
};
