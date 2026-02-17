# Billing & Subscription — Social Media Manager API

## Objetivo

Definir as regras de domínio para **planos**, **assinaturas**, **limites de uso** e **integração com payment gateway**, garantindo que o SaaS tenha controle de cobrança e enforce de limites por plano.

---

## Conceitos

### Plan (Entity)

Representa um plano disponível na plataforma (Free, Pro, Enterprise). Define limites e features disponíveis.

### Subscription (Aggregate Root)

Assinatura ativa de uma organização a um plano. Controla período, status e renovação.

### UsageRecord (Entity)

Registro de consumo de recursos limitados (gerações de IA, publicações, storage). Base para enforce de limites e para billing por uso (futuro).

### Invoice (Entity)

Registro de fatura gerada pelo payment gateway, associada a uma subscription.

---

## Plan

### Campos

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | UUID | Identificador |
| `name` | string | Nome do plano (Free, Pro, Enterprise) |
| `slug` | string | Identificador único (free, pro, enterprise) |
| `description` | text | Descrição do plano |
| `price_monthly_cents` | integer | Preço mensal em centavos (0 para free) |
| `price_yearly_cents` | integer | Preço anual em centavos (0 para free) |
| `currency` | string | Moeda (BRL, USD) |
| `limits` | JSON | Limites do plano (ver tabela abaixo) |
| `features` | JSON | Features habilitadas |
| `is_active` | boolean | Se o plano está disponível para novas assinaturas |
| `sort_order` | integer | Ordem de exibição |
| `created_at` | datetime | Timestamp |

### Limites por Plano

| Recurso | Free | Pro | Enterprise |
|---------|------|-----|------------|
| Membros por org | 1 | 5 | Ilimitado |
| Contas sociais | 3 | 10 | 50 |
| Publicações/mês | 30 | 300 | Ilimitado |
| Gerações IA/mês | 50 | 500 | 5000 |
| Storage (GB) | 1 | 10 | 100 |
| Campanhas ativas | 3 | 20 | Ilimitado |
| Automações | 0 | 10 | 100 |
| Webhooks | 0 | 3 | 20 |
| Relatórios/mês | 5 | 50 | Ilimitado |
| Retenção analytics | 30 dias | 6 meses | 2 anos |

### Regras

- **RN-BIL-01**: Planos são gerenciados exclusivamente por Platform Admins.
- **RN-BIL-02**: Planos inativos (`is_active = false`) não aceitam novas assinaturas mas mantêm assinaturas existentes.
- **RN-BIL-03**: Alteração de limites de um plano afeta todas as orgs assinantes na próxima verificação de uso.

---

## Subscription

### Campos

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | UUID | Identificador |
| `organization_id` | UUID | Organização (tenant) |
| `plan_id` | UUID | Plano assinado |
| `status` | enum | active, trialing, past_due, canceled, expired |
| `billing_cycle` | enum | monthly, yearly |
| `current_period_start` | datetime | Início do período atual |
| `current_period_end` | datetime | Fim do período atual |
| `trial_ends_at` | datetime | Fim do trial (null se não aplicável) |
| `canceled_at` | datetime | Quando foi cancelada |
| `cancel_at_period_end` | boolean | Se cancela no fim do período atual |
| `external_subscription_id` | string | ID na gateway de pagamento (Stripe) |
| `external_customer_id` | string | ID do customer na gateway |
| `created_at` | datetime | Timestamp |

### Ciclo de Vida

```
trialing → active → canceled (ao fim do período)
                  → past_due → active (pagamento regularizado)
                             → expired (não regularizado em 7 dias)
active → canceled (cancel_at_period_end = true, acesso até fim do período)
```

### Transições Válidas

| De | Para | Trigger |
|----|------|---------|
| `trialing` | `active` | Trial expirou + pagamento confirmado |
| `trialing` | `canceled` | Usuário cancela durante trial |
| `active` | `past_due` | Pagamento falhou |
| `active` | `canceled` | Usuário solicita cancelamento |
| `past_due` | `active` | Pagamento regularizado |
| `past_due` | `expired` | 7 dias sem regularizar |
| `canceled` | `active` | Reativação antes do fim do período |

### Regras de Negócio

- **RN-BIL-04**: Toda organização deve ter exatamente 1 subscription ativa.
- **RN-BIL-05**: Ao criar organização, subscription é criada automaticamente no plano Free.
- **RN-BIL-06**: Upgrade de plano é imediato com cobrança pro-rata.
- **RN-BIL-07**: Downgrade de plano efetivo apenas no fim do período corrente.
- **RN-BIL-08**: Trial de 14 dias para novos planos pagos (configurável por plano).
- **RN-BIL-09**: Cancelamento mantém acesso até o fim do período pago.
- **RN-BIL-10**: Subscription `expired` rebaixa organização para plano Free automaticamente.

---

## Controle de Uso (Usage Enforcement)

### Fluxo

```
Request do usuário → Middleware verifica limite
                           ↓
              Limite não atingido → prossegue normalmente
              Limite atingido → HTTP 402 com PLAN_LIMIT_REACHED
```

### UsageRecord

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | UUID | Identificador |
| `organization_id` | UUID | Organização |
| `resource_type` | enum | publications, ai_generations, storage_bytes, members, social_accounts, campaigns, automations, webhooks, reports |
| `quantity` | integer | Quantidade consumida |
| `period_start` | date | Início do período de contagem |
| `period_end` | date | Fim do período de contagem |
| `recorded_at` | datetime | Timestamp do registro |

### Regras de Enforcement

- **RN-BIL-11**: Limites são verificados antes de executar a ação (fail-fast).
- **RN-BIL-12**: Verificação de uso utiliza cache Redis (TTL 60s) para performance.
- **RN-BIL-13**: Storage é contabilizado em tempo real (incremento/decremento no upload/delete).
- **RN-BIL-14**: Contadores mensais (publicações, gerações IA, relatórios) resetam no início do período.
- **RN-BIL-15**: Limites absolutos (membros, contas sociais, campanhas) são verificados contra contagem atual.
- **RN-BIL-16**: Ao fazer downgrade, recursos existentes acima do novo limite não são excluídos, mas novas criações são bloqueadas.

### Endpoint de Uso

`GET /api/v1/billing/usage` retorna:

```json
{
  "plan": "pro",
  "billing_cycle": "monthly",
  "current_period_end": "2026-03-15T00:00:00Z",
  "usage": {
    "publications": { "used": 87, "limit": 300, "percentage": 29 },
    "ai_generations": { "used": 234, "limit": 500, "percentage": 47 },
    "storage_bytes": { "used": 2147483648, "limit": 10737418240, "percentage": 20 },
    "social_accounts": { "used": 4, "limit": 10, "percentage": 40 },
    "members": { "used": 3, "limit": 5, "percentage": 60 }
  }
}
```

---

## Integração com Payment Gateway

### Provider: Stripe

- **RN-BIL-17**: Stripe é o payment gateway principal.
- **RN-BIL-18**: Webhooks do Stripe processados via endpoint dedicado (`POST /api/v1/webhooks/stripe`).
- **RN-BIL-19**: Webhook signature validada com HMAC (Stripe-Signature header).
- **RN-BIL-20**: Processamento de webhook é idempotente (Stripe event ID como chave).

### Eventos do Stripe Processados

| Stripe Event | Ação |
|-------------|------|
| `customer.subscription.created` | Criar/atualizar Subscription |
| `customer.subscription.updated` | Atualizar status/plano |
| `customer.subscription.deleted` | Marcar como canceled |
| `invoice.paid` | Registrar Invoice, confirmar período |
| `invoice.payment_failed` | Marcar subscription como past_due |
| `customer.subscription.trial_will_end` | Notificar organização (3 dias antes) |

### Invoice

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | UUID | Identificador |
| `organization_id` | UUID | Organização |
| `subscription_id` | UUID | Subscription |
| `external_invoice_id` | string | ID da fatura no Stripe |
| `amount_cents` | integer | Valor em centavos |
| `currency` | string | Moeda |
| `status` | enum | paid, open, void, uncollectible |
| `invoice_url` | string | URL do PDF da fatura no Stripe |
| `period_start` | datetime | Início do período faturado |
| `period_end` | datetime | Fim do período faturado |
| `paid_at` | datetime | Quando foi pago |
| `created_at` | datetime | Timestamp |

---

## Checkout e Portal

### Checkout

- **RN-BIL-21**: Upgrade inicia Stripe Checkout Session.
- **RN-BIL-22**: Após pagamento, Stripe webhook confirma e atualiza subscription.
- **RN-BIL-23**: Se checkout é abandonado, nenhuma alteração ocorre (stateless).

### Customer Portal

- **RN-BIL-24**: Organização acessa Stripe Customer Portal para gerenciar método de pagamento e ver faturas.
- **RN-BIL-25**: Portal é acessível apenas pelo owner da organização.

### Endpoints

| Endpoint | Método | Descrição |
|----------|--------|-----------|
| `GET /api/v1/billing/subscription` | GET | Subscription atual da org |
| `GET /api/v1/billing/usage` | GET | Uso atual vs limites |
| `GET /api/v1/billing/invoices` | GET | Histórico de faturas |
| `POST /api/v1/billing/checkout` | POST | Criar Checkout Session (upgrade) |
| `POST /api/v1/billing/portal` | POST | Criar Customer Portal session |
| `GET /api/v1/plans` | GET | Listar planos disponíveis (público) |

---

## Domain Events

| Evento | Quando | Dados |
|--------|--------|-------|
| `SubscriptionCreated` | Nova assinatura | subscription_id, organization_id, plan_id |
| `SubscriptionUpgraded` | Upgrade de plano | subscription_id, old_plan_id, new_plan_id |
| `SubscriptionDowngraded` | Downgrade solicitado | subscription_id, old_plan_id, new_plan_id, effective_at |
| `SubscriptionCanceled` | Cancelamento solicitado | subscription_id, cancel_at_period_end |
| `SubscriptionExpired` | Subscription expirou | subscription_id, organization_id |
| `SubscriptionReactivated` | Reativação | subscription_id |
| `PaymentFailed` | Pagamento falhou | subscription_id, organization_id |
| `PaymentSucceeded` | Pagamento confirmado | subscription_id, invoice_id, amount |
| `PlanLimitReached` | Limite de uso atingido | organization_id, resource_type, limit |
| `TrialEnding` | Trial acabando (3d) | subscription_id, trial_ends_at |

---

## Tratamento de Falhas

- **Webhook indisponível**: Stripe retenta automaticamente (até 3 dias).
- **Pagamento falhou**: subscription → `past_due`, notificar owner, 7 dias de carência.
- **Stripe indisponível**: cache local de subscription status (graceful degradation).
- **Webhook duplicado**: idempotência via Stripe event ID (processar apenas 1x).

---

## Anti-Patterns

- Verificar limites apenas no frontend (backend é a fonte de verdade).
- Bloquear acesso imediatamente ao cancelar (manter até fim do período).
- Excluir dados ao fazer downgrade (apenas bloquear novas criações).
- Lógica de billing dentro de Use Cases de outros bounded contexts (usar middleware/service).
- Armazenar dados de cartão de crédito (Stripe gerencia PCI compliance).
- Checkout síncrono que bloqueia o request (Stripe Checkout Session é redirect-based).
- Ignorar webhook signature validation (segurança obrigatória).
- Cache de limites sem TTL (dados desatualizados).

---

## Dependências

- `06-domain/ai-content-generation.md` (limites de gerações IA por plano)
- `06-domain/media-management.md` (limites de storage por plano)
- `06-domain/publishing-scheduling.md` (limites de publicações por plano)
- `06-domain/engagement-automation.md` (limites de automações e webhooks por plano)
- `06-domain/analytics-reporting.md` (limites de relatórios e retenção por plano)
- `06-domain/platform-administration.md` (gerenciamento de planos)
