<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_locks', function (Blueprint $t) {
            $t->unsignedBigInteger('workspace_id')->primary();
        });
        Schema::create('contacts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('workspace_id')->index();
            $t->string('name');
            $t->string('email');
            $t->timestamps();
            $t->unique(['workspace_id', 'id']);
        });
        Schema::create('conversations', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('workspace_id')->index();
            $t->string('name');
            $t->unsignedBigInteger('contact_id');
            $t->string('status');
            $t->string('ai_mode');
            $t->timestamps();
            $t->unique(['workspace_id', 'id']);
        });
        Schema::create('tickets', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('workspace_id')->index();
            $t->string('name');
            $t->unsignedBigInteger('contact_id');
            $t->string('priority');
            $t->string('status');
            $t->timestamps();
            $t->unique(['workspace_id', 'id']);
        });
        Schema::create('knowledge_articles', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('workspace_id')->index();
            $t->string('name');
            $t->text('content');
            $t->string('status');
            $t->timestamps();
            $t->unique(['workspace_id', 'id']);
        });
        Schema::create('ai_agents', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('workspace_id')->index();
            $t->string('name');
            $t->text('instructions');
            $t->string('model');
            $t->timestamps();
            $t->unique(['workspace_id', 'id']);
        });
        Schema::create('domain_events', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('workspace_id')->index();
            $t->string('event_type');
            $t->string('subject_type');
            $t->unsignedBigInteger('subject_id');
            $t->unsignedBigInteger('actor_id')->nullable();
            $t->json('payload');
            $t->timestamps();
        });
        Schema::create('messages', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('workspace_id');
            $t->unsignedBigInteger('conversation_id');
            $t->unsignedBigInteger('actor_id');
            $t->string('kind');
            $t->text('body');
            $t->string('idempotency_key');
            $t->char('request_hash', 64);
            $t->timestamps();
            $t->unique(['workspace_id', 'idempotency_key']);
        });
        Schema::table('conversations', function (Blueprint $t) {
            $t->foreign(['workspace_id', 'contact_id'], 'conversations_contact_id_scope')->references(['workspace_id', 'id'])->on('contacts')->restrictOnDelete();
        });
        Schema::table('tickets', function (Blueprint $t) {
            $t->foreign(['workspace_id', 'contact_id'], 'tickets_contact_id_scope')->references(['workspace_id', 'id'])->on('contacts')->restrictOnDelete();
        });
        Schema::table('messages', function (Blueprint $t) {
            $t->foreign(['workspace_id', 'conversation_id'], 'messages_conversation_id_scope')->references(['workspace_id', 'id'])->on('conversations')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_locks');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('domain_events');
        Schema::dropIfExists('ai_agents');
        Schema::dropIfExists('knowledge_articles');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('contacts');
    }
};
