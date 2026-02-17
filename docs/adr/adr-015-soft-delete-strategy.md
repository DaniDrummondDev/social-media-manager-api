# ADR-015: Estratégia de Soft Delete

[← Voltar ao índice](00-index.md)

---

- **Status:** Accepted
- **Data:** 2026-02-15
- **Decisores:** Equipe de arquitetura

## Contexto

O sistema lida com dados que o usuário pode querer excluir (campanhas, conteúdos, mídias,
contas sociais), mas que possuem dependências e valor histórico:

- Uma campanha excluída pode ter conteúdos já publicados com métricas
- Uma mídia excluída pode estar vinculada a agendamentos
- LGPD exige possibilidade de exclusão, mas com período de carência
- Erros de exclusão devem ser reversíveis

## Decisão

Adotar **soft delete** como estratégia padrão para exclusão de recursos, com
**hard delete agendado** (purge) após período de carência.

### Implementação

Todas as entidades que suportam exclusão possuem:

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `deleted_at` | `timestamp null` | Data da exclusão lógica |
| `purge_at` | `timestamp null` | Data agendada para exclusão física |

### Ciclo de vida

```
ATIVO ──(delete)──▶ SOFT DELETED ──(purge job)──▶ HARD DELETED
                         │
                    (restore)
                         │
                         ▼
                       ATIVO
```

### Períodos de carência por recurso

| Recurso | Carência | Justificativa |
|---------|----------|---------------|
| **Campaign** | 30 dias | Pode ter dados históricos relevantes |
| **Content** | 30 dias | Pode ter métricas e comentários |
| **Media** | 30 dias | Pode estar vinculada a conteúdos |
| **Social Account** | 7 dias | Reconexão rápida em caso de erro |
| **Automation Rule** | 7 dias | Restauração de regras desativadas por engano |
| **User Account** | 30 dias | LGPD — período de reconsideração |
| **Webhook** | 7 dias | Restauração rápida |

### Regras

1. **Queries padrão excluem soft-deleted:**
   - Eloquent global scope `SoftDeletes` filtra automaticamente
   - Endpoints de listagem nunca retornam itens excluídos

2. **Cascata de soft delete:**
   - Excluir campanha → soft delete das peças de conteúdo não publicadas
   - Excluir conta social → cancelar agendamentos pendentes
   - Excluir usuário (LGPD) → soft delete de tudo, depois purge

3. **Bloqueios de exclusão:**
   - Mídia vinculada a agendamento pendente → bloqueada (HTTP 409 Conflict)
   - Conta social com publicação em andamento → bloqueada

4. **Restauração:**
   - Endpoint `POST /api/v1/{resource}/{id}/restore`
   - Possível apenas durante período de carência
   - Restaura o recurso e dependências (ex: campanha + peças)

### Purge job

```php
// Executa diariamente
$schedule->command('data:purge-expired')
    ->dailyAt('03:00')
    ->withoutOverlapping();
```

O job de purge:
1. Busca registros com `purge_at <= now()`
2. Para mídias: exclui arquivo físico do storage
3. Remove registro do banco (hard delete)
4. Log de auditoria da exclusão definitiva

### Exclusão LGPD (direito ao esquecimento)

Quando o usuário solicita exclusão de conta:
1. Soft delete imediato de todos os dados
2. Revogação de tokens de redes sociais
3. Email de confirmação com link para cancelar (30 dias)
4. Após 30 dias: hard delete de PII (nome, email, telefone)
5. Dados de analytics anonimizados (mantidos para métricas agregadas)

```sql
-- Anonimização
UPDATE users SET
    name = 'Usuário Excluído',
    email = 'deleted_' || id || '@removed.local',
    phone = NULL,
    avatar_path = NULL
WHERE id = :userId AND purge_at <= NOW();
```

### Índices

```sql
-- Índice parcial para queries de dados ativos (maioria dos casos)
CREATE INDEX idx_campaigns_active ON campaigns (user_id, created_at)
    WHERE deleted_at IS NULL;

-- Índice para o job de purge
CREATE INDEX idx_campaigns_purge ON campaigns (purge_at)
    WHERE purge_at IS NOT NULL AND deleted_at IS NOT NULL;
```

## Alternativas consideradas

### 1. Hard delete imediato
- **Prós:** Simples, sem dados residuais, sem job de purge
- **Contras:** Irreversível, perda de dados históricos, incompatível com LGPD (período de carência)
- **Por que descartado:** Risco muito alto de perda acidental de dados

### 2. Soft delete sem purge (nunca excluir fisicamente)
- **Prós:** Nunca perde dados, simples
- **Contras:** Banco cresce indefinidamente, compliance com LGPD questionável, storage de mídias nunca é liberado
- **Por que descartado:** Crescimento descontrolado de dados e non-compliance com LGPD

### 3. Archival (mover para tabela de histórico)
- **Prós:** Dados ativos sempre limpos, histórico separado
- **Contras:** Complexidade de migração, queries cross-table, foreign keys quebradas
- **Por que descartado:** Complexidade excessiva sem benefício claro sobre soft delete + purge

## Consequências

### Positivas
- Exclusões reversíveis — segurança contra erros
- Período de carência atende LGPD
- Storage é liberado após carência (mídias)
- Banco não cresce indefinidamente
- Índices parciais mantêm performance para queries de dados ativos

### Negativas
- Todas as queries precisam considerar `deleted_at` (mitigado por global scope)
- Job de purge precisa ser confiável e monitorado
- Mais complexidade em cascata (soft delete de dependências)
- Espaço ocupado durante o período de carência

### Riscos
- Job de purge falhar silenciosamente = dados nunca são limpos — mitigado por monitoramento e alertas
- Soft delete de campanha não cascatear para conteúdos — mitigado por testes de integração
