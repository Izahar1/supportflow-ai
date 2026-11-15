<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->boolean('is_platform_admin')->default(false));
        Schema::create('plans', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('code')->unique();
            $t->json('limits');
            $t->string('stripe_price')->nullable();
            $t->timestamps();
        });
        Schema::create('workspaces', function (Blueprint $t) {
            $t->id();
            $t->foreignId('owner_user_id')->constrained('users');
            $t->foreignId('plan_id')->constrained();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('status')->default('active');
            $t->string('timezone')->default('UTC');
            $t->string('provisioning_status')->default('ready');
            $t->string('database_name')->nullable();
            $t->string('stripe_id')->nullable()->unique();
            $t->string('pm_type')->nullable();
            $t->string('pm_last_four', 4)->nullable();
            $t->timestamp('trial_ends_at')->nullable();
            $t->timestamps();
        });
        Schema::create('workspace_user', function (Blueprint $t) {
            $t->id();
            $t->foreignId('workspace_id')->constrained();
            $t->foreignId('user_id')->constrained();
            $t->string('role');
            $t->string('status')->default('active');
            $t->unique(['workspace_id', 'user_id']);
        });
        Schema::create('subscriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('workspace_id')->constrained();
            $t->string('type');
            $t->string('stripe_id')->unique();
            $t->string('stripe_status');
            $t->string('stripe_price')->nullable();
            $t->integer('quantity')->nullable();
            $t->timestamp('trial_ends_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->timestamps();
            $t->index(['workspace_id', 'stripe_status']);
        });
        Schema::create('subscription_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('subscription_id')->constrained();
            $t->string('stripe_id')->unique();
            $t->string('stripe_product');
            $t->string('stripe_price');
            $t->string('meter_id')->nullable();
            $t->string('meter_event_name')->nullable();
            $t->integer('quantity')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['subscription_items', 'subscriptions', 'workspace_user', 'workspaces', 'plans'] as $table) {
            Schema::dropIfExists($table);
        } Schema::table('users', fn (Blueprint $t) => $t->dropColumn('is_platform_admin'));
    }
};
