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
            DB::statement('DROP TABLE IF EXISTS content_metric_snapshots CASCADE');
        } else {
            Schema::dropIfExists('content_metric_snapshots');
        }
    }

    private function createPartitionedTable(): void
    {
        DB::statement('
            CREATE TABLE content_metric_snapshots (
                id UUID NOT NULL,
                content_metric_id UUID NOT NULL REFERENCES content_metrics(id) ON DELETE CASCADE,
                impressions BIGINT NOT NULL DEFAULT 0,
                reach BIGINT NOT NULL DEFAULT 0,
                likes BIGINT NOT NULL DEFAULT 0,
                comments BIGINT NOT NULL DEFAULT 0,
                shares BIGINT NOT NULL DEFAULT 0,
                saves BIGINT NOT NULL DEFAULT 0,
                clicks BIGINT NOT NULL DEFAULT 0,
                views BIGINT,
                watch_time_seconds BIGINT,
                engagement_rate DECIMAL(8,4) NOT NULL DEFAULT 0,
                captured_at TIMESTAMP NOT NULL,
                PRIMARY KEY (id, captured_at)
            ) PARTITION BY RANGE (captured_at)
        ');

        $this->createMonthlyPartitions();

        DB::statement('CREATE INDEX idx_cms_content_metric_id ON content_metric_snapshots (content_metric_id)');
        DB::statement('CREATE INDEX idx_cms_captured_at ON content_metric_snapshots (captured_at)');
    }

    private function createStandardTable(): void
    {
        Schema::create('content_metric_snapshots', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('content_metric_id');
            $table->bigInteger('impressions')->default(0);
            $table->bigInteger('reach')->default(0);
            $table->bigInteger('likes')->default(0);
            $table->bigInteger('comments')->default(0);
            $table->bigInteger('shares')->default(0);
            $table->bigInteger('saves')->default(0);
            $table->bigInteger('clicks')->default(0);
            $table->bigInteger('views')->nullable();
            $table->bigInteger('watch_time_seconds')->nullable();
            $table->decimal('engagement_rate', 8, 4)->default(0);
            $table->timestamp('captured_at');

            $table->primary(['id', 'captured_at']);
            $table->foreign('content_metric_id')->references('id')->on('content_metrics')->cascadeOnDelete();
            $table->index('content_metric_id');
            $table->index('captured_at');
        });
    }

    private function createMonthlyPartitions(): void
    {
        $now = now();

        for ($i = 0; $i < 3; $i++) {
            $date = $now->copy()->addMonths($i);
            $partitionName = 'content_metric_snapshots_'.$date->format('Y_m');
            $start = $date->startOfMonth()->format('Y-m-d');
            $end = $date->copy()->addMonth()->startOfMonth()->format('Y-m-d');

            DB::statement("
                CREATE TABLE IF NOT EXISTS {$partitionName}
                PARTITION OF content_metric_snapshots
                FOR VALUES FROM ('{$start}') TO ('{$end}')
            ");
        }
    }
};
