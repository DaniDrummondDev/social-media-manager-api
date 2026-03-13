# Campaign Brief — Design Spec

**Data:** 2026-03-11
**Status:** Aprovado
**Bounded Context:** Campaign Management + Content AI

---

## Objetivo

Permitir que o usuario defina um briefing criativo por campanha, fornecendo contexto base para a geracao de conteudo por IA. O brief pode ser usado sozinho, combinado com os campos do formulario de geracao, ou ignorado.

## Decisoes de Design

| Decisao | Escolha | Justificativa |
|---------|---------|---------------|
| Onde armazenar o brief | Campos na tabela `campaigns` | Brief e conceitualmente parte da campanha. Tabela separada seria over-engineering (YAGNI) |
| Interacao brief vs campos | Toggle explicito com 3 modos | Usuario escolhe conscientemente como gerar. Previsivel e simples |
| Resolucao de conflitos | Campo do formulario prevalece | Escolha mais recente = mais especifica. Comportamento previsivel |
| Acesso por plano | Todos os planos | Brief e apenas contexto, nao consome recursos extras. Custo real esta na geracao AI (ja limitada por plano) |
| Estrutura do brief | Hibrido (texto livre + campos opcionais) | Texto livre captura a essencia rapidamente, campos opcionais permitem precisao |
| Injecao de brief no prompt | UseCase prepende contexto no `$topic` | Evita alterar a assinatura do `TextGeneratorInterface`. O brief vira parte do contexto textual enviado ao provider |
| Limpeza de campos brief | DTO explicito com flag `clearBrief` | Resolve ambiguidade do `??` coalescing — null = "nao informado", `clearBrief=true` = "limpar" |

---

## Modelo de Dados

### Novos campos na tabela `campaigns`

| Campo | Tipo | Nullable | Max | Descricao |
|-------|------|----------|-----|-----------|
| `brief_text` | `text` | sim | 2000 chars | Texto livre principal do brief |
| `brief_target_audience` | `varchar(500)` | sim | 500 chars | Publico-alvo da campanha |
| `brief_restrictions` | `text` | sim | 2000 chars | O que evitar na geracao |
| `brief_cta` | `varchar(500)` | sim | 500 chars | Call-to-action desejado |

### Value Object `CampaignBrief`

```
Caminho: app/Domain/Campaign/ValueObjects/CampaignBrief.php
```

Value Object imutavel (`readonly`) que encapsula os 4 campos:

- `text: ?string`
- `targetAudience: ?string`
- `restrictions: ?string`
- `cta: ?string`

Metodos:

- `isEmpty(): bool` — retorna true se todos os campos sao null
- `toPromptContext(): string` — monta bloco de contexto para injecao no prompt da IA, omitindo campos null

### Entidade Campaign

A entidade `Campaign` (`final readonly`) ganha o campo `brief: ?CampaignBrief`.

**Impacto nos factory methods:** Como a entidade e `readonly` e usa `new self(...)` internamente, todos os metodos que constroem a entidade precisam ser atualizados:

- `create()` — aceita `?CampaignBrief` opcionalmente
- `update()` — aceita `?CampaignBrief` (ver secao "Limpeza de brief" abaixo)
- `reconstitute()` — recebe brief do banco
- `softDelete()` — propaga brief existente
- `restore()` — propaga brief existente
- `releaseEvents()` — propaga brief existente

### Limpeza de brief no update

O metodo `update()` da entidade Campaign usa o padrao `$value ?? $this->value` (null coalescing), que nao distingue "campo omitido" de "campo enviado como null para limpar".

**Solucao:** O `UpdateCampaignInput` DTO ganha um campo `bool $clearBrief = false`. Quando `clearBrief` e `true`, o UseCase passa `CampaignBrief` com todos os campos null. Quando `clearBrief` e `false` e nenhum campo de brief e enviado, o brief existente e mantido.

### Estrategia de merge do brief no update

Quando campos de brief sao enviados no update, o UseCase faz merge field-level: campos enviados sobrescrevem, campos omitidos (null) preservam o valor existente.

O Value Object ganha o metodo:

```php
// CampaignBrief::mergeWith()
public function mergeWith(?CampaignBrief $override): self
{
    if ($override === null) {
        return $this;
    }
    return new self(
        text: $override->text ?? $this->text,
        targetAudience: $override->targetAudience ?? $this->targetAudience,
        restrictions: $override->restrictions ?? $this->restrictions,
        cta: $override->cta ?? $this->cta,
    );
}
```

O UseCase de update usa: `$existingBrief->mergeWith($inputBrief)`. Isso garante que enviar apenas `brief_cta` preserva os demais campos do brief existente.

**Nota:** O padrao null-coalescing no `Campaign::update()` ja existe para outros campos (como `description`). A impossibilidade de "limpar" campos individuais via null e uma limitacao pre-existente. O `clearBrief` resolve isso para o brief como um todo. Limpar campos individuais do brief nao e suportado nesta versao — para isso, o usuario usa `clearBrief: true` e reenvia o brief completo.

---

## Modos de Geracao

Novo campo nos requests de geracao AI: `generation_mode` (enum string).

| Modo | Comportamento | Requisitos |
|------|--------------|------------|
| `fields_only` (padrao) | Usa topic, tone, keywords — como funciona hoje | Campos obrigatorios do formulario |
| `brief_only` | Usa apenas o brief da campanha | `campaign_id` + campanha com brief (pelo menos `brief_text`) |
| `brief_and_fields` | Brief como base + campos refinam/sobrescrevem | `campaign_id` + brief + pelo menos `topic` |

**Regra de conflito:** Quando `brief_and_fields`, o campo do formulario sempre prevalece sobre o brief.

### Validacao condicional por modo

| Campo | `fields_only` | `brief_only` | `brief_and_fields` | Endpoints |
|-------|--------------|--------------|---------------------|-----------|
| `topic` | obrigatorio | ignorado | obrigatorio | todos |
| `tone` | opcional | ignorado | opcional | title, description, full-content |
| `keywords` | opcional | ignorado | opcional | description, full-content |
| `niche` | opcional | ignorado | opcional | hashtags |
| `campaign_id` | nao necessario | obrigatorio | obrigatorio | todos |
| `generation_mode` | default | explicito | explicito | todos |

Nota: cada endpoint tem seus proprios campos (hashtags usa `niche` em vez de `tone`, por exemplo). A validacao condicional por `generation_mode` se aplica a todos, mas os campos especificos variam por endpoint.

Em `brief_only`, o campo `topic` deixa de ser obrigatorio no request — a validacao do Form Request deve aplicar regras condicionais baseadas em `generation_mode`.

---

## Pipeline de Geracao AI

### Fluxo

```
Request de geracao -> UseCase resolve generation_mode
    |-- fields_only    -> monta prompt com topic, tone, keywords (como hoje)
    |-- brief_only     -> monta prompt com brief da campanha
    |-- brief_and_fields -> monta prompt com brief + campos (campos prevalecem)
```

### Novos campos nos Input DTOs

Todos os 4 Input DTOs ganham 2 novos campos opcionais:

```php
// GenerateTitleInput, GenerateDescriptionInput, GenerateHashtagsInput, GenerateFullContentInput
public ?string $campaignId = null,
public string $generationMode = 'fields_only',  // 'fields_only' | 'brief_only' | 'brief_and_fields'
```

**Mudanca no campo `$topic`:** Nos 4 Input DTOs, `$topic` muda de `string` (obrigatorio) para `string $topic = ''` (default vazio). Em `brief_only`, o UseCase ignora o topic e usa apenas o brief. A validacao condicional no Form Request garante que `topic` e obrigatorio em `fields_only` e `brief_and_fields`.

### Nova dependencia nos UseCases

Os UseCases de geracao (GenerateTitleUseCase, etc.) recebem `CampaignRepositoryInterface` via injecao de dependencia para buscar a campanha quando `generation_mode` inclui brief.

### Injecao do brief no prompt (sem alterar TextGeneratorInterface)

A `TextGeneratorInterface` **nao e modificada**. O UseCase monta o contexto do brief e o prepende ao `$topic` antes de chamar o interface:

```php
// No UseCase, antes de chamar o TextGeneratorInterface:
if ($input->generationMode !== 'fields_only') {
    $campaign = $this->campaignRepository->findById(
        Uuid::fromString($input->campaignId)
    );

    // Tenant isolation: verifica que campanha pertence a org do token
    if ($campaign === null || $campaign->organizationId->toString() !== $input->organizationId) {
        throw new CampaignNotFoundException();
    }

    if ($campaign->brief === null || $campaign->brief->isEmpty()) {
        throw new CampaignBriefRequiredException();
    }

    $briefContext = $campaign->brief->toPromptContext();

    if ($input->generationMode === 'brief_only') {
        $topic = $briefContext;
    } else {
        // brief_and_fields: brief como prefixo do topic
        $topic = $briefContext . "\n\n[USER TOPIC]\n" . $input->topic;
    }
}

$this->textGenerator->generateTitle($topic, ...);
```

Nota: `findById()` aceita apenas `Uuid $id` (sem organizationId). O tenant isolation e feito apos o fetch, verificando `$campaign->organizationId` contra o `$input->organizationId` do token JWT.

Esta abordagem:
- Nao altera a interface (zero breaking change no contrato)
- Mantem a responsabilidade de montagem no UseCase (onde pertence)
- O provider (LangGraph/Prism) recebe um topic enriquecido e gera normalmente

### Formato do contexto (`toPromptContext()`)

```
[CAMPAIGN BRIEF]
Objective: {brief_text}
Target Audience: {brief_target_audience}
Restrictions: {brief_restrictions}
Desired CTA: {brief_cta}

Generate content based on this campaign brief context.
```

Campos null sao omitidos. Labels em ingles porque LLMs processam melhor instrucoes em ingles independente do idioma de saida.

Em modo `brief_and_fields`, o prompt final fica:

```
[CAMPAIGN BRIEF]
Objective: {brief_text}
Target Audience: {brief_target_audience}
Restrictions: {brief_restrictions}
Desired CTA: {brief_cta}

Generate content based on this campaign brief context.

[USER TOPIC]
{topic}
```

O campo do formulario (topic, tone, etc.) prevalece naturalmente por ser o input mais proximo do ponto de geracao. Os demais campos (tone, keywords, language) sao passados normalmente como parametros separados ao `TextGeneratorInterface`.

---

## Impacto nos Endpoints

### Endpoints modificados

**Create Campaign** (`POST /api/v1/campaigns`)
- Novos campos opcionais: `brief_text`, `brief_target_audience`, `brief_restrictions`, `brief_cta`

**Update Campaign** (`PUT /api/v1/campaigns/{id}`)
- Mesmos campos opcionais + `clear_brief` (boolean, default false)
- Enviar `clear_brief: true` limpa todos os campos do brief

**Generate Title** (`POST /api/v1/ai/generate-title`)
- Novos campos: `generation_mode` (default: `fields_only`), `campaign_id` (obrigatorio se mode != `fields_only`)

**Generate Description** (`POST /api/v1/ai/generate-description`)
- Novos campos: `generation_mode`, `campaign_id`

**Generate Hashtags** (`POST /api/v1/ai/generate-hashtags`)
- Novos campos: `generation_mode`, `campaign_id`

**Generate Full Content** (`POST /api/v1/ai/generate-content`)
- Novos campos: `generation_mode`, `campaign_id`

### Nenhum endpoint novo

Tudo se encaixa nos endpoints existentes. Zero breaking changes — `generation_mode` tem default `fields_only`, mantendo compatibilidade total.

---

## Validacoes

| Regra | Contexto |
|-------|----------|
| `brief_text` max 2000 caracteres | Create/Update Campaign request |
| `brief_target_audience` max 500 caracteres | Create/Update Campaign request |
| `brief_restrictions` max 2000 caracteres | Create/Update Campaign request |
| `brief_cta` max 500 caracteres | Create/Update Campaign request |
| `generation_mode` deve ser `fields_only`, `brief_only` ou `brief_and_fields` | Generate requests |
| `campaign_id` obrigatorio quando `generation_mode` != `fields_only` | Generate requests |
| `campaign_id` deve pertencer a `organization_id` do token | Generate UseCase (tenant isolation) |
| `brief_only` requer campanha com brief preenchido (pelo menos `brief_text`) | Generate UseCase |
| `brief_and_fields` requer brief + pelo menos `topic` | Generate UseCase |
| `topic` condicional: obrigatorio em `fields_only` e `brief_and_fields`, ignorado em `brief_only` | Generate requests |

---

## Edge Cases

| Cenario | Comportamento |
|---------|--------------|
| Brief com so `brief_text` preenchido | Valido — campos estruturados sao opcionais |
| `brief_only` sem brief na campanha | Erro 422: "Campaign has no brief defined" |
| `brief_and_fields` sem `topic` | Erro 422: validacao normal do campo `topic` |
| `brief_only` com `topic` enviado | `topic` e ignorado (nao usado) |
| `fields_only` com `campaign_id` enviado | `campaign_id` e ignorado |
| Campanha sem conteudos usando brief | OK — brief e da campanha, nao depende de conteudos |
| Atualizar brief com conteudos ja gerados | OK — nao afeta conteudos ja gerados, so novas geracoes |
| Brief com todos os campos null | `CampaignBrief::isEmpty()` retorna true, tratado como sem brief |
| `campaign_id` de outra organization | Erro 404/403 — tenant isolation |
| `clear_brief: true` + campos de brief no mesmo request | `clear_brief` prevalece — brief e limpo |

---

## Testes

### Domain (Unit)

- `CampaignBrief::isEmpty()` retorna true quando todos os campos sao null
- `CampaignBrief::isEmpty()` retorna false com pelo menos 1 campo preenchido
- `CampaignBrief::toPromptContext()` omite campos null
- `CampaignBrief::toPromptContext()` inclui todos os campos quando preenchidos
- `Campaign::create()` com e sem brief
- `Campaign::update()` mantendo brief existente quando nao informado
- `Campaign::update()` com novo brief sobrescreve existente

### Application (Unit)

- `GenerateTitleUseCase` com `fields_only` — ignora brief mesmo se existir na campanha
- `GenerateTitleUseCase` com `brief_only` — usa brief, falha se brief vazio
- `GenerateTitleUseCase` com `brief_only` — `campaign_id` de outra org retorna erro
- `GenerateTitleUseCase` com `brief_and_fields` — combina ambos (brief + topic)
- `UpdateCampaignUseCase` com `clearBrief=true` — limpa o brief
- `UpdateCampaignUseCase` com campos de brief — atualiza o brief
- Mesmos testes de geracao para Description, Hashtags, FullContent

### Infrastructure (Feature)

- `POST /campaigns` com campos de brief — persiste corretamente
- `PUT /campaigns/{id}` atualiza brief
- `PUT /campaigns/{id}` com `clear_brief: true` — limpa o brief
- `POST /ai/generate-title` com `fields_only` — funciona como hoje
- `POST /ai/generate-title` com `brief_only` + `campaign_id` valido — funciona
- `POST /ai/generate-title` com `brief_only` sem brief na campanha — retorna 422
- `POST /ai/generate-title` com `brief_only` sem `campaign_id` — retorna 422
- `POST /ai/generate-title` com `brief_only` + `campaign_id` de outra org — retorna 403/404

---

## Migration

- Adiciona 4 colunas nullable na tabela `campaigns`
- Sem default, sem impacto em dados existentes
- Backward-compatible: campanhas existentes continuam funcionando com `fields_only`

---

## Resumo

1. **4 campos nullable** na tabela campaigns encapsulados no VO `CampaignBrief`
2. **3 modos de geracao**: `fields_only`, `brief_only`, `brief_and_fields`
3. **Campo prevalece** sobre brief em conflitos
4. **Todos os planos** tem acesso
5. **Zero breaking changes** — tudo backward-compatible
6. **TextGeneratorInterface inalterada** — brief injetado via prepend no `$topic` pelo UseCase
7. **Limpeza de brief** via flag `clearBrief` no DTO de update (resolve ambiguidade do null coalescing)
