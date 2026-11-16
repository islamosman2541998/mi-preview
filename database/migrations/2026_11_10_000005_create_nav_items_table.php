<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nav_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('nav_items')->cascadeOnDelete();
            $table->string('label_ar');
            $table->string('label_en')->nullable();
            $table->string('type', 20)->default('page');
            $table->string('target')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_cta')->default(false);
            $table->timestamps();

            $table->index(['site_id', 'parent_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nav_items');
    }
};
