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
            DB::statement('DROP TABLE IF EXISTS account_metrics CASCADE');
        } else {
            Schema::dropIfExists('account_metrics');
        }
    }

    private function createPartitionedTable(): void
    {
        DB::statement('
            CREATE TABLE account_metrics (
                id UUID NOT NULL,
                social_account_id UUID NOT NULL REFERENCES social_accounts(id) ON DELETE CASCADE,
                provider VARCHAR(20) NOT NULL,
                date DATE NOT NULL,
                followers_count BIGINT NOT NULL DEFAULT 0,
                followers_gained BIGINT NOT NULL DEFAULT 0,
                followers_lost BIGINT NOT NULL DEFAULT 0,
                profile_views BIGINT NOT NULL DEFAULT 0,
                reach BIGINT NOT NULL DEFAULT 0,
                impressions BIGINT NOT NULL DEFAULT 0,
                synced_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP,
                updated_at TIMESTAMP,
                PRIMARY KEY (id, date),
                UNIQUE (social_account_id, date)
            ) PARTITION BY RANGE (date)
        ');

        $this->createMonthlyPartitions();

        DB::statement('CREATE INDEX idx_am_social_account_id ON account_metrics (social_account_id)');
        DB::statement('CREATE INDEX idx_am_date ON account_metrics (date)');
        DB::statement('CREATE INDEX idx_am_provider ON account_metrics (provider)');
    }

    private function createStandardTable(): void
    {
        Schema::create('account_metrics', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('social_account_id');
            $table->string('provider', 20);
            $table->date('date');
            $table->bigInteger('followers_count')->default(0);
            $table->bigInteger('followers_gained')->default(0);
            $table->bigInteger('followers_lost')->default(0);
            $table->bigInteger('profile_views')->default(0);
            $table->bigInteger('reach')->default(0);
            $table->bigInteger('impressions')->default(0);
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->primary(['id', 'date']);
            $table->foreign('social_account_id')->references('id')->on('social_accounts')->cascadeOnDelete();
            $table->unique(['social_account_id', 'date']);
            $table->index('social_account_id');
            $table->index('date');
            $table->index('provider');
        });
    }

    private function createMonthlyPartitions(): void
    {
        $now = now();

        for ($i = 0; $i < 3; $i++) {
            $date = $now->copy()->addMonths($i);
            $partitionName = 'account_metrics_'.$date->format('Y_m');
            $start = $date->startOfMonth()->format('Y-m-d');
            $end = $date->copy()->addMonth()->startOfMonth()->format('Y-m-d');

            DB::statement("
                CREATE TABLE IF NOT EXISTS {$partitionName}
                PARTITION OF account_metrics
                FOR VALUES FROM ('{$start}') TO ('{$end}')
            ");
        }
    }
};
