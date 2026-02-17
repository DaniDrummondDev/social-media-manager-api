# Clean Architecture + Domain-Driven Design

## Objetivo

Definir as regras de Clean Architecture combinada com DDD para o Social Media Manager, incluindo camadas, bounded contexts, regra de dependencia, aggregate roots e padroes obrigatorios de implementacao.

> Referencia: ADR-001 (Clean Architecture com DDD)

---

## Camadas da Arquitetura

### Diagrama de Camadas

```
┌─────────────────────────────────────────────────────┐
│                 Infrastructure                       │
│  (Controllers, Eloquent, APIs externas, Jobs,       │
│   Providers, Cache, Queue, Middleware)               │
│                                                      │
│  ┌─────────────────────────────────────────────┐    │
│  │              Application                     │    │
│  │  (Use Cases, DTOs, Application Services)     │    │
│  │                                               │    │
│  │  ┌─────────────────────────────────────┐     │    │
│  │  │            Domain                    │     │    │
│  │  │  (Entities, Value Objects,           │     │    │
│  │  │   Domain Events, Domain Services,    │     │    │
│  │  │   Repository Interfaces, Exceptions) │     │    │
│  │  └─────────────────────────────────────┘     │    │
│  └─────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────┘
```

---

### Domain Layer (Camada de Dominio)

A camada mais interna. Contem **regras de negocio puras** sem nenhuma dependencia externa.

**Contem:**

- **Entities**: Objetos com identidade unica (User, Campaign, Content, ScheduledPost)
- **Value Objects**: Objetos imutaveis sem identidade (Email, Hashtag, EncryptedToken, Sentiment)
- **Domain Events**: Fatos imutaveis que ocorreram no dominio (PostPublished, CommentCaptured)
- **Repository Interfaces**: Contratos para persistencia (definidos aqui, implementados na Infrastructure)
- **Domain Services**: Logica que nao pertence a uma unica entidade (PasswordPolicy, AutomationEngine)
- **Enums**: Enumeracoes do dominio (UserStatus, CampaignStatus, SocialProvider)
- **Exceptions**: Excecoes especificas do dominio (InvalidEmailException, UserAlreadyExistsException)
- **Contracts**: Interfaces para servicos externos (SocialMediaPublisher, ContentGenerator)

**Regras:**

- **ZERO dependencias externas** — apenas PHP stdlib e o Shared Kernel
- Entities encapsulam estado e comportamento (nao sao anemic models)
- Value Objects sao imutaveis e comparam por valor
- Domain Events sao imutaveis e representam fatos passados (nunca comandos)
- Repository Interfaces definem contratos, nunca implementacoes
- Nao importa nada de Laravel, Eloquent, ou qualquer pacote externo

### Application Layer (Camada de Aplicacao)

Orquestra o dominio. Contem a logica de aplicacao que coordena entities, repositories e servicos.

**Contem:**

- **Use Cases**: Uma classe por caso de uso (RegisterUserUseCase, SchedulePostUseCase)
- **DTOs**: Data Transfer Objects para input e output (RegisterUserInput, AuthTokensOutput)
- **Application Services**: Servicos de orquestracao que coordenam multiplos agregados ou contextos

**Regras:**

- Depende **apenas** da Domain Layer
- Use Cases recebem DTOs e retornam DTOs (nunca entities diretamente)
- Use Cases sempre recebem `organization_id` e `user_id` como parametros (multi-tenancy + autoria)
- Um Use Case = uma operacao de negocio
- Application Services coordenam, mas **nao contem regras de negocio**
- Nao importa nada de Laravel, Eloquent, ou qualquer pacote externo

### Infrastructure Layer (Camada de Infraestrutura)

Implementa interfaces definidas pelas camadas internas. Conhece detalhes tecnologicos.

**Contem:**

- **Controllers**: Recebem HTTP, validam, chamam Use Cases, retornam responses
- **Eloquent Models**: Modelos de persistencia (separados das entities de dominio)
- **Repository Implementations**: EloquentUserRepository, EloquentCampaignRepository
- **Mappers**: Conversao entre Domain Entities e Eloquent Models
- **API Clients**: Implementacoes de integracao com Instagram, TikTok, YouTube, OpenAI
- **Jobs**: Jobs de fila que chamam Use Cases (nunca contem logica de negocio)
- **Listeners**: Event listeners que despacham jobs ou chamam Use Cases
- **Middleware**: Rate limiting, autenticacao, logging
- **Form Requests**: Validacao de input HTTP
- **API Resources**: Transformacao de output para response JSON
- **Providers**: Service providers que fazem bind de interfaces para implementacoes
- **Services**: Servicos de infraestrutura (JwtService, EncryptionService, StorageService)

**Regras:**

- Pode depender de Domain e Application
- Implementa interfaces definidas nas camadas internas
- Eloquent Models **nao sao** Domain Entities (sao objetos de infraestrutura)
- Controllers sao finos: validam, chamam Use Case, retornam response
- Jobs sao finos: deserializam, chamam Use Case, tratam resultado

---

## Regra de Dependencia

As dependencias **sempre** apontam para dentro:

| Camada | Pode depender de | NAO pode depender de |
|--------|------------------|---------------------|
| Domain | `Domain\Shared`, PHP stdlib | Application, Infrastructure, Laravel, pacotes externos |
| Application | Domain | Infrastructure, Laravel, pacotes externos |
| Infrastructure | Domain, Application, Laravel, pacotes externos | — |

```
Infrastructure → Application → Domain
     ↑                 ↑           ↑
  conhece           conhece     nao conhece
  tudo              dominio     ninguem
```

Esta regra e **validada automaticamente** por testes de arquitetura (Pest Architecture Plugin).

---

## Bounded Contexts

O dominio e dividido em **11 Bounded Contexts**, cada um com seu proprio modelo de dominio e linguagem ubiqua.

| # | Bounded Context | Namespace | Aggregate Roots |
|---|----------------|-----------|-----------------|
| 1 | Identity & Access | `Identity` | User |
| 2 | Organization Management | `Organization` | Organization, OrganizationMember |
| 3 | Social Account Management | `SocialAccount` | SocialAccount |
| 4 | Campaign Management | `Campaign` | Campaign, Content |
| 5 | Content AI | `ContentAI` | AIGeneration, AISettings |
| 6 | Publishing | `Publishing` | ScheduledPost |
| 7 | Analytics | `Analytics` | ContentMetrics, AccountMetrics, ReportExport |
| 8 | Engagement & Automation | `Engagement` | Comment, AutomationRule, WebhookEndpoint |
| 9 | Media Management | `Media` | Media |
| 10 | Billing & Subscription | `Billing` | Subscription, Plan, Invoice |
| 11 | Platform Administration | `PlatformAdmin` | PlatformAdmin, SystemConfig |

### Estrutura de cada Bounded Context

Cada bounded context possui subdiretorios em cada camada:

```
app/Domain/{Context}/          → Entities, ValueObjects, Events, Repositories, Services, Enums, Exceptions
app/Application/{Context}/     → UseCases, DTOs, Services
app/Infrastructure/{Context}/  → Models, Repositories, Providers (quando aplicavel)
```

### Comunicacao entre Bounded Contexts

- **Permitido**: Via Domain Events ou Application Services
- **Proibido**: Acesso direto a entities ou repositories de outro contexto

```
# CORRETO — Comunicacao via Domain Event
PostPublished (Publishing) → Listener → SyncMetricsUseCase (Analytics)

# CORRETO — Comunicacao via Application Service
PublishingUseCase chama SocialAccountQueryService para obter tokens

# ERRADO — Acesso direto a entity de outro contexto
PublishingUseCase instancia SocialAccount entity diretamente
```

---

## Aggregate Roots e Value Objects

### Aggregate Roots

| Aggregate Root | Bounded Context | Responsabilidade |
|---------------|-----------------|------------------|
| User | Identity | Identidade, autenticacao, perfil |
| Organization | Organization | Tenant logico, configuracoes da org |
| OrganizationMember | Organization | Associacao User-Organization com role |
| SocialAccount | SocialAccount | Conexao OAuth2, tokens, status |
| Campaign | Campaign | Organizacao de conteudo |
| Content | Campaign | Peca de conteudo com overrides por rede |
| AIGeneration | ContentAI | Registro de geracao de IA |
| ScheduledPost | Publishing | Agendamento e publicacao |
| ContentMetrics | Analytics | Metricas de conteudo |
| Comment | Engagement | Comentario capturado |
| AutomationRule | Engagement | Regra de automacao |
| Media | Media | Arquivo de midia |
| Subscription | Billing | Assinatura de plano da organizacao |
| Plan | Billing | Plano disponivel na plataforma |
| Invoice | Billing | Fatura de pagamento |
| PlatformAdmin | PlatformAdmin | Administrador da plataforma |
| SystemConfig | PlatformAdmin | Configuracao global do sistema |

### Value Objects (exemplos)

| Value Object | Bounded Context | Descricao |
|-------------|-----------------|-----------|
| Email | Identity | Email validado e normalizado |
| HashedPassword | Identity | Senha com hash bcrypt |
| SocialProvider | SocialAccount | Enum: instagram, tiktok, youtube |
| EncryptedToken | SocialAccount | Token criptografado com AES-256-GCM |
| OrganizationRole | Organization | Enum: owner, admin, member |
| CampaignName | Campaign | Nome validado (3-100 chars, unique por organization) |
| Hashtag | Campaign | Formato validado (#palavra) |
| NetworkOverride | Campaign | Customizacao de conteudo por rede |
| ToneOfVoice | ContentAI | Tom de voz para geracao de IA |
| PostStatus | Publishing | Enum com transicoes validas |
| PublishError | Publishing | Erro imutavel com classificacao |
| Sentiment | Engagement | Enum: positive, neutral, negative |
| MimeType | Media | Tipo de arquivo validado contra whitelist |
| FileSize | Media | Tamanho em bytes com validacao de limite |
| Dimensions | Media | Width x Height com validacao |
| BillingCycle | Billing | Enum: monthly, yearly |
| SubscriptionStatus | Billing | Enum com transicoes validas |
| PlanLimits | Billing | Limites do plano (JSON tipado) |
| Money | Billing | Valor monetario com moeda |
| PlatformRole | PlatformAdmin | Enum: super_admin, admin, support |

---

## Regras para Criacao de Novos Bounded Contexts

1. Identificar se o conceito pertence a um contexto existente antes de criar um novo
2. Definir a linguagem ubiqua do novo contexto (termos e significados)
3. Identificar Aggregate Roots, Entities e Value Objects
4. Definir Domain Events que o contexto emite e consome
5. Mapear relacoes com outros contextos (upstream/downstream)
6. Criar a estrutura de pastas nas 3 camadas (Domain, Application, Infrastructure)
7. Registrar a decisao em um ADR
8. Adicionar testes de arquitetura para validar regras de dependencia

---

## Anti-patterns

### Controller com logica de negocio

```php
// ERRADO — Logica de negocio no controller
class CampaignController
{
    public function store(Request $request)
    {
        $campaign = new CampaignModel();
        $campaign->name = $request->name;
        $campaign->status = 'draft';
        if ($request->starts_at < now()) { // regra de negocio
            throw new ValidationException('...');
        }
        $campaign->save();
    }
}

// CORRETO — Controller fino que delega para Use Case
class CampaignController
{
    public function store(CreateCampaignRequest $request, CreateCampaignUseCase $useCase)
    {
        $output = $useCase->execute(CreateCampaignInput::fromRequest($request));
        return new CampaignResource($output);
    }
}
```

### Eloquent Model como Domain Entity

```php
// ERRADO — Usar Eloquent Model como entity
class Campaign extends Model  // Eloquent Model NAO e Domain Entity
{
    public function publish() { /* regra de negocio */ }
}

// CORRETO — Domain Entity pura, Eloquent Model separado
// app/Domain/Campaign/Entities/Campaign.php (entity pura)
// app/Infrastructure/Persistence/Eloquent/Models/CampaignModel.php (infraestrutura)
```

### Acesso direto cross-context

```php
// ERRADO — Use Case de Publishing acessando repository de SocialAccount
class PublishPostUseCase
{
    public function __construct(
        private SocialAccountRepository $socialAccountRepo  // cross-context!
    ) {}
}

// CORRETO — Comunicacao via Application Service ou Domain Event
class PublishPostUseCase
{
    public function __construct(
        private SocialAccountQueryService $socialAccountQuery  // interface definida em Publishing
    ) {}
}
```

### Domain dependendo de Infrastructure

```php
// ERRADO — Entity importando Eloquent
namespace App\Domain\Campaign\Entities;
use Illuminate\Database\Eloquent\Model;  // VIOLACAO!

// CORRETO — Entity sem dependencias externas
namespace App\Domain\Campaign\Entities;
// apenas PHP puro e Domain\Shared
```

---

## Fora de Escopo deste Documento

- Estrutura detalhada de pastas → ver `folder-structure.md`
- Regras de negocio especificas de cada bounded context → ver skills em `06-domain/`
- Estrategia de testes de arquitetura → ver skills em `05-quality/`
- Detalhes de Domain Events e filas → ver ADR-007 e ADR-013
