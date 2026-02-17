# ADR-014: Cursor-based Pagination

[← Voltar ao índice](00-index.md)

---

- **Status:** Accepted
- **Data:** 2026-02-15
- **Decisores:** Equipe de arquitetura

## Contexto

A API retorna listas paginadas em diversos endpoints: campanhas, conteúdos, comentários,
agendamentos, mídias, etc. Precisamos de uma estratégia de paginação que:

- Funcione de forma consistente quando dados são inseridos/removidos durante a navegação
- Tenha performance estável independente da "página" acessada
- Seja eficiente para datasets que crescem continuamente (comentários, métricas)
- Funcione bem com feeds em tempo real (novos comentários)

## Decisão

Adotar **cursor-based pagination** como padrão para todos os endpoints de listagem.

### Formato de response

```json
{
  "data": [
    { "id": "uuid-1", "name": "Campanha A", "created_at": "2026-02-15T10:00:00Z" },
    { "id": "uuid-2", "name": "Campanha B", "created_at": "2026-02-15T09:00:00Z" }
  ],
  "meta": {
    "per_page": 20,
    "has_more": true,
    "next_cursor": "eyJjcmVhdGVkX2F0IjoiMjAyNi0wMi0xNVQwOTowMDowMFoiLCJpZCI6InV1aWQtMiJ9",
    "prev_cursor": "eyJjcmVhdGVkX2F0IjoiMjAyNi0wMi0xNVQxMDowMDowMFoiLCJpZCI6InV1aWQtMSJ9"
  }
}
```

### Query parameters

```
GET /api/v1/campaigns?per_page=20&cursor={next_cursor}&sort=-created_at
```

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `per_page` | int | 20 | Itens por página (máx: 100) |
| `cursor` | string | null | Cursor opaco para próxima/anterior página |
| `sort` | string | -created_at | Campo de ordenação (prefixo `-` = desc) |

### Como o cursor funciona

O cursor é um base64-encoded JSON contendo o valor do campo de ordenação e o ID
do último item retornado:

```json
// Decodificado:
{
  "created_at": "2026-02-15T09:00:00Z",
  "id": "uuid-2"
}

// Encodado (opaco para o cliente):
"eyJjcmVhdGVkX2F0IjoiMjAyNi0wMi0xNVQwOTowMDowMFoiLCJpZCI6InV1aWQtMiJ9"
```

### Query gerada

```sql
-- Próxima página (sort: created_at DESC)
SELECT * FROM campaigns
WHERE user_id = :userId
  AND (created_at, id) < (:cursorCreatedAt, :cursorId)
ORDER BY created_at DESC, id DESC
LIMIT 21;  -- +1 para saber se has_more

-- O ID é incluído no cursor para desempate (tie-breaking)
-- quando múltiplos registros têm o mesmo created_at
```

### Implementação no Laravel

```php
// Utilizar cursorPaginate() do Eloquent
$campaigns = CampaignModel::where('user_id', $userId)
    ->orderBy('created_at', 'desc')
    ->orderBy('id', 'desc')
    ->cursorPaginate(perPage: $perPage, cursor: $cursor);
```

### Endpoints que usam cursor pagination

| Endpoint | Ordenação padrão |
|----------|-----------------|
| `GET /api/v1/campaigns` | `-created_at` |
| `GET /api/v1/campaigns/{id}/contents` | `-created_at` |
| `GET /api/v1/scheduled-posts` | `scheduled_at` |
| `GET /api/v1/comments` | `-captured_at` |
| `GET /api/v1/media` | `-created_at` |
| `GET /api/v1/ai/history` | `-created_at` |
| `GET /api/v1/automation-rules` | `priority` |

## Alternativas consideradas

### 1. Offset-based pagination (page=1&per_page=20)
- **Prós:** Simples, intuitivo, suporta "ir para página X"
- **Contras:**
  - `OFFSET 10000` faz full scan (performance O(n))
  - Dados inconsistentes quando registros são inseridos/removidos entre páginas
  - Itens duplicados ou pulados em feeds dinâmicos
- **Por que descartada:** Performance degradante e inconsistência são inaceitáveis para comentários e feeds em tempo real

### 2. Keyset pagination manual (sem cursor opaco)
- **Prós:** Performance de cursor, sem necessidade de encode/decode
- **Contras:** Expõe lógica interna ao cliente, breaking change se mudar campo de ordenação
- **Por que descartada:** Cursor opaco desacopla o cliente da implementação

## Consequências

### Positivas
- Performance constante O(1) independente da posição na lista
- Consistência — novos registros inseridos não causam duplicação
- Funciona bem com feeds em tempo real (novos comentários)
- Cursor opaco permite mudar implementação interna sem quebrar clientes
- Suporte nativo do Laravel (`cursorPaginate()`)

### Negativas
- Não suporta "ir para página X" diretamente
- Não retorna total de registros (sem `total_count`)
- Cliente precisa navegar sequencialmente (próximo/anterior)
- Mudar campo de ordenação invalida cursors existentes

### Riscos
- Cursor com dados sensíveis — mitigado por encode com assinatura (HMAC) para prevenir tampering
