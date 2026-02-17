# Arquitetura de Autenticacao

## Objetivo

Definir a arquitetura de autenticacao do Social Media Manager API, baseada em JWT RS256
com suporte a 2FA, refresh token rotation e deteccao de reutilizacao de tokens.

## Principios

- Autenticacao stateless via JWT (sem sessoes no servidor).
- Tokens assinados com chave assimetrica (RS256): chave privada assina, chave publica verifica.
- Refresh tokens sao opacos, armazenados no banco e rotacionados a cada uso.
- Toda operacao sensivel exige verificacao adicional (2FA ou re-autenticacao).
- Auditoria completa de eventos de autenticacao.

---

## Regras

### JWT RS256

- Usar par de chaves RSA (minimo 2048 bits, recomendado 4096).
- Chave privada: usada exclusivamente pelo servico de autenticacao para assinar tokens.
- Chave publica: distribuida para servicos que precisam apenas verificar tokens.
- Chaves armazenadas em variaveis de ambiente (`JWT_PRIVATE_KEY`, `JWT_PUBLIC_KEY`) ou vault.
- NUNCA versionar chaves no repositorio.

### Estrutura do Access Token (Claims)

```json
{
  "sub": "uuid-do-usuario",
  "org": "uuid-da-organizacao-ativa",
  "email": "usuario@exemplo.com",
  "role": "admin",
  "token_type": "access",
  "exp": 1700000900,
  "iat": 1700000000,
  "jti": "uuid-unico-do-token"
}
```

- `sub`: user_id (UUID) — identifica o usuario autenticado.
- `org`: organization_id (UUID) — identifica a organizacao ativa (tenant).
- `email`: email verificado do usuario.
- `role`: role do usuario na organizacao ativa (owner, admin, member).
- `platform_role`: role de admin da plataforma, se aplicavel (super_admin, admin, support). Ausente para users regulares.
- `token_type`: sempre `"access"` para access tokens.
- `exp`: expiracao em 15 minutos a partir da emissao.
- `iat`: timestamp de emissao.
- `jti`: identificador unico do token (UUID v4), usado para blacklist no logout.

### Troca de Organizacao Ativa

- Usuario pode pertencer a multiplas organizacoes (N:N com roles).
- Para trocar de organizacao: `POST /auth/switch-organization` com `organization_id`.
- Valida que o usuario e membro da organizacao solicitada.
- Emite novo par de tokens (access + refresh) com o novo `org` claim.
- Tokens anteriores permanecem validos ate expirar (nao e logout).

### Access Token

- Expiracao: **15 minutos**.
- Enviado no header `Authorization: Bearer <token>`.
- Stateless: validado apenas pela assinatura e claims, sem consulta ao banco.
- Unica excecao: verificar blacklist no Redis (operacao O(1)).

### Refresh Token

- Expiracao: **7 dias**.
- Valor opaco (UUID v4 ou string aleatoria de 64 caracteres).
- Armazenado na tabela `refresh_tokens` com colunas:
  - `id`, `user_id`, `token_hash` (SHA-256 do token), `family_id`, `expires_at`,
    `revoked_at`, `replaced_by_id`, `created_at`.
- Retornado no body da resposta de login (API-first, sem cookies).
- Rotacionado a cada uso: ao trocar por novo par, o refresh token antigo e revogado.

### Refresh Token Chain (Deteccao de Reutilizacao)

- Cada refresh token pertence a uma `family_id` (criada no login).
- Ao usar um refresh token:
  1. Verificar se esta revogado.
  2. Se **nao revogado**: gerar novo par (access + refresh), revogar o token usado.
  3. Se **revogado** (reutilizacao detectada): revogar **toda a familia** (`family_id`).
- Reutilizacao de refresh token indica possivel roubo — invalidar toda a cadeia e
  forcar re-autenticacao do usuario.
- Registrar evento de seguranca no audit log ao detectar reutilizacao.

### Autenticacao em Duas Etapas (2FA)

- Implementar TOTP (Time-based One-Time Password), compativel com Google Authenticator.
- Algoritmo: HMAC-SHA1, 6 digitos, intervalo de 30 segundos.
- Secret armazenado encriptado no banco (AES-256-GCM, mesma estrategia de tokens sociais).
- Backup codes: 8 codigos de uso unico, gerados na ativacao, armazenados como hash bcrypt.
- 2FA e **obrigatorio** para:
  - Alteracao de email.
  - Exclusao de conta.
- 2FA e **opcional** para login (configuravel pelo usuario).
- Fluxo de login com 2FA habilitado:
  1. `POST /auth/login` com email/password → resposta `202` com `challenge_token`.
  2. `POST /auth/2fa/verify` com `challenge_token` + codigo TOTP → JWT pair.
  3. `challenge_token` expira em 5 minutos e e de uso unico.

### Fluxo de Login

1. Usuario envia `POST /auth/login` com `email` e `password`.
2. Verificar se email existe e se a conta esta ativa.
3. Verificar password com `password_verify()` (bcrypt).
4. Se 2FA **desabilitado**: retornar `{ access_token, refresh_token, token_type, expires_in }`.
   - Token emitido com a ultima organizacao ativa do usuario (ou a primeira se primeiro login).
5. Se 2FA **habilitado**: retornar `{ challenge_token, challenge_type: "totp", expires_in }`.
6. Registrar tentativa no `login_histories` (sucesso ou falha).

### Politica de Senhas

- Hashing: bcrypt com cost factor **12**.
- Requisitos minimos:
  - Minimo 8 caracteres.
  - Pelo menos 1 letra maiuscula.
  - Pelo menos 1 letra minuscula.
  - Pelo menos 1 numero.
  - Pelo menos 1 caractere especial (`!@#$%^&*()-_=+`).
- Validar contra lista de senhas comuns (top 10.000).
- Nao permitir senha igual ao email.
- Nao armazenar historico de senhas (fora do escopo atual).

### Verificacao de Email

- Ao criar conta, enviar email de verificacao com token.
- Token: string aleatoria de 64 caracteres, armazenado como hash SHA-256 no banco.
- Expiracao: **24 horas**.
- Ate verificar email, usuario tem acesso restrito (pode autenticar, mas nao pode
  publicar conteudo nem conectar redes sociais).
- Endpoint: `POST /auth/email/verify` com `token`.
- Reenvio: `POST /auth/email/resend` — rate limited a 3 tentativas por hora.

### Reset de Senha

- `POST /auth/password/forgot` com `email` — sempre retorna `200` (nao revelar se email existe).
- Token: string aleatoria de 64 caracteres, armazenado como hash SHA-256.
- Expiracao: **1 hora**.
- Uso unico: invalidado apos uso ou apos novo pedido de reset.
- `POST /auth/password/reset` com `token`, `password`, `password_confirmation`.
- Apos reset: revogar todos os refresh tokens do usuario (forcar re-login em todos os dispositivos).

### Logout e Blacklist

- `POST /auth/logout` com access token no header.
- Adicionar o `jti` do access token ao Redis com TTL igual ao tempo restante de vida do token.
- Chave no Redis: `token:blacklist:{jti}`.
- Em cada request autenticada, verificar se o `jti` esta na blacklist antes de aceitar o token.
- Revogar o refresh token associado (se enviado no body).
- Operacao no Redis e O(1) — impacto minimo na performance.

### Rate Limiting em Endpoints de Autenticacao

| Endpoint                    | Limite              | Janela   | Chave              |
|-----------------------------|---------------------|----------|---------------------|
| `POST /auth/login`          | 5 tentativas        | 15 min   | IP                  |
| `POST /auth/password/forgot`| 3 tentativas        | 15 min   | IP                  |
| `POST /auth/password/reset` | 3 tentativas        | 15 min   | IP + token          |
| `POST /auth/2fa/verify`     | 5 tentativas        | 15 min   | IP + challenge      |
| `POST /auth/email/resend`   | 3 tentativas        | 60 min   | user_id             |
| `POST /auth/register`       | 3 tentativas        | 60 min   | IP                  |

- Retornar `429 Too Many Requests` com header `Retry-After`.
- Usar Redis para contagem (key com TTL da janela).

### Auditoria de Login

- Tabela `login_histories`:
  - `id`, `user_id` (nullable para tentativas com email invalido), `email`,
    `ip_address`, `user_agent`, `status` (success, failed_password, failed_2fa,
    account_locked), `failure_reason`, `created_at`.
- Registrar **toda** tentativa de login, incluindo falhas.
- Nao armazenar a senha tentada, apenas o resultado.
- Disponibilizar endpoint `GET /account/login-history` para o usuario consultar.

---

## Anti-patterns

- **Armazenar JWT no banco de dados**: JWT e stateless, salvar no banco anula o proposito.
- **Sessoes stateful**: este projeto usa JWT, nao sessoes do Laravel.
- **Tokens sem expiracao**: todo token DEVE ter `exp` definido.
- **Senhas em logs**: NUNCA logar senhas, nem em modo debug.
- **Retornar tokens no GET**: tokens so devem ser retornados em respostas POST.
- **Validar JWT apenas pelo formato**: sempre verificar assinatura, expiracao e blacklist.
- **Refresh token sem rotacao**: reutilizar o mesmo refresh token indefinidamente.
- **Ignorar reutilizacao de refresh token**: nao detectar replay attack.
- **Secret do 2FA em plain text**: deve ser encriptado no banco.
- **Revelar existencia de email**: endpoints de forgot password devem sempre retornar 200.

---

## Dependencias

- `php-open-source-saver/jwt-auth` ou `lcobucci/jwt`: geracao e validacao de JWT RS256.
- `pragmarx/google2fa-laravel`: implementacao TOTP para 2FA.
- `bacon/bacon-qr-code`: geracao de QR code para setup do 2FA.
- Redis: blacklist de tokens, rate limiting, contadores de tentativas.
- PostgreSQL: tabelas `users`, `refresh_tokens`, `login_histories`, `two_factor_secrets`.
