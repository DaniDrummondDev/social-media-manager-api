# Testing Strategy — Social Media Manager API

## Objetivo

Definir a estratégia oficial de testes do projeto, incluindo tipos de testes, ferramentas, convenções e cobertura mínima.

> Referência: ADR-010 (Pest Testing Strategy)

---

## Princípio Central

> Testes usam **banco de dados real** (SQLite) para evitar falsos positivos de mocking excessivo.

---

## Princípios Não Negociáveis

- Testes usam **SQLite** como banco de teste (migrations reais executadas).
- Testes são **determinísticos** — mesmo input = mesmo output, sempre.
- **Sem estado compartilhado** entre testes (RefreshDatabase trait).
- Falha em testes **bloqueia** merge/deploy.
- Todo código novo **deve ter testes** correspondentes.

---

## Framework e Ferramentas

- **Pest 4**: framework de testes principal.
- **Pest Architecture Plugin**: testes de regras de camadas.
- **Laravel Test Helpers**: factories, HTTP testing, queue faking.
- **SQLite**: banco de dados para testes.

---

## Pirâmide de Testes

```
        ╱╲
       ╱ E2E ╲        (poucos, lentos, críticos)
      ╱────────╲
     ╱ Contract  ╲     (API contracts, versioned)
    ╱──────────────╲
   ╱  Integration    ╲  (repos, adapters, jobs)
  ╱────────────────────╲
 ╱  Application Layer    ╲ (use cases com SQLite)
╱──────────────────────────╲
╱    Domain Layer Tests      ╲ (entities, VOs, regras)
╱──────────────────────────────╲
```

---

## Tipos de Testes

### 1. Domain Tests (`tests/Unit/Domain/`)

**O que testa**: Entities, Value Objects, Domain Services, regras de negócio.

**Características**:
- Rápidos (sem I/O).
- Sem dependências externas.
- Testam comportamento, não implementação.

```php
test('campaign cannot be scheduled if status is not draft', function () {
    $campaign = Campaign::create(name: 'Test', userId: $userId);
    $campaign->markAsActive();

    expect(fn() => $campaign->schedule())
        ->toThrow(InvalidCampaignStateException::class);
});

test('email value object rejects invalid format', function () {
    expect(fn() => Email::fromString('invalid'))
        ->toThrow(InvalidEmailException::class);
});
```

**Cobertura mínima**: **95%**

### 2. Application Layer Tests (`tests/Unit/Application/`)

**O que testa**: Use Cases, Application Services, orquestração.

**Características**:
- Usa SQLite (migrations reais).
- Repositories reais (não mockados).
- Mock apenas de serviços externos (APIs sociais, OpenAI).

```php
test('CreateCampaignUseCase creates campaign with correct status', function () {
    $useCase = app(CreateCampaignUseCase::class);

    $result = $useCase->execute(new CreateCampaignDTO(
        organizationId: $organization->id,
        userId: $user->id,
        name: 'Black Friday',
        description: 'Campanha de Black Friday',
    ));

    expect($result->status)->toBe('draft');
    $this->assertDatabaseHas('campaigns', ['name' => 'Black Friday', 'organization_id' => $organization->id]);
});
```

**Cobertura mínima**: **85%**

### 3. Integration Tests (`tests/Integration/`)

**O que testa**: Repositories, External Adapters, Jobs, Event Listeners.

**Características**:
- SQLite para repositories.
- Mock de APIs externas (HTTP fake).
- Testa integração real entre componentes internos.

```php
test('EloquentCampaignRepository filters by organization_id', function () {
    // Arrange: campaigns de 2 organizações
    Campaign::factory()->for($org1)->count(3)->create();
    Campaign::factory()->for($org2)->count(2)->create();

    // Act
    $campaigns = $repo->findByOrganizationId($org1->id);

    // Assert: retorna apenas da org1
    expect($campaigns)->toHaveCount(3);
});
```

### 4. Contract Tests (`tests/Feature/`)

**O que testa**: Endpoints da API, request/response format, auth, status codes.

**Características**:
- Testa o contrato da API (não a implementação interna).
- Verifica status codes, headers, formato de resposta.
- Testa autenticação e autorização.

```php
test('POST /api/v1/campaigns requires authentication', function () {
    $response = $this->postJson('/api/v1/campaigns', [
        'name' => 'Test Campaign',
    ]);

    $response->assertStatus(401);
});

test('GET /api/v1/campaigns returns only organization campaigns', function () {
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $org->addMember($user, 'admin');
    Campaign::factory()->for($org)->count(2)->create();
    Campaign::factory()->count(3)->create(); // outra organização

    $response = $this->actingAs($user, $org)->getJson('/api/v1/campaigns');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');
});
```

### 5. Architecture Tests (`tests/Architecture/`)

**O que testa**: Regras de camadas, dependências, convenções.

Ver skill `05-quality/architecture-tests.md` para detalhes.

---

## Convenções

### Nomenclatura

- Usar `test('descrição clara do comportamento', function () {...})`.
- Descrição em inglês, formato: `subject + action + expected result`.
- Evitar `it()` — preferir `test()` por clareza.

### Organização

- Espelhar estrutura do código em `tests/`.
- Um arquivo de teste por classe testada.
- Datasets para testar múltiplos cenários.

### Factories

- Usar Laravel Factories para criar dados de teste.
- Factories refletem estados válidos por padrão.
- States para cenários específicos (`->suspended()`, `->expired()`).

---

## O que Testar por Tipo de Mudança

| Mudança | Testes Obrigatórios |
|---------|-------------------|
| Nova Entity/VO | Domain tests (invariantes, validações) |
| Novo Use Case | Application test + Contract test |
| Novo Endpoint | Contract test (auth, validation, response format) |
| Novo Job | Integration test (idempotência, retry, fallback) |
| Novo Adapter | Integration test (mock de API externa) |
| Bug fix | Teste que reproduz o bug antes do fix |

---

## Anti-Patterns

- Testes sem banco de dados (in-memory sem SQLite).
- Mock de repositories por padrão (usar implementação real).
- Estado compartilhado entre testes.
- Testes que dependem de ordem de execução.
- Testes que passam por acidente (assert genérico).
- Cobertura sem qualidade (testar getters/setters).
- Testar implementação ao invés de comportamento.
