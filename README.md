# Social Media Manager API

API SaaS para agendamento, publicação e gestão de conteúdo em múltiplas redes sociais (Instagram, TikTok, YouTube), com geração de conteúdo por IA, analytics cross-network, automação de engajamento e integração com CRMs.

## Público-alvo

- **Criadores de conteúdo e marcas pessoais** que precisam de agendamento e IA para escalar sua produção
- **Agências de social media** que gerenciam múltiplos clientes e precisam de relatórios, automações e colaboração em equipe
- **E-commerces** com presença multi-canal que precisam de publicação coordenada e analytics unificados
- **Empresas** que exigem compliance (LGPD), audit trail e controle granular de permissões

## Stack

| Camada | Tecnologia |
|--------|-----------|
| Linguagem | PHP 8.4 |
| Framework | Laravel 12 |
| Banco de dados | PostgreSQL 17 + pgvector |
| Cache / Filas | Redis 7 (4 databases isoladas) |
| IA | Laravel Prism (OpenAI, Anthropic) |
| Testes | Pest 3 + Architecture Plugin |
| Análise estática | PHPStan Level 6 |
| Code style | Laravel Pint |
| Arquitetura | DDD, Clean Architecture, SOLID |
| Autenticação | JWT RS256 + 2FA (TOTP) |
| Pagamentos | Stripe (Checkout, Webhooks, Customer Portal) |
| Storage | MinIO (S3-compatible) |

## Arquitetura

```
app/
├── Domain/          # Entidades, Value Objects, regras de negócio (zero dependências externas)
├── Application/     # Use Cases, DTOs, Listeners (orquestração)
└── Infrastructure/  # Eloquent, Controllers, Jobs, APIs externas (implementação)
```

**11 Bounded Contexts:** Identity, Organization, Social Account, Campaign, Content AI, Publishing, Analytics, Engagement, Media, Billing, Platform Admin.

**Multi-tenancy** por `organization_id` — mesmo banco, isolamento lógico por organização.

## Serviços

| Serviço | Porta | Função |
|---------|-------|--------|
| API (Nginx) | 8080 | Aplicação principal |
| PostgreSQL | 5432 | Banco de dados |
| PgBouncer | 6432 | Connection pooling |
| Redis | 6379 | Cache (DB0), Filas (DB1), Rate-limiting (DB2), Sessions (DB3) |
| Horizon | — | 7 filas, 15 workers (52+ jobs) |
| Scheduler | — | 13+ tarefas agendadas |
| MinIO | 9000 / 9001 | Object storage (API / Console) |
| Mailpit | 8025 / 1025 | Email testing (UI / SMTP) |

## Instalação

### Pré-requisitos

- Docker e Docker Compose
- Git

### Setup

```bash
git clone git@github.com:DaniDrummondDev/social-media-manager-api.git
cd social-media-manager-api
./setup.sh
```

O script automatiza:

1. Copia `.env.example` para `.env`
2. Faz build dos containers
3. Sobe todos os serviços
4. Aguarda health checks (PostgreSQL, Redis, MinIO)
5. Instala dependências PHP (`composer install`)
6. Gera APP_KEY e chaves JWT (RS256)
7. Executa migrations e seeds
8. Cria bucket no MinIO
9. Instala Horizon
10. Roda testes de arquitetura
11. Verifica health check da API

### Após o setup

| URL | Descrição |
|-----|-----------|
| http://localhost:8080 | API |
| http://localhost:8080/api/v1/health | Health check |
| http://localhost:8025 | Mailpit (emails) |
| http://localhost:9001 | MinIO Console |

### Comandos úteis

```bash
# Logs em tempo real
docker compose logs -f

# Console interativo
docker compose exec app php artisan tinker

# Rodar testes
docker compose exec app php artisan test

# Testes de arquitetura
docker compose exec app php artisan test --filter=Architecture

# Análise estática
docker compose exec app vendor/bin/phpstan analyse

# Code style
docker compose exec app vendor/bin/pint
```

## Testes

- **Unit** — Regras de domínio e use cases
- **Feature** — Endpoints da API (request → response)
- **Integration** — Repositórios, jobs, APIs externas
- **Architecture** — Regras de camada (Domain ≠ Infrastructure, etc.)

```bash
docker compose exec app php artisan test
```

## Licença

Este software é propriedade privada. Consulte o arquivo [LICENSE](LICENSE) para detalhes.
