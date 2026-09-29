<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // Who
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name', 150)->nullable();   // denormalised: survives user deletion
            $table->string('user_email', 190)->nullable();
            $table->string('user_role', 60)->nullable();

            // What
            $table->string('action', 80)->index();
            $table->string('action_group', 40)->index();
            $table->string('description', 500)->nullable();

            // On which record (polymorphic, nullable for auth events)
            $table->string('subject_type', 120)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label', 190)->nullable();

            // Payload — before/after diff, never contains secrets
            $table->json('properties')->nullable();

            // Request context
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('user_agent', 500)->nullable();
            $table->string('method', 10)->nullable();
            $table->string('url', 500)->nullable();
            $table->string('route_name', 120)->nullable();

            $table->boolean('is_security_event')->default(false)->index();

            $table->timestamp('created_at')->nullable()->index();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
