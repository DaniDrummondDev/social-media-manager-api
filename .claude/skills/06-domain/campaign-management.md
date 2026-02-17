# Campaign Management — Social Media Manager API

## Objetivo

Definir as regras de domínio para **Campanhas** e **Conteúdos**, incluindo ciclo de vida, overrides por rede social e invariantes do agregado.

---

## Conceitos

### Campaign (Aggregate Root)

Representa um agrupamento lógico de conteúdos com objetivo definido.

### Content (Entity)

Peça de conteúdo (texto + mídia) que pertence a uma campanha. Pode ter customizações por rede social.

### ContentNetworkOverride (Entity)

Customização de um conteúdo para uma rede social específica (título, descrição, hashtags diferentes).

---

## Campaign

### Campos

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `id` | UUID | Auto | — |
| `organization_id` | UUID | Sim | Organização (tenant) |
| `name` | string | Sim | 3-100 chars, único por organização |
| `description` | text | Não | Máx 500 chars |
| `status` | enum | Auto | draft, active, paused, completed, archived |
| `starts_at` | datetime | Não | Futuro ou null |
| `ends_at` | datetime | Não | Após starts_at ou null |
| `created_at` | datetime | Auto | — |
| `deleted_at` | datetime | Null | Soft delete |

### Ciclo de Vida

```
draft → active → paused → active (reativação)
                    ↓
  draft → active → completed → archived
                                   ↓
                              (soft delete → purge 30d)
```

### Transições Válidas

| De | Para | Condição |
|----|------|----------|
| `draft` | `active` | Tem pelo menos 1 conteúdo |
| `active` | `paused` | Sempre permitido |
| `paused` | `active` | Sempre permitido |
| `active` | `completed` | Todos os conteúdos publicados ou cancelados |
| `completed` | `archived` | Sempre permitido |
| Qualquer | `deleted` (soft) | Sem conteúdos agendados pendentes |

### Regras de Negócio

- **RN-CAM-01**: Nome da campanha é único por organização.
- **RN-CAM-02**: Campanha não pode ser excluída se tem conteúdos com agendamentos pendentes.
- **RN-CAM-03**: Campanha com `ends_at` no passado é automaticamente `completed`.
- **RN-CAM-04**: Duplicação de campanha cria cópia com status `draft` e sufixo "(cópia)".

---

## Content

### Campos

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `id` | UUID | Auto | — |
| `organization_id` | UUID | Sim | Organização (tenant) |
| `campaign_id` | UUID | Sim | Campanha da organização |
| `title` | string | Não | Máx 200 chars |
| `body` | text | Não | Máx 5000 chars |
| `hashtags` | string[] | Não | Máx 30 |
| `status` | enum | Auto | draft, ready, scheduled, published, failed |
| `media_ids` | UUID[] | Não | Mídias da organização, scan_status = clean |
| `created_at` | datetime | Auto | — |
| `deleted_at` | datetime | Null | Soft delete |

### Ciclo de Vida

```
draft → ready (tem mídia e/ou texto) → scheduled → published
                                                  → failed → (retry) → published
                                     → cancelled → draft
```

### Transições Válidas

| De | Para | Condição |
|----|------|----------|
| `draft` | `ready` | Tem body ou mídia compatível |
| `ready` | `scheduled` | Agendamento criado |
| `scheduled` | `published` | Publicado com sucesso em pelo menos 1 rede |
| `scheduled` | `failed` | Falhou em todas as redes |
| `scheduled` | `cancelled` | Agendamento cancelado pelo user |
| `failed` | `scheduled` | Retry solicitado |
| `cancelled` | `draft` | Reset automático |

### Regras de Negócio

- **RN-CON-01**: Conteúdo precisa de pelo menos body ou mídia para ser `ready`.
- **RN-CON-02**: Mídia associada deve ter `scan_status = clean`.
- **RN-CON-03**: Ao agendar, validar compatibilidade de mídia com cada rede destino.
- **RN-CON-04**: Um conteúdo pode ser publicado em múltiplas redes simultaneamente.
- **RN-CON-05**: Exclusão de conteúdo agendado cancela agendamentos primeiro.

---

## ContentNetworkOverride

### Campos

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `content_id` | UUID | Sim | — |
| `provider` | enum | Sim | instagram, tiktok, youtube |
| `title` | string | Não | Override do título |
| `body` | text | Não | Override do body |
| `hashtags` | string[] | Não | Override das hashtags |

### Regras

- **RN-OVR-01**: Um conteúdo pode ter no máximo 1 override por provider.
- **RN-OVR-02**: Override pode ser parcial (só title, só body, só hashtags).
- **RN-OVR-03**: Na publicação, override tem precedência sobre campos do conteúdo base.
- **RN-OVR-04**: Se não há override, usa campos do conteúdo base.

---

## Domain Events

| Evento | Quando | Dados |
|--------|--------|-------|
| `CampaignCreated` | Nova campanha | campaign_id, organization_id, name |
| `CampaignStatusChanged` | Mudança de status | campaign_id, old_status, new_status |
| `CampaignDeleted` | Soft delete | campaign_id, organization_id |
| `ContentCreated` | Novo conteúdo | content_id, campaign_id, organization_id |
| `ContentUpdated` | Conteúdo atualizado | content_id, changed_fields |
| `ContentStatusChanged` | Mudança de status | content_id, old_status, new_status |
| `ContentDeleted` | Soft delete | content_id, organization_id |

---

## Anti-Patterns

- Campanha sem status explícito (inferir status é frágil).
- Conteúdo publicado diretamente sem passar por `ready` (pular validações).
- Override que substitui 100% do conteúdo base (deveria ser conteúdo separado).
- Exclusão de campanha sem verificar agendamentos pendentes.
- Transição de status direta via setter (usar métodos de domínio).

---

## Dependências

- `06-domain/publishing-scheduling.md` (agendamento de conteúdos)
- `06-domain/media-management.md` (associação de mídias)
- `06-domain/ai-content-generation.md` (geração de conteúdo)
