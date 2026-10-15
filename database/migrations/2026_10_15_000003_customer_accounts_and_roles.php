<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
            $table->boolean('is_admin')->default(false);
            $table->boolean('is_wholesale')->default(false);
            $table->boolean('is_influencer')->default(false);
            $table->string('influencer_code', 32)->nullable()->unique();
            $table->decimal('influencer_discount', 5, 2)->default(30);
            $table->unsignedBigInteger('onboarding_order_id')->nullable();
        });

        // Existing users are the site's existing Filament accounts.
        DB::table('users')->update(['is_admin' => true]);

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('promo_code', 32)->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0);
        });

        Schema::create('role_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->string('status', 20)->default('pending');
            $table->text('message')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'role', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_applications');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['promo_code', 'discount_amount']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['influencer_code']);
            $table->dropColumn([
                'is_admin', 'is_wholesale', 'is_influencer', 'influencer_code',
                'influencer_discount', 'onboarding_order_id',
            ]);
            $table->string('password')->nullable(false)->change();
        });
    }
};
