<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $this->createPartitionedTable();
        } else {
            $this->createStandardTable();
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP TABLE IF EXISTS mentions CASCADE');
        } else {
            Schema::dropIfExists('mentions');
        }
    }

    private function createPartitionedTable(): void
    {
        DB::statement('
            CREATE TABLE mentions (
                id UUID NOT NULL,
                query_id UUID NOT NULL,
                organization_id UUID NOT NULL,
                platform VARCHAR(20) NOT NULL,
                external_id VARCHAR(255) NOT NULL,
                author_username VARCHAR(255) NOT NULL,
                author_display_name VARCHAR(255) NOT NULL,
                author_follower_count BIGINT,
                profile_url TEXT,
                content TEXT NOT NULL,
                url TEXT,
                sentiment VARCHAR(20),
                sentiment_score DECIMAL(5,4),
                reach BIGINT NOT NULL DEFAULT 0,
                engagement_count BIGINT NOT NULL DEFAULT 0,
                is_flagged BOOLEAN NOT NULL DEFAULT FALSE,
                is_read BOOLEAN NOT NULL DEFAULT FALSE,
                published_at TIMESTAMP NOT NULL,
                detected_at TIMESTAMP NOT NULL,
                PRIMARY KEY (id, detected_at)
            ) PARTITION BY RANGE (detected_at)
        ');

        $this->createMonthlyPartitions();

        DB::statement('CREATE INDEX idx_mentions_org_detected ON mentions (organization_id, detected_at)');
        DB::statement('CREATE INDEX idx_mentions_query_detected ON mentions (query_id, detected_at)');
        DB::statement('CREATE INDEX idx_mentions_org_sentiment ON mentions (organization_id, sentiment)');
        DB::statement('CREATE INDEX idx_mentions_org_flagged ON mentions (organization_id, is_flagged)');
        DB::statement('CREATE UNIQUE INDEX uq_mentions_external ON mentions (external_id, platform, detected_at)');
    }

    private function createStandardTable(): void
    {
        Schema::create('mentions', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('query_id');
            $table->uuid('organization_id');
            $table->string('platform', 20);
            $table->string('external_id', 255);
            $table->string('author_username', 255);
            $table->string('author_display_name', 255);
            $table->bigInteger('author_follower_count')->nullable();
            $table->text('profile_url')->nullable();
            $table->text('content');
            $table->text('url')->nullable();
            $table->string('sentiment', 20)->nullable();
            $table->decimal('sentiment_score', 5, 4)->nullable();
            $table->bigInteger('reach')->default(0);
            $table->bigInteger('engagement_count')->default(0);
            $table->boolean('is_flagged')->default(false);
            $table->boolean('is_read')->default(false);
            $table->timestamp('published_at');
            $table->timestamp('detected_at');

            $table->primary(['id', 'detected_at']);
            $table->index(['organization_id', 'detected_at']);
            $table->index(['query_id', 'detected_at']);
            $table->index(['organization_id', 'sentiment']);
            $table->index(['organization_id', 'is_flagged']);
            $table->unique(['external_id', 'platform', 'detected_at']);
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('query_id')->references('id')->on('listening_queries')->cascadeOnDelete();
        });
    }

    private function createMonthlyPartitions(): void
    {
        $now = now();

        for ($i = 0; $i < 3; $i++) {
            $date = $now->copy()->addMonths($i);
            $partitionName = 'mentions_'.$date->format('Y_m');
            $start = $date->startOfMonth()->format('Y-m-d');
            $end = $date->copy()->addMonth()->startOfMonth()->format('Y-m-d');

            DB::statement("
                CREATE TABLE IF NOT EXISTS {$partitionName}
                PARTITION OF mentions
                FOR VALUES FROM ('{$start}') TO ('{$end}')
            ");
        }
    }
};
