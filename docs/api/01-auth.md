# 01 — Autenticação & Perfil

[← Voltar ao índice](00-index.md)

---

## POST /api/v1/auth/register

Registra um novo usuário.

**Autenticação:** Nenhuma

### Request

```json
{
  "name": "Marina Silva",
  "email": "marina@exemplo.com",
  "password": "MinhaSenh@123",
  "password_confirmation": "MinhaSenh@123"
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `name` | string | Sim | 2-100 caracteres |
| `email` | string | Sim | Email válido, único |
| `password` | string | Sim | Mín. 8 chars, 1 maiúscula, 1 número, 1 especial |
| `password_confirmation` | string | Sim | Deve coincidir com `password` |

### Response — 201 Created

```json
{
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "type": "user",
    "attributes": {
      "name": "Marina Silva",
      "email": "marina@exemplo.com",
      "email_verified": false,
      "created_at": "2026-02-15T10:30:00Z"
    }
  },
  "meta": {
    "message": "Conta criada com sucesso. Verifique seu email para ativar a conta."
  }
}
```

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 422 | VALIDATION_ERROR | Dados inválidos |
| 429 | RATE_LIMIT_EXCEEDED | Mais de 5 registros/min por IP |

---

## POST /api/v1/auth/verify-email

Verifica o email do usuário com o token enviado por email.

**Autenticação:** Nenhuma

### Request

```json
{
  "token": "a1b2c3d4e5f6..."
}
```

### Response — 200 OK

```json
{
  "data": {
    "message": "Email verificado com sucesso."
  }
}
```

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 404 | RESOURCE_NOT_FOUND | Token inválido |
| 410 | RESOURCE_NOT_FOUND | Token expirado (24h) |

---

## POST /api/v1/auth/login

Autentica o usuário e retorna par de tokens.

**Autenticação:** Nenhuma

### Request

```json
{
  "email": "marina@exemplo.com",
  "password": "MinhaSenh@123"
}
```

### Response — 200 OK (sem 2FA)

```json
{
  "data": {
    "access_token": "eyJhbGciOiJSUzI1NiIs...",
    "refresh_token": "dGhpcyBpcyBhIHJlZnJl...",
    "token_type": "Bearer",
    "expires_in": 900
  }
}
```

### Response — 200 OK (com 2FA ativo)

```json
{
  "data": {
    "requires_2fa": true,
    "temp_token": "temp_a1b2c3d4..."
  }
}
```

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 401 | AUTHENTICATION_ERROR | Credenciais inválidas |
| 403 | AUTHENTICATION_ERROR | Conta não verificada / bloqueada |
| 429 | RATE_LIMIT_EXCEEDED | 5 tentativas falhas |

---

## POST /api/v1/auth/2fa/verify

Verifica código TOTP para completar login com 2FA.

**Autenticação:** Nenhuma (usa `temp_token`)

### Request

```json
{
  "temp_token": "temp_a1b2c3d4...",
  "otp_code": "123456"
}
```

### Response — 200 OK

```json
{
  "data": {
    "access_token": "eyJhbGciOiJSUzI1NiIs...",
    "refresh_token": "dGhpcyBpcyBhIHJlZnJl...",
    "token_type": "Bearer",
    "expires_in": 900
  }
}
```

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 401 | AUTHENTICATION_ERROR | Código TOTP inválido |
| 410 | AUTHENTICATION_ERROR | temp_token expirado (5 min) |

---

## POST /api/v1/auth/refresh

Renova o par de tokens.

**Autenticação:** Nenhuma (usa `refresh_token`)

### Request

```json
{
  "refresh_token": "dGhpcyBpcyBhIHJlZnJl..."
}
```

### Response — 200 OK

```json
{
  "data": {
    "access_token": "eyJhbGciOiJSUzI1NiIs...",
    "refresh_token": "bmV3IHJlZnJlc2ggdG9r...",
    "token_type": "Bearer",
    "expires_in": 900
  }
}
```

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 401 | AUTHENTICATION_ERROR | Refresh token inválido/expirado/revogado |

---

## POST /api/v1/auth/logout

Invalida a sessão atual.

**Autenticação:** Bearer token

### Request

```json
{
  "all_sessions": false
}
```

| Campo | Tipo | Padrão | Descrição |
|-------|------|--------|-----------|
| `all_sessions` | boolean | false | Se `true`, invalida todas as sessões |

### Response — 204 No Content

---

## POST /api/v1/auth/forgot-password

Solicita redefinição de senha.

**Autenticação:** Nenhuma

### Request

```json
{
  "email": "marina@exemplo.com"
}
```

### Response — 200 OK

```json
{
  "data": {
    "message": "Se o email existir, um link de redefinição foi enviado."
  }
}
```

> **Nota:** Sempre retorna 200 independentemente do email existir (previne enumeração de emails).

---

## POST /api/v1/auth/reset-password

Redefine a senha com o token recebido por email.

**Autenticação:** Nenhuma

### Request

```json
{
  "token": "a1b2c3d4e5f6...",
  "password": "NovaSenha@456",
  "password_confirmation": "NovaSenha@456"
}
```

### Response — 200 OK

```json
{
  "data": {
    "message": "Senha redefinida com sucesso."
  }
}
```

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 404 | RESOURCE_NOT_FOUND | Token inválido |
| 410 | RESOURCE_NOT_FOUND | Token expirado (1h) |
| 422 | VALIDATION_ERROR | Senha não atende requisitos |

---

## POST /api/v1/auth/2fa/enable

Inicia a ativação do 2FA.

**Autenticação:** Bearer token

### Response — 200 OK

```json
{
  "data": {
    "secret": "JBSWY3DPEHPK3PXP",
    "qr_code_url": "otpauth://totp/SocialMediaManager:marina@exemplo.com?secret=JBSWY3DPEHPK3PXP&issuer=SocialMediaManager",
    "qr_code_svg": "<svg>...</svg>"
  }
}
```

---

## POST /api/v1/auth/2fa/confirm

Confirma ativação do 2FA com código TOTP.

**Autenticação:** Bearer token

### Request

```json
{
  "otp_code": "123456"
}
```

### Response — 200 OK

```json
{
  "data": {
    "recovery_codes": [
      "abc12-def34",
      "ghi56-jkl78",
      "mno90-pqr12",
      "stu34-vwx56",
      "yza78-bcd90",
      "efg12-hij34",
      "klm56-nop78",
      "qrs90-tuv12",
      "wxy34-zab56",
      "cde78-fgh90"
    ],
    "message": "2FA ativado com sucesso. Guarde os códigos de recuperação em local seguro."
  }
}
```

---

## POST /api/v1/auth/2fa/disable

Desativa o 2FA.

**Autenticação:** Bearer token

### Request

```json
{
  "password": "MinhaSenh@123"
}
```

### Response — 200 OK

```json
{
  "data": {
    "message": "2FA desativado com sucesso."
  }
}
```

---

## GET /api/v1/profile

Retorna dados do perfil do usuário autenticado.

**Autenticação:** Bearer token

### Response — 200 OK

```json
{
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "type": "user",
    "attributes": {
      "name": "Marina Silva",
      "email": "marina@exemplo.com",
      "phone": "+5511999887766",
      "timezone": "America/Sao_Paulo",
      "avatar_url": "https://storage.example.com/avatars/550e8400.jpg",
      "email_verified": true,
      "two_factor_enabled": true,
      "last_login_at": "2026-02-15T10:30:00Z",
      "created_at": "2026-01-01T00:00:00Z",
      "updated_at": "2026-02-15T10:30:00Z"
    }
  }
}
```

---

## PUT /api/v1/profile

Atualiza dados do perfil.

**Autenticação:** Bearer token

### Request

```json
{
  "name": "Marina S. Costa",
  "phone": "+5511999887766",
  "timezone": "America/Sao_Paulo"
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `name` | string | Não | 2-100 caracteres |
| `phone` | string | Não | Formato internacional |
| `timezone` | string | Não | IANA timezone válido |

### Response — 200 OK

```json
{
  "data": {
    "id": "550e8400-...",
    "type": "user",
    "attributes": {
      "name": "Marina S. Costa",
      "phone": "+5511999887766",
      "timezone": "America/Sao_Paulo",
      "..."
    }
  }
}
```

---

## PUT /api/v1/profile/avatar

Atualiza foto de perfil.

**Autenticação:** Bearer token
**Content-Type:** `multipart/form-data`

### Request

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `avatar` | file | Sim | JPG/PNG, máx 2MB |

### Response — 200 OK

```json
{
  "data": {
    "avatar_url": "https://storage.example.com/avatars/550e8400.jpg"
  }
}
```

---

## PUT /api/v1/profile/email

Atualiza email (requer re-verificação).

**Autenticação:** Bearer token

### Request

```json
{
  "email": "novo.email@exemplo.com",
  "password": "MinhaSenh@123"
}
```

### Response — 200 OK

```json
{
  "data": {
    "message": "Email de verificação enviado para novo.email@exemplo.com."
  }
}
```

---

## PUT /api/v1/profile/password

Altera a senha.

**Autenticação:** Bearer token

### Request

```json
{
  "current_password": "MinhaSenh@123",
  "password": "NovaSenha@456",
  "password_confirmation": "NovaSenha@456"
}
```

### Response — 200 OK

```json
{
  "data": {
    "message": "Senha alterada com sucesso."
  }
}
```

---

## DELETE /api/v1/profile

Solicita exclusão da conta (LGPD).

**Autenticação:** Bearer token

### Request

```json
{
  "password": "MinhaSenh@123",
  "reason": "Não preciso mais do serviço"
}
```

### Response — 200 OK

```json
{
  "data": {
    "message": "Exclusão agendada. Você tem 30 dias para cancelar. Um email de confirmação foi enviado.",
    "purge_at": "2026-03-17T10:30:00Z"
  }
}
```

---

## GET /api/v1/profile/data-export

Solicita exportação de dados pessoais (LGPD).

**Autenticação:** Bearer token

### Response — 202 Accepted

```json
{
  "data": {
    "message": "Exportação iniciada. Você receberá um email com o link de download quando estiver pronto.",
    "export_id": "660e8400-..."
  }
}
```

---

## GET /api/health

Health check do sistema.

**Autenticação:** Nenhuma

### Response — 200 OK

```json
{
  "status": "healthy",
  "checks": {
    "database": { "status": "up", "latency_ms": 2 },
    "redis": { "status": "up", "latency_ms": 1 },
    "queue": { "status": "up", "pending_jobs": 42 },
    "storage": { "status": "up" }
  },
  "timestamp": "2026-02-15T10:30:00Z"
}
```
