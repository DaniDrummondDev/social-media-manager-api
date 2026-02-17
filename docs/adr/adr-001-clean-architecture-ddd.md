# ADR-001: Clean Architecture com Domain-Driven Design

[← Voltar ao índice](00-index.md)

---

- **Status:** Accepted
- **Data:** 2026-02-15
- **Decisores:** Equipe de arquitetura

## Contexto

O Social Media Manager é um SaaS com domínio complexo que envolve múltiplas integrações
externas (redes sociais, IA, CRMs), regras de negócio elaboradas (agendamento, automação,
publicação) e necessidade de evolução contínua (novas redes, novos recursos).

Precisamos de uma arquitetura que:
- Isole regras de negócio de detalhes de infraestrutura (APIs externas, banco, framework)
- Permita testar o domínio de forma isolada (sem banco, sem HTTP, sem APIs externas)
- Facilite a adição de novas redes sociais sem impactar regras existentes
- Seja compreensível para novos desenvolvedores
- Resista à deterioração ao longo do tempo

## Decisão

Adotar **Clean Architecture** combinada com **Domain-Driven Design (DDD)** como
fundamento arquitetural do projeto.

### Camadas (de dentro para fora)

```
┌─────────────────────────────────────────────┐
│              Infrastructure                  │
│  (Controllers, Repositories Impl, APIs,     │
│   Eloquent Models, Jobs, Providers)         │
│                                             │
│  ┌─────────────────────────────────────┐    │
│  │           Application               │    │
│  │  (Use Cases, DTOs, Interfaces,      │    │
│  │   Application Services)             │    │
│  │                                     │    │
│  │  ┌─────────────────────────────┐    │    │
│  │  │         Domain              │    │    │
│  │  │  (Entities, Value Objects,  │    │    │
│  │  │   Domain Services,         │    │    │
│  │  │   Domain Events,           │    │    │
│  │  │   Repository Interfaces)   │    │    │
│  │  └─────────────────────────────┘    │    │
│  └─────────────────────────────────────┘    │
└─────────────────────────────────────────────┘
```

### Regra de Dependência

As dependências SEMPRE apontam para dentro:
- **Domain** não conhece Application nem Infrastructure
- **Application** conhece Domain, mas não conhece Infrastructure
- **Infrastructure** conhece Application e Domain

### DDD Tático

- **Entities:** Objetos com identidade (User, Campaign, Content, ScheduledPost)
- **Value Objects:** Objetos imutáveis sem identidade (Email, Hashtag, EncryptedToken)
- **Aggregates:** Clusters de entidades com raiz (Campaign → Contents)
- **Domain Events:** Fatos que ocorreram no domínio (PostPublished, CommentCaptured)
- **Domain Services:** Lógica que não pertence a uma entidade específica
- **Repository Interfaces:** Contratos definidos no domínio, implementados na infraestrutura

### DDD Estratégico

8 Bounded Contexts identificados (ver PRD 05-bounded-contexts.md), cada um com
seu próprio modelo de domínio e linguagem ubíqua.

## Alternativas consideradas

### 1. MVC tradicional do Laravel
- **Prós:** Mais simples, curva de aprendizado menor, mais documentação
- **Contras:** Regras de negócio acopladas ao framework, difícil de testar, tendência a fat controllers e fat models
- **Por que descartada:** O domínio é complexo demais para MVC puro; a tendência de deterioração é alta

### 2. Hexagonal Architecture (Ports & Adapters)
- **Prós:** Boa separação de concerns, testável
- **Contras:** Terminologia diferente, menos material em português
- **Por que descartada:** Clean Architecture é essencialmente uma evolução de Hexagonal; os conceitos são compatíveis. Optamos pela nomenclatura Clean Architecture por ser mais difundida na comunidade PHP/Laravel

### 3. CQRS completo
- **Prós:** Separação total de leitura e escrita, otimização individual
- **Contras:** Complexidade significativamente maior, eventual consistency
- **Por que descartada:** Overhead excessivo para o MVP. Pode ser adotado em módulos específicos (Analytics) no futuro

## Consequências

### Positivas
- Domínio testável sem dependências externas
- Adicionar nova rede social = criar novo Adapter (sem tocar no domínio)
- Framework pode ser substituído sem impactar regras de negócio
- Código autoexplicativo com linguagem ubíqua
- Testes de arquitetura (Pest) garantem que as regras de dependência sejam respeitadas

### Negativas
- Mais código boilerplate (interfaces, DTOs, mappers)
- Curva de aprendizado para desenvolvedores acostumados com MVC Laravel
- Mapeamento entre Eloquent Models e Domain Entities adiciona complexidade
- Desenvolvimento inicial mais lento

### Riscos
- Desenvolvedores podem "furar" as camadas por conveniência — mitigado por testes de arquitetura
- Over-engineering em módulos simples — mitigado pela regra: módulos CRUD simples podem ser simplificados
