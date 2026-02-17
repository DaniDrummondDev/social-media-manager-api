# 04 — Requisitos Não-Funcionais

[← Voltar ao índice](00-index.md)

---

## 4.1 Performance

### RNF-001: Tempo de resposta da API
- Endpoints de leitura (GET): p95 < 200ms
- Endpoints de escrita (POST/PUT): p95 < 500ms
- Endpoints de geração IA: p95 < 10s (dependente do provedor)
- Upload de mídia: p95 < 30s para arquivos até 100MB

### RNF-002: Throughput
- A API MUST suportar no mínimo 1.000 requisições por segundo em condições normais
- Picos de até 5.000 req/s devem ser absorvidos com degradação graceful

### RNF-003: Latência de publicação
- Publicações agendadas MUST ser processadas com atraso máximo de 60 segundos após o horário agendado
- Publicações imediatas MUST entrar na fila em < 5 segundos

### RNF-004: Processamento de filas
- Workers de publicação MUST processar no mínimo 100 jobs por minuto
- Filas separadas por prioridade: high (publicação imediata), default (agendada), low (analytics sync)

---

## 4.2 Segurança

### RNF-010: Autenticação
- Tokens JWT assinados com RS256 (chaves assimétricas)
- Access token: 15 minutos de expiração
- Refresh token: 7 dias, opaque, armazenado com hash no banco
- Rotação obrigatória de refresh tokens
- Suporte a 2FA via TOTP (RFC 6238)

### RNF-011: Autorização
- Controle de acesso baseado em políticas (Policy-based)
- Cada recurso MUST verificar ownership (tenant isolation)
- Nenhum endpoint público além de auth (register, login, forgot-password)

### RNF-012: Criptografia
- Dados em trânsito: TLS 1.3 obrigatório
- Dados em repouso: tokens de redes sociais criptografados com AES-256-GCM
- Chaves de criptografia gerenciadas via variáveis de ambiente (nunca no código)
- Senhas: bcrypt com cost factor 12

### RNF-013: Proteção contra ataques
- Rate limiting por IP e por usuário (configurable)
- Proteção CSRF via SameSite cookies + token (para fluxos OAuth)
- Proteção contra SQL Injection via Eloquent ORM (prepared statements)
- Proteção contra XSS via sanitização de input
- Headers de segurança: X-Content-Type-Options, X-Frame-Options, Strict-Transport-Security, Content-Security-Policy
- Proteção contra mass assignment via $fillable explícito
- Request validation em todas as entradas

### RNF-014: Auditoria
- Log de auditoria para todas as ações sensíveis:
  - Login/logout
  - Alteração de senha/email
  - Conexão/desconexão de redes sociais
  - Criação/exclusão de campanhas
  - Ações de automação executadas
- Formato: user_id, action, resource_type, resource_id, ip, user_agent, timestamp, old_values, new_values
- Retenção: 1 ano mínimo

### RNF-015: Gestão de secrets
- Secrets MUST ser armazenados em variáveis de ambiente
- Secrets MUST NOT aparecer em logs, stack traces ou responses
- Rotação de secrets sem downtime
- APP_KEY do Laravel protegido e com rotação periódica

### RNF-016: Proteção de dados pessoais
- Dados de redes sociais (tokens) MUST ser criptografados
- Senhas MUST NOT ser armazenadas em texto plano
- Logs MUST NOT conter dados pessoais identificáveis (PII)
- Dados de sessão MUST ser isolados por tenant

---

## 4.3 Escalabilidade

### RNF-020: Escalabilidade horizontal
- A aplicação MUST ser stateless (sem sessões em memória)
- Sessões e cache via Redis
- File storage via object storage (S3-compatible)
- Múltiplos workers de fila podem rodar em paralelo

### RNF-021: Banco de dados
- PostgreSQL com connection pooling (PgBouncer)
- Índices otimizados para queries frequentes
- Particionamento de tabelas de analytics por período (quando volume justificar)
- pgvector para embeddings de conteúdo e busca semântica

### RNF-022: Cache
- Cache de configurações do usuário: TTL 1h
- Cache de dados de redes sociais (perfil, métricas): TTL conforme ciclo de sync
- Cache de respostas de API de leitura: TTL 5min (quando aplicável)
- Invalidação granular por recurso

---

## 4.4 Disponibilidade

### RNF-030: Uptime
- SLA alvo: 99.9% (máximo ~8.7h de downtime por ano)
- Publicações agendadas MUST ter mecanismo de recuperação em caso de downtime

### RNF-031: Graceful degradation
- Se a API de uma rede social estiver indisponível, as demais MUST continuar funcionando
- Se o serviço de IA estiver indisponível, todas as funcionalidades exceto geração de conteúdo MUST funcionar
- Se o Redis estiver indisponível, a aplicação SHOULD funcionar com degradação (sem cache, filas em banco)

### RNF-032: Health checks
- **Endpoint:** `GET /api/health`
- Verifica: database, redis, queue, storage
- Retorna status individual de cada dependência
- Não requer autenticação

---

## 4.5 Conformidade e LGPD

### RNF-040: Consentimento
- Termos de uso e política de privacidade aceitos no registro
- Consentimento explícito para acesso às redes sociais
- Registro de todos os consentimentos com timestamp

### RNF-041: Direito de acesso
- **Endpoint:** `GET /api/v1/profile/data-export`
- Exportação de todos os dados pessoais do usuário
- Formato: JSON
- Processamento assíncrono com link de download

### RNF-042: Direito de exclusão
- **Endpoint:** `DELETE /api/v1/profile`
- Exclusão de conta com todos os dados pessoais
- Revogação de tokens de todas as redes sociais
- Dados anonimizados (não excluídos) para analytics agregados
- Confirmação por email obrigatória
- Período de carência de 30 dias antes da exclusão definitiva

### RNF-043: Retenção de dados
- Dados de analytics: 2 anos
- Logs de auditoria: 1 ano
- Dados de conta excluída: 30 dias (período de carência)
- Mídias de conta excluída: excluídas imediatamente após período de carência
- Jobs de limpeza agendados para enforce de políticas de retenção

### RNF-044: Transferência de dados
- Dados MUST ser armazenados em território brasileiro (ou conforme definição do cliente)
- Transferências internacionais (APIs de redes sociais) são necessárias e documentadas na política de privacidade

---

## 4.6 Observabilidade

### RNF-050: Logging
- Log estruturado em JSON
- Níveis: DEBUG, INFO, WARNING, ERROR, CRITICAL
- Contexto obrigatório: request_id, user_id, action
- Centralização em ferramenta de log (ELK, Loki, ou similar)
- Retenção: 30 dias (logs operacionais), 1 ano (audit logs)

### RNF-051: Métricas
- Métricas de aplicação expostas (Prometheus format ou similar):
  - request_duration_seconds (histograma por endpoint)
  - request_total (counter por status code)
  - queue_jobs_processed_total
  - queue_jobs_failed_total
  - social_api_request_duration_seconds (por provider)
  - social_api_errors_total (por provider)
  - ai_tokens_used_total
  - active_users_total

### RNF-052: Alertas
- Alertas configurados para:
  - Error rate > 1% por 5 minutos
  - Latência p95 > 1s por 5 minutos
  - Fila de publicação com jobs atrasados > 5 minutos
  - Token de rede social expirado
  - Falha em 3+ publicações consecutivas
  - Uso de disco > 80%

### RNF-053: Tracing
- Distributed tracing com correlation ID propagado
- Trace de requests end-to-end (API → Queue → Social API)
- Spans para operações externas (database, redis, social APIs, OpenAI)

---

## 4.7 Testabilidade

### RNF-060: Cobertura de testes
- Cobertura mínima de 80% em código de domínio e aplicação
- 100% de cobertura em regras de negócio críticas (publicação, automação)
- Testes obrigatórios: unit, integration, architecture

### RNF-061: Tipos de testes (Pest 4)
- **Unit tests:** Entidades, Value Objects, Domain Services
- **Integration tests:** Repositories, External API adapters
- **Feature tests:** Endpoints da API (HTTP tests)
- **Architecture tests:** Validação de dependências entre camadas (Pest Architecture Plugin)

### RNF-062: Testes de arquitetura
- Camada de domínio NÃO deve depender de framework
- Camada de aplicação NÃO deve depender de infraestrutura diretamente
- Controllers NÃO devem conter lógica de negócio
- Todas as entidades devem ser final
- Use cases devem implementar uma interface ou seguir convenção de naming

---

## 4.8 Manutenibilidade

### RNF-070: Padrões de código
- PSR-12 como coding standard
- PHPStan nível 8 (máximo)
- PHP CS Fixer para formatação automática

### RNF-071: Documentação de API
- OpenAPI 3.1 spec gerada automaticamente
- Exemplos de request/response em todos os endpoints
- Changelog versionado

### RNF-072: Versionamento
- Semantic Versioning (SemVer) para releases
- API versionada via URL prefix (/api/v1/)
- Breaking changes apenas em major versions
