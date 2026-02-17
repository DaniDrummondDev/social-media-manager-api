# Data Retention Policy — Social Media Manager API

## Objetivo

Definir políticas de retenção e purge de dados, garantindo que nenhum dado seja armazenado indefinidamente sem justificativa.

> Referência: ADR-015 (Soft Delete Strategy)

---

## Princípio

> Dados têm ciclo de vida. Nada é armazenado para sempre sem justificativa legal ou de negócio.

---

## Soft Delete Strategy

### Regras Gerais

- Todos os recursos visíveis ao usuário usam **soft delete** (`deleted_at` column).
- Recursos soft-deleted são excluídos de queries por padrão (global scope).
- Grace period permite restauração.
- Após grace period: **hard delete** + limpeza de arquivos.

### Fluxo

```
Recurso ativo → DELETE request → soft delete (deleted_at = now())
                                       ↓
                         Grace period (ex: 30 dias)
                                       ↓
                    PurgeExpiredRecordsJob (diário)
                                       ↓
                         Hard delete + file cleanup
```

---

## Períodos de Retenção por Recurso

### Dados do Usuário e Organização

| Recurso | Soft Delete | Hard Delete / Purge | Notas |
|---------|-------------|---------------------|-------|
| User (após exclusão) | Imediato | 30 dias → anonimizar | Removido de todas as orgs |
| Organization (após exclusão) | Imediato | 30 dias → hard delete | Todos dados da org removidos |
| Organization members | — | Imediato ao remover | Desassociação |
| Social accounts | — | Imediato ao desconectar | Tokens criptografados destruídos |
| Refresh tokens | — | Automático ao expirar (7 dias) | Revogados no logout |
| Password reset tokens | — | 1 hora | Single-use |
| Email verification tokens | — | 24 horas | Reenvio reseta token |

### Conteúdo & Campanhas

| Recurso | Soft Delete | Hard Delete / Purge | Notas |
|---------|-------------|---------------------|-------|
| Campaigns | 30 dias | Após grace period | Conteúdos associados também |
| Contents | 30 dias | Após grace period | Overrides e associações incluídos |
| Content network overrides | — | Com o content pai | Cascade |
| Content-media associations | — | Com o content pai | Cascade |

### Mídia

| Recurso | Soft Delete | Hard Delete / Purge | Notas |
|---------|-------------|---------------------|-------|
| Media (arquivo + registro) | 30 dias | Após grace period | Arquivo removido do storage |
| Thumbnails | — | Com a mídia pai | Arquivo removido do storage |

### Publicação

| Recurso | Retenção | Notas |
|---------|----------|-------|
| Scheduled posts | 90 dias após execução | published/failed/cancelled |
| Publishing attempts/errors | Com o scheduled post | Incluído no registro |

### Analytics

| Recurso | Retenção | Notas |
|---------|----------|-------|
| Content metrics | 2 anos | Dados agregados mantidos |
| Content metric snapshots | 2 anos | Partições mensais dropadas |
| Account metrics | 2 anos | Partições mensais dropadas |
| Report exports (arquivo) | 24 horas | Arquivo removido do storage |
| Report exports (registro) | 30 dias | Registro mantido para histórico |

### Engajamento & Automação

| Recurso | Retenção | Notas |
|---------|----------|-------|
| Comments | 1 ano após captura | Embeddings incluídos |
| Automation rules | 30 dias soft delete | Execuções associadas incluídas |
| Automation executions | 90 dias | Histórico de execuções |
| Blacklist words | — | Sem retenção (permanente enquanto ativo) |
| Webhook endpoints | — | Deletado imediatamente |
| Webhook deliveries | 30 dias | Histórico de entregas |

### IA & Auditoria

| Recurso | Retenção | Notas |
|---------|----------|-------|
| AI generations | 6 meses | Histórico de uso |
| AI settings | — | Com o usuário | Configurações permanentes |
| Audit logs (segurança) | 2 anos | Login, 2FA, token reuse |
| Audit logs (operacionais) | 1 ano | CRUD de recursos |
| Login history | 1 ano | IP, user-agent, resultado |

---

## Implementação do Purge

### PurgeExpiredRecordsJob

- **Frequência**: diário (via Laravel Scheduler, 03:00 UTC).
- **Processamento**: cada tipo de recurso é processado independentemente.
- **Batch size**: 100 registros por iteração (evitar lock contention).
- **Transação**: cada batch em sua própria transação.
- **Logging**: registra quantos registros purgados por tipo.
- **Audit**: cria registro de auditoria para o job.

```php
// Pseudo-código do job
foreach ($resourceTypes as $type) {
    $expiredRecords = $type::onlyTrashed()
        ->where('deleted_at', '<', now()->sub($type::GRACE_PERIOD))
        ->limit(100)
        ->get();

    foreach ($expiredRecords as $record) {
        $record->purge(); // hard delete + file cleanup
    }
}
```

### Partitioned Tables

Para tabelas com particionamento mensal:

- `content_metric_snapshots`: drop partições mais antigas que 2 anos.
- `account_metrics`: drop partições mais antigas que 2 anos.
- Criar novas partições automaticamente (3 meses à frente).

```sql
-- Drop partição expirada
DROP TABLE IF EXISTS content_metric_snapshots_2024_01;

-- Criar partição futura
CREATE TABLE content_metric_snapshots_2026_05
    PARTITION OF content_metric_snapshots
    FOR VALUES FROM ('2026-05-01') TO ('2026-06-01');
```

### File Storage Cleanup

Quando um registro de mídia é hard-deleted:

1. Remover arquivo original do object storage.
2. Remover thumbnail do object storage.
3. Registrar cleanup no log.

Quando um report export expira:

1. Remover arquivo PDF/CSV do storage.
2. Manter registro no banco por mais 30 dias (referência).

---

## Anti-Patterns

- Retenção indefinida sem justificativa.
- Hard delete sem grace period para recursos do usuário.
- Purge sem audit log.
- Esquecer de limpar arquivos do storage junto com registros do banco.
- Drop de partição sem verificar se há dados referenciados.
- Purge job que processa tudo em uma única transação.
- Purge síncrono dentro de um request HTTP.
