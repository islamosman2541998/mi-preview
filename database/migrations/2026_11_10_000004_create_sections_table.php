<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('variant', 30)->nullable();
            // { "ar": {...}, "en": {...}, "shared": {...} } — shared holds
            // locale-independent values (images, numbers, links)
            $table->json('content')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index(['page_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
