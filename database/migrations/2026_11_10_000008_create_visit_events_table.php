<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->json('payload')->nullable(); // section key, seconds, path, depth…
            $table->timestamp('occurred_at');

            $table->index(['visit_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_events');
    }
};
