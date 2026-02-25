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

        Schema::create('comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('content_id');
            $table->uuid('social_account_id');
            $table->string('provider', 20);
            $table->string('external_comment_id', 255);
            $table->string('author_name', 255);
            $table->string('author_external_id', 255)->nullable();
            $table->string('author_profile_url', 2000)->nullable();
            $table->text('text');
            $table->string('sentiment', 20)->nullable();
            $table->decimal('sentiment_score', 5, 4)->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_from_owner')->default(false);
            $table->timestamp('replied_at')->nullable();
            $table->uuid('replied_by')->nullable();
            $table->boolean('replied_by_automation')->default(false);
            $table->text('reply_text')->nullable();
            $table->string('reply_external_id', 255)->nullable();
            $table->timestamp('commented_at');
            $table->timestamp('captured_at');
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('content_id')->references('id')->on('contents')->cascadeOnDelete();
            $table->foreign('social_account_id')->references('id')->on('social_accounts')->cascadeOnDelete();

            $table->unique(['social_account_id', 'external_comment_id']);
            $table->index(['organization_id', 'captured_at']);
            $table->index('content_id');
            $table->index('sentiment');
            $table->index(['is_read', 'replied_at']);
        });

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE comments ADD COLUMN text_searchable tsvector GENERATED ALWAYS AS (to_tsvector('portuguese', text)) STORED");
            DB::statement('CREATE INDEX idx_comments_text_search ON comments USING GIN (text_searchable)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
