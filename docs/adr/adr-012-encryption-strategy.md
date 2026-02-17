# ADR-012: Estratégia de Criptografia para Tokens Sociais

[← Voltar ao índice](00-index.md)

---

- **Status:** Accepted
- **Data:** 2026-02-15
- **Decisores:** Equipe de arquitetura

## Contexto

O sistema armazena tokens de acesso de redes sociais (Instagram, TikTok, YouTube)
que permitem publicar conteúdo, ler comentários e acessar métricas em nome do usuário.

Esses tokens são altamente sensíveis:
- Um token comprometido permite acesso total à conta social do usuário
- Tokens de redes sociais são alvos frequentes de ataques
- Regulamentações (LGPD) exigem proteção de dados sensíveis
- O sistema anterior do cliente (Gestão de Eventos) já aplica boas práticas de segurança

## Decisão

Adotar **AES-256-GCM** (Galois/Counter Mode) para criptografia de tokens sociais,
com gerenciamento de chaves via variáveis de ambiente e suporte a rotação.

### Por que AES-256-GCM?

| Aspecto | AES-256-GCM | AES-256-CBC |
|---------|-------------|-------------|
| Confidencialidade | Sim | Sim |
| Integridade (autenticação) | Sim (AEAD) | Não (precisa de HMAC separado) |
| Detecção de tampering | Nativa | Manual |
| Performance | Paralelizável, hardware-accelerated | Sequencial |
| Padding oracle attacks | Imune | Vulnerável sem HMAC |

### Implementação

```php
// src/Domain/SocialAccount/ValueObjects/EncryptedToken.php
final readonly class EncryptedToken
{
    private function __construct(
        private string $encryptedValue,
    ) {}

    public static function fromPlainText(string $plainText): self
    {
        // Criptografia acontece via Encrypter injetado na infra
        // Domain define o Value Object, Infra faz a criptografia
    }

    public function cipherText(): string
    {
        return $this->encryptedValue;
    }
}

// src/Infrastructure/Services/TokenEncryptionService.php
class TokenEncryptionService
{
    private string $key;

    public function __construct()
    {
        // Chave dedicada para tokens sociais (diferente do APP_KEY)
        $this->key = config('social.encryption_key');
    }

    public function encrypt(string $plainText): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_AES256GCM_NPUBBYTES);
        $ciphertext = sodium_crypto_aead_aes256gcm_encrypt(
            $plainText,
            '',  // additional data
            $nonce,
            $this->key
        );

        return base64_encode($nonce . $ciphertext);
    }

    public function decrypt(string $encrypted): string
    {
        $decoded = base64_decode($encrypted);
        $nonceLength = SODIUM_CRYPTO_AEAD_AES256GCM_NPUBBYTES;
        $nonce = substr($decoded, 0, $nonceLength);
        $ciphertext = substr($decoded, $nonceLength);

        $plainText = sodium_crypto_aead_aes256gcm_decrypt(
            $ciphertext,
            '',
            $nonce,
            $this->key
        );

        if ($plainText === false) {
            throw new DecryptionFailedException();
        }

        return $plainText;
    }
}
```

### Gerenciamento de chaves

| Chave | Uso | Armazenamento |
|-------|-----|---------------|
| `APP_KEY` | Criptografia geral do Laravel (cookies, sessions) | `.env` |
| `SOCIAL_ENCRYPTION_KEY` | Criptografia de tokens de redes sociais | `.env` (dedicada) |
| Chaves RSA (JWT) | Assinatura de JWTs | Arquivo em `storage/` |

### Rotação de chaves

1. Nova chave é adicionada ao `.env` como `SOCIAL_ENCRYPTION_KEY_NEW`
2. Job de migração re-encripta todos os tokens com a nova chave
3. Após migração completa, a chave antiga é removida
4. Zero downtime — sistema lê com ambas as chaves durante a migração

### O que é criptografado

| Dado | Criptografia | Justificativa |
|------|-------------|---------------|
| Social access_token | AES-256-GCM | Acesso à conta social |
| Social refresh_token | AES-256-GCM | Permite obter novo access_token |
| 2FA secret (TOTP) | AES-256-GCM | Segredo do autenticador |
| Recovery codes (2FA) | AES-256-GCM | Recuperação de 2FA |
| Webhook secrets | AES-256-GCM | Assinatura de webhooks |
| Senhas | bcrypt (hash, não criptografia) | Irreversível por design |

### Regras de segurança

- Tokens descriptografados NUNCA são logados
- Tokens descriptografados NUNCA aparecem em responses da API
- Tokens são descriptografados apenas no momento do uso (just-in-time)
- Em caso de falha de descriptografia, a conta é marcada como `error` e o usuário é notificado para reconectar

## Alternativas consideradas

### 1. Laravel Encrypt (APP_KEY)
- **Prós:** Simples, nativo, uma linha de código
- **Contras:** Chave compartilhada com sessões/cookies, comprometimento = tudo exposto, sem controle granular
- **Por que descartado:** Tokens sociais merecem chave dedicada; isolamento de blast radius

### 2. Vault (HashiCorp)
- **Prós:** Gerenciamento de secrets enterprise-grade, audit log, rotação automática
- **Contras:** Dependência externa, complexidade operacional, custo, latência
- **Por que descartado:** Overkill para o MVP. Pode ser adotado quando o volume de clientes justificar

### 3. Armazenar tokens em banco separado/criptografado
- **Prós:** Isolamento total
- **Contras:** Complexidade de infraestrutura, latência adicional
- **Por que descartado:** AES-256-GCM no mesmo banco com chave dedicada é suficiente

## Consequências

### Positivas
- Tokens protegidos mesmo em caso de dump do banco de dados
- Chave dedicada limita blast radius (compromisso de APP_KEY não expõe tokens)
- GCM garante integridade além de confidencialidade
- Rotação de chaves sem downtime
- Compatível com extensão sodium do PHP 8.4

### Negativas
- Overhead de criptografia/descriptografia em cada uso de token
- Complexidade adicional no código de acesso a tokens
- Necessidade de gerenciar chave adicional no deploy
- Tokens criptografados ocupam mais espaço no banco

### Riscos
- Perda da chave de criptografia = perda de todos os tokens (reconexão obrigatória) — mitigado por backup seguro da chave
- Performance de descriptografia em operações batch (sync de métricas para muitas contas) — mitigado por cache de tokens descriptografados em memória durante o job (nunca em Redis)
