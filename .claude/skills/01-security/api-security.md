# API Security — Social Media Manager API

## Objetivo

Definir os padrões de segurança da API alinhados ao **OWASP API Security Top 10**, garantindo que todo endpoint seja seguro por padrão.

> A API é o produto. Todo endpoint é potencialmente público.

---

## Princípios

- **Explicit is safer than implicit** — toda permissão é declarada, nunca assumida.
- **Fail closed** — negar por padrão, liberar explicitamente.
- **Contract before implementation** — contrato de API definido antes do código.
- **Defense in depth** — múltiplas camadas de proteção.

---

## OWASP API Security Top 10 — Mitigações

### 1. Broken Object Level Authorization (BOLA)

- Toda operação que acessa recurso por ID **deve validar ownership via `organization_id`**.
- Repository queries sempre incluem `WHERE organization_id = ?`.
- Global scopes no Eloquent como camada adicional, filtrando por `organization_id` do JWT.
- Testes obrigatórios: acessar recurso de outra organização deve retornar 404 (não 403).
- Validar também que o `user_id` tem permissão (role) na organização para a operação.

### 2. Broken Authentication

- JWT RS256 com access token de 15 minutos.
- Refresh token rotacionado a cada uso, com detecção de reutilização.
- Rate limiting em endpoints de auth (5 tentativas/15min).
- Blacklist de tokens via Redis.
- 2FA disponível para login e obrigatório para operações sensíveis.

### 3. Broken Object Property Level Authorization (BOPLA)

- Response DTOs filtram campos internos — nunca expor `password_hash`, `token`, `secret`.
- Mass assignment protegido via `$fillable` explícito nos Models.
- API Resources controlam exatamente quais campos são retornados.
- Campos sensíveis em logs: mascarados ou omitidos.

### 4. Unrestricted Resource Consumption

Rate limits por escopo:

| Escopo | Limite | Janela |
|--------|--------|--------|
| Auth (login, register) | 5 req | 15 min / IP |
| AI generation | 10 req | 1 min / user |
| Media upload | 20 req | 1 min / user |
| General API | 60 req | 1 min / user |
| Webhooks test | 5 req | 15 min / user |

- Upload de mídia: imagem ≤ 10MB, vídeo ≤ 500MB.
- Paginação: máximo 50 itens por página.
- Busca textual: máximo 500 caracteres.

### 5. Broken Function Level Authorization

- Middleware valida permissões por endpoint, não apenas autenticação.
- Roles da organização (owner, admin, member) determinam permissões funcionais.
- Operações destrutivas (delete account, disconnect social) exigem verificação adicional e role adequado.
- Endpoints administrativos (`/api/v1/admin/*`) isolados com middleware `PlatformAdminMiddleware`.
- Middleware admin valida claim `platform_role` no JWT.
- Billing endpoints (`/api/v1/billing/*`) acessíveis apenas pelo owner da organização (exceto leitura de uso).
- Testes obrigatórios: user regular acessando `/admin/*` retorna 403.

### 6. Unrestricted Access to Sensitive Business Flows

Fluxos críticos protegidos:

- **Publicação**: conteúdo deve ter mídia compatível e conta social ativa.
- **Exclusão de conta**: requer 2FA + confirmação.
- **Alteração de email**: requer 2FA + verificação do novo email.
- **Conexão de rede social**: validação OAuth completa, não apenas token.

### 7. Server Side Request Forgery (SSRF)

- URLs de webhooks: apenas HTTPS, validar contra allowlist de schemes.
- Não seguir redirects automaticamente em webhook delivery.
- Validar que URL não aponta para IPs internos (127.0.0.1, 10.x, 172.16.x, 192.168.x).
- Timeout de 10 segundos em chamadas de webhook.

### 8. Security Misconfiguration

- CORS: origins explícitas, nunca `*` em produção.
- Debug mode desabilitado em produção (`APP_DEBUG=false`).
- Headers de segurança em toda resposta:
  - `X-Content-Type-Options: nosniff`
  - `X-Frame-Options: DENY`
  - `Strict-Transport-Security: max-age=31536000`
  - `X-XSS-Protection: 0` (usar CSP)
  - `Content-Security-Policy: default-src 'none'`
- Stack traces nunca expostos em produção.

### 9. Improper Inventory Management

- Todos endpoints documentados na API spec (`docs/api/`).
- Rotas não documentadas são removidas.
- Versionamento via URL prefix — endpoints depreciados têm prazo de remoção.
- Health check (`/health`) não requer autenticação mas não expõe dados internos.

### 10. Unsafe Consumption of APIs

- Respostas de Instagram/TikTok/YouTube **sempre validadas** antes de processar.
- Timeout configurado por provider (padrão 30s).
- Circuit breaker por provider — falhas consecutivas interrompem chamadas.
- Respostas de OpenAI: validar estrutura, sanitizar output antes de persistir.

---

## Validação de Input

### Camadas de validação

1. **Sintática** (Form Request): tipo, formato, tamanho.
2. **Semântica** (Use Case): regras de negócio, existência de recursos.
3. **Domínio** (Entity): invariantes do agregado.

### Regras

- Validação é **sempre server-side** (nunca confiar no cliente).
- Campos opcionais têm defaults explícitos.
- UUIDs validados no formato antes de query ao banco.
- Datas em formato ISO 8601 (`2026-02-15T10:30:00Z`).

---

## Respostas de Erro

- Mensagens de erro **nunca** vazam informação interna.
- Códigos de erro padronizados da aplicação (VALIDATION_ERROR, RESOURCE_NOT_FOUND, etc.).
- HTTP status codes corretos (400, 401, 403, 404, 409, 413, 422, 429, 500, 503).
- Formato consistente: `{ "error": { "code": "...", "message": "...", "details": [...] } }`.

---

## Testes de Segurança Obrigatórios

Para cada endpoint:

- Teste de autenticação (request sem token = 401).
- Teste de autorização/ownership (recurso de outra organização = 404).
- Teste de role (operação sem role adequado = 403).
- Teste de validação de input (dados inválidos = 400/422).
- Teste de rate limiting (exceder limite = 429).

---

## Dependências

Herda regras de:
- `01-security/auth-architecture.md`
- `01-security/encryption-strategy.md`

Complementa:
- `02-compliance/lgpd-compliance.md`
