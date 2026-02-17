# ADR-011: Estrutura de Pastas por Bounded Context

[← Voltar ao índice](00-index.md)

---

- **Status:** Accepted
- **Data:** 2026-02-15
- **Decisores:** Equipe de arquitetura

## Contexto

A estrutura de pastas padrão do Laravel organiza o código por tipo técnico
(Controllers, Models, Requests, etc.). Com Clean Architecture + DDD, precisamos
de uma estrutura que:

- Reflita os bounded contexts do domínio
- Separe claramente as camadas (Domain, Application, Infrastructure)
- Facilite a navegação por contexto de negócio
- Permita que um desenvolvedor trabalhe em um bounded context sem conhecer os outros
- Seja compatível com o autoloader do Laravel/Composer

## Decisão

Adotar uma **estrutura de pastas híbrida** que organiza o código por bounded context
dentro de cada camada arquitetural.

### Estrutura completa

```
app/
├── Domain/                              # Camada de Domínio (PHP puro)
│   ├── Identity/                        # BC: Identity & Access
│   │   ├── Entities/
│   │   │   └── User.php
│   │   ├── ValueObjects/
│   │   │   ├── UserId.php
│   │   │   ├── Email.php
│   │   │   ├── Name.php
│   │   │   ├── HashedPassword.php
│   │   │   ├── Phone.php
│   │   │   └── Timezone.php
│   │   ├── Events/
│   │   │   ├── UserRegistered.php
│   │   │   ├── UserEmailVerified.php
│   │   │   └── UserLoggedIn.php
│   │   ├── Enums/
│   │   │   └── UserStatus.php
│   │   ├── Repositories/               # Interfaces apenas
│   │   │   └── UserRepositoryInterface.php
│   │   ├── Services/                    # Domain Services
│   │   │   └── PasswordPolicy.php
│   │   └── Exceptions/
│   │       ├── InvalidEmailException.php
│   │       └── UserAlreadyExistsException.php
│   │
│   ├── SocialAccount/                   # BC: Social Account Management
│   │   ├── Entities/
│   │   │   └── SocialAccount.php
│   │   ├── ValueObjects/
│   │   │   ├── SocialAccountId.php
│   │   │   ├── EncryptedToken.php
│   │   │   └── ConnectionStatus.php
│   │   ├── Enums/
│   │   │   └── SocialProvider.php
│   │   ├── Events/
│   │   ├── Repositories/
│   │   ├── Contracts/                   # Interfaces para adapters externos
│   │   │   ├── SocialMediaAuthenticator.php
│   │   │   └── ...
│   │   └── Exceptions/
│   │
│   ├── Campaign/                        # BC: Campaign Management
│   │   ├── Entities/
│   │   │   ├── Campaign.php
│   │   │   └── Content.php
│   │   ├── ValueObjects/
│   │   │   ├── CampaignId.php
│   │   │   ├── ContentId.php
│   │   │   ├── CampaignName.php
│   │   │   ├── Hashtag.php
│   │   │   └── NetworkOverride.php
│   │   ├── Enums/
│   │   │   ├── CampaignStatus.php
│   │   │   └── ContentStatus.php
│   │   ├── Events/
│   │   ├── Repositories/
│   │   └── Exceptions/
│   │
│   ├── ContentAI/                       # BC: Content AI
│   │   ├── Entities/
│   │   │   ├── AIGeneration.php
│   │   │   └── AISettings.php
│   │   ├── ValueObjects/
│   │   │   ├── GenerationId.php
│   │   │   ├── ToneOfVoice.php
│   │   │   └── Language.php
│   │   ├── Enums/
│   │   │   └── GenerationType.php
│   │   ├── Contracts/
│   │   │   ├── ContentGenerator.php
│   │   │   └── SentimentClassifier.php
│   │   ├── Events/
│   │   ├── Repositories/
│   │   └── Exceptions/
│   │
│   ├── Publishing/                      # BC: Publishing
│   │   ├── Entities/
│   │   │   └── ScheduledPost.php
│   │   ├── ValueObjects/
│   │   │   ├── ScheduledPostId.php
│   │   │   └── PublishError.php
│   │   ├── Enums/
│   │   │   └── PostStatus.php
│   │   ├── Contracts/
│   │   │   └── SocialMediaPublisher.php
│   │   ├── Events/
│   │   ├── Repositories/
│   │   └── Exceptions/
│   │
│   ├── Analytics/                       # BC: Analytics
│   │   ├── Entities/
│   │   │   ├── ContentMetrics.php
│   │   │   ├── AccountMetrics.php
│   │   │   └── ReportExport.php
│   │   ├── ValueObjects/
│   │   ├── Contracts/
│   │   │   └── SocialMediaAnalytics.php
│   │   ├── Events/
│   │   ├── Repositories/
│   │   └── Exceptions/
│   │
│   ├── Engagement/                      # BC: Engagement & Automation
│   │   ├── Entities/
│   │   │   ├── Comment.php
│   │   │   ├── AutomationRule.php
│   │   │   ├── WebhookEndpoint.php
│   │   │   └── WebhookDelivery.php
│   │   ├── ValueObjects/
│   │   │   ├── RuleCondition.php
│   │   │   ├── RuleAction.php
│   │   │   └── Sentiment.php
│   │   ├── Contracts/
│   │   │   └── SocialMediaEngagement.php
│   │   ├── Events/
│   │   ├── Repositories/
│   │   ├── Services/
│   │   │   └── AutomationEngine.php
│   │   └── Exceptions/
│   │
│   ├── Media/                           # BC: Media Management
│   │   ├── Entities/
│   │   │   └── Media.php
│   │   ├── ValueObjects/
│   │   │   ├── MediaId.php
│   │   │   ├── MimeType.php
│   │   │   ├── FileSize.php
│   │   │   └── Dimensions.php
│   │   ├── Enums/
│   │   │   └── ScanStatus.php
│   │   ├── Events/
│   │   ├── Repositories/
│   │   └── Exceptions/
│   │
│   └── Shared/                          # Shared Kernel
│       ├── ValueObjects/
│       │   ├── Uuid.php
│       │   └── DateRange.php
│       └── Contracts/
│           └── DomainEvent.php
│
├── Application/                         # Camada de Aplicação (Use Cases)
│   ├── Identity/
│   │   ├── UseCases/
│   │   │   ├── RegisterUserUseCase.php
│   │   │   ├── LoginUseCase.php
│   │   │   ├── RefreshTokenUseCase.php
│   │   │   └── ...
│   │   └── DTOs/
│   │       ├── RegisterUserInput.php
│   │       ├── LoginInput.php
│   │       └── AuthTokensOutput.php
│   │
│   ├── SocialAccount/
│   │   ├── UseCases/
│   │   │   ├── ConnectSocialAccountUseCase.php
│   │   │   ├── DisconnectSocialAccountUseCase.php
│   │   │   └── ...
│   │   └── DTOs/
│   │
│   ├── Campaign/
│   │   ├── UseCases/
│   │   │   ├── CreateCampaignUseCase.php
│   │   │   ├── UpdateCampaignUseCase.php
│   │   │   ├── DeleteCampaignUseCase.php
│   │   │   ├── DuplicateCampaignUseCase.php
│   │   │   ├── CreateContentUseCase.php
│   │   │   └── ...
│   │   └── DTOs/
│   │
│   ├── ContentAI/
│   │   ├── UseCases/
│   │   │   ├── GenerateTitleUseCase.php
│   │   │   ├── GenerateDescriptionUseCase.php
│   │   │   ├── GenerateHashtagsUseCase.php
│   │   │   ├── GenerateFullContentUseCase.php
│   │   │   └── ...
│   │   └── DTOs/
│   │
│   ├── Publishing/
│   │   ├── UseCases/
│   │   │   ├── SchedulePostUseCase.php
│   │   │   ├── PublishNowUseCase.php
│   │   │   ├── CancelScheduleUseCase.php
│   │   │   ├── ReschedulePostUseCase.php
│   │   │   └── ...
│   │   └── DTOs/
│   │
│   ├── Analytics/
│   │   ├── UseCases/
│   │   └── DTOs/
│   │
│   ├── Engagement/
│   │   ├── UseCases/
│   │   └── DTOs/
│   │
│   └── Media/
│       ├── UseCases/
│       └── DTOs/
│
├── Infrastructure/                      # Camada de Infraestrutura (Laravel)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   ├── RegisterController.php
│   │   │   │   ├── LoginController.php
│   │   │   │   └── ...
│   │   │   ├── Campaign/
│   │   │   │   ├── CampaignController.php
│   │   │   │   └── ContentController.php
│   │   │   ├── Publishing/
│   │   │   ├── Analytics/
│   │   │   ├── Engagement/
│   │   │   ├── Media/
│   │   │   ├── AI/
│   │   │   └── SocialAccount/
│   │   ├── Middleware/
│   │   │   ├── RateLimitMiddleware.php
│   │   │   └── EnsureEmailVerified.php
│   │   ├── Requests/                    # Form Requests (validação)
│   │   │   ├── Campaign/
│   │   │   ├── Publishing/
│   │   │   └── ...
│   │   └── Resources/                   # API Resources (transformação)
│   │       ├── Campaign/
│   │       ├── Publishing/
│   │       └── ...
│   │
│   ├── Persistence/
│   │   ├── Eloquent/
│   │   │   ├── Models/                  # Eloquent Models (infra only)
│   │   │   │   ├── UserModel.php
│   │   │   │   ├── CampaignModel.php
│   │   │   │   ├── ContentModel.php
│   │   │   │   └── ...
│   │   │   └── Repositories/           # Implementações dos repositories
│   │   │       ├── EloquentUserRepository.php
│   │   │       ├── EloquentCampaignRepository.php
│   │   │       └── ...
│   │   └── Mappers/                     # Domain ↔ Eloquent mappers
│   │       ├── UserMapper.php
│   │       ├── CampaignMapper.php
│   │       └── ...
│   │
│   ├── SocialMedia/                     # Adapters de redes sociais
│   │   ├── Instagram/
│   │   │   ├── InstagramPublisher.php
│   │   │   ├── InstagramAuthenticator.php
│   │   │   ├── InstagramAnalytics.php
│   │   │   ├── InstagramEngagement.php
│   │   │   └── InstagramApiClient.php
│   │   ├── TikTok/
│   │   ├── YouTube/
│   │   └── SocialMediaAdapterFactory.php
│   │
│   ├── AI/                              # Implementações de IA
│   │   ├── PrismContentGenerator.php
│   │   ├── PrismSentimentClassifier.php
│   │   ├── PrismReplyGenerator.php
│   │   └── Prompts/
│   │       ├── TitlePrompt.php
│   │       ├── DescriptionPrompt.php
│   │       └── ...
│   │
│   ├── Queue/
│   │   ├── Jobs/
│   │   │   ├── ProcessScheduledPostJob.php
│   │   │   ├── SyncMetricsJob.php
│   │   │   ├── CaptureCommentsJob.php
│   │   │   ├── RefreshSocialTokensJob.php
│   │   │   └── ...
│   │   └── Listeners/
│   │       ├── Publishing/
│   │       ├── Analytics/
│   │       └── Engagement/
│   │
│   ├── Services/
│   │   ├── JwtService.php
│   │   ├── EncryptionService.php
│   │   └── StorageService.php
│   │
│   └── Providers/
│       ├── DomainServiceProvider.php    # Bind interfaces → implementações
│       ├── SocialMediaServiceProvider.php
│       └── AIServiceProvider.php
│
├── Console/
│   └── Commands/                        # Artisan commands
│
config/
database/
│   ├── migrations/
│   └── factories/
routes/
│   └── api/
│       └── v1/
tests/
│   ├── Unit/
│   ├── Integration/
│   ├── Feature/
│   └── Architecture/
```

### Regras de namespace

| Namespace | Pode depender de |
|-----------|-----------------|
| `App\Domain\*` | Apenas `App\Domain\Shared`, PHP stdlib |
| `App\Application\*` | `App\Domain\*` |
| `App\Infrastructure\*` | `App\Domain\*`, `App\Application\*`, Laravel, pacotes externos |

## Alternativas consideradas

### 1. Estrutura padrão do Laravel (por tipo técnico)
- **Prós:** Familiar para desenvolvedores Laravel, sem configuração
- **Contras:** Mistura bounded contexts nos mesmos diretórios, sem separação de camadas
- **Por que descartada:** Incompatível com Clean Architecture e DDD

### 2. Módulos Laravel (nwidart/laravel-modules)
- **Prós:** Isolamento total por módulo, autoload independente
- **Contras:** Overhead de configuração, cada módulo é quase um projeto separado, duplicação de providers
- **Por que descartado:** Complexidade excessiva; a estrutura de pastas por bounded context alcança o isolamento necessário sem o overhead

### 3. Organização por feature (vertical slices)
- **Prós:** Tudo de uma feature em um lugar
- **Contras:** Dificulta o reuso de entidades entre features, camadas misturadas
- **Por que descartada:** Conflita com a separação de camadas da Clean Architecture

## Consequências

### Positivas
- Navegação intuitiva — encontrar código por contexto de negócio
- Separação clara de camadas validada por testes de arquitetura
- Um bounded context pode evoluir sem afetar outros
- Novo desenvolvedor entende a estrutura do domínio pela organização de pastas
- Composer autoload funciona nativamente com PSR-4

### Negativas
- Mais pastas e níveis de diretório
- Mapeamento entre Eloquent Models e Domain Entities adiciona arquivos
- Desenvolvedores precisam saber em qual camada colocar cada classe
- Algum código de infraestrutura Laravel fica em locais não convencionais

### Riscos
- Desenvolvedores podem colocar código na camada errada — mitigado por testes de arquitetura e code review
