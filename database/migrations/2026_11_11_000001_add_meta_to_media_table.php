<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            // pexels: {id, photographer, photographer_url, url, avg_color, width, height}
            $table->json('meta')->nullable()->after('credit');
            $table->index(['site_id', 'path']);
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex(['site_id', 'path']);
            $table->dropColumn('meta');
        });
    }
};
