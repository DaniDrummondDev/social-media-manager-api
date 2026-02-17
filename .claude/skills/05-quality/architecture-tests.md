# Architecture Tests — Social Media Manager API

## Objetivo

Definir os testes de arquitetura que validam automaticamente as regras de camadas, dependências e convenções do projeto usando **Pest Architecture Plugin**.

---

## Por que Testes de Arquitetura

- Previnem erosão arquitetural ao longo do tempo.
- Detectam violações de camadas automaticamente no CI.
- Documentam regras arquiteturais de forma executável.
- Falha = bloqueio de merge (non-negotiable).

---

## Regras de Camadas

### Domain Layer — Sem Dependências Externas

```php
arch('Domain layer should not depend on Application')
    ->expect('App\Domain')
    ->toBeIndependentOf('App\Application');

arch('Domain layer should not depend on Infrastructure')
    ->expect('App\Domain')
    ->toBeIndependentOf('App\Infrastructure');

arch('Domain layer should not use Laravel')
    ->expect('App\Domain')
    ->toBeIndependentOf('Illuminate');

arch('Domain layer should not use Eloquent')
    ->expect('App\Domain')
    ->toBeIndependentOf('Illuminate\Database\Eloquent');
```

### Application Layer — Depende Apenas do Domain

```php
arch('Application layer should not depend on Infrastructure')
    ->expect('App\Application')
    ->toBeIndependentOf('App\Infrastructure');

arch('Application layer should not use Eloquent')
    ->expect('App\Application')
    ->toBeIndependentOf('Illuminate\Database\Eloquent');

arch('Application layer should not use HTTP classes')
    ->expect('App\Application')
    ->toBeIndependentOf('Illuminate\Http');
```

### Infrastructure Layer — Pode Depender de Tudo

```php
arch('Infrastructure can depend on Domain')
    ->expect('App\Infrastructure')
    ->toUse('App\Domain');

arch('Infrastructure can depend on Application')
    ->expect('App\Infrastructure')
    ->toUse('App\Application');
```

---

## Regras de Bounded Context

### Isolamento entre Contexts

```php
arch('Campaign context should not directly access Publishing entities')
    ->expect('App\Domain\Campaign')
    ->toBeIndependentOf('App\Domain\Publishing');

arch('Publishing context should not directly access Campaign repository')
    ->expect('App\Application\Publishing')
    ->toBeIndependentOf('App\Domain\Campaign\Repositories');

arch('Analytics context should not access Engagement entities')
    ->expect('App\Domain\Analytics')
    ->toBeIndependentOf('App\Domain\Engagement');
```

### Shared Kernel

```php
arch('All contexts can use Shared')
    ->expect('App\Domain')
    ->toUse('App\Domain\Shared');
```

---

## Convenções de Nomenclatura

### Entities

```php
arch('Entities should be in correct namespace')
    ->expect('App\Domain\*\Entities')
    ->toBeClasses();
```

### Value Objects

```php
arch('Value Objects should be readonly')
    ->expect('App\Domain\*\ValueObjects')
    ->toBeReadonly();
```

### Use Cases

```php
arch('Use Cases should have execute method')
    ->expect('App\Application\*\UseCases')
    ->toHaveMethod('execute');

arch('Use Cases should be final')
    ->expect('App\Application\*\UseCases')
    ->toBeFinal();
```

### Controllers

```php
arch('Controllers should be in Infrastructure layer')
    ->expect('App\Infrastructure\*\Controllers')
    ->toBeClasses();

arch('Controllers should not contain business logic keywords')
    ->expect('App\Infrastructure\*\Controllers')
    ->not->toUse('App\Domain\*\Repositories');
```

### Jobs

```php
arch('Jobs should be in Infrastructure layer')
    ->expect('App\Infrastructure\*\Jobs')
    ->toImplement('Illuminate\Contracts\Queue\ShouldQueue');
```

### DTOs

```php
arch('DTOs should be readonly')
    ->expect('App\Application\*\DTOs')
    ->toBeReadonly();
```

### Repository Interfaces

```php
arch('Repository interfaces should be in Domain layer')
    ->expect('App\Domain\*\Repositories')
    ->toBeInterfaces();
```

### Repository Implementations

```php
arch('Repository implementations should be in Infrastructure')
    ->expect('App\Infrastructure\*\Repositories')
    ->toImplement('App\Domain\*\Repositories\*RepositoryInterface');
```

---

## Regras de Segurança

```php
arch('No class should use DB facade directly')
    ->expect('App')
    ->not->toUse('Illuminate\Support\Facades\DB')
    ->ignoring('App\Infrastructure');

arch('Domain should not use env() function')
    ->expect('App\Domain')
    ->not->toUse('env');

arch('Application should not use env() function')
    ->expect('App\Application')
    ->not->toUse('env');
```

---

## Regras Específicas do Projeto

```php
arch('Encryption service should only be in Infrastructure\Shared')
    ->expect('App\Infrastructure\Shared\Encryption')
    ->toBeClasses();

arch('External adapters should implement domain contracts')
    ->expect('App\Infrastructure\External\Instagram')
    ->toImplement('App\Domain\SocialAccount\Contracts');

arch('Models should have Model suffix')
    ->expect('App\Infrastructure\*\Models')
    ->toHaveSuffix('Model');

arch('Events should have past tense naming')
    ->expect('App\Domain\*\Events')
    ->toBeClasses();
```

---

## Execução

```bash
# Rodar todos os testes de arquitetura
php artisan test tests/Architecture/

# Rodar com Pest
./vendor/bin/pest tests/Architecture/
```

---

## Quando Adicionar Novas Regras

- Ao criar novo Bounded Context.
- Ao identificar violação recorrente.
- Ao adicionar nova convenção ao projeto.
- Ao definir novo ADR que impacta estrutura.

---

## Anti-Patterns

- Desabilitar testes de arquitetura para "destravar" CI.
- Exceptions demais nas regras (perde o propósito).
- Regras que não refletem decisões arquiteturais reais.
- Testes de arquitetura sem manutenção (ficam desatualizados).
