# OAuth & Social Accounts — Social Media Manager API

## Objetivo

Definir o fluxo de conexão, desconexão e manutenção de contas de redes sociais via OAuth 2.0, incluindo armazenamento seguro de tokens e refresh automático.

---

## Fluxo OAuth 2.0

### Conexão de Nova Conta

```
1. Frontend: GET /api/v1/social-accounts/auth/{provider}
2. Backend: gera state token (UUID), armazena no Redis (TTL 10min)
3. Backend: retorna authorization_url do provider
4. Usuário: autoriza no provider (Instagram, TikTok, YouTube)
5. Provider: redireciona para callback com code + state
6. Frontend: POST /api/v1/social-accounts/auth/{provider}/callback { code, state }
7. Backend: valida state, troca code por tokens, obtém info da conta
8. Backend: criptografa tokens (AES-256-GCM), salva social_account
9. Backend: emite SocialAccountConnected event
```

### Validações no Callback

- `state` deve existir no Redis e não estar expirado.
- `state` é single-use (removido após validação).
- `code` é trocado pelo token no provider.
- Conta social não pode estar conectada por outra organização.
- Se conta já está conectada pela mesma organização: atualizar tokens.
- Contas sociais pertencem à **organização** (não ao usuário individual).

---

## Armazenamento de Tokens

### Tabela `social_accounts`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `access_token_encrypted` | TEXT | AES-256-GCM encrypted |
| `refresh_token_encrypted` | TEXT | AES-256-GCM encrypted |
| `token_expires_at` | TIMESTAMPTZ | Expiração do access token |
| `token_refreshed_at` | TIMESTAMPTZ | Último refresh |
| `token_scopes` | JSONB | Escopos concedidos |

### Regras

- Tokens são criptografados **imediatamente** ao receber do provider.
- Nunca armazenados em plain text, nem temporariamente.
- Decriptados apenas no momento de uso (chamada à API).
- Nunca cacheados após decrypt.
- Nunca logados (nem criptografados).

---

## Refresh Automático de Tokens

### Estratégia Proativa

- Job periódico (`RefreshExpiringTokensJob`): a cada hora.
- Seleciona contas com `token_expires_at < now() + 1 hour`.
- Para cada conta: chama `SocialAuthenticatorInterface::refreshToken()`.
- Novos tokens criptografados e salvos.
- Emite `TokenRefreshed` event.

### Estratégia Reativa

- Ao usar um token e receber `401 Unauthorized` do provider:
  1. Tentar refresh do token.
  2. Se sucesso: repetir operação original.
  3. Se falha: marcar conta como `token_expired`, notificar usuário.

### Falha de Refresh

- Após 3 tentativas falhadas de refresh: marcar conta como `requires_reconnection`.
- Usuário deve reconectar manualmente via OAuth.
- Publicações agendadas para esta conta são pausadas (não canceladas).
- Emite `SocialAccountTokenExpired` event.

---

## Desconexão de Conta

```
1. DELETE /api/v1/social-accounts/{id}
2. Revogar token no provider (best-effort, não bloquear se falhar)
3. Destruir tokens criptografados do banco (DELETE, não anonimizar)
4. Cancelar agendamentos pendentes para esta conta
5. Emitir SocialAccountDisconnected event
6. Registrar no audit log
```

---

## Reconexão de Conta

```
1. POST /api/v1/social-accounts/{id}/reconnect
2. Mesmo fluxo OAuth, mas atualiza conta existente
3. Preserva histórico de analytics e publicações
4. Novos tokens substituem os anteriores
5. Status volta para 'active'
```

---

## Estados da Conta Social

```
active → token_expired → requires_reconnection
  ↑          ↓                    ↓
  └── (refresh ok) ←──── (reconexão OAuth)
```

| Estado | Descrição | Pode publicar? |
|--------|-----------|---------------|
| `active` | Token válido, conta funcional | Sim |
| `token_expired` | Token expirou, tentando refresh | Não |
| `requires_reconnection` | Refresh falhou, precisa reconectar | Não |
| `disconnected` | Usuário desconectou | Não |

---

## Segurança OAuth

- State parameter obrigatório (CSRF protection).
- PKCE (Proof Key for Code Exchange) quando suportado pelo provider.
- Scopes mínimos necessários solicitados (principle of least privilege).
- Redirect URI fixo e validado (não dinâmico).
- Token exchange server-side (nunca no frontend).

---

## Anti-Patterns

- Tokens em plain text no banco.
- Tokens no frontend (JavaScript).
- State sem validação ou reusável.
- Refresh token sem criptografia.
- Ignorar falha de refresh (deixar token expirado sem ação).
- Revogar tokens do provider de forma síncrona bloqueante.
- Mesma conta social conectada por múltiplas organizações.

---

## Dependências

- `01-security/encryption-strategy.md` (AES-256-GCM)
- `03-integrations/social-media-adapters.md` (interfaces)
- `01-security/audit-logging.md` (registro de conexão/desconexão)
