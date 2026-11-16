<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per high-intent alert actually sent. The unique index makes the
     * "max one alert per visit per type" rule atomic, even when two requests
     * for the same click arrive at the same time.
     */
    public function up(): void
    {
        Schema::create('visit_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30); // whatsapp | phone | cta | return
            $table->timestamp('sent_at');

            $table->unique(['visit_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_alerts');
    }
};
