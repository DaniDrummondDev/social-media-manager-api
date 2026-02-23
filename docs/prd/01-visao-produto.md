# 01 — Visão do Produto

[← Voltar ao índice](00-index.md)

---

## 1.1 Nome do Produto

**Social Media Manager** (nome de trabalho)

## 1.2 Declaração de Visão

Plataforma centralizada que permite a criadores de conteúdo, agências e empresas
gerenciar toda a sua presença em redes sociais a partir de um único ponto — agendando,
publicando, analisando e automatizando interações com inteligência artificial.

## 1.3 Problema

| Dor | Impacto |
|-----|---------|
| Publicar o mesmo conteúdo em várias redes exige acessar cada plataforma individualmente | Perda de tempo, inconsistência entre plataformas |
| Criar títulos, descrições e hashtags otimizados demanda criatividade constante | Queda na qualidade do conteúdo, baixo engajamento |
| Acompanhar métricas de performance exige consultar dashboards separados | Falta de visão unificada, decisões baseadas em dados incompletos |
| Responder comentários manualmente em todas as redes é inviável em escala | Perda de oportunidades de engajamento, clientes insatisfeitos |
| Organizar campanhas com múltiplas peças e cronogramas é complexo | Publicações perdidas, falta de alinhamento estratégico |

## 1.4 Proposta de Valor

> **"Agende uma vez, publique em todas as redes. Deixe a IA criar o conteúdo e a automação cuidar do engajamento."**

### Diferenciais competitivos

1. **Publicação unificada** — Uma única ação agenda e publica em todas as redes selecionadas
2. **IA nativa** — ChatGPT integrado para gerar títulos, descrições e hashtags otimizados por rede
3. **Automação de engajamento** — Captura de comentários com respostas automáticas inteligentes
4. **Integração com CRM** — Comentários e interações alimentam o CRM automaticamente
5. **Campanhas organizadas** — Agrupamento de peças de conteúdo em campanhas com cronograma
6. **Relatórios multi-nível** — Performance geral, por rede e por conteúdo individual
7. **API-first** — Backend desacoplado permite múltiplos frontends e integrações

## 1.5 Público-alvo

| Segmento | Descrição | Necessidade principal |
|----------|-----------|----------------------|
| **Social Media Managers** | Profissionais que gerenciam redes de múltiplos clientes | Eficiência, escala, relatórios |
| **Agências de Marketing** | Equipes que atendem dezenas de marcas | Multi-tenant, colaboração, aprovações |
| **Pequenos empreendedores** | Donos de negócio que gerenciam suas próprias redes | Simplicidade, IA para gerar conteúdo |
| **Criadores de conteúdo** | Influenciadores e produtores de conteúdo | Agendamento, analytics, crescimento |

## 1.6 Redes Sociais Suportadas

### Fase 1 (MVP)

| Rede | Tipos de conteúdo | API |
|------|-------------------|-----|
| **Instagram** | Feed posts, Reels, Stories, Carrossel | Instagram Graph API / Facebook Graph API |
| **TikTok** | Vídeos | TikTok Content Posting API |
| **YouTube** | Vídeos, Shorts | YouTube Data API v3 |

### Fase 2

| Rede | Tipos de conteúdo | API |
|------|-------------------|-----|
| **Facebook** | Posts, Reels, Stories | Facebook Graph API |
| **LinkedIn** | Posts, Articles | LinkedIn Marketing API |
| **X (Twitter)** | Tweets, Threads | X API v2 |
| **Pinterest** | Pins | Pinterest API v5 |

### Fase 3

| Rede | Tipos de conteúdo | API |
|------|-------------------|-----|
| **Threads** | Posts | Threads API |
| **Kwai** | Vídeos | Kwai Open Platform |
| **Telegram** | Posts em canais | Telegram Bot API |

## 1.7 Fases do Produto

### Fase 1 — MVP (Core)

- Autenticação e gerenciamento de usuários
- Onboarding com conexão de redes sociais (OAuth2)
- CRUD de campanhas
- Agendamento e publicação unificada (Instagram, TikTok, YouTube)
- Geração de conteúdo com IA (títulos, descrições, hashtags)
- Upload e gerenciamento de mídias (imagens e vídeos)
- Relatórios básicos de performance (geral, por rede, por conteúdo)
- Fila assíncrona de publicação com retry

### Fase 2 — Automação, Engajamento & IA Inteligente

- Captura de comentários via webhooks
- Respostas automáticas baseadas em regras
- Integração com CRM (envio de leads e interações)
- Classificação de sentimento dos comentários (IA)
- Novas redes sociais (Facebook, LinkedIn, X, Pinterest)
- Relatórios avançados e exportação
- **Gestão financeira / faturamento de clientes** (agências faturando seus próprios clientes)
- **Social listening** (monitoramento de menções, keywords e concorrentes)
- **Best Time to Post** (horários ótimos personalizados por organização/rede com base em dados históricos)
- **Brand Safety & Compliance Pre-Check** (verificação de LGPD, políticas de plataforma e sensibilidade antes de publicar)
- **Cross-Network Content Adaptation** (adaptar conteúdo de sucesso em uma rede para formato/estilo de outras redes)
- **AI Content Calendar Planning** (sugestões de calendário editorial baseadas em performance histórica e lacunas)

### Fase 3 — Escala & Inteligência Avançada

- **Content DNA Profiling** (análise de padrões de conteúdo via pgvector para identificar "DNA" que gera engajamento)
- **Pre-publish Performance Prediction** (score 0-100 de engajamento previsto antes de publicar)
- **Audience Feedback Loop** (análise de comentários alimenta automaticamente geração de conteúdo com preferências da audiência)
- **Competitive Content Gap Analysis** (identificação de lacunas de conteúdo vs concorrentes via Social Listening)
- A/B testing de conteúdo
- Fluxos de aprovação para equipes
- White-label para agências
- Mais redes sociais (Threads, Kwai, Telegram)
- Marketplace de templates de conteúdo
- **Geração de mídia com IA** (imagens e vídeos via agentes de IA a partir de prompts do usuário)

### Fase 4 — Integrações CRM Nativas

- **Conectores nativos CRM Fase 1** (HubSpot, RD Station, Pipedrive) — OAuth, sincronização bidirecional, mapeamento de campos
- **Conectores nativos CRM Fase 2** (Salesforce, ActiveCampaign) — Enterprise CRM e automação avançada
- Sincronização bidirecional SMM ↔ CRM (comentários → contatos, leads → deals, publicações → atividades)
- Webhooks inbound do CRM (deal fechado → trigger campanha)

## 1.8 Fora de Escopo (v1)

- Frontend (será outro projeto separado)
- App mobile nativo
- Editor de imagem/vídeo embutido
- Chat ao vivo com seguidores
