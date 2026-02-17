# ADR-002: Laravel 12 como Framework

[← Voltar ao índice](00-index.md)

---

- **Status:** Accepted
- **Data:** 2026-02-15
- **Decisores:** Equipe de arquitetura

## Contexto

Precisamos de um framework PHP que ofereça produtividade, ecossistema maduro e
suporte de longo prazo para construir uma API RESTful robusta.

O projeto requer:
- Sistema de filas robusto (publicações assíncronas)
- ORM com suporte a PostgreSQL
- Sistema de autenticação extensível
- Scheduler para jobs periódicos
- Ecossistema de pacotes rico
- Suporte a PHP 8.4

## Decisão

Adotar **Laravel 12** como framework, utilizando-o como camada de infraestrutura
dentro da Clean Architecture.

### Como o Laravel se encaixa na Clean Architecture

```
Clean Architecture Layer    │  Laravel Component
────────────────────────────┼──────────────────────────
Domain                      │  Plain PHP (sem Laravel)
Application (Use Cases)     │  Plain PHP (sem Laravel)
Infrastructure              │  Controllers, Eloquent Models,
                            │  Repository Implementations,
                            │  Jobs, Events, Mail, Providers,
                            │  Middleware, Routes, Requests
```

### Componentes do Laravel utilizados

| Componente | Uso no projeto |
|-----------|----------------|
| **Eloquent ORM** | Implementação de repositories (camada de infra) |
| **Queue System** | Publicação assíncrona, sync de métricas, exports |
| **Task Scheduling** | Jobs periódicos (token refresh, metrics sync, cleanup) |
| **HTTP Foundation** | Controllers, Middleware, Form Requests |
| **Encryption** | Criptografia de tokens de redes sociais |
| **Hashing** | Hash de senhas (bcrypt) |
| **Mail** | Notificações por email |
| **Storage** | Abstração de file storage (S3, local) |
| **Cache** | Cache layer com Redis driver |
| **Sanctum/JWT** | Base para autenticação (substituído por JWT custom) |
| **Socialite** | Base para OAuth (estendido com adapters customizados) |

### Regras de uso

1. **Controllers** são finos: recebem request, chamam use case, retornam response
2. **Eloquent Models** ficam APENAS na camada de infraestrutura
3. **Domain Entities** são Plain PHP — não estendem Model do Eloquent
4. **Form Requests** fazem validação de input na borda (infrastructure)
5. **Facades** permitidas apenas na camada de infraestrutura
6. **Helpers** do Laravel (config, env, app) apenas na camada de infraestrutura

## Alternativas consideradas

### 1. Symfony
- **Prós:** Mais flexível, menor acoplamento, melhor para DDD puro
- **Contras:** Mais verboso, ecossistema menor, desenvolvimento mais lento
- **Por que descartado:** Laravel oferece produtividade significativamente maior para o MVP. O queue system e scheduler do Laravel são superiores para nosso caso de uso

### 2. Slim/Mezzio (Micro-framework)
- **Prós:** Leve, sem opinião, liberdade total de arquitetura
- **Contras:** Precisaria construir tudo do zero (queue, scheduler, ORM setup)
- **Por que descartado:** Reescreveríamos muita funcionalidade que o Laravel já oferece

### 3. API Platform (Symfony)
- **Prós:** API-first, OpenAPI automático, CRUD automático
- **Contras:** Muito opinativo, difícil de customizar fluxos complexos
- **Por que descartado:** O domínio é complexo demais para geração automática de CRUD

## Consequências

### Positivas
- Produtividade alta para MVP
- Queue system robusto e battle-tested para publicações assíncronas
- Ecossistema rico (Socialite, Horizon, Telescope)
- Comunidade grande e documentação excelente
- Laravel AI SDK nativo para integração com OpenAI
- Scheduler nativo para jobs periódicos

### Negativas
- Eloquent pode "contaminar" o domínio se não houver disciplina
- Tentação de usar "atalhos" do Laravel que violam Clean Architecture
- Dependência forte do framework na camada de infraestrutura
- Algumas features do Laravel (Service Container auto-injection) podem mascarar dependências

### Riscos
- Desenvolvedores podem importar classes do Laravel no domínio — mitigado por testes de arquitetura
- Eloquent Models podem ser usados como entidades de domínio — mitigado por convenção e code review
