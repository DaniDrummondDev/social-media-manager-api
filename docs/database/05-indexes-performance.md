# 05 — Índices & Performance

[← Voltar ao índice](00-index.md)

---

## 5.1 Estratégia de Índices

### Princípios

1. **Índices parciais** para tabelas com soft delete — exclui registros deletados
2. **Índices compostos** alinhados com os padrões de query mais frequentes
3. **GIN indexes** para arrays (tags, hashtags, events) e full-text search
4. **IVFFlat** para embeddings vetoriais (pgvector)
5. **Índices funcionais** para queries com transformações (LOWER, date_trunc)

### Índices parciais (Soft Delete)

Tabelas com `deleted_at` usam índices parciais para que queries normais ignorem registros
excluídos sem custo adicional:

```sql
-- Padrão: exclui soft-deleted dos índices
CREATE INDEX idx_campaigns_user_status
    ON campaigns (user_id, status, created_at DESC)
    WHERE deleted_at IS NULL;
```

Benefício: o índice é menor (menos registros) e queries filtradas por `deleted_at IS NULL`
usam o índice automaticamente.

---

## 5.2 Resumo de todos os índices

### Identity & Access

| Tabela | Índice | Colunas | Tipo | Filtro parcial |
|--------|--------|---------|------|----------------|
| users | `uq_users_email` | email | UNIQUE | — |
| users | `idx_users_email_active` | email | B-tree | deleted_at IS NULL |
| users | `idx_users_status` | status | B-tree | deleted_at IS NULL |
| users | `idx_users_purge` | purge_at | B-tree | purge_at IS NOT NULL |
| refresh_tokens | `uq_refresh_tokens_hash` | token_hash | UNIQUE | — |
| refresh_tokens | `idx_refresh_tokens_user` | user_id | B-tree | — |
| refresh_tokens | `idx_refresh_tokens_hash` | token_hash | B-tree | revoked_at IS NULL |
| refresh_tokens | `idx_refresh_tokens_expires` | expires_at | B-tree | revoked_at IS NULL |
| login_histories | `idx_login_histories_user` | user_id, logged_in_at DESC | B-tree | — |
| login_histories | `idx_login_histories_ip` | ip_address, logged_in_at DESC | B-tree | — |
| audit_logs | `idx_audit_logs_user` | user_id, created_at DESC | B-tree | — |
| audit_logs | `idx_audit_logs_resource` | resource_type, resource_id, created_at DESC | B-tree | — |
| audit_logs | `idx_audit_logs_action` | action, created_at DESC | B-tree | — |
| audit_logs | `idx_audit_logs_created_at` | created_at DESC | B-tree | — |

### Social Account

| Tabela | Índice | Colunas | Tipo | Filtro parcial |
|--------|--------|---------|------|----------------|
| social_accounts | `uq_social_accounts_user_provider` | user_id, provider | UNIQUE | deleted_at IS NULL |
| social_accounts | `idx_social_accounts_user` | user_id | B-tree | deleted_at IS NULL |
| social_accounts | `idx_social_accounts_status` | status | B-tree | deleted_at IS NULL |
| social_accounts | `idx_social_accounts_expires` | token_expires_at | B-tree | status = connected, deleted_at IS NULL |
| social_accounts | `idx_social_accounts_provider` | provider, status | B-tree | deleted_at IS NULL |

### Campaign & Content

| Tabela | Índice | Colunas | Tipo | Filtro parcial |
|--------|--------|---------|------|----------------|
| campaigns | `uq_campaigns_user_name` | user_id, LOWER(name) | UNIQUE | deleted_at IS NULL |
| campaigns | `idx_campaigns_user_status` | user_id, status, created_at DESC | B-tree | deleted_at IS NULL |
| campaigns | `idx_campaigns_user_dates` | user_id, starts_at, ends_at | B-tree | deleted_at IS NULL |
| campaigns | `idx_campaigns_tags` | tags | GIN | deleted_at IS NULL |
| campaigns | `idx_campaigns_purge` | purge_at | B-tree | purge_at IS NOT NULL |
| contents | `idx_contents_campaign` | campaign_id, status, created_at DESC | B-tree | deleted_at IS NULL |
| contents | `idx_contents_user` | user_id, created_at DESC | B-tree | deleted_at IS NULL |
| contents | `idx_contents_status` | status | B-tree | deleted_at IS NULL |
| contents | `idx_contents_hashtags` | hashtags | GIN | deleted_at IS NULL |
| contents | `idx_contents_embedding` | embedding | IVFFlat (cosine) | embedding IS NOT NULL, deleted_at IS NULL |
| contents | `idx_contents_purge` | purge_at | B-tree | purge_at IS NOT NULL |
| content_network_overrides | `uq_content_overrides_content_provider` | content_id, provider | UNIQUE | — |
| content_media | `uq_content_media` | content_id, media_id | UNIQUE | — |
| content_media | `uq_content_media_position` | content_id, position | UNIQUE | — |

### Media

| Tabela | Índice | Colunas | Tipo | Filtro parcial |
|--------|--------|---------|------|----------------|
| media | `idx_media_user` | user_id, created_at DESC | B-tree | deleted_at IS NULL |
| media | `idx_media_user_mime` | user_id, mime_type | B-tree | deleted_at IS NULL |
| media | `idx_media_scan` | scan_status | B-tree | scan_status = pending |
| media | `idx_media_purge` | purge_at | B-tree | purge_at IS NOT NULL |
| media | `idx_media_checksum` | user_id, checksum | B-tree | deleted_at IS NULL |

### Publishing

| Tabela | Índice | Colunas | Tipo | Filtro parcial |
|--------|--------|---------|------|----------------|
| scheduled_posts | `idx_scheduled_posts_due` | scheduled_at | B-tree | status = pending |
| scheduled_posts | `idx_scheduled_posts_retry` | next_retry_at | B-tree | status = failed, not permanent |
| scheduled_posts | `idx_scheduled_posts_user` | user_id, scheduled_at DESC | B-tree | — |
| scheduled_posts | `idx_scheduled_posts_content` | content_id, status | B-tree | — |
| scheduled_posts | `idx_scheduled_posts_account` | social_account_id, scheduled_at DESC | B-tree | — |
| scheduled_posts | `idx_scheduled_posts_calendar` | user_id, scheduled_at | B-tree | status IN (pending, dispatched, publishing, published) |
| scheduled_posts | `idx_scheduled_posts_daily` | social_account_id, date_trunc('day', scheduled_at) | B-tree | status IN (...) |

### Analytics

| Tabela | Índice | Colunas | Tipo | Filtro parcial |
|--------|--------|---------|------|----------------|
| content_metrics | `uq_content_metrics_content_account` | content_id, social_account_id | UNIQUE | — |
| content_metrics | `idx_content_metrics_content` | content_id | B-tree | — |
| content_metrics | `idx_content_metrics_account` | social_account_id, synced_at DESC | B-tree | — |
| content_metrics | `idx_content_metrics_engagement` | social_account_id, engagement_rate DESC | B-tree | — |
| content_metrics | `idx_content_metrics_sync` | synced_at | B-tree | synced_at < NOW() - 1h |
| content_metric_snapshots | `idx_metric_snapshots_metrics` | content_metrics_id, captured_at DESC | B-tree | — |
| account_metrics | `uq_account_metrics_account_date` | social_account_id, date | UNIQUE | — |
| account_metrics | `idx_account_metrics_account` | social_account_id, date DESC | B-tree | — |

### Engagement

| Tabela | Índice | Colunas | Tipo | Filtro parcial |
|--------|--------|---------|------|----------------|
| comments | `uq_comments_external` | social_account_id, external_comment_id | UNIQUE | — |
| comments | `idx_comments_user_inbox` | user_id, captured_at DESC | B-tree | — |
| comments | `idx_comments_content` | content_id, captured_at DESC | B-tree | — |
| comments | `idx_comments_sentiment` | user_id, sentiment, captured_at DESC | B-tree | sentiment IS NOT NULL |
| comments | `idx_comments_unread` | user_id, captured_at DESC | B-tree | is_read = FALSE |
| comments | `idx_comments_unreplied` | user_id, captured_at DESC | B-tree | replied_at IS NULL, not owner |
| comments | `idx_comments_text_search` | to_tsvector('portuguese', text) | GIN | — |
| comments | `idx_comments_embedding` | embedding | IVFFlat (cosine) | embedding IS NOT NULL |
| automation_rules | `uq_automation_rules_priority` | user_id, priority | UNIQUE | active, not deleted |
| automation_rules | `idx_automation_rules_user` | user_id, priority | B-tree | active, not deleted |
| automation_executions | `idx_automation_executions_daily` | user_id, date_trunc('day', executed_at) | B-tree | success = TRUE |
| webhook_endpoints | `idx_webhook_endpoints_events` | events | GIN | active, not deleted |
| webhook_deliveries | `idx_webhook_deliveries_retry` | next_retry_at | B-tree | failed_at IS NULL, retry pending |

---

## 5.3 Particionamento

### Tabelas particionadas

| Tabela | Estratégia | Chave | Estimativa/ano |
|--------|-----------|-------|----------------|
| `content_metric_snapshots` | RANGE por mês | `captured_at` | 50M registros |
| `account_metrics` | RANGE por mês | `date` | 10M registros |

### Gestão de partições

```sql
-- Job mensal: criar partições 3 meses à frente
-- Exemplo para Março 2026
CREATE TABLE content_metric_snapshots_2026_03
    PARTITION OF content_metric_snapshots
    FOR VALUES FROM ('2026-03-01') TO ('2026-04-01');

CREATE TABLE account_metrics_2026_03
    PARTITION OF account_metrics
    FOR VALUES FROM ('2026-03-01') TO ('2026-04-01');
```

### Retenção de partições

| Tabela | Retenção | Ação |
|--------|----------|------|
| `content_metric_snapshots` | 2 anos | DROP partição antiga |
| `account_metrics` | 2 anos | DROP partição antiga |

```sql
-- Exemplo: remover dados de Janeiro 2024
DROP TABLE content_metric_snapshots_2024_01;
DROP TABLE account_metrics_2024_01;
```

### Candidatas a particionamento futuro

Se o volume justificar, estas tabelas podem ser particionadas no futuro:

| Tabela | Chave candidata | Trigger de volume |
|--------|----------------|-------------------|
| `comments` | `captured_at` | > 100M registros |
| `audit_logs` | `created_at` | > 50M registros |
| `automation_executions` | `executed_at` | > 50M registros |
| `webhook_deliveries` | `created_at` | > 20M registros |
| `scheduled_posts` | `scheduled_at` | > 20M registros |

---

## 5.4 Queries críticas e seus índices

### Q1: Buscar posts pendentes para dispatch (a cada minuto)

```sql
SELECT * FROM scheduled_posts
WHERE status = 'pending'
  AND scheduled_at <= NOW()
ORDER BY scheduled_at ASC
LIMIT 100;

-- Usa: idx_scheduled_posts_due
-- Estimativa: < 1ms (índice parcial, poucos registros pending)
```

### Q2: Listar campanhas do usuário (paginação cursor)

```sql
SELECT * FROM campaigns
WHERE user_id = :userId
  AND deleted_at IS NULL
  AND status = :status
  AND (created_at, id) < (:cursorCreatedAt, :cursorId)
ORDER BY created_at DESC, id DESC
LIMIT 21;

-- Usa: idx_campaigns_user_status
-- Estimativa: < 5ms
```

### Q3: Inbox de comentários (unified inbox)

```sql
SELECT * FROM comments
WHERE user_id = :userId
  AND is_read = FALSE
  AND (captured_at, id) < (:cursorCapturedAt, :cursorId)
ORDER BY captured_at DESC, id DESC
LIMIT 21;

-- Usa: idx_comments_unread
-- Estimativa: < 10ms
```

### Q4: Dashboard de analytics (métricas agregadas)

```sql
SELECT
    SUM(impressions) as total_impressions,
    SUM(reach) as total_reach,
    SUM(likes + comments + shares + saves) as total_engagement,
    COUNT(*) as total_posts
FROM content_metrics cm
JOIN contents c ON c.id = cm.content_id
WHERE c.user_id = :userId
  AND cm.synced_at >= :startDate;

-- Usa: idx_content_metrics_content + idx_contents_user
-- Estimativa: < 50ms (dependendo do volume)
```

### Q5: Busca textual em comentários

```sql
SELECT * FROM comments
WHERE user_id = :userId
  AND to_tsvector('portuguese', text) @@ plainto_tsquery('portuguese', :searchTerm)
ORDER BY captured_at DESC
LIMIT 20;

-- Usa: idx_comments_text_search + idx_comments_user_inbox
-- Estimativa: < 20ms
```

### Q6: Busca semântica de conteúdo similar

```sql
SELECT id, title, body,
       1 - (embedding <=> :queryEmbedding) as similarity
FROM contents
WHERE user_id = :userId
  AND embedding IS NOT NULL
  AND deleted_at IS NULL
ORDER BY embedding <=> :queryEmbedding
LIMIT 10;

-- Usa: idx_contents_embedding (IVFFlat)
-- Estimativa: < 50ms
```

### Q7: Verificar limite diário de automação

```sql
SELECT COUNT(*) FROM automation_executions
WHERE user_id = :userId
  AND success = TRUE
  AND executed_at >= date_trunc('day', NOW());

-- Usa: idx_automation_executions_daily
-- Estimativa: < 5ms
```

### Q8: Tokens próximos de expirar (job de refresh)

```sql
SELECT * FROM social_accounts
WHERE status = 'connected'
  AND deleted_at IS NULL
  AND token_expires_at IS NOT NULL
  AND token_expires_at <= NOW() + INTERVAL '7 days';

-- Usa: idx_social_accounts_expires
-- Estimativa: < 5ms
```

---

## 5.5 Connection Pooling

### PgBouncer

```ini
[pgbouncer]
pool_mode = transaction
default_pool_size = 20
max_client_conn = 200
max_db_connections = 50
server_idle_timeout = 600
query_timeout = 30
```

### Laravel config

```php
// config/database.php
'pgsql' => [
    'driver' => 'pgsql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '6432'),  // PgBouncer port
    'database' => env('DB_DATABASE', 'social_media_manager'),
    'username' => env('DB_USERNAME', 'app'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8',
    'prefix' => '',
    'search_path' => 'public',
    'sslmode' => 'prefer',
    'options' => [
        PDO::ATTR_PERSISTENT => false,  // PgBouncer gerencia
    ],
],
```

---

## 5.6 Políticas de retenção

| Tabela | Retenção | Ação | Job |
|--------|----------|------|-----|
| `login_histories` | 1 ano | Hard delete | Mensal |
| `audit_logs` | 1 ano | Hard delete | Mensal |
| `ai_generations` | 1 ano | Hard delete | Mensal |
| `automation_executions` | 6 meses | Hard delete | Mensal |
| `webhook_deliveries` | 30 dias | Hard delete | Semanal |
| `password_reset_tokens` | 7 dias | Hard delete | Diário |
| `refresh_tokens` (revogados) | 30 dias | Hard delete | Semanal |
| `report_exports` (expirados) | 7 dias | Hard delete + storage | Diário |
| Soft deleted resources | Conforme `purge_at` | Hard delete + storage | Diário |
| `content_metric_snapshots` (partições) | 2 anos | DROP partition | Mensal |
| `account_metrics` (partições) | 2 anos | DROP partition | Mensal |

### Job de limpeza

```bash
# Artisan command executado via scheduler
php artisan data:cleanup --retention
php artisan data:purge-soft-deleted
php artisan partitions:manage  # criar futuras, remover antigas
```
