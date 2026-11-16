<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('industries', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->json('sections_blueprint')->nullable(); // default pages → sections list
            $table->json('stock_keywords')->nullable();
            $table->text('ai_hints')->nullable();
            $table->json('default_palette')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('industries');
    }
};
