<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Sprint 13 tables requiring RLS.
     *
     * @var array<string>
     */
    private array $tables = [
        'audience_insights',
        'ai_generation_context',
        'content_gap_analyses',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->tables as $table) {
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
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->tables as $table) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation ON {$table}");
            DB::statement("DROP POLICY IF EXISTS bypass_rls ON {$table}");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} NO FORCE ROW LEVEL SECURITY");
        }
    }
};
