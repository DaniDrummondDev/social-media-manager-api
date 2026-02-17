# ADR-008: Versionamento de API via URL Prefix

[← Voltar ao índice](00-index.md)

---

- **Status:** Accepted
- **Data:** 2026-02-15
- **Decisores:** Equipe de arquitetura

## Contexto

O Social Media Manager é API-first, com frontend separado e potenciais integrações
de terceiros. A API vai evoluir ao longo do tempo e precisamos garantir:

- Clientes existentes não quebram quando a API muda
- Capacidade de deprecar endpoints antigos gradualmente
- Clareza sobre qual versão está sendo utilizada
- Simplicidade para desenvolvedores que consomem a API

## Decisão

Adotar **versionamento via URL prefix** no formato `/api/v{N}/`.

### Formato

```
https://api.socialmediamanager.com/api/v1/campaigns
https://api.socialmediamanager.com/api/v1/contents
https://api.socialmediamanager.com/api/v2/campaigns  (futuro)
```

### Estrutura de rotas no Laravel

```
routes/
├── api/
│   ├── v1/
│   │   ├── auth.php
│   │   ├── campaigns.php
│   │   ├── contents.php
│   │   ├── social-accounts.php
│   │   ├── publishing.php
│   │   ├── analytics.php
│   │   ├── engagement.php
│   │   ├── media.php
│   │   └── ai.php
│   └── v2/     (futuro)
│       └── ...
```

### Política de versionamento

| Tipo de mudança | Nova versão? | Exemplo |
|----------------|-------------|---------|
| Adicionar campo opcional na response | Não | Adicionar `thumbnail_url` ao content |
| Adicionar endpoint novo | Não | Novo `GET /api/v1/analytics/trends` |
| Adicionar query parameter opcional | Não | Novo filtro `?sentiment=positive` |
| Remover campo da response | Sim (major) | Remover `legacy_id` |
| Renomear campo | Sim (major) | `name` → `display_name` |
| Alterar formato de campo | Sim (major) | `date: "2026-01-01"` → `date: 1706745600` |
| Alterar comportamento de endpoint | Sim (major) | Mudar lógica de paginação |

### Deprecation policy

1. Anunciar deprecação com 6 meses de antecedência
2. Header `Deprecation: true` + `Sunset: date` em respostas da versão antiga
3. Documentar migração para nova versão
4. Desativar versão antiga após prazo

### Headers padrão de response

```http
HTTP/1.1 200 OK
Content-Type: application/json
X-API-Version: v1
X-Request-Id: uuid
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 58
```

## Alternativas consideradas

### 1. Versionamento via header (Accept: application/vnd.smm.v1+json)
- **Prós:** URL limpa, mais "RESTful" segundo puristas
- **Contras:** Difícil de testar no browser, menos visível, fácil de esquecer o header
- **Por que descartado:** Complexidade desnecessária; URL prefix é mais pragmático

### 2. Versionamento via query parameter (?version=1)
- **Prós:** Fácil de implementar
- **Contras:** Não é convencional, fácil de omitir, mistura versionamento com filtros
- **Por que descartado:** Anti-pattern reconhecido pela comunidade

### 3. Sem versionamento (evolução contínua)
- **Prós:** Simples, sem overhead
- **Contras:** Impossível fazer breaking changes, clientes podem quebrar
- **Por que descartado:** Inviável para um SaaS com múltiplos clientes e integrações

## Consequências

### Positivas
- Visível e explícito — qualquer desenvolvedor entende qual versão está usando
- Fácil de testar (basta mudar a URL)
- Rotas organizadas por versão no Laravel
- Múltiplas versões podem coexistir
- Cache de CDN por URL funciona naturalmente

### Negativas
- URLs mais longas
- Duplicação de rotas quando nova versão é criada
- Manutenção de múltiplas versões simultâneas consome recursos

### Riscos
- Proliferação de versões se não houver disciplina — mitigado por política de deprecação agressiva
