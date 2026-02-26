<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_generations', function (Blueprint $table) {
            if (! Schema::hasColumn('ai_generations', 'prompt_template_id')) {
                $table->uuid('prompt_template_id')->nullable()->after('cost_estimate');
            }
            if (! Schema::hasColumn('ai_generations', 'experiment_id')) {
                $table->uuid('experiment_id')->nullable()->after('prompt_template_id');
            }
            if (! Schema::hasColumn('ai_generations', 'rag_context_used')) {
                $table->json('rag_context_used')->nullable()->after('experiment_id');
            }
            if (! Schema::hasColumn('ai_generations', 'style_context_used')) {
                $table->boolean('style_context_used')->default(false)->after('rag_context_used');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_generations', function (Blueprint $table) {
            $table->dropColumn([
                'prompt_template_id',
                'experiment_id',
                'rag_context_used',
                'style_context_used',
            ]);
        });
    }
};
