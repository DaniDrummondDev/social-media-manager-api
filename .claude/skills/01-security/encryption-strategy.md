# Encryption Strategy — Social Media Manager API

## Objetivo

Definir a estratégia de criptografia para dados sensíveis, com foco nos tokens de redes sociais que necessitam criptografia reversível.

> Referência: ADR-012 (Encryption Strategy)

---

## Categorias de Dados Sensíveis

### 1. Credenciais de Usuário (One-Way)

| Dado | Algoritmo | Detalhes |
|------|-----------|----------|
| Senha | bcrypt | Cost factor 12, irreversível |
| Refresh token (armazenado) | SHA-256 | Hash para comparação |
| Email verification token | SHA-256 | Hash para comparação |
| Password reset token | SHA-256 | Hash para comparação |

### 2. Tokens de Redes Sociais (Reversível)

| Dado | Algoritmo | Detalhes |
|------|-----------|----------|
| OAuth access_token | AES-256-GCM | Criptografia autenticada |
| OAuth refresh_token | AES-256-GCM | Criptografia autenticada |
| 2FA TOTP secret | AES-256-GCM | Criptografia autenticada |

---

## AES-256-GCM — Implementação

### Por que AES-256-GCM

- **Authenticated encryption**: garante confidencialidade E integridade.
- **Tag de autenticação**: detecta qualquer alteração no ciphertext.
- **Nonce único**: previne ataques de repetição.
- **Performance**: suporte nativo via `sodium_*` functions do PHP.

### Estrutura do Dado Criptografado

```
[nonce (12 bytes)] + [ciphertext (variável)] + [auth tag (16 bytes)]
```

Armazenado no banco como string base64-encoded.

### Chave de Criptografia

- Variável de ambiente: `SOCIAL_TOKEN_KEY` (32 bytes, base64-encoded).
- **Separada** da `APP_KEY` do Laravel — comprometimento de uma não afeta a outra.
- Em produção: armazenada em vault (AWS Secrets Manager, HashiCorp Vault).
- NUNCA versionada no repositório.
- NUNCA logada.

### Implementação (Value Object + Service)

```php
// Domain Layer — Value Object
class EncryptedToken
{
    private function __construct(
        private readonly string $encryptedValue
    ) {}

    public static function fromPlainText(string $value): self;
    public static function fromEncrypted(string $encrypted): self;
    public function decrypt(): string;
    public function toString(): string; // retorna valor criptografado
}

// Infrastructure Layer — Service
class SocialTokenEncrypter
{
    public function encrypt(string $plainText): string;
    public function decrypt(string $encrypted): string;
}
```

### Regras de Uso

- Tokens são criptografados **imediatamente** ao receber do OAuth callback.
- Tokens são decriptados **apenas** no momento de uso (chamada à API externa).
- Tokens decriptados **nunca** são cacheados.
- Tokens decriptados **nunca** aparecem em logs.
- Em responses de API: tokens são omitidos ou mostrados como `"***"`.
- Tokens pertencem a contas sociais que pertencem a organizações — isolamento por `organization_id`.

---

## Rotação de Chaves

### Quando rotacionar

- Comprometimento suspeito da chave.
- Rotação periódica (a cada 12 meses, recomendado).
- Mudança de infraestrutura de vault.

### Processo de rotação

1. Gerar nova chave (`SOCIAL_TOKEN_KEY_NEW`).
2. Configurar aplicação com ambas as chaves (dual-key period).
3. Executar job batch: `RotateSocialTokenKeysJob`.
   - Para cada `social_account`: decrypt com chave antiga, encrypt com chave nova.
   - Processar em batches de 100.
   - Log de progresso e falhas.
4. Após conclusão: remover chave antiga, manter apenas a nova.

### Dual-Key Period

Durante a rotação, o serviço de decrypt tenta:
1. Decrypt com chave nova.
2. Se falhar: decrypt com chave antiga.
3. Se ambas falharem: marcar conta para reconexão.

---

## Bcrypt para Senhas

- Cost factor: **12** (balance entre segurança e performance).
- Função: `password_hash()` / `password_verify()` do PHP.
- NUNCA implementar hash customizado.
- NUNCA armazenar senha em plain text, mesmo temporariamente.

---

## Anti-Patterns

- Usar `APP_KEY` do Laravel para criptografar tokens de redes sociais.
- Usar `encrypt()` / `decrypt()` genérico do Laravel (usa APP_KEY internamente).
- Armazenar tokens em plain text no banco.
- Cachear tokens decriptados em Redis ou memória.
- Logar tokens (nem criptografados) em nível debug.
- Chave de criptografia hardcoded no código.
- Mesmo nonce para múltiplas operações de encrypt.
- Criptografia sem autenticação (AES-CBC sem HMAC).

---

## Dependências

Esta skill é referenciada por:
- `01-security/auth-architecture.md` (2FA secret encryption)
- `03-integrations/oauth-social-accounts.md` (token storage)
