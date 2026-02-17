# ADR-006: Adapter Pattern para Redes Sociais

[← Voltar ao índice](00-index.md)

---

- **Status:** Accepted
- **Data:** 2026-02-15
- **Decisores:** Equipe de arquitetura

## Contexto

O sistema precisa integrar com múltiplas redes sociais (Instagram, TikTok, YouTube na
Fase 1, e Facebook, LinkedIn, X, Pinterest nas fases seguintes). Cada rede tem:

- API diferente (REST, GraphQL, endpoints distintos)
- Fluxo OAuth diferente (Facebook Graph, Google OAuth, TikTok Login Kit)
- Formatos de conteúdo diferentes (limites de caracteres, tipos de mídia)
- Métricas diferentes (Instagram tem "saves", TikTok tem "watch_time")
- Rate limits diferentes

A adição de novas redes é uma certeza (o roadmap já prevê 10+ redes). Precisamos que
isso não impacte as regras de negócio existentes.

## Decisão

Adotar o **Adapter Pattern** (também conhecido como Ports & Adapters neste contexto)
para encapsular toda comunicação com redes sociais atrás de interfaces unificadas
definidas na camada de domínio/aplicação.

### Interfaces (Ports) — definidas no domínio

```php
// src/Domain/Publishing/Contracts/SocialMediaPublisher.php
interface SocialMediaPublisher
{
    public function publish(PublishableContent $content): PublishResult;
    public function delete(string $externalPostId): bool;
    public function getPostStatus(string $externalPostId): PostStatusResult;
}

// src/Domain/SocialAccount/Contracts/SocialMediaAuthenticator.php
interface SocialMediaAuthenticator
{
    public function getAuthorizationUrl(string $state): string;
    public function exchangeCode(string $code): TokenPair;
    public function refreshToken(string $refreshToken): TokenPair;
    public function revokeToken(string $accessToken): bool;
    public function getProfile(string $accessToken): SocialProfile;
}

// src/Domain/Analytics/Contracts/SocialMediaAnalytics.php
interface SocialMediaAnalytics
{
    public function getPostMetrics(string $accessToken, string $externalPostId): MetricsData;
    public function getAccountMetrics(string $accessToken, DateRange $period): AccountMetricsData;
}

// src/Domain/Engagement/Contracts/SocialMediaEngagement.php
interface SocialMediaEngagement
{
    public function getComments(string $accessToken, string $externalPostId, ?string $cursor): CommentCollection;
    public function replyToComment(string $accessToken, string $commentId, string $text): ReplyResult;
}
```

### Adapters (Implementações) — na camada de infraestrutura

```
src/Infrastructure/SocialMedia/
├── Instagram/
│   ├── InstagramPublisher.php          implements SocialMediaPublisher
│   ├── InstagramAuthenticator.php      implements SocialMediaAuthenticator
│   ├── InstagramAnalytics.php          implements SocialMediaAnalytics
│   ├── InstagramEngagement.php         implements SocialMediaEngagement
│   └── InstagramApiClient.php          HTTP client compartilhado
├── TikTok/
│   ├── TikTokPublisher.php
│   ├── TikTokAuthenticator.php
│   ├── TikTokAnalytics.php
│   ├── TikTokEngagement.php
│   └── TikTokApiClient.php
├── YouTube/
│   ├── YouTubePublisher.php
│   ├── YouTubeAuthenticator.php
│   ├── YouTubeAnalytics.php
│   ├── YouTubeEngagement.php
│   └── YouTubeApiClient.php
└── SocialMediaAdapterFactory.php       Resolve adapter por provider
```

### Factory para resolução dinâmica

```php
// src/Infrastructure/SocialMedia/SocialMediaAdapterFactory.php
class SocialMediaAdapterFactory
{
    public function makePublisher(SocialProvider $provider): SocialMediaPublisher
    {
        return match ($provider) {
            SocialProvider::Instagram => app(InstagramPublisher::class),
            SocialProvider::TikTok   => app(TikTokPublisher::class),
            SocialProvider::YouTube  => app(YouTubePublisher::class),
        };
    }

    // makeAuthenticator(), makeAnalytics(), makeEngagement()...
}
```

### Como adicionar uma nova rede (ex: Facebook)

1. Criar pasta `src/Infrastructure/SocialMedia/Facebook/`
2. Implementar as 4 interfaces (`Publisher`, `Authenticator`, `Analytics`, `Engagement`)
3. Adicionar `Facebook` ao enum `SocialProvider`
4. Registrar no `SocialMediaAdapterFactory`
5. Adicionar credenciais OAuth no `.env`
6. **Zero alteração** em regras de negócio, use cases ou domínio

## Alternativas consideradas

### 1. Service class única com switch/case por provider
- **Prós:** Simples, tudo em um lugar
- **Contras:** Classe gigante, violação de OCP (Open/Closed Principle), cada nova rede modifica código existente
- **Por que descartada:** Viola SOLID, não escala com 10+ providers

### 2. Strategy Pattern puro
- **Prós:** Similar ao Adapter, flexível
- **Contras:** Não encapsula a tradução entre interfaces diferentes — foco é em algoritmos intercambiáveis
- **Por que descartado:** Adapter é mais adequado porque estamos adaptando interfaces externas incompatíveis para uma interface interna unificada

### 3. SDK de cada plataforma diretamente nos use cases
- **Prós:** Mais direto, sem abstrações
- **Contras:** Acoplamento total, impossível testar sem API real, cada mudança de API impacta regras de negócio
- **Por que descartada:** Viola completamente Clean Architecture

## Consequências

### Positivas
- Adicionar nova rede = adicionar código, nunca modificar existente (OCP)
- Cada adapter é testável com mocks da API correspondente
- Use cases não sabem nem se importam qual rede estão publicando
- Falha em uma rede não afeta outras (isolamento)
- Cada adapter pode tratar peculiaridades da API (upload em 2 steps do Instagram, chunked upload do TikTok)

### Negativas
- Mais interfaces e classes (boilerplate)
- Lowest common denominator: funcionalidades exclusivas de uma rede precisam de extensão da interface ou interfaces específicas
- Mapeamento de dados entre formato interno e formato de cada API

### Riscos
- Interfaces muito genéricas podem não capturar particularidades — mitigado por permitir interfaces adicionais específicas quando necessário
- Mudança na API de uma rede requer atualização do adapter — isolado, sem impacto em outros
