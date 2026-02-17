# 08 — Glossário (Linguagem Ubíqua)

[← Voltar ao índice](00-index.md)

---

> Este glossário define a **linguagem ubíqua** do domínio, conforme princípios do DDD.
> Todos os membros da equipe devem usar estes termos de forma consistente no código,
> documentação e comunicação.

---

## A

### Access Token
Token de curta duração usado para autenticar requisições às APIs de redes sociais. Cada provider tem um tempo de expiração diferente. Armazenado de forma criptografada no sistema.

### Adapter (Social Media Adapter)
Implementação do padrão Adapter que encapsula a comunicação com uma rede social específica. Cada rede social tem seu próprio adapter que implementa a interface `SocialMediaAdapter`.

### Agendamento (Scheduling)
Ato de definir uma data e horário futuro para a publicação automática de uma peça de conteúdo em uma ou mais redes sociais. Ver **ScheduledPost**.

### Automação (Automation)
Sistema de regras configuráveis que executam ações automáticas em resposta a eventos, como responder comentários baseado em palavras-chave ou sentimento.

---

## B

### Backoff Exponencial
Estratégia de retry onde o tempo de espera entre tentativas aumenta exponencialmente. Usado em publicações falhas (1min → 5min → 15min) e entregas de webhook.

### Blacklist
Lista de palavras ou expressões que impedem a execução de respostas automáticas. Comentários contendo palavras da blacklist são direcionados para revisão manual.

### Bounded Context
Limite conceitual dentro do domínio onde um modelo particular é definido e aplicável. O sistema possui 8 bounded contexts: Identity & Access, Social Account, Campaign, Content AI, Publishing, Analytics, Engagement e Media.

---

## C

### Campanha (Campaign)
Agrupamento lógico de peças de conteúdo com um objetivo comum. Possui nome, descrição, período (início/fim) e status. Uma campanha contém múltiplas peças de conteúdo.

### Comentário (Comment)
Interação textual de um usuário de rede social em um conteúdo publicado. Capturado automaticamente pelo sistema e classificado por sentimento.

### Conteúdo (Content)
Ver **Peça de Conteúdo**.

### Connection Status
Estado atual da conexão com uma rede social. Valores possíveis: `connected` (ativa), `expired` (token expirado), `revoked` (acesso revogado pelo usuário na rede), `error` (falha técnica).

---

## D

### Delay de Resposta
Tempo de espera intencional antes de enviar uma resposta automática a um comentário. Mínimo 30 segundos. Objetivo: simular comportamento humano.

---

## E

### Engagement
Conjunto de interações dos usuários com um conteúdo publicado. Inclui: likes, comentários, compartilhamentos, salvamentos e cliques.

### Engagement Rate
Métrica calculada como: `(likes + comments + shares + saves) / reach * 100`. Indica o nível de interação do público com o conteúdo.

### Erro Permanente
Erro de API que não será resolvido com retry. Exemplos: HTTP 400 (conteúdo inválido), 401 (não autorizado), 403 (proibido). O sistema não faz retry para esses erros.

### Erro Transitório
Erro de API temporário que pode ser resolvido com retry. Exemplos: HTTP 429 (rate limit), 500/502/503 (erro do servidor), timeout. O sistema faz até 3 retries com backoff exponencial.

---

## G

### Geração (AI Generation)
Ato de usar inteligência artificial para criar conteúdo textual (títulos, descrições, hashtags). Cada geração consome tokens e é registrada no histórico.

---

## H

### Hashtag
Palavra-chave precedida por `#` usada para categorizar conteúdo em redes sociais. O sistema gera hashtags via IA e classifica por nível de competição (alta, média, baixa).

### Health Check
Verificação periódica do estado das conexões com redes sociais. Valida se os tokens ainda são funcionais e atualiza o Connection Status.

### HMAC-SHA256
Algoritmo usado para assinar payloads de webhooks, garantindo autenticidade e integridade da mensagem.

---

## L

### Lead
Usuário de rede social identificado como potencial cliente por meio de regras de automação (ex: comentou pedindo preço). Pode ser enviado para CRM via webhook.

### Long-lived Token
Token de longa duração obtido após troca de um short-lived token. No Instagram, dura ~60 dias. Renovável antes da expiração.

---

## M

### Mídia (Media)
Arquivo de imagem ou vídeo enviado ao sistema para uso em peças de conteúdo. Armazenada em object storage com geração automática de thumbnail.

### Métrica (Metric)
Dado numérico que representa a performance de um conteúdo ou conta em uma rede social. Exemplos: impressões, alcance, likes, comentários.

### Motor de Automação (Automation Engine)
Componente que avalia novos comentários contra as regras de automação configuradas e executa a ação correspondente.

---

## N

### Network Override
Customização de título, descrição ou hashtags de uma peça de conteúdo para uma rede social específica. Permite adaptar o conteúdo para cada plataforma.

---

## O

### OAuth 2.0
Protocolo de autorização usado para conectar contas de redes sociais. O sistema usa o fluxo Authorization Code com PKCE quando disponível.

### Onboarding
Fluxo de primeiro acesso onde o usuário seleciona e conecta suas redes sociais. Pode ser pulado e completado posteriormente.

---

## P

### Peça de Conteúdo (Content Piece)
Unidade de conteúdo dentro de uma campanha. Contém: título, descrição, hashtags, mídias e configurações por rede. Pode ser publicada em uma ou mais redes sociais.

### Publicação (Publishing)
Ato de enviar o conteúdo para uma rede social via API. Pode ser agendada (scheduled) ou imediata (publish now).

### Provider
Identificador de uma rede social no sistema. Valores: `instagram`, `tiktok`, `youtube` (Fase 1). Cada provider tem seu próprio Adapter.

---

## R

### Rate Limiting
Mecanismo que limita a quantidade de requisições em um período de tempo. Aplicado tanto internamente (proteção da API) quanto externamente (respeito aos limites das APIs de redes sociais).

### Refresh Token
Token de longa duração usado para obter novos access tokens sem reautenticação do usuário. Armazenado de forma criptografada.

### Regra de Automação (Automation Rule)
Configuração que define condições (palavras-chave, sentimento, rede) e ações (resposta fixa, template, IA, webhook) para processamento automático de comentários.

### Relatório (Report)
Compilação de métricas e análises de performance. Tipos: geral (todas as redes), por rede, por conteúdo. Exportável em PDF e CSV.

### Retry
Tentativa automática de reexecutar uma operação que falhou. Usado em publicações e entregas de webhook. Máximo 3 tentativas com backoff exponencial.

---

## S

### ScheduledPost
Entidade que representa o agendamento de uma peça de conteúdo em uma rede social específica. Uma peça pode ter múltiplos ScheduledPosts (um por rede).

### Sentimento (Sentiment)
Classificação emocional de um comentário, determinada por IA. Categorias: `positive`, `neutral`, `negative`. Usado para filtros e regras de automação.

### Snapshot (Metric Snapshot)
Captura pontual de métricas em um momento específico. Permite construir séries temporais e analisar evolução de performance.

### Social Account
Conta de rede social conectada ao sistema via OAuth. Contém tokens criptografados, dados do perfil e status da conexão.

### Soft Delete
Exclusão lógica onde o registro é marcado como excluído (`deleted_at`) mas permanece no banco de dados. Permite restauração dentro de um período de carência.

---

## T

### Tenant
No contexto deste sistema, cada usuário é um tenant. Todos os dados são isolados por `user_id`. Não há compartilhamento de dados entre tenants.

### Thumbnail
Versão reduzida de uma mídia usada para preview. Gerada automaticamente no upload (imagens: 300x300, vídeos: frame aos 2s).

### Token (AI)
Unidade de medida de texto processado pela IA (OpenAI). Usado para controle de custos. Aproximadamente 1 token = 4 caracteres em inglês, ~3 caracteres em português.

### Tom de Voz (Tone of Voice)
Estilo de comunicação usado pela IA ao gerar conteúdo. Configurável por usuário e por peça. Opções: professional, casual, fun, informative, inspirational, custom.

---

## U

### User Story
Descrição de uma funcionalidade do ponto de vista do usuário. Formato: "Como [persona], quero [ação], para [benefício]". Identificadas pelo prefixo US-XXX.

---

## W

### Webhook
Mecanismo de notificação HTTP onde o sistema envia dados (payload) para uma URL configurada quando eventos específicos ocorrem. Usado para integração com CRMs e sistemas externos.

### Worker
Processo que consome jobs de uma fila (queue). No sistema, workers processam publicações, sincronização de métricas, exportação de relatórios e outras tarefas assíncronas.
