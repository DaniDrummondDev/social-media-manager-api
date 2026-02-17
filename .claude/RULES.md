# RULES.md — Regras Não Negociáveis

## Princípio Central

> **Segurança, confiabilidade, escalabilidade, compliance e auditabilidade desde o primeiro commit.**

Estas regras são **absolutas** e não podem ser ignoradas, adiadas ou simplificadas.

---

## 1. Segurança da Informação (Obrigatória)

### 1.1 LGPD (Lei Geral de Proteção de Dados)

* Minimização de dados: coletar apenas o necessário.
* Finalidade explícita: todo dado tem propósito declarado.
* Consentimento: quando aplicável, registrado e rastreável.
* Direitos do titular: acesso, correção, exclusão, portabilidade.
* Retenção: dados possuem período de vida definido. Após expiração, purge automático.
* Anonimização: dados exportados para analytics ou IA devem ser anonimizados quando possível.
* Incidentes: plano de resposta a violações de dados.

### 1.2 OWASP API Security Top 10

Todo endpoint deve considerar:

1. **Broken Object Level Authorization (BOLA)** — Validar ownership em toda operação.
2. **Broken Authentication** — JWT RS256, refresh rotation, 2FA para operações sensíveis.
3. **Broken Object Property Level Authorization** — Não expor campos internos, filtrar responses.
4. **Unrestricted Resource Consumption** — Rate limiting por IP, token e escopo.
5. **Broken Function Level Authorization** — Validar permissões por endpoint, não apenas autenticação.
6. **Unrestricted Access to Sensitive Business Flows** — Proteger fluxos críticos (publicação, exclusão de conta).
7. **Server Side Request Forgery (SSRF)** — Validar URLs de webhooks, não seguir redirects arbitrários.
8. **Security Misconfiguration** — Headers de segurança, CORS restritivo, debug desabilitado em produção.
9. **Improper Inventory Management** — Documentar todos os endpoints, desativar rotas não utilizadas.
10. **Unsafe Consumption of APIs** — Validar respostas de APIs externas (Instagram, TikTok, YouTube, OpenAI).

### 1.3 ISO 27001 (Baseline)

* Menor privilégio em todo acesso.
* Segregação de ambientes (dev, staging, prod).
* Secrets gerenciados por vault (nunca em código ou .env em produção).
* Log de eventos de segurança.
* Plano de resposta a incidentes.

---

## 2. Multi-Tenancy por Organization

* O tenant lógico é a **Organization** — todo dado de negócio pertence a um `organization_id`.
* Um **User** pode pertencer a **múltiplas Organizations** (N:N com roles: owner, admin, member).
* Toda query de negócio deve incluir escopo de `organization_id`.
* JWT carrega `organization_id` (org ativa) + `user_id` (quem autenticou).
* Troca de organização requer novo token.
* Não existe acesso cross-organization em nenhuma circunstância.
* Tokens de redes sociais são isolados e criptografados por organização.
* Embeddings (pgvector) são isolados por `organization_id`.
* Logs e métricas incluem `organization_id` + `user_id` para rastreabilidade.
* Jobs e eventos carregam `organization_id` + `user_id` no payload.
* Permissões são contextuais à organização (role do user naquela org).

---

## 3. Autenticação & Autorização

* Autenticação centralizada via JWT RS256.
* Access token de curta duração (15 min).
* Refresh token rotacionado a cada uso.
* 2FA (TOTP) disponível, obrigatório para exclusão de conta e alteração de email.
* Logout invalida cadeia completa de tokens.
* Blacklist de tokens via Redis com TTL.
* Rate limiting em endpoints de autenticação (5 tentativas/15 min).
* Auditoria de login: IP, user-agent, timestamp, sucesso/falha.

---

## 4. Auditoria & Logs

* Ações sensíveis são auditadas: login, conexão de contas sociais, publicações, exclusões.
* Logs contêm: quem, o quê, quando, contexto.
* Dados sensíveis **nunca** aparecem em logs em texto claro.
* Logs são estruturados em JSON.
* Logs centralizados com busca por `organization_id`, `user_id`, `correlation_id`.
* Logs de auditoria são **imutáveis** (append-only).

---

## 5. Criptografia

* Tokens de redes sociais (access_token, refresh_token) criptografados com **AES-256-GCM**.
* Chave de criptografia dedicada, separada da APP_KEY do Laravel.
* Rotação de chaves suportada (re-encrypt batch).
* Senhas com bcrypt (cost 12).
* Comunicação externa somente via HTTPS.

---

## 6. IA & Dados Sensíveis

* IA **nunca** toma decisões autônomas.
* Resultados de IA são sugestões que requerem confirmação do usuário.
* Dados pessoais são removidos/anonimizados antes de envio para APIs externas de IA.
* Embeddings isolados por `organization_id`.
* Toda interação com IA é auditada (input, output, modelo, tokens, custo).
* IA pode ser desabilitada sem impacto no core do sistema.
* Limites mensais de geração por organização.

---

## 7. Redes Sociais (Integrações Externas)

* OAuth 2.0 para conexão de contas (nunca armazenar senha de rede social).
* Tokens criptografados em repouso.
* Circuit breaker por provider (Instagram, TikTok, YouTube).
* Retry com exponential backoff para falhas transientes.
* Rate limiting respeitado por provider (nunca exceder limites da API externa).
* Respostas de APIs externas **sempre** validadas antes de processar.
* Webhook signatures verificadas (HMAC-SHA256).
* Adapter Pattern: domínio não conhece detalhes de implementação dos providers.

---

## 8. Publicação & Agendamento

* Publicações são processadas via filas assíncronas.
* Jobs de publicação são **idempotentes**.
* Cada tentativa de publicação é registrada (attempts, errors).
* Máximo de 3 tentativas automáticas.
* Falha permanente → status `failed` + notificação ao usuário.
* Agendamento com lock (< 1 min para publicação = não cancelável).
* Conteúdo deve ter mídia compatível com a rede de destino.

---

## 9. Domínio & Regras de Negócio

### Campanhas & Conteúdo
* Campanha possui ciclo de vida: draft → active → paused → completed → archived.
* Conteúdo pertence a uma campanha.
* Conteúdo pode ter overrides por rede social.
* Exclusão de campanha é soft delete com período de carência (30 dias).

### Mídia
* Upload validado por formato e tamanho (imagem ≤ 10MB, vídeo ≤ 500MB).
* Scan de segurança antes de uso (scan_status: pending → clean/rejected).
* Compatibilidade calculada por tipo, dimensões e duração vs limites de cada rede.
* Mídia vinculada a conteúdo agendado não pode ser excluída.

### Analytics
* Métricas sincronizadas periodicamente das redes sociais.
* Dados históricos particionados por mês.
* Relatórios exportados assincronamente.

### Engajamento
* Comentários capturados com análise de sentimento.
* Automação respeita blacklist de palavras.
* Automação tem limites diários.
* Delay obrigatório em respostas automáticas (30-3600 segundos).

---

## 10. Qualidade de Código

* Clean Code, SOLID, DDD.
* Testes obrigatórios para todo código (Pest 4).
* Code review obrigatório para merge.
* Débito técnico documentado como ADR ou issue.
* Domain Layer com cobertura mínima de 95%.

---

## 11. Observabilidade

* Logs estruturados em JSON.
* Health checks: liveness, readiness, startup.
* SLIs/SLOs definidos para endpoints críticos.
* Alertas acionáveis (não apenas informativos).
* Métricas de aplicação, negócio e infraestrutura.
* Dados sensíveis **nunca** em logs ou métricas.

---

## 12. CI/CD & Deploy

* Pipeline automatizado.
* Testes obrigatórios: unitários, integração, arquitetura, contrato.
* Aprovação manual para produção.
* Migrations automáticas e reversíveis.
* Segregação de ambientes.
* Secrets via gerenciador dedicado.
* Rollback testado.

---

## 13. Documentação

* Skills documentam arquitetura e regras.
* ADRs para decisões arquiteturais.
* API specification como contrato formal.
* PRD como fonte de requisitos.

---

## Regra Final

> **Segurança > Velocidade**
> **Isolamento > Simplicidade**
> **Auditabilidade > Conveniência**
> **Contrato > Implementação**
