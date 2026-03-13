<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Enable Row-Level Security on tables added after Sprint 13.
 *
 * This migration adds RLS to 14 tables that were created after the initial
 * RLS migration (000057) and sprint 13 RLS migration (000061).
 *
 * Security Impact: CRITICAL
 * - Without RLS, these tables allow cross-tenant data access
 * - This migration enforces organization isolation at database level
 *
 * Tables covered:
 * - content_embeddings (AI Intelligence - pgvector embeddings)
 * - generation_feedback (Content AI - user feedback on AI generations)
 * - prompt_experiments (Content AI - A/B testing prompts)
 * - prediction_validations (AI Intelligence - accuracy tracking)
 * - org_style_profiles (AI Intelligence - organization style DNA)
 * - crm_connections (Engagement - CRM integrations)
 * - crm_field_mappings (Engagement - field mapping configs)
 * - crm_sync_logs (Engagement - sync history)
 * - crm_conversion_attributions (Engagement - conversion tracking)
 * - ad_accounts (Advertising - connected ad accounts)
 * - audiences (Advertising - target audiences)
 * - ad_boosts (Advertising - boosted posts)
 * - ad_performance_insights (Advertising - AI insights)
 *
 * Special case:
 * - prompt_templates (nullable organization_id - system + tenant templates)
 *
 * @see docs/audits/security-audit-database.md
 */
return new class extends Migration
{
    /**
     * Tables with NOT NULL organization_id.
     */
    private array $tenantTables = [
        // AI Intelligence (Sprint 14+)
        'content_embeddings',
        'prediction_validations',
        'org_style_profiles',

        // Content AI (Sprint 17 - AI Learning)
        'generation_feedback',
        'prompt_experiments',

        // Engagement - CRM (Sprint 18)
        'crm_connections',
        // 'crm_field_mappings' - uses subquery RLS via parent (crm_connections)
        'crm_sync_logs',
        'crm_conversion_attributions',

        // Advertising (Sprint 19)
        'ad_accounts',
        'audiences',
        'ad_boosts',
        'ad_performance_insights',
    ];

    /**
     * Child tables that inherit tenant isolation via parent FK.
     * Key = child table, Value = [parent_table, parent_fk_column]
     */
    private array $childTables = [
        'crm_field_mappings' => ['crm_connections', 'crm_connection_id'],
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Standard tenant tables (organization_id NOT NULL)
        foreach ($this->tenantTables as $table) {
            $this->enableRlsForTenantTable($table);
        }

        // Child tables that inherit isolation via parent FK
        foreach ($this->childTables as $table => [$parentTable, $parentFk]) {
            $this->enableRlsForChildTable($table, $parentTable, $parentFk);
        }

        // Special case: prompt_templates has nullable organization_id
        // NULL = system template (available to all)
        // UUID = organization-specific template
        $this->enableRlsForPromptTemplates();
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $allTables = array_merge(
            $this->tenantTables,
            array_keys($this->childTables),
            ['prompt_templates']
        );

        foreach ($allTables as $table) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation ON {$table}");
            DB::statement("DROP POLICY IF EXISTS bypass_rls ON {$table}");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} NO FORCE ROW LEVEL SECURITY");
        }
    }

    private function enableRlsForTenantTable(string $table): void
    {
        DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
        DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");

        // Policy: Only allow access to rows matching current org context
        DB::statement("
            CREATE POLICY tenant_isolation ON {$table}
                USING (organization_id = current_setting('app.current_org_id', true)::uuid)
                WITH CHECK (organization_id = current_setting('app.current_org_id', true)::uuid)
        ");

        // Policy: Allow bypass when no org context is set (migrations, jobs, admin)
        DB::statement("
            CREATE POLICY bypass_rls ON {$table}
                USING (current_setting('app.current_org_id', true) IS NULL)
                WITH CHECK (current_setting('app.current_org_id', true) IS NULL)
        ");
    }

    /**
     * Enable RLS for child tables that inherit tenant isolation via parent FK.
     * Uses a subquery to check organization_id in the parent table.
     */
    private function enableRlsForChildTable(string $table, string $parentTable, string $parentFk): void
    {
        DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
        DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");

        // Policy: Only allow access to rows where parent belongs to current org
        DB::statement("
            CREATE POLICY tenant_isolation ON {$table}
                USING (
                    EXISTS (
                        SELECT 1 FROM {$parentTable}
                        WHERE {$parentTable}.id = {$table}.{$parentFk}
                        AND {$parentTable}.organization_id = current_setting('app.current_org_id', true)::uuid
                    )
                )
                WITH CHECK (
                    EXISTS (
                        SELECT 1 FROM {$parentTable}
                        WHERE {$parentTable}.id = {$table}.{$parentFk}
                        AND {$parentTable}.organization_id = current_setting('app.current_org_id', true)::uuid
                    )
                )
        ");

        // Policy: Allow bypass when no org context is set (migrations, jobs, admin)
        DB::statement("
            CREATE POLICY bypass_rls ON {$table}
                USING (current_setting('app.current_org_id', true) IS NULL)
                WITH CHECK (current_setting('app.current_org_id', true) IS NULL)
        ");
    }

    private function enableRlsForPromptTemplates(): void
    {
        DB::statement('ALTER TABLE prompt_templates ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE prompt_templates FORCE ROW LEVEL SECURITY');

        // Policy: Allow access to system templates (org_id IS NULL) OR matching org
        DB::statement("
            CREATE POLICY tenant_isolation ON prompt_templates
                USING (
                    organization_id IS NULL
                    OR organization_id = current_setting('app.current_org_id', true)::uuid
                )
                WITH CHECK (
                    organization_id IS NULL
                    OR organization_id = current_setting('app.current_org_id', true)::uuid
                )
        ");

        // Policy: Allow bypass when no org context is set
        DB::statement("
            CREATE POLICY bypass_rls ON prompt_templates
                USING (current_setting('app.current_org_id', true) IS NULL)
                WITH CHECK (current_setting('app.current_org_id', true) IS NULL)
        ");
    }
};
