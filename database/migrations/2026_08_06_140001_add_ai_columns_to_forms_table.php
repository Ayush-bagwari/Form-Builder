<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->string('ai_status')->default('idle')->after('status'); // 'idle', 'queued', 'processing', 'completed', 'failed'
            $table->text('ai_prompt')->nullable()->after('ai_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn(['ai_status', 'ai_prompt']);
        });
    }
};
