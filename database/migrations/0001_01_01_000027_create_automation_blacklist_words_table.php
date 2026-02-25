<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        Schema::create('automation_blacklist_words', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('word', 100);
            $table->boolean('is_regex')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
        });

        if ($driver === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX idx_blacklist_org_word ON automation_blacklist_words (organization_id, LOWER(word))');
        } else {
            Schema::table('automation_blacklist_words', function (Blueprint $table) {
                $table->unique(['organization_id', 'word']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_blacklist_words');
    }
};
