# ADR-010: Pest 4 como Framework de Testes

[← Voltar ao índice](00-index.md)

---

- **Status:** Accepted
- **Data:** 2026-02-15
- **Decisores:** Equipe de arquitetura

## Contexto

Precisamos de um framework de testes que:
- Suporte testes unitários, de integração e feature (HTTP)
- Ofereça testes de arquitetura para validar regras de dependência (Clean Architecture)
- Seja expressivo e de fácil leitura
- Integre com Laravel
- Tenha boa performance de execução

## Decisão

Adotar **Pest 4** com o **Architecture Testing Plugin** como framework de testes
principal do projeto.

### Estrutura de testes

```
tests/
├── Unit/
│   ├── Domain/
│   │   ├── Identity/
│   │   │   ├── EmailTest.php           # Value Object
│   │   │   ├── UserTest.php            # Entity
│   │   │   └── HashedPasswordTest.php  # Value Object
│   │   ├── Campaign/
│   │   │   ├── CampaignTest.php
│   │   │   ├── CampaignNameTest.php
│   │   │   ├── ContentTest.php
│   │   │   └── HashtagTest.php
│   │   ├── Publishing/
│   │   │   ├── ScheduledPostTest.php
│   │   │   └── PostStatusTest.php
│   │   └── ...
│   └── Application/
│       ├── Campaign/
│       │   ├── CreateCampaignUseCaseTest.php
│       │   └── SchedulePostUseCaseTest.php
│       └── ...
├── Integration/
│   ├── Repositories/
│   │   ├── EloquentCampaignRepositoryTest.php
│   │   ├── EloquentContentRepositoryTest.php
│   │   └── ...
│   └── SocialMedia/
│       ├── InstagramPublisherTest.php
│       ├── TikTokPublisherTest.php
│       └── YouTubePublisherTest.php
├── Feature/
│   ├── Auth/
│   │   ├── RegisterTest.php
│   │   ├── LoginTest.php
│   │   └── TwoFactorTest.php
│   ├── Campaign/
│   │   ├── CreateCampaignTest.php
│   │   ├── ListCampaignsTest.php
│   │   └── ...
│   ├── Publishing/
│   │   ├── SchedulePostTest.php
│   │   └── PublishNowTest.php
│   └── ...
└── Architecture/
    └── ArchitectureTest.php
```

### Testes de arquitetura (Critical)

```php
// tests/Architecture/ArchitectureTest.php

arch('Domain não depende de Laravel')
    ->expect('App\\Domain')
    ->toUseNothing()
    ->ignoring([
        'DateTimeImmutable',
        'InvalidArgumentException',
        'DomainException',
        'JsonSerializable',
    ]);

arch('Domain não depende de Application')
    ->expect('App\\Domain')
    ->not->toBeUsedIn('App\\Application')
    ->reversed();

arch('Application não depende de Infrastructure')
    ->expect('App\\Application')
    ->not->toUse('App\\Infrastructure');

arch('Entidades de domínio são final')
    ->expect('App\\Domain\\*\\Entities')
    ->toBeFinal();

arch('Value Objects são readonly')
    ->expect('App\\Domain\\*\\ValueObjects')
    ->toBeReadonly();

arch('Use Cases implementam interface ou convenção')
    ->expect('App\\Application\\*\\UseCases')
    ->toHaveSuffix('UseCase');

arch('Controllers são finos')
    ->expect('App\\Infrastructure\\Http\\Controllers')
    ->toHaveSuffix('Controller')
    ->not->toUse('Illuminate\\Support\\Facades\\DB');

arch('Domain Events são readonly')
    ->expect('App\\Domain\\*\\Events')
    ->toBeReadonly();

arch('Repositories do domínio são interfaces')
    ->expect('App\\Domain\\*\\Repositories')
    ->toBeInterfaces();

arch('Nenhuma classe usa dd() ou dump()')
    ->expect('App')
    ->not->toUse(['dd', 'dump', 'var_dump', 'print_r']);

arch('Nenhuma classe usa env() diretamente')
    ->expect('App\\Domain')
    ->not->toUse('env');
```

### Convenções de testes

#### Unit tests (domínio puro)

```php
// tests/Unit/Domain/Campaign/CampaignNameTest.php
describe('CampaignName', function () {
    it('creates a valid campaign name', function () {
        $name = new CampaignName('Black Friday 2026');
        expect($name->value())->toBe('Black Friday 2026');
    });

    it('rejects name shorter than 3 characters', function () {
        expect(fn () => new CampaignName('AB'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('rejects name longer than 100 characters', function () {
        expect(fn () => new CampaignName(str_repeat('A', 101)))
            ->toThrow(InvalidArgumentException::class);
    });
});
```

#### Feature tests (HTTP endpoints)

```php
// tests/Feature/Campaign/CreateCampaignTest.php
describe('POST /api/v1/campaigns', function () {
    it('creates a campaign successfully', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/campaigns', [
            'name' => 'Black Friday 2026',
            'description' => 'Campanha de Black Friday',
            'starts_at' => '2026-11-20',
            'ends_at' => '2026-11-30',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'name', 'status']]);
    });

    it('rejects duplicate campaign name', function () {
        $user = User::factory()->create();
        Campaign::factory()->for($user)->create(['name' => 'Existing']);

        $response = $this->actingAs($user)->postJson('/api/v1/campaigns', [
            'name' => 'Existing',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');
    });

    it('requires authentication', function () {
        $this->postJson('/api/v1/campaigns', ['name' => 'Test'])
            ->assertStatus(401);
    });
});
```

### Métricas de cobertura

| Camada | Cobertura mínima | Tipo de teste |
|--------|-----------------|---------------|
| **Domain** | 90%+ | Unit (sem banco, sem HTTP, sem framework) |
| **Application** | 80%+ | Unit (com mocks de repositories e services) |
| **Infrastructure** | 70%+ | Integration (com banco real, mocks de APIs externas) |
| **HTTP (Controllers)** | 80%+ | Feature (requests HTTP completas) |
| **Architecture** | 100% | Architecture tests (Pest plugin) |

### Datasets para testes parametrizados

```php
dataset('invalid passwords', [
    'too short' => ['abc'],
    'no uppercase' => ['password123!'],
    'no number' => ['Password!!!'],
    'no special char' => ['Password123'],
]);

it('rejects invalid passwords', function (string $password) {
    expect(fn () => new HashedPassword($password))
        ->toThrow(InvalidArgumentException::class);
})->with('invalid passwords');
```

## Alternativas consideradas

### 1. PHPUnit puro
- **Prós:** Padrão da indústria, sem dependência extra
- **Contras:** Verbose, sem testes de arquitetura nativos, DSL menos expressiva
- **Por que descartado:** Pest roda sobre PHPUnit mas com sintaxe mais expressiva e o plugin de arquitetura é essencial

### 2. Codeception
- **Prós:** BDD-style, acceptance tests, API testing built-in
- **Contras:** Pesado, curva de aprendizado alta, menos popular no ecossistema Laravel
- **Por que descartado:** Pest é mais alinhado com o ecossistema Laravel e mais leve

## Consequências

### Positivas
- Testes de arquitetura previnem violações de Clean Architecture automaticamente
- Sintaxe expressiva facilita leitura e manutenção
- Integração nativa com Laravel (factory, actingAs, assertJson)
- Performance superior ao PHPUnit em suites grandes (parallel testing)
- Datasets permitem testes parametrizados limpos

### Negativas
- Desenvolvedores acostumados com PHPUnit precisam se adaptar à sintaxe
- Plugin de arquitetura pode ter falsos positivos em edge cases
- Dependência de um framework de testes específico

### Riscos
- Testes de arquitetura podem ficar desatualizados se namespaces mudarem — rodam em CI, falha é detectada imediatamente
