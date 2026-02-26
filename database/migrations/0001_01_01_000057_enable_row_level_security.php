<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Multi-tenant tables with NOT NULL organization_id.
     *
     * @var array<string>
     */
    private array $tenantTables = [
        // Organization Management
        'organization_members',
        'organization_invites',

        // Social Account Management
        'social_accounts',

        // Campaign Management
        'campaigns',
        'contents',

        // Content AI
        'ai_generations',
        'ai_settings',

        // Publishing
        'scheduled_posts',

        // Analytics
        'report_exports',

        // Engagement & Automation
        'comments',
        'automation_rules',
        'automation_executions',
        'automation_blacklist_words',
        'webhook_endpoints',

        // Media Management
        'media',
        'media_uploads',

        // Billing & Subscription
        'subscriptions',
        'invoices',
        'usage_records',

        // Client Finance
        'clients',
        'client_contracts',
        'client_invoices',
        'cost_allocations',

        // Social Listening
        'listening_queries',
        'mentions',
        'listening_alerts',
        'listening_alert_notifications',
        'listening_reports',

        // AI Intelligence
        'embedding_jobs',
        'content_profiles',
        'brand_safety_checks',
        'brand_safety_rules',
        'calendar_suggestions',
        'posting_time_recommendations',
        'performance_predictions',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Standard tenant tables (organization_id NOT NULL)
        foreach ($this->tenantTables as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");

            DB::statement("
                CREATE POLICY tenant_isolation ON {$table}
                    USING (organization_id = current_setting('app.current_org_id', true)::uuid)
                    WITH CHECK (organization_id = current_setting('app.current_org_id', true)::uuid)
            ");

            DB::statement("
                CREATE POLICY bypass_rls ON {$table}
                    USING (current_setting('app.current_org_id', true) IS NULL)
                    WITH CHECK (current_setting('app.current_org_id', true) IS NULL)
            ");
        }

        // Special case: audit_logs has nullable organization_id
        DB::statement('ALTER TABLE audit_logs ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE audit_logs FORCE ROW LEVEL SECURITY');

        DB::statement("
            CREATE POLICY tenant_isolation ON audit_logs
                USING (
                    organization_id IS NULL
                    OR organization_id = current_setting('app.current_org_id', true)::uuid
                )
                WITH CHECK (
                    organization_id IS NULL
                    OR organization_id = current_setting('app.current_org_id', true)::uuid
                )
        ");

        DB::statement("
            CREATE POLICY bypass_rls ON audit_logs
                USING (current_setting('app.current_org_id', true) IS NULL)
                WITH CHECK (current_setting('app.current_org_id', true) IS NULL)
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $allTables = array_merge($this->tenantTables, ['audit_logs']);

        foreach ($allTables as $table) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation ON {$table}");
            DB::statement("DROP POLICY IF EXISTS bypass_rls ON {$table}");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} NO FORCE ROW LEVEL SECURITY");
        }
    }
};
