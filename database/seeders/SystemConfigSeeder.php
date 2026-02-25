<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class SystemConfigSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            ['key' => 'maintenance_mode', 'value' => json_encode(false), 'value_type' => 'boolean', 'description' => 'Ativa modo de manutenção'],
            ['key' => 'registration_enabled', 'value' => json_encode(true), 'value_type' => 'boolean', 'description' => 'Permite novos registros'],
            ['key' => 'default_trial_days', 'value' => json_encode(14), 'value_type' => 'integer', 'description' => 'Dias de trial para planos pagos'],
            ['key' => 'max_orgs_per_user', 'value' => json_encode(5), 'value_type' => 'integer', 'description' => 'Máximo de organizações por usuário'],
            ['key' => 'ai_global_enabled', 'value' => json_encode(true), 'value_type' => 'boolean', 'description' => 'Habilita/desabilita IA globalmente'],
            ['key' => 'publishing_global_enabled', 'value' => json_encode(true), 'value_type' => 'boolean', 'description' => 'Habilita/desabilita publicação'],
        ];

        foreach ($configs as $config) {
            DB::table('system_configs')->updateOrInsert(
                ['key' => $config['key']],
                array_merge($config, [
                    'is_secret' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
            );
        }
    }
}
