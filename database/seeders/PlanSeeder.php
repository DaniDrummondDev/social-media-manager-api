<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class PlanSeeder extends Seeder
{
    // Fixed UUIDs for deterministic seeding
    public const FREE_PLAN_ID = '00000000-0000-4000-a000-000000000001';

    public const CREATOR_PLAN_ID = '00000000-0000-4000-a000-000000000002';

    public const PROFESSIONAL_PLAN_ID = '00000000-0000-4000-a000-000000000003';

    public const AGENCY_PLAN_ID = '00000000-0000-4000-a000-000000000004';

    public function run(): void
    {
        DB::table('plans')->truncate();

        $plans = [
            [
                'id' => self::FREE_PLAN_ID,
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Teste a plataforma sem custo. Publique em 3 redes com IA basica.',
                'price_monthly_cents' => 0,
                'price_yearly_cents' => 0,
                'currency' => 'BRL',
                'limits' => json_encode([
                    'members' => 1,
                    'social_accounts' => 3,
                    'publications_month' => 30,
                    'ai_generations_month' => 50,
                    'storage_gb' => 1,
                    'active_campaigns' => 3,
                    'automations' => 0,
                    'webhooks' => 0,
                    'reports_month' => 5,
                    'analytics_retention_days' => 30,
                    'paid_advertising' => 0,
                ]),
                'features' => json_encode([
                    'ai_generation_basic' => true,
                    'ai_generation_advanced' => false,
                    'ai_intelligence' => false,
                    'ai_learning' => false,
                    'automations' => false,
                    'webhooks' => false,
                    'crm_native' => false,
                    'paid_advertising' => false,
                    'export_pdf' => false,
                    'export_csv' => true,
                    'priority_publishing' => false,
                ]),
                'is_active' => true,
                'sort_order' => 1,
                'stripe_price_monthly_id' => null,
                'stripe_price_yearly_id' => null,
            ],
            [
                'id' => self::CREATOR_PLAN_ID,
                'name' => 'Creator',
                'slug' => 'creator',
                'description' => 'Crie conteudo profissional com IA avancada que aprende com seu conteudo.',
                'price_monthly_cents' => 9700,
                'price_yearly_cents' => 97000,
                'currency' => 'BRL',
                'limits' => json_encode([
                    'members' => 2,
                    'social_accounts' => 5,
                    'publications_month' => 150,
                    'ai_generations_month' => 200,
                    'storage_gb' => 5,
                    'active_campaigns' => 10,
                    'automations' => 5,
                    'webhooks' => 0,
                    'reports_month' => 15,
                    'analytics_retention_days' => 90,
                    'paid_advertising' => 0,
                ]),
                'features' => json_encode([
                    'ai_generation_basic' => true,
                    'ai_generation_advanced' => true,
                    'ai_intelligence' => false,
                    'ai_learning' => false,
                    'automations' => true,
                    'webhooks' => false,
                    'crm_native' => false,
                    'paid_advertising' => false,
                    'export_pdf' => false,
                    'export_csv' => true,
                    'priority_publishing' => true,
                ]),
                'is_active' => true,
                'sort_order' => 2,
                'stripe_price_monthly_id' => env('STRIPE_PRICE_CREATOR_MONTHLY', 'price_1T8kTeK0TFrYT4C1gTZARb3x'),
                'stripe_price_yearly_id' => env('STRIPE_PRICE_CREATOR_YEARLY', 'price_1T8kU6K0TFrYT4C1KcWVaSoy'),
            ],
            [
                'id' => self::PROFESSIONAL_PLAN_ID,
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Gerencie multiplos clientes com IA preditiva que evolui com seu estilo e CRM nativos integrados.',
                'price_monthly_cents' => 29700,
                'price_yearly_cents' => 297000,
                'currency' => 'BRL',
                'limits' => json_encode([
                    'members' => 5,
                    'social_accounts' => 15,
                    'publications_month' => 500,
                    'ai_generations_month' => 500,
                    'storage_gb' => 15,
                    'active_campaigns' => 30,
                    'automations' => 15,
                    'webhooks' => 5,
                    'reports_month' => 50,
                    'analytics_retention_days' => 180,
                    'paid_advertising' => -1,
                ]),
                'features' => json_encode([
                    'ai_generation_basic' => true,
                    'ai_generation_advanced' => true,
                    'ai_intelligence' => true,
                    'ai_learning' => true,
                    'automations' => true,
                    'webhooks' => true,
                    'crm_native' => true,
                    'paid_advertising' => true,
                    'export_pdf' => true,
                    'export_csv' => true,
                    'priority_publishing' => true,
                ]),
                'is_active' => true,
                'sort_order' => 3,
                'stripe_price_monthly_id' => env('STRIPE_PRICE_PROFESSIONAL_MONTHLY', 'price_1T8kUYK0TFrYT4C18m77Epoh'),
                'stripe_price_yearly_id' => env('STRIPE_PRICE_PROFESSIONAL_YEARLY', 'price_1T8kUlK0TFrYT4C1fOYDyruK'),
            ],
            [
                'id' => self::AGENCY_PLAN_ID,
                'name' => 'Agency',
                'slug' => 'agency',
                'description' => 'Escale sua operacao com IA completa que se auto-otimiza, 5 CRMs e membros ilimitados.',
                'price_monthly_cents' => 69700,
                'price_yearly_cents' => 697000,
                'currency' => 'BRL',
                'limits' => json_encode([
                    'members' => -1,
                    'social_accounts' => 50,
                    'publications_month' => -1,
                    'ai_generations_month' => 5000,
                    'storage_gb' => 100,
                    'active_campaigns' => -1,
                    'automations' => 100,
                    'webhooks' => 20,
                    'reports_month' => -1,
                    'analytics_retention_days' => 730,
                    'paid_advertising' => -1,
                ]),
                'features' => json_encode([
                    'ai_generation_basic' => true,
                    'ai_generation_advanced' => true,
                    'ai_intelligence' => true,
                    'ai_learning' => true,
                    'automations' => true,
                    'webhooks' => true,
                    'crm_native' => true,
                    'paid_advertising' => true,
                    'export_pdf' => true,
                    'export_csv' => true,
                    'priority_publishing' => true,
                ]),
                'is_active' => true,
                'sort_order' => 4,
                'stripe_price_monthly_id' => env('STRIPE_PRICE_AGENCY_MONTHLY', 'price_1T8kVEK0TFrYT4C1oyGbdlFd'),
                'stripe_price_yearly_id' => env('STRIPE_PRICE_AGENCY_YEARLY', 'price_1T8kVRK0TFrYT4C1Zgv2m9Oy'),
            ],
        ];

        $now = now()->toDateTimeString();

        foreach ($plans as $plan) {
            DB::table('plans')->insert(array_merge($plan, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }
}
