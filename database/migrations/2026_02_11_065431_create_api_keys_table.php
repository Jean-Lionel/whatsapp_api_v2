<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('key', 64)->unique();
            $table->json('scopes')->nullable();
            $table->integer('rate_limit')->default(100);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['key', 'is_active']);
        });

        Schema::create('api_key_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_key_id')->constrained()->cascadeOnDelete();
            $table->string('endpoint');
            $table->string('method', 10);
            $table->integer('status_code');
            $table->string('ip_address', 45)->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->timestamp('created_at');

            $table->index(['api_key_id', 'created_at']);
        });

        Schema::create('client_webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('url');
            $table->string('secret', 64);
            $table->json('events');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('failure_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });

        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_webhook_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->json('payload');
            $table->integer('status_code')->nullable();
            $table->text('response')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->boolean('success')->default(false);
            $table->timestamp('created_at');

            $table->index(['client_webhook_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('client_webhooks');
        Schema::dropIfExists('api_key_usage');
        Schema::dropIfExists('api_keys');
    }
};
