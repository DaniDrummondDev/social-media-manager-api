<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_executions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('automation_rule_id');
            $table->uuid('comment_id');
            $table->string('action_type', 30);
            $table->text('response_text')->nullable();
            $table->boolean('success');
            $table->text('error_message')->nullable();
            $table->integer('delay_applied')->default(0);
            $table->timestamp('executed_at');

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('automation_rule_id')->references('id')->on('automation_rules')->cascadeOnDelete();
            $table->foreign('comment_id')->references('id')->on('comments')->cascadeOnDelete();
            $table->index(['organization_id', 'executed_at']);
            $table->index(['automation_rule_id', 'executed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_executions');
    }
};
