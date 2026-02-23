# 01 — Identity & Access + Social Account

[← Voltar ao índice](00-index.md)

---

## Tabela: `organizations`

Organizações (tenants) da plataforma. Toda dado de negócio pertence a uma organização.

```sql
CREATE TABLE organizations (
    id                  UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    name                VARCHAR(200)    NOT NULL,
    slug                VARCHAR(100)    NOT NULL,
    logo_path           VARCHAR(500)    NULL,
    timezone            VARCHAR(50)     NOT NULL DEFAULT 'America/Sao_Paulo',
    status              VARCHAR(20)     NOT NULL DEFAULT 'active',  -- active, suspended, deleted
    stripe_customer_id  VARCHAR(255)    NULL,
    settings            JSONB           NOT NULL DEFAULT '{}',
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    deleted_at          TIMESTAMPTZ     NULL,
    purge_at            TIMESTAMPTZ     NULL,

    CONSTRAINT uq_organizations_slug UNIQUE (slug)
);

-- Índices
CREATE INDEX idx_organizations_status ON organizations (status) WHERE deleted_at IS NULL;
CREATE INDEX idx_organizations_stripe ON organizations (stripe_customer_id) WHERE stripe_customer_id IS NOT NULL;
CREATE INDEX idx_organizations_purge  ON organizations (purge_at) WHERE purge_at IS NOT NULL;
```

### Notas
- `slug` é o identificador público da organização (usado em URLs).
- `stripe_customer_id` vincula a organização ao Stripe para billing.
- `settings` armazena configurações gerais da organização em JSONB.
- Tenant lógico do sistema — toda query de negócio filtra por `organization_id` (ADR-019).

---

## Tabela: `organization_members`

Relação N:N entre usuários e organizações com roles.

```sql
CREATE TABLE organization_members (
    id              UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID            NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    user_id         UUID            NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role            VARCHAR(20)     NOT NULL DEFAULT 'member',  -- owner, admin, member
    invited_by      UUID            NULL REFERENCES users(id) ON DELETE SET NULL,
    joined_at       TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),

    CONSTRAINT uq_org_members UNIQUE (organization_id, user_id),
    CONSTRAINT ck_org_members_role CHECK (role IN ('owner', 'admin', 'member'))
);

-- Índices
CREATE INDEX idx_org_members_user ON organization_members (user_id);
CREATE INDEX idx_org_members_org  ON organization_members (organization_id, role);
```

### Notas
- Um usuário pode pertencer a múltiplas organizações (relação N:N).
- Cada organização deve ter exatamente 1 `owner`.
- `invited_by` rastreia quem convidou o membro.
- JWT carrega `organization_id` + `user_id` — troca de org requer novo token.

---

## Tabela: `users`

Armazena os usuários da plataforma.

```sql
CREATE TABLE users (
    id                    UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    name                  VARCHAR(100)    NOT NULL,
    email                 VARCHAR(255)    NOT NULL,
    email_verified_at     TIMESTAMPTZ     NULL,
    password              VARCHAR(255)    NOT NULL,  -- bcrypt hash
    phone                 VARCHAR(20)     NULL,
    timezone              VARCHAR(50)     NOT NULL DEFAULT 'America/Sao_Paulo',
    avatar_path           VARCHAR(500)    NULL,
    status                user_status_type NOT NULL DEFAULT 'active',
    two_factor_enabled    BOOLEAN         NOT NULL DEFAULT FALSE,
    two_factor_secret     TEXT            NULL,      -- criptografado (AES-256-GCM)
    two_factor_confirmed_at TIMESTAMPTZ   NULL,
    recovery_codes        TEXT            NULL,      -- criptografado (AES-256-GCM)
    last_login_at         TIMESTAMPTZ     NULL,
    last_login_ip         INET            NULL,
    created_at            TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at            TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    deleted_at            TIMESTAMPTZ     NULL,
    purge_at              TIMESTAMPTZ     NULL,

    CONSTRAINT uq_users_email UNIQUE (email)
);

-- Índices
CREATE INDEX idx_users_email_active ON users (email) WHERE deleted_at IS NULL;
CREATE INDEX idx_users_status       ON users (status) WHERE deleted_at IS NULL;
CREATE INDEX idx_users_purge        ON users (purge_at) WHERE purge_at IS NOT NULL;
```

### Relacionamentos
- `N:N` → `organizations` (via `organization_members`)
- `1:N` → `refresh_tokens`
- `1:N` → `login_histories`
- `1:N` → `audit_logs`

---

## Tabela: `refresh_tokens`

Armazena refresh tokens para rotação e revogação.

```sql
CREATE TABLE refresh_tokens (
    id              UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id         UUID            NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash      VARCHAR(64)     NOT NULL,  -- SHA-256 do token
    ip_address      INET            NOT NULL,
    user_agent      VARCHAR(500)    NOT NULL,
    expires_at      TIMESTAMPTZ     NOT NULL,
    revoked_at      TIMESTAMPTZ     NULL,
    is_used         BOOLEAN         NOT NULL DEFAULT FALSE,  -- para detecção de replay
    replaced_by_id  UUID            NULL REFERENCES refresh_tokens(id),
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),

    CONSTRAINT uq_refresh_tokens_hash UNIQUE (token_hash)
);

-- Índices
CREATE INDEX idx_refresh_tokens_user    ON refresh_tokens (user_id);
CREATE INDEX idx_refresh_tokens_hash    ON refresh_tokens (token_hash) WHERE revoked_at IS NULL;
CREATE INDEX idx_refresh_tokens_expires ON refresh_tokens (expires_at) WHERE revoked_at IS NULL;
```

### Notas
- `token_hash`: hash SHA-256 do refresh token. O token original nunca é armazenado.
- `is_used`: quando `true` e o token é reutilizado, detecta replay attack → revogar toda a família.
- `replaced_by_id`: forma cadeia de rotação para rastreabilidade.

---

## Tabela: `password_reset_tokens`

Tokens de recuperação de senha.

```sql
CREATE TABLE password_reset_tokens (
    id          UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id     UUID            NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash  VARCHAR(64)     NOT NULL,  -- SHA-256
    expires_at  TIMESTAMPTZ     NOT NULL,
    used_at     TIMESTAMPTZ     NULL,
    created_at  TIMESTAMPTZ     NOT NULL DEFAULT NOW(),

    CONSTRAINT uq_password_reset_hash UNIQUE (token_hash)
);

-- Índice
CREATE INDEX idx_password_reset_user ON password_reset_tokens (user_id);
```

---

## Tabela: `login_histories`

Histórico de logins para auditoria e segurança.

```sql
CREATE TABLE login_histories (
    id          UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id     UUID            NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    ip_address  INET            NOT NULL,
    user_agent  VARCHAR(500)    NOT NULL,
    success     BOOLEAN         NOT NULL,
    failure_reason VARCHAR(100) NULL,  -- 'invalid_password', 'account_locked', '2fa_failed'
    logged_in_at TIMESTAMPTZ    NOT NULL DEFAULT NOW()
);

-- Índices
CREATE INDEX idx_login_histories_user    ON login_histories (user_id, logged_in_at DESC);
CREATE INDEX idx_login_histories_ip      ON login_histories (ip_address, logged_in_at DESC);
```

### Notas
- Registra tanto logins bem-sucedidos quanto falhos.
- `failure_reason` permite análise de padrões de ataque.
- Retenção: 1 ano (job de limpeza).

---

## Tabela: `audit_logs`

Log de auditoria para ações sensíveis.

```sql
CREATE TABLE audit_logs (
    id              UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID            NULL REFERENCES organizations(id) ON DELETE SET NULL,
    user_id         UUID            NULL REFERENCES users(id) ON DELETE SET NULL,
    action          VARCHAR(100)    NOT NULL,  -- 'user.password_changed', 'campaign.deleted'
    resource_type   VARCHAR(100)    NOT NULL,  -- 'user', 'campaign', 'social_account'
    resource_id     UUID            NULL,
    ip_address      INET            NULL,
    user_agent      VARCHAR(500)    NULL,
    old_values      JSONB           NULL,
    new_values      JSONB           NULL,
    metadata        JSONB           NULL,      -- dados extras contextuais
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

-- Índices
CREATE INDEX idx_audit_logs_org        ON audit_logs (organization_id, created_at DESC) WHERE organization_id IS NOT NULL;
CREATE INDEX idx_audit_logs_user       ON audit_logs (user_id, created_at DESC);
CREATE INDEX idx_audit_logs_resource   ON audit_logs (resource_type, resource_id, created_at DESC);
CREATE INDEX idx_audit_logs_action     ON audit_logs (action, created_at DESC);
CREATE INDEX idx_audit_logs_created_at ON audit_logs (created_at DESC);
```

### Ações auditadas

| Ação | Descrição |
|------|-----------|
| `user.registered` | Registro de novo usuário |
| `user.email_verified` | Verificação de email |
| `user.password_changed` | Alteração de senha |
| `user.email_changed` | Alteração de email |
| `user.2fa_enabled` | Ativação de 2FA |
| `user.2fa_disabled` | Desativação de 2FA |
| `user.deleted` | Exclusão de conta (LGPD) |
| `social_account.connected` | Conexão de rede social |
| `social_account.disconnected` | Desconexão de rede social |
| `social_account.token_refreshed` | Renovação de token |
| `campaign.created` | Criação de campanha |
| `campaign.updated` | Atualização de campanha |
| `campaign.deleted` | Exclusão de campanha |
| `content.published` | Publicação de conteúdo |
| `content.failed` | Falha na publicação |
| `automation.triggered` | Automação executada |
| `automation.rule_created` | Regra de automação criada |

### Notas
- `organization_id` é NULL para ações de nível usuário (login, password_changed). Preenchido para ações de negócio (campaign.deleted, content.published).
- `user_id` é `SET NULL` para manter o log mesmo se o usuário for excluído.
- `old_values`/`new_values` armazenam diffs em JSONB para rastreabilidade.
- Retenção: 1 ano (job de limpeza).

---

## Tabela: `social_accounts`

Contas de redes sociais conectadas via OAuth.

```sql
CREATE TABLE social_accounts (
    id                  UUID                    PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id     UUID                    NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    connected_by        UUID                    NOT NULL REFERENCES users(id),
    provider            social_provider_type    NOT NULL,
    provider_user_id    VARCHAR(255)            NOT NULL,
    username            VARCHAR(255)            NOT NULL,
    display_name        VARCHAR(255)            NULL,
    profile_picture_url VARCHAR(1000)           NULL,
    access_token        TEXT                    NOT NULL,  -- criptografado (AES-256-GCM)
    refresh_token       TEXT                    NULL,      -- criptografado (AES-256-GCM)
    token_expires_at    TIMESTAMPTZ             NULL,
    scopes              TEXT[]                  NOT NULL DEFAULT '{}',
    status              connection_status_type  NOT NULL DEFAULT 'connected',
    last_synced_at      TIMESTAMPTZ             NULL,
    connected_at        TIMESTAMPTZ             NOT NULL DEFAULT NOW(),
    disconnected_at     TIMESTAMPTZ             NULL,
    metadata            JSONB                   NULL,      -- dados extras do provider
    created_at          TIMESTAMPTZ             NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ             NOT NULL DEFAULT NOW(),
    deleted_at          TIMESTAMPTZ             NULL,
    purge_at            TIMESTAMPTZ             NULL
);

-- Unique index parcial: nos planos Free/Creator, 1 conta por provider por organização.
-- Nos planos Professional/Agency, múltiplas contas por provider são permitidas
-- (a validação de limite total é feita na Application Layer com base no plano).
CREATE UNIQUE INDEX uq_social_accounts_org_provider_user
    ON social_accounts (organization_id, provider, provider_user_id)
    WHERE deleted_at IS NULL;

-- Índices
CREATE INDEX idx_social_accounts_org        ON social_accounts (organization_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_social_accounts_status     ON social_accounts (status) WHERE deleted_at IS NULL;
CREATE INDEX idx_social_accounts_expires    ON social_accounts (token_expires_at)
    WHERE status = 'connected' AND deleted_at IS NULL;
CREATE INDEX idx_social_accounts_provider   ON social_accounts (provider, status)
    WHERE deleted_at IS NULL;
```

### Relacionamentos
- `N:1` → `organizations`
- `1:N` → `scheduled_posts`
- `1:N` → `content_metrics`
- `1:N` → `account_metrics`
- `1:N` → `comments`

### Notas
- Contas sociais pertencem à **organização**, não ao usuário individual (RN-010, ADR-019).
- `connected_by` registra qual usuário conectou a conta (atribuição).
- `access_token` e `refresh_token` são criptografados com AES-256-GCM (ver ADR-012).
- `scopes` usa array nativo do PostgreSQL para listar permissões concedidas.
- `metadata` armazena dados específicos do provider (ex: facebook_page_id para Instagram).
- O unique index garante que a mesma conta externa não é conectada duas vezes na mesma organização.
- Limites de contas por plano (Free: 3, Creator: 5, Professional: 15, Agency: 50) são validados na Application Layer.

---

## ER — Identity, Organization & Social Account

```
organizations
├── id (PK)
├── name, slug (UNIQUE)
├── stripe_customer_id
├── status, settings
│
├──── organization_members (N:N pivot)
│     ├── id (PK)
│     ├── organization_id (FK → organizations)
│     ├── user_id (FK → users)
│     ├── role (owner, admin, member)
│     └── invited_by (FK → users)
│
└──── social_accounts
      ├── id (PK)
      ├── organization_id (FK → organizations)
      ├── connected_by (FK → users)
      ├── provider
      ├── provider_user_id
      ├── access_token (encrypted)
      ├── refresh_token (encrypted)
      └── status

users
├── id (PK)
├── email (UNIQUE)
├── password
├── ...
│
├──── refresh_tokens
│     ├── id (PK)
│     ├── user_id (FK → users)
│     ├── token_hash (UNIQUE)
│     └── replaced_by_id (FK → refresh_tokens, self-ref)
│
├──── password_reset_tokens
│     ├── id (PK)
│     ├── user_id (FK → users)
│     └── token_hash (UNIQUE)
│
├──── login_histories
│     ├── id (PK)
│     ├── user_id (FK → users)
│     └── ip_address
│
└──── audit_logs
      ├── id (PK)
      ├── organization_id (FK → organizations, NULL)
      ├── user_id (FK → users, SET NULL)
      ├── action
      └── resource_type + resource_id
```
