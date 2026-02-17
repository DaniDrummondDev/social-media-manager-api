# ADR-005: Autenticação JWT com RS256

[← Voltar ao índice](00-index.md)

---

- **Status:** Accepted
- **Data:** 2026-02-15
- **Decisores:** Equipe de arquitetura

## Contexto

O sistema é uma API RESTful stateless que será consumida por um frontend separado
(e potencialmente apps mobile no futuro). Precisamos de:

- Autenticação stateless (sem sessão no servidor)
- Suporte a múltiplos clientes (web, mobile, integrações)
- Tokens de curta duração para segurança
- Mecanismo de refresh seguro
- Possibilidade de revogar sessões
- Compatibilidade com 2FA

## Decisão

Adotar **JWT (JSON Web Tokens)** assinados com **RS256** (RSA + SHA-256) para
autenticação, com sistema de refresh token opaco.

### Fluxo de autenticação

```
┌────────┐                    ┌──────────┐                    ┌─────────┐
│ Client │                    │   API    │                    │  Redis  │
└───┬────┘                    └────┬─────┘                    └────┬────┘
    │  POST /auth/login            │                               │
    │  {email, password}           │                               │
    ├─────────────────────────────▶│                               │
    │                              │  Validate credentials         │
    │                              │  Generate JWT (RS256)         │
    │                              │  Generate refresh token       │
    │                              │  Store refresh token hash ────┼──▶
    │  {access_token, refresh_token}                               │
    │◀─────────────────────────────┤                               │
    │                              │                               │
    │  GET /api/v1/campaigns       │                               │
    │  Authorization: Bearer JWT   │                               │
    ├─────────────────────────────▶│                               │
    │                              │  Verify JWT signature         │
    │                              │  (using public key only)      │
    │  {data: [...]}               │                               │
    │◀─────────────────────────────┤                               │
    │                              │                               │
    │  POST /auth/refresh          │                               │
    │  {refresh_token}             │                               │
    ├─────────────────────────────▶│                               │
    │                              │  Verify refresh token ────────┼──▶
    │                              │  Rotate (invalidate old) ─────┼──▶
    │                              │  Issue new pair               │
    │  {access_token, refresh_token}                               │
    │◀─────────────────────────────┤                               │
```

### Estrutura do JWT

```json
{
  "header": {
    "alg": "RS256",
    "typ": "JWT",
    "kid": "key-2026-02"
  },
  "payload": {
    "sub": "uuid-do-usuario",
    "iat": 1708000000,
    "exp": 1708000900,
    "jti": "uuid-unico-do-token",
    "iss": "social-media-manager",
    "type": "access"
  }
}
```

### Configuração de tokens

| Token | Tipo | Duração | Armazenamento |
|-------|------|---------|---------------|
| **Access Token** | JWT (RS256) | 15 minutos | Client-side (memory/httpOnly cookie) |
| **Refresh Token** | Opaque (random 64 bytes) | 7 dias | Hash SHA-256 no banco + Redis |

### Por que RS256 (assimétrico) e não HS256 (simétrico)?

| Aspecto | HS256 | RS256 |
|---------|-------|-------|
| Chave | Shared secret | Par público/privado |
| Validação | Requer o secret | Apenas chave pública |
| Microserviços | Todos precisam do secret | Apenas a API de auth tem a chave privada |
| Rotação | Complexa | Suportada via `kid` no header |
| Segurança | Comprometimento do secret = total | Comprometimento da pública = sem impacto |

### Refresh Token Rotation

- Cada uso do refresh token gera um novo par (access + refresh)
- O refresh token antigo é invalidado imediatamente
- Se um refresh token já usado for reutilizado (replay attack), TODAS as sessões do usuário são invalidadas
- Detecção via flag `is_used` no registro do refresh token

### Revogação

- **Logout:** Invalida refresh token no banco + adiciona `jti` do access token na blacklist (Redis, TTL = tempo restante do token)
- **Logout all:** Invalida todos os refresh tokens do usuário
- **Blacklist:** Verificada em cada request (Redis, O(1))

### Fluxo com 2FA

```
1. POST /auth/login {email, password}
2. Se 2FA ativo → 200 {requires_2fa: true, temp_token: "..."}
3. POST /auth/2fa/verify {temp_token, otp_code}
4. Se válido → 200 {access_token, refresh_token}
```

## Alternativas consideradas

### 1. Laravel Sanctum (SPA tokens)
- **Prós:** Simples, nativo do Laravel, cookie-based
- **Contras:** Session-based para SPAs (não stateless), limitado para mobile/integrações
- **Por que descartado:** Não é verdadeiramente stateless; incompatível com arquitetura API-first

### 2. Laravel Passport (OAuth2 completo)
- **Prós:** OAuth2 completo, client credentials, authorization code
- **Contras:** Complexidade excessiva para nosso caso, overhead de OAuth2 completo
- **Por que descartado:** Não precisamos de OAuth2 server completo; JWT customizado é mais enxuto

### 3. Opaque tokens (sem JWT)
- **Prós:** Revogação instantânea, sem payload exposto
- **Contras:** Cada request requer lookup no banco/cache
- **Por que descartado:** JWT permite validação sem lookup (exceto blacklist). Access token de 15min minimiza o risco de tokens não revogados

## Consequências

### Positivas
- Autenticação verdadeiramente stateless
- Validação sem banco de dados (apenas chave pública)
- Suporte a múltiplos clientes (web, mobile, API)
- Rotação de chaves via `kid` sem downtime
- Refresh token rotation previne replay attacks
- 2FA integrado no fluxo

### Negativas
- JWT não pode ser "revogado" instantaneamente (mitigado por blacklist + TTL curto)
- Blacklist no Redis adiciona um lookup por request
- Gerenciamento de chaves RSA requer cuidado
- Payload do JWT é legível (não encriptado) — não colocar dados sensíveis

### Riscos
- Chave privada comprometida = todos os tokens podem ser forjados — mitigado por rotação periódica de chaves e armazenamento seguro
- Blacklist no Redis down = tokens revogados podem ser aceitos por 15 min — mitigado por TTL curto do access token
