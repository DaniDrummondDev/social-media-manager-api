# Analytics & Reporting — Social Media Manager API

## Objetivo

Definir as regras de domínio para **coleta de métricas**, **analytics cross-network** e **exportação de relatórios**.

---

## Conceitos

### ContentMetric (Entity)

Métricas agregadas de um conteúdo publicado, consolidando dados de todas as redes.

### ContentMetricSnapshot (Entity)

Snapshot periódico das métricas (tabela particionada por mês). Permite análise de evolução.

### AccountMetric (Entity)

Métricas da conta social em um determinado período (seguidores, reach, etc.).

### ReportExport (Entity)

Solicitação de exportação de relatório (PDF/CSV), processada assincronamente.

---

## Sincronização de Métricas

### Fluxo

```
PostPublished event → SyncPostMetricsJob (após 24h, 48h, 7d)
                                ↓
Scheduler periódico → SyncAccountMetricsJob (a cada 6h)
                                ↓
SocialAnalyticsInterface::getPostMetrics() / getAccountMetrics()
                                ↓
Upsert em content_metrics / account_metrics
                                ↓
Snapshot em content_metric_snapshots (diário)
```

### Regras de Sincronização

- **RN-ANA-01**: Métricas de post são sincronizadas em 3 momentos: 24h, 48h e 7d após publicação.
- **RN-ANA-02**: Métricas de conta são sincronizadas a cada 6 horas.
- **RN-ANA-03**: Circuit breaker por provider evita chamadas quando API está instável.
- **RN-ANA-04**: Rate limits do provider são respeitados (usar token bucket).
- **RN-ANA-05**: Sincronização usa **upsert** (idempotente).
- **RN-ANA-06**: Snapshots diários capturam estado para análise de evolução.

---

## Métricas por Provider

### Instagram

| Métrica | Tipo | Disponível em |
|---------|------|---------------|
| `impressions` | counter | Post |
| `reach` | counter | Post, Conta |
| `likes` | counter | Post |
| `comments` | counter | Post |
| `shares` | counter | Post |
| `saves` | counter | Post |
| `followers_count` | gauge | Conta |
| `profile_views` | counter | Conta |

### TikTok

| Métrica | Tipo | Disponível em |
|---------|------|---------------|
| `views` | counter | Post |
| `likes` | counter | Post |
| `comments` | counter | Post |
| `shares` | counter | Post |
| `watch_time_seconds` | counter | Post |
| `followers_count` | gauge | Conta |

### YouTube

| Métrica | Tipo | Disponível em |
|---------|------|---------------|
| `views` | counter | Post |
| `likes` | counter | Post |
| `comments` | counter | Post |
| `watch_time_hours` | counter | Post, Conta |
| `subscribers_count` | gauge | Conta |

---

## Analytics Overview

O endpoint `GET /api/v1/analytics/overview` consolida:

1. **Summary**: totais do período (posts, reach, engagement, comments, likes, shares, saves).
2. **Comparison**: variação percentual vs período anterior.
3. **By Network**: breakdown por provider.
4. **Trend**: dados diários para gráfico.
5. **Top Contents**: top 5 conteúdos por engagement.

### Cálculos

- **Engagement rate**: `(likes + comments + shares + saves) / reach * 100`
- **Comparison**: `((valor_atual - valor_anterior) / valor_anterior) * 100`

---

## Best Posting Times

- Calculado com base nas métricas dos posts da organização.
- Agrupa por dia da semana + hora.
- Retorna top 3 combinações com maior média de engagement.
- Requer mínimo de 10 posts para cálculo confiável.

---

## Exportação de Relatórios

### Fluxo

```
POST /api/v1/analytics/export → cria ReportExport (status: processing)
                                         ↓
                              GenerateReportJob (fila: low)
                                         ↓
                         Gera PDF/CSV → upload para storage
                                         ↓
                         ReportExport (status: ready, download_url)
                                         ↓
                         Expiração: 24 horas → cleanup
```

### Regras

- **RN-EXP-01**: Relatórios são gerados assincronamente.
- **RN-EXP-02**: Download URL expira em 24 horas.
- **RN-EXP-03**: Formatos: PDF e CSV.
- **RN-EXP-04**: Filtros: período, provider, campanha, conteúdo específico.
- **RN-EXP-05**: Rate limit: 5 exportações por hora.

---

## Particionamento

Tabelas particionadas por mês:

- `content_metric_snapshots`: dados diários de evolução de métricas.
- `account_metrics`: dados periódicos de métricas de conta.

### Regras

- Partições criadas automaticamente 3 meses à frente.
- Partições com mais de 2 anos são dropadas pelo `PurgeExpiredRecordsJob`.
- Queries sempre incluem filtro temporal para partition pruning.

---

## Domain Events

| Evento | Quando | Dados |
|--------|--------|-------|
| `MetricsSynced` | Métricas atualizadas | content_id ou account_id, provider |
| `ReportGenerated` | Relatório pronto | export_id, format, file_size |
| `ReportExpired` | URL expirou | export_id |

---

## Anti-Patterns

- Sincronização síncrona de métricas no request HTTP.
- Queries de analytics sem filtro temporal (full table scan).
- Cálculos de engagement sem cache (recalcular em cada request).
- Exportação síncrona (bloqueia request).
- Manter relatórios indefinidamente no storage.
- Ignorar rate limits dos providers ao sincronizar.
