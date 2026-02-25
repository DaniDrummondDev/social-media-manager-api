<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_rule_conditions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('automation_rule_id');
            $table->string('field', 50);
            $table->string('operator', 30);
            $table->text('value');
            $table->boolean('is_case_sensitive')->default(false);
            $table->smallInteger('position')->default(0);

            $table->foreign('automation_rule_id')->references('id')->on('automation_rules')->cascadeOnDelete();
            $table->index('automation_rule_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_rule_conditions');
    }
};
