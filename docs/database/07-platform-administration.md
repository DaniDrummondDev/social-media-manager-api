# 07 — Platform Administration

[← Voltar ao índice](00-index.md)

---

## Tipos ENUM (Administration)

```sql
CREATE TYPE platform_admin_role_type AS ENUM ('super_admin', 'admin', 'support');
```

---

## Tabela: `platform_admins`

Usuários com privilégios administrativos sobre a plataforma.

```sql
CREATE TABLE platform_admins (
    id              UUID                        PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id         UUID                        NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role            platform_admin_role_type    NOT NULL DEFAULT 'support',
    permissions     JSONB                       NOT NULL DEFAULT '{}',
    is_active       BOOLEAN                     NOT NULL DEFAULT TRUE,
    last_login_at   TIMESTAMPTZ                 NULL,
    created_at      TIMESTAMPTZ                 NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ                 NOT NULL DEFAULT NOW(),

    CONSTRAINT uq_platform_admins_user UNIQUE (user_id)
);

-- Índices
CREATE INDEX idx_platform_admins_active
    ON platform_admins (role)
    WHERE is_active = TRUE;
```

### Relacionamentos
- `1:1` → `users`
- `1:N` → `admin_audit_entries`

### Notas
- Um user pode ser tanto usuário regular quanto Platform Admin.
- `permissions` JSONB permite granularidade adicional dentro de cada role.
- Autenticação via mesmo JWT com claim `platform_role` adicional.
- Último `super_admin` não pode ser removido (constraint de aplicação).

---

## Tabela: `system_configs`

Configurações globais da plataforma.

```sql
CREATE TABLE system_configs (
    key             VARCHAR(100)    PRIMARY KEY,
    value           JSONB           NOT NULL,
    value_type      VARCHAR(20)     NOT NULL DEFAULT 'string',  -- string, integer, boolean, json
    description     TEXT            NULL,
    is_secret       BOOLEAN         NOT NULL DEFAULT FALSE,
    updated_by      UUID            NULL REFERENCES platform_admins(id) ON DELETE SET NULL,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);
```

### Dados iniciais (seed)

```sql
INSERT INTO system_configs (key, value, value_type, description) VALUES
    ('maintenance_mode',            'false',    'boolean',  'Ativa modo de manutenção'),
    ('registration_enabled',        'true',     'boolean',  'Permite novos registros'),
    ('default_trial_days',          '14',       'integer',  'Dias de trial para planos pagos'),
    ('max_orgs_per_user',           '5',        'integer',  'Máximo de organizações por usuário'),
    ('ai_global_enabled',           'true',     'boolean',  'Habilita/desabilita IA globalmente'),
    ('publishing_global_enabled',   'true',     'boolean',  'Habilita/desabilita publicação');
```

### Notas
- `key` é a chave primária (busca direta por chave sem necessidade de índice).
- `is_secret = true` indica que o valor é criptografado (ex: `stripe_webhook_secret`).
- Secrets não são retornados em listagens da API.
- Alterações auditadas automaticamente.

---

## Tabela: `admin_audit_entries`

Registro de ações administrativas para compliance e rastreabilidade.

```sql
CREATE TABLE admin_audit_entries (
    id              UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    admin_id        UUID            NOT NULL REFERENCES platform_admins(id) ON DELETE RESTRICT,
    action          VARCHAR(100)    NOT NULL,
    resource_type   VARCHAR(50)     NOT NULL,
    resource_id     UUID            NULL,
    context         JSONB           NOT NULL DEFAULT '{}',
    ip_address      INET            NOT NULL,
    user_agent      VARCHAR(500)    NULL,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

-- Listagem geral (timeline)
CREATE INDEX idx_admin_audit_created
    ON admin_audit_entries (created_at DESC);

-- Filtro por admin
CREATE INDEX idx_admin_audit_admin
    ON admin_audit_entries (admin_id, created_at DESC);

-- Filtro por ação
CREATE INDEX idx_admin_audit_action
    ON admin_audit_entries (action, created_at DESC);

-- Filtro por recurso
CREATE INDEX idx_admin_audit_resource
    ON admin_audit_entries (resource_type, resource_id, created_at DESC)
    WHERE resource_id IS NOT NULL;
```

### Ações auditadas

| Ação | Descrição |
|------|-----------|
| `organization.suspended` | Admin suspendeu organização |
| `organization.unsuspended` | Admin reativou organização |
| `organization.deleted` | Admin excluiu organização |
| `user.banned` | Admin baniu usuário |
| `user.unbanned` | Admin desbaniu usuário |
| `user.force_verified` | Admin forçou verificação de email |
| `user.password_reset_sent` | Admin enviou reset de senha |
| `plan.created` | Admin criou plano |
| `plan.updated` | Admin atualizou plano |
| `plan.deactivated` | Admin desativou plano |
| `config.updated` | Admin alterou configuração do sistema |
| `subscription.overridden` | Admin alterou subscription manualmente |

### Notas
- `ON DELETE RESTRICT` no admin_id impede exclusão de admin que tenha audit entries.
- `context` JSONB armazena dados extras: `old_value`, `new_value`, `reason`.
- Registros de audit nunca são excluídos (compliance).
- `ip_address` e `user_agent` para rastreabilidade completa.

---

## Tabela: `platform_metrics_cache`

Cache de métricas agregadas para o dashboard admin.

```sql
CREATE TABLE platform_metrics_cache (
    key             VARCHAR(100)    PRIMARY KEY,
    value           JSONB           NOT NULL,
    computed_at     TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    ttl_seconds     INTEGER         NOT NULL DEFAULT 300
);
```

### Métricas cacheadas

| Key | Descrição | TTL |
|-----|-----------|-----|
| `dashboard_overview` | Contadores gerais (orgs, users) | 5 min |
| `dashboard_subscriptions` | Distribuição por plano, MRR, ARR | 5 min |
| `dashboard_usage` | Publicações, gerações, storage | 5 min |
| `dashboard_health` | Success rate, latência, providers | 1 min |

### Notas
- Métricas computadas por job agendado (evita queries pesadas no request do admin).
- Fallback: se cache expirado, computa on-demand com timeout de 10s.
- Tabela simples key-value para flexibilidade.

---

## ER — Platform Administration

```
platform_admins
├── id (PK)
├── user_id (FK → users, UNIQUE)
├── role (super_admin, admin, support)
├── permissions (JSONB)
├── is_active
│
└──── admin_audit_entries
      ├── id (PK)
      ├── admin_id (FK → platform_admins)
      ├── action, resource_type, resource_id
      ├── context (JSONB)
      ├── ip_address, user_agent
      └── created_at

system_configs
├── key (PK)
├── value (JSONB)
├── value_type
├── is_secret
├── updated_by (FK → platform_admins)
└── description

platform_metrics_cache
├── key (PK)
├── value (JSONB)
├── computed_at, ttl_seconds
```
