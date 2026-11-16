<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('visitor_uuid', 36)->index();
            $table->char('ip_hash', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('device', 10)->nullable();
            $table->char('country', 2)->nullable();
            $table->string('city')->nullable();
            $table->string('referrer', 512)->nullable();
            $table->string('landing_path', 512)->nullable();

            $table->timestamp('started_at');
            $table->timestamp('last_ping_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);

            $table->boolean('is_return')->default(false);
            $table->timestamp('summary_sent_at')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'started_at']);
            // CloseStaleVisits: open visits whose last ping is older than 60s
            $table->index(['ended_at', 'last_ping_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
