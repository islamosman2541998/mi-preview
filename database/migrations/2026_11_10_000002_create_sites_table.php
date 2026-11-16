<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 40)->unique();
            $table->string('status', 20)->default('draft')->index();

            $table->string('company_name_ar');
            $table->string('company_name_en')->nullable();
            $table->foreignId('industry_id')->nullable()->constrained()->nullOnDelete();
            $table->string('template', 40)->default('industrial');
            $table->text('short_description')->nullable(); // input for AI (phase 3)

            $table->string('logo_path')->nullable();
            $table->string('logo_nobg_path')->nullable();
            $table->json('palette')->nullable();   // primary, secondary, accent, text, bg + shades
            $table->string('font_key', 40)->nullable(); // null → template default

            $table->string('phone', 30)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('address_ar')->nullable();
            $table->string('address_en')->nullable();
            $table->text('map_url')->nullable();
            $table->json('socials')->nullable();

            $table->string('default_locale', 2)->default('ar');
            $table->boolean('preloader_enabled')->default(true);
            $table->boolean('notify_enabled')->default(true);

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('first_viewed_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->unsignedInteger('views_count')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
