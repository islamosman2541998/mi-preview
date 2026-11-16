<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // AI content generation runs (whole site or selected sections), queued.
        Schema::create('ai_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->json('section_ids')->nullable();        // null = every section of the site
            $table->string('status', 20)->default('queued'); // queued|running|done|failed
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('stage')->nullable();             // human-readable step (Arabic)
            $table->json('options')->nullable();             // {instructions, fill_images, seo}
            $table->json('usage')->nullable();               // {model, input_tokens, output_tokens, cost_usd}
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'status']);
        });

        Schema::table('sites', function (Blueprint $table) {
            // {title_ar, title_en, description_ar, description_en} — generated, editable
            $table->json('seo')->nullable()->after('socials');
        });
    }

    public function down(): void
    {
        Schema::table('sites', fn (Blueprint $table) => $table->dropColumn('seo'));
        Schema::dropIfExists('ai_runs');
    }
};
