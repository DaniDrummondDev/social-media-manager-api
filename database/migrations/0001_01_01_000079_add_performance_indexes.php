<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver !== 'pgsql') {
            return;
        }

        // Index for scheduled_posts.published_at - used in analytics queries
        DB::statement('CREATE INDEX IF NOT EXISTS idx_scheduled_posts_published_at ON scheduled_posts (published_at) WHERE published_at IS NOT NULL');

        // Index for ai_generations by organization and month - used in usage calculations
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ai_generations_org_created ON ai_generations (organization_id, created_at)');

        // Index for content_metrics by content_id and synced_at - analytics queries
        // Note: content_metrics doesn't have organization_id directly, it goes through content_id
        DB::statement('CREATE INDEX IF NOT EXISTS idx_content_metrics_content_synced ON content_metrics (content_id, synced_at)');

        // Index for comments by organization and captured_at - engagement queries
        DB::statement('CREATE INDEX IF NOT EXISTS idx_comments_org_captured ON comments (organization_id, captured_at)');

        // Index for webhook_deliveries retry queries - partial index for pending retries
        // Note: webhook_deliveries uses delivered_at/failed_at instead of status column
        DB::statement('CREATE INDEX IF NOT EXISTS idx_webhook_deliveries_retry ON webhook_deliveries (next_retry_at, attempts) WHERE delivered_at IS NULL AND failed_at IS NULL');

        // Index for automation_executions by rule and date - daily limit checks
        DB::statement('CREATE INDEX IF NOT EXISTS idx_automation_executions_rule_executed ON automation_executions (automation_rule_id, executed_at)');

        // Index for contents by organization and status - listing queries
        DB::statement('CREATE INDEX IF NOT EXISTS idx_contents_org_status_created ON contents (organization_id, status, created_at)');

        // Index for social_accounts by organization - frequently queried
        DB::statement('CREATE INDEX IF NOT EXISTS idx_social_accounts_org_provider ON social_accounts (organization_id, provider)');

        // Partial index for pending jobs - dispatcher queries
        DB::statement("CREATE INDEX IF NOT EXISTS idx_scheduled_posts_pending ON scheduled_posts (scheduled_at) WHERE status = 'pending'");

        // Partial index for active automation rules - automation engine queries
        DB::statement('CREATE INDEX IF NOT EXISTS idx_automation_rules_active ON automation_rules (organization_id, priority) WHERE is_active = true AND deleted_at IS NULL');
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS idx_scheduled_posts_published_at');
        DB::statement('DROP INDEX IF EXISTS idx_ai_generations_org_created');
        DB::statement('DROP INDEX IF EXISTS idx_content_metrics_content_synced');
        DB::statement('DROP INDEX IF EXISTS idx_comments_org_captured');
        DB::statement('DROP INDEX IF EXISTS idx_webhook_deliveries_retry');
        DB::statement('DROP INDEX IF EXISTS idx_automation_executions_rule_executed');
        DB::statement('DROP INDEX IF EXISTS idx_contents_org_status_created');
        DB::statement('DROP INDEX IF EXISTS idx_social_accounts_org_provider');
        DB::statement('DROP INDEX IF EXISTS idx_scheduled_posts_pending');
        DB::statement('DROP INDEX IF EXISTS idx_automation_rules_active');
    }
};
