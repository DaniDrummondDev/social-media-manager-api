# Social Media Adapters — Social Media Manager API

## Objetivo

Definir o **Adapter Pattern** para integração com redes sociais, garantindo que o domínio não conheça detalhes de implementação dos providers.

> Referência: ADR-006 (Adapter Pattern for Social Media)

---

## Princípio Fundamental

> Adicionar uma nova rede social = implementar as interfaces + zero mudança no domínio.

O domínio define **o que** precisa ser feito. A infraestrutura define **como** cada provider faz.

---

## Interfaces (Domain Layer)

Definidas em `app/Domain/SocialAccount/Contracts/`:

### SocialAuthenticatorInterface

```php
interface SocialAuthenticatorInterface
{
    public function getAuthorizationUrl(string $state): string;
    public function handleCallback(string $code, string $state): OAuthCredentials;
    public function refreshToken(EncryptedToken $refreshToken): OAuthCredentials;
    public function revokeToken(EncryptedToken $accessToken): void;
    public function getAccountInfo(EncryptedToken $accessToken): SocialAccountInfo;
}
```

### SocialPublisherInterface

```php
interface SocialPublisherInterface
{
    public function publish(PublishRequest $request): PublishResult;
    public function getPostStatus(string $externalPostId): PostStatus;
    public function deletePost(string $externalPostId): void;
}
```

### SocialAnalyticsInterface

```php
interface SocialAnalyticsInterface
{
    public function getPostMetrics(string $externalPostId): PostMetrics;
    public function getAccountMetrics(DateRange $period): AccountMetrics;
    public function getFollowerMetrics(DateRange $period): FollowerMetrics;
}
```

### SocialEngagementInterface

```php
interface SocialEngagementInterface
{
    public function getComments(string $externalPostId, ?string $cursor): CommentCollection;
    public function replyToComment(string $externalCommentId, string $text): ReplyResult;
}
```

---

## Factory Pattern

```php
// Infrastructure Layer
class SocialAdapterFactory
{
    public function makeAuthenticator(SocialProvider $provider): SocialAuthenticatorInterface;
    public function makePublisher(SocialProvider $provider): SocialPublisherInterface;
    public function makeAnalytics(SocialProvider $provider): SocialAnalyticsInterface;
    public function makeEngagement(SocialProvider $provider): SocialEngagementInterface;
}
```

O factory resolve o adapter correto com base no `SocialProvider` enum.

---

## Implementações (Infrastructure Layer)

Organizadas em `app/Infrastructure/External/{Provider}/`:

```
External/
├── Instagram/
│   ├── InstagramAuthenticator.php    → implements SocialAuthenticatorInterface
│   ├── InstagramPublisher.php        → implements SocialPublisherInterface
│   ├── InstagramAnalytics.php        → implements SocialAnalyticsInterface
│   └── InstagramEngagement.php       → implements SocialEngagementInterface
├── TikTok/
│   ├── TikTokAuthenticator.php
│   ├── TikTokPublisher.php
│   ├── TikTokAnalytics.php
│   └── TikTokEngagement.php
└── YouTube/
    ├── YouTubeAuthenticator.php
    ├── YouTubePublisher.php
    ├── YouTubeAnalytics.php
    └── YouTubeEngagement.php
```

---

## Especificidades por Provider

### Instagram (Graph API)

- Auth: Facebook OAuth 2.0 → Instagram Business Account.
- Publicação: Container-based (create container → publish).
- Tipos: Image, Carousel, Reel, Story.
- Limites: 25 posts/dia por conta.
- Analytics: Insights API com métricas específicas (reach, impressions, saves).

### TikTok (Content Posting API)

- Auth: TikTok OAuth 2.0.
- Publicação: Upload → Create Video.
- Tipos: apenas vídeo.
- Limites: variam por conta.
- Analytics: Video Data API.

### YouTube (Data API v3)

- Auth: Google OAuth 2.0.
- Publicação: Resumable upload → set metadata.
- Tipos: Video, Short.
- Limites: quota diária de unidades (10.000 units/dia default).
- Analytics: YouTube Analytics API (separada).

---

## Rate Limiting por Provider

Cada adapter respeita os limites da API externa:

| Provider | Limite | Estratégia |
|----------|--------|-----------|
| Instagram | 200 calls/hour | Token bucket no Redis |
| TikTok | Varia | Token bucket no Redis |
| YouTube | 10.000 units/day | Counter diário no Redis |

- Antes de cada chamada: verificar se há quota disponível.
- Se excedido: lançar `ProviderRateLimitException` → circuit breaker reage.

---

## Adicionando Nova Rede Social

1. Criar enum value em `SocialProvider`.
2. Criar diretório `app/Infrastructure/External/{NewProvider}/`.
3. Implementar as 4 interfaces.
4. Registrar no `SocialAdapterFactory`.
5. Adicionar rate limiting config.
6. Adicionar circuit breaker config.
7. Criar testes de integração.
8. Atualizar API spec e documentação.
9. **Nenhuma alteração no Domain ou Application Layer.**

---

## Anti-Patterns

- Lógica específica de provider no Domain Layer.
- Use Case verificando `if (provider === 'instagram')`.
- API client direto no Use Case (sem interface).
- Mesmo adapter para autenticação e publicação (separação de responsabilidades).
- Ignorar rate limits do provider externo.
- Retry infinito em chamadas para APIs externas.
- Tokens decriptados passados entre adapters.

---

## Dependências

Esta skill complementa:
- `03-integrations/oauth-social-accounts.md` (fluxo OAuth)
- `04-operations/failure-handling.md` (circuit breaker, retry)
- `01-security/encryption-strategy.md` (tokens criptografados)
