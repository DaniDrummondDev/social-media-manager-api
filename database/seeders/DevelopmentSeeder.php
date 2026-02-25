<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Identity\ValueObjects\HashedPassword;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        // Limpar dados anteriores (ordem respeita foreign keys)
        DB::table('webhook_deliveries')->truncate();
        DB::table('webhook_endpoints')->truncate();
        DB::table('automation_executions')->truncate();
        DB::table('automation_rule_conditions')->truncate();
        DB::table('automation_rules')->truncate();
        DB::table('automation_blacklist_words')->truncate();
        DB::table('comments')->truncate();
        DB::table('report_exports')->truncate();
        DB::table('content_metric_snapshots')->truncate();
        DB::table('content_metrics')->truncate();
        DB::table('account_metrics')->truncate();
        DB::table('scheduled_posts')->truncate();
        DB::table('contents')->truncate();
        DB::table('campaigns')->truncate();
        DB::table('ai_settings')->truncate();
        DB::table('social_accounts')->truncate();
        DB::table('organization_members')->truncate();
        DB::table('organizations')->truncate();
        DB::table('refresh_tokens')->truncate();
        DB::table('users')->truncate();

        // ── 1. User ─────────────────────────────────────────────────
        $userId = (string) Str::uuid();
        $email = 'admin@demo.com';
        $password = 'Secret@123';

        DB::table('users')->insert([
            'id' => $userId,
            'name' => 'Demo Admin',
            'email' => $email,
            'password' => (string) HashedPassword::fromPlainText($password),
            'phone' => null,
            'timezone' => 'America/Sao_Paulo',
            'email_verified_at' => now()->toDateTimeString(),
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'recovery_codes' => null,
            'status' => 'active',
            'last_login_at' => null,
            'last_login_ip' => null,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        // ── 2. Organization + Member ────────────────────────────────
        $orgId = (string) Str::uuid();

        DB::table('organizations')->insert([
            'id' => $orgId,
            'name' => 'Demo Agency',
            'slug' => 'demo-agency',
            'timezone' => 'America/Sao_Paulo',
            'status' => 'active',
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        DB::table('organization_members')->insert([
            'id' => (string) Str::uuid(),
            'organization_id' => $orgId,
            'user_id' => $userId,
            'role' => 'owner',
            'invited_by' => null,
            'joined_at' => now()->toDateTimeString(),
        ]);

        // ── 3. Social Accounts (3) ─────────────────────────────────
        $igAccountId = (string) Str::uuid();
        $ttAccountId = (string) Str::uuid();
        $ytAccountId = (string) Str::uuid();

        $socialAccounts = [
            [
                'id' => $igAccountId,
                'provider' => 'instagram',
                'provider_user_id' => 'ig-demo-001',
                'username' => '@demo_instagram',
                'display_name' => 'Demo Instagram',
            ],
            [
                'id' => $ttAccountId,
                'provider' => 'tiktok',
                'provider_user_id' => 'tt-demo-001',
                'username' => '@demo_tiktok',
                'display_name' => 'Demo TikTok',
            ],
            [
                'id' => $ytAccountId,
                'provider' => 'youtube',
                'provider_user_id' => 'yt-demo-001',
                'username' => '@demo_youtube',
                'display_name' => 'Demo YouTube',
            ],
        ];

        foreach ($socialAccounts as $account) {
            DB::table('social_accounts')->insert([
                'id' => $account['id'],
                'organization_id' => $orgId,
                'connected_by' => $userId,
                'provider' => $account['provider'],
                'provider_user_id' => $account['provider_user_id'],
                'username' => $account['username'],
                'display_name' => $account['display_name'],
                'profile_picture_url' => null,
                'access_token' => 'demo-encrypted-access-token-'.$account['provider'],
                'refresh_token' => 'demo-encrypted-refresh-token-'.$account['provider'],
                'token_expires_at' => now()->addDays(30)->toDateTimeString(),
                'scopes' => json_encode(['read', 'write', 'publish']),
                'status' => 'connected',
                'last_synced_at' => now()->toDateTimeString(),
                'connected_at' => now()->subDays(7)->toDateTimeString(),
                'disconnected_at' => null,
                'metadata' => null,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
                'deleted_at' => null,
                'purge_at' => null,
            ]);
        }

        // ── 4. Campaigns (2) ────────────────────────────────────────
        $campaignBfId = (string) Str::uuid();
        $campaignVeraoId = (string) Str::uuid();

        DB::table('campaigns')->insert([
            'id' => $campaignBfId,
            'organization_id' => $orgId,
            'created_by' => $userId,
            'name' => 'Black Friday 2026',
            'description' => 'Campanha de Black Friday com promocoes em todas as redes.',
            'starts_at' => now()->addMonths(9)->toDateTimeString(),
            'ends_at' => now()->addMonths(9)->addDays(7)->toDateTimeString(),
            'status' => 'active',
            'tags' => json_encode(['black-friday', 'promocao', '2026']),
            'deleted_at' => null,
            'purge_at' => null,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        DB::table('campaigns')->insert([
            'id' => $campaignVeraoId,
            'organization_id' => $orgId,
            'created_by' => $userId,
            'name' => 'Lancamento Verao',
            'description' => 'Lancamento da colecao de verao 2026.',
            'starts_at' => null,
            'ends_at' => null,
            'status' => 'draft',
            'tags' => json_encode(['verao', 'lancamento']),
            'deleted_at' => null,
            'purge_at' => null,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        // ── 5. Contents (4) ────────────────────────────────────────
        $contentMegaPromoId = (string) Str::uuid();
        $contentDescontoId = (string) Str::uuid();
        $contentColecaoId = (string) Str::uuid();
        $contentTeaserId = (string) Str::uuid();

        $contents = [
            [
                'id' => $contentMegaPromoId,
                'campaign_id' => $campaignBfId,
                'title' => 'Mega Promocao BF',
                'body' => 'Aproveite descontos de ate 70% em toda a loja! Somente nesta Black Friday.',
                'hashtags' => json_encode(['blackfriday', 'desconto', 'promocao']),
                'status' => 'ready',
            ],
            [
                'id' => $contentDescontoId,
                'campaign_id' => $campaignBfId,
                'title' => 'Desconto Relampago',
                'body' => 'Flash sale: 50% de desconto nos proximos 30 minutos!',
                'hashtags' => json_encode(['flashsale', 'desconto']),
                'status' => 'draft',
            ],
            [
                'id' => $contentColecaoId,
                'campaign_id' => $campaignVeraoId,
                'title' => 'Colecao Verao 2026',
                'body' => 'A nova colecao de verao chegou! Pecas leves, cores vibrantes e muito estilo.',
                'hashtags' => json_encode(['verao2026', 'moda', 'colecao']),
                'status' => 'ready',
            ],
            [
                'id' => $contentTeaserId,
                'campaign_id' => $campaignVeraoId,
                'title' => 'Teaser Verao',
                'body' => 'Algo incrivel esta chegando... Fiquem ligados!',
                'hashtags' => json_encode(['teaser', 'verao', 'embreve']),
                'status' => 'scheduled',
            ],
        ];

        foreach ($contents as $content) {
            DB::table('contents')->insert([
                'id' => $content['id'],
                'organization_id' => $orgId,
                'campaign_id' => $content['campaign_id'],
                'created_by' => $userId,
                'title' => $content['title'],
                'body' => $content['body'],
                'hashtags' => $content['hashtags'],
                'status' => $content['status'],
                'ai_generation_id' => null,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
                'deleted_at' => null,
                'purge_at' => null,
            ]);
        }

        // ── 6. Scheduled Posts (4) ──────────────────────────────────
        $scheduledPosts = [
            [
                'id' => (string) Str::uuid(),
                'content_id' => $contentTeaserId,
                'social_account_id' => $igAccountId,
                'scheduled_at' => now()->addHours(2)->toDateTimeString(),
                'status' => 'pending',
                'published_at' => null,
                'external_post_id' => null,
                'external_post_url' => null,
                'attempts' => 0,
                'last_attempted_at' => null,
                'last_error' => null,
                'next_retry_at' => null,
                'dispatched_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'content_id' => $contentTeaserId,
                'social_account_id' => $ttAccountId,
                'scheduled_at' => now()->addHours(3)->toDateTimeString(),
                'status' => 'pending',
                'published_at' => null,
                'external_post_id' => null,
                'external_post_url' => null,
                'attempts' => 0,
                'last_attempted_at' => null,
                'last_error' => null,
                'next_retry_at' => null,
                'dispatched_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'content_id' => $contentMegaPromoId,
                'social_account_id' => $igAccountId,
                'scheduled_at' => now()->subHour()->toDateTimeString(),
                'status' => 'failed',
                'published_at' => null,
                'external_post_id' => null,
                'external_post_url' => null,
                'attempts' => 1,
                'last_attempted_at' => now()->subMinutes(30)->toDateTimeString(),
                'last_error' => json_encode(['code' => 'API_TIMEOUT', 'message' => 'Instagram API timeout after 30s', 'is_permanent' => false]),
                'next_retry_at' => now()->addMinutes(5)->toDateTimeString(),
                'dispatched_at' => now()->subHour()->toDateTimeString(),
            ],
            [
                'id' => (string) Str::uuid(),
                'content_id' => $contentColecaoId,
                'social_account_id' => $ytAccountId,
                'scheduled_at' => now()->subHours(2)->toDateTimeString(),
                'status' => 'published',
                'published_at' => now()->subHours(2)->addMinutes(1)->toDateTimeString(),
                'external_post_id' => 'yt-post-abc123',
                'external_post_url' => 'https://youtube.com/watch?v=abc123',
                'attempts' => 1,
                'last_attempted_at' => now()->subHours(2)->toDateTimeString(),
                'last_error' => null,
                'next_retry_at' => null,
                'dispatched_at' => now()->subHours(2)->toDateTimeString(),
            ],
        ];

        foreach ($scheduledPosts as $post) {
            DB::table('scheduled_posts')->insert([
                'id' => $post['id'],
                'organization_id' => $orgId,
                'content_id' => $post['content_id'],
                'social_account_id' => $post['social_account_id'],
                'scheduled_by' => $userId,
                'scheduled_at' => $post['scheduled_at'],
                'status' => $post['status'],
                'published_at' => $post['published_at'],
                'external_post_id' => $post['external_post_id'],
                'external_post_url' => $post['external_post_url'],
                'attempts' => $post['attempts'],
                'max_attempts' => 3,
                'last_attempted_at' => $post['last_attempted_at'],
                'last_error' => $post['last_error'],
                'next_retry_at' => $post['next_retry_at'],
                'dispatched_at' => $post['dispatched_at'],
                'idempotency_key' => null,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ]);
        }

        // ── 7. AI Settings ──────────────────────────────────────────
        DB::table('ai_settings')->insert([
            'organization_id' => $orgId,
            'default_tone' => 'professional',
            'custom_tone_description' => null,
            'default_language' => 'pt_BR',
            'monthly_generation_limit' => 100,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        // ── 8. Content Metrics ───────────────────────────────────────
        $contentMetricIds = [];
        $contentMetricData = [
            [
                'content_id' => $contentColecaoId,
                'social_account_id' => $ytAccountId,
                'provider' => 'youtube',
                'external_post_id' => 'yt-post-abc123',
                'impressions' => 12500,
                'reach' => 8700,
                'likes' => 340,
                'comments' => 45,
                'shares' => 28,
                'saves' => 15,
                'clicks' => 120,
                'views' => 9800,
                'watch_time_seconds' => 45000,
            ],
            [
                'content_id' => $contentMegaPromoId,
                'social_account_id' => $igAccountId,
                'provider' => 'instagram',
                'external_post_id' => null,
                'impressions' => 8200,
                'reach' => 5400,
                'likes' => 620,
                'comments' => 85,
                'shares' => 42,
                'saves' => 95,
                'clicks' => 210,
                'views' => null,
                'watch_time_seconds' => null,
            ],
        ];

        foreach ($contentMetricData as $metric) {
            $metricId = (string) Str::uuid();
            $contentMetricIds[] = $metricId;
            $reach = $metric['reach'] > 0 ? $metric['reach'] : 1;
            $engagementRate = round(
                ($metric['likes'] + $metric['comments'] + $metric['shares'] + $metric['saves']) / $reach * 100,
                4,
            );

            DB::table('content_metrics')->insert([
                'id' => $metricId,
                'content_id' => $metric['content_id'],
                'social_account_id' => $metric['social_account_id'],
                'provider' => $metric['provider'],
                'external_post_id' => $metric['external_post_id'],
                'impressions' => $metric['impressions'],
                'reach' => $metric['reach'],
                'likes' => $metric['likes'],
                'comments' => $metric['comments'],
                'shares' => $metric['shares'],
                'saves' => $metric['saves'],
                'clicks' => $metric['clicks'],
                'views' => $metric['views'],
                'watch_time_seconds' => $metric['watch_time_seconds'],
                'engagement_rate' => $engagementRate,
                'synced_at' => now()->toDateTimeString(),
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ]);
        }

        // ── 9. Content Metric Snapshots ──────────────────────────────
        foreach ($contentMetricIds as $i => $cmId) {
            $m = $contentMetricData[$i];
            foreach ([24, 48, 168] as $hours) {
                $factor = match ($hours) {
                    24 => 0.3,
                    48 => 0.6,
                    default => 1.0,
                };
                DB::table('content_metric_snapshots')->insert([
                    'id' => (string) Str::uuid(),
                    'content_metric_id' => $cmId,
                    'impressions' => (int) ($m['impressions'] * $factor),
                    'reach' => (int) ($m['reach'] * $factor),
                    'likes' => (int) ($m['likes'] * $factor),
                    'comments' => (int) ($m['comments'] * $factor),
                    'shares' => (int) ($m['shares'] * $factor),
                    'saves' => (int) ($m['saves'] * $factor),
                    'clicks' => (int) ($m['clicks'] * $factor),
                    'views' => $m['views'] !== null ? (int) ($m['views'] * $factor) : null,
                    'watch_time_seconds' => $m['watch_time_seconds'] !== null ? (int) ($m['watch_time_seconds'] * $factor) : null,
                    'engagement_rate' => round(
                        ((int) ($m['likes'] * $factor) + (int) ($m['comments'] * $factor) + (int) ($m['shares'] * $factor) + (int) ($m['saves'] * $factor))
                        / max((int) ($m['reach'] * $factor), 1) * 100,
                        4,
                    ),
                    'captured_at' => now()->subHours($hours)->toDateTimeString(),
                ]);
            }
        }

        // ── 10. Account Metrics (últimos 7 dias) ─────────────────────
        $accountProviders = [
            $igAccountId => 'instagram',
            $ttAccountId => 'tiktok',
            $ytAccountId => 'youtube',
        ];

        foreach ($accountProviders as $accId => $provider) {
            $baseFollowers = match ($provider) {
                'instagram' => 15000,
                'tiktok' => 8500,
                'youtube' => 22000,
                default => 1000,
            };

            for ($day = 6; $day >= 0; $day--) {
                $date = now()->subDays($day);
                $gained = random_int(20, 80);
                $lost = random_int(5, 20);
                $baseFollowers += ($gained - $lost);

                DB::table('account_metrics')->insert([
                    'id' => (string) Str::uuid(),
                    'social_account_id' => $accId,
                    'provider' => $provider,
                    'date' => $date->toDateString(),
                    'followers_count' => $baseFollowers,
                    'followers_gained' => $gained,
                    'followers_lost' => $lost,
                    'profile_views' => random_int(100, 500),
                    'reach' => random_int(2000, 8000),
                    'impressions' => random_int(5000, 15000),
                    'synced_at' => now()->toDateTimeString(),
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ]);
            }
        }

        // ── 11. Report Exports ───────────────────────────────────────
        $exportReadyId = (string) Str::uuid();
        $exportProcessingId = (string) Str::uuid();

        DB::table('report_exports')->insert([
            'id' => $exportReadyId,
            'organization_id' => $orgId,
            'user_id' => $userId,
            'type' => 'overview',
            'format' => 'pdf',
            'filters' => json_encode(['period' => '30d']),
            'status' => 'ready',
            'file_path' => 'exports/report-overview-30d.pdf',
            'file_size' => 245760,
            'error_message' => null,
            'expires_at' => now()->addHours(24)->toDateTimeString(),
            'completed_at' => now()->subMinutes(30)->toDateTimeString(),
            'created_at' => now()->subMinutes(35)->toDateTimeString(),
            'updated_at' => now()->subMinutes(30)->toDateTimeString(),
        ]);

        DB::table('report_exports')->insert([
            'id' => $exportProcessingId,
            'organization_id' => $orgId,
            'user_id' => $userId,
            'type' => 'network',
            'format' => 'csv',
            'filters' => json_encode(['period' => '7d', 'provider' => 'instagram']),
            'status' => 'processing',
            'file_path' => null,
            'file_size' => null,
            'error_message' => null,
            'expires_at' => null,
            'completed_at' => null,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        // ── 12. Comments (4) ──────────────────────────────────────────
        $commentIds = [];
        $commentsData = [
            [
                'external_comment_id' => 'ig-comment-001',
                'content_id' => $contentColecaoId,
                'social_account_id' => $ytAccountId,
                'provider' => 'youtube',
                'author_name' => 'Maria Silva',
                'text' => 'Amei a colecao! Quando chega nas lojas?',
                'sentiment' => 'positive',
                'sentiment_score' => 0.92,
                'is_from_owner' => false,
            ],
            [
                'external_comment_id' => 'ig-comment-002',
                'content_id' => $contentMegaPromoId,
                'social_account_id' => $igAccountId,
                'provider' => 'instagram',
                'author_name' => 'Joao Santos',
                'text' => 'Desconto real ou so marketing?',
                'sentiment' => 'negative',
                'sentiment_score' => 0.3,
                'is_from_owner' => false,
            ],
            [
                'external_comment_id' => 'ig-comment-003',
                'content_id' => $contentMegaPromoId,
                'social_account_id' => $igAccountId,
                'provider' => 'instagram',
                'author_name' => 'Ana Oliveira',
                'text' => 'Comprei no ano passado e adorei! Recomendo!',
                'sentiment' => 'positive',
                'sentiment_score' => 0.88,
                'is_from_owner' => false,
            ],
            [
                'external_comment_id' => 'ig-comment-004',
                'content_id' => $contentColecaoId,
                'social_account_id' => $ytAccountId,
                'provider' => 'youtube',
                'author_name' => 'Demo YouTube',
                'text' => 'Obrigado pelo feedback! Em breve nas lojas.',
                'sentiment' => 'positive',
                'sentiment_score' => 0.85,
                'is_from_owner' => true,
            ],
        ];

        foreach ($commentsData as $comment) {
            $commentId = (string) Str::uuid();
            $commentIds[] = $commentId;
            DB::table('comments')->insert([
                'id' => $commentId,
                'organization_id' => $orgId,
                'content_id' => $comment['content_id'],
                'social_account_id' => $comment['social_account_id'],
                'provider' => $comment['provider'],
                'external_comment_id' => $comment['external_comment_id'],
                'author_name' => $comment['author_name'],
                'author_external_id' => null,
                'author_profile_url' => null,
                'text' => $comment['text'],
                'sentiment' => $comment['sentiment'],
                'sentiment_score' => $comment['sentiment_score'],
                'is_read' => false,
                'is_from_owner' => $comment['is_from_owner'],
                'replied_at' => null,
                'replied_by' => null,
                'replied_by_automation' => false,
                'reply_text' => null,
                'reply_external_id' => null,
                'commented_at' => now()->subHours(random_int(1, 48))->toDateTimeString(),
                'captured_at' => now()->toDateTimeString(),
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ]);
        }

        // ── 13. Automation Rules (2) ─────────────────────────────────
        $ruleWelcomeId = (string) Str::uuid();
        $ruleNegativeId = (string) Str::uuid();

        DB::table('automation_rules')->insert([
            'id' => $ruleWelcomeId,
            'organization_id' => $orgId,
            'name' => 'Resposta Automatica - Agradecimento',
            'priority' => 1,
            'action_type' => 'reply_fixed',
            'response_template' => 'Obrigado pelo seu comentario! Ficamos felizes com o feedback. 😊',
            'webhook_id' => null,
            'delay_seconds' => 120,
            'daily_limit' => 50,
            'is_active' => true,
            'applies_to_networks' => null,
            'applies_to_campaigns' => null,
            'deleted_at' => null,
            'purge_at' => null,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        DB::table('automation_rule_conditions')->insert([
            'id' => (string) Str::uuid(),
            'automation_rule_id' => $ruleWelcomeId,
            'field' => 'sentiment',
            'operator' => 'equals',
            'value' => 'positive',
            'is_case_sensitive' => false,
            'position' => 0,
        ]);

        DB::table('automation_rules')->insert([
            'id' => $ruleNegativeId,
            'organization_id' => $orgId,
            'name' => 'Alerta - Comentario Negativo',
            'priority' => 2,
            'action_type' => 'send_webhook',
            'response_template' => null,
            'webhook_id' => null,
            'delay_seconds' => 60,
            'daily_limit' => 100,
            'is_active' => true,
            'applies_to_networks' => null,
            'applies_to_campaigns' => null,
            'deleted_at' => null,
            'purge_at' => null,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        DB::table('automation_rule_conditions')->insert([
            'id' => (string) Str::uuid(),
            'automation_rule_id' => $ruleNegativeId,
            'field' => 'sentiment',
            'operator' => 'equals',
            'value' => 'negative',
            'is_case_sensitive' => false,
            'position' => 0,
        ]);

        // ── 14. Blacklist Words (3) ───────────────────────────────────
        $blacklistWords = ['spam', 'scam', '/\b(compre|clique)\s+agora\b/i'];
        foreach ($blacklistWords as $word) {
            DB::table('automation_blacklist_words')->insert([
                'id' => (string) Str::uuid(),
                'organization_id' => $orgId,
                'word' => $word,
                'is_regex' => str_starts_with($word, '/'),
                'created_at' => now()->toDateTimeString(),
            ]);
        }

        // ── 15. Webhook Endpoints (1) ─────────────────────────────────
        $webhookId = (string) Str::uuid();
        $webhookSecret = 'whsec_'.bin2hex(random_bytes(32));

        DB::table('webhook_endpoints')->insert([
            'id' => $webhookId,
            'organization_id' => $orgId,
            'name' => 'CRM Integration',
            'url' => 'https://httpbin.org/post',
            'secret' => $webhookSecret,
            'events' => json_encode(['comment.created', 'comment.replied']),
            'headers' => null,
            'is_active' => true,
            'last_delivery_at' => null,
            'last_delivery_status' => null,
            'failure_count' => 0,
            'deleted_at' => null,
            'purge_at' => null,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        // ── Output ──────────────────────────────────────────────────
        $this->command->newLine();
        $this->command->info('══════════════════════════════════════════');
        $this->command->info('  Development Seed Complete');
        $this->command->info('══════════════════════════════════════════');
        $this->command->newLine();
        $this->command->line("  Email:           <fg=green>{$email}</>");
        $this->command->line("  Password:        <fg=green>{$password}</>");
        $this->command->line('  Organization:    <fg=green>Demo Agency (demo-agency)</>');
        $this->command->line("  Organization ID: <fg=yellow>{$orgId}</>");
        $this->command->line("  User ID:         <fg=yellow>{$userId}</>");
        $this->command->newLine();
        $this->command->line('  Social Accounts:');
        $this->command->line("    Instagram: <fg=yellow>{$igAccountId}</>");
        $this->command->line("    TikTok:    <fg=yellow>{$ttAccountId}</>");
        $this->command->line("    YouTube:   <fg=yellow>{$ytAccountId}</>");
        $this->command->newLine();
        $this->command->line('  Campaigns:');
        $this->command->line("    Black Friday 2026: <fg=yellow>{$campaignBfId}</>");
        $this->command->line("    Lancamento Verao:  <fg=yellow>{$campaignVeraoId}</>");
        $this->command->newLine();
        $this->command->line('  Contents (ready):');
        $this->command->line("    Mega Promocao BF:   <fg=yellow>{$contentMegaPromoId}</>");
        $this->command->line("    Colecao Verao 2026: <fg=yellow>{$contentColecaoId}</>");
        $this->command->newLine();
        $this->command->line('  Analytics:');
        $this->command->line('    Content Metrics: 2 records');
        $this->command->line('    Snapshots:       6 records (3 per content metric)');
        $this->command->line('    Account Metrics: 21 records (7 days x 3 accounts)');
        $this->command->line("    Report Export (ready):      <fg=yellow>{$exportReadyId}</>");
        $this->command->line("    Report Export (processing): <fg=yellow>{$exportProcessingId}</>");
        $this->command->newLine();
        $this->command->line('  Engagement:');
        $this->command->line('    Comments:         4 records');
        $this->command->line('    Automation Rules: 2 records');
        $this->command->line('    Blacklist Words:  3 records');
        $this->command->line("    Webhook:          <fg=yellow>{$webhookId}</>");
        $this->command->newLine();
        $this->command->info('  Fluxo no Insomnia:');
        $this->command->line('  1. POST /auth/login com email/password acima');
        $this->command->line('  2. Copiar access_token da resposta');
        $this->command->line("  3. POST /organizations/switch com organization_id: {$orgId}");
        $this->command->line('  4. Copiar o novo access_token (com org context)');
        $this->command->line('  5. Usar em todos os endpoints');
        $this->command->info('══════════════════════════════════════════');
        $this->command->newLine();
    }
}
