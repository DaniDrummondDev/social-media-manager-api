# 07 — Regras de Negócio

[← Voltar ao índice](00-index.md)

---

## 7.1 Regras de Autenticação e Acesso

### RN-001: Unicidade de email
- O email é o identificador único do usuário no sistema.
- Não é permitido registrar duas contas com o mesmo email.
- A validação deve ser case-insensitive (normalizar para lowercase).

### RN-002: Verificação de email obrigatória
- O usuário MUST verificar o email antes de acessar qualquer funcionalidade.
- Até a verificação, apenas os endpoints de verificação e reenvio são acessíveis.

### RN-003: Política de senha
- Mínimo 8 caracteres.
- Pelo menos 1 letra maiúscula.
- Pelo menos 1 número.
- Pelo menos 1 caractere especial (!@#$%^&*).
- Não pode ser igual às 5 últimas senhas utilizadas.

### RN-004: Bloqueio por tentativas
- Após 5 tentativas de login falhas consecutivas, a conta é bloqueada por 15 minutos.
- O contador é resetado após login bem-sucedido.
- O bloqueio é por combinação de email + IP.

### RN-005: Sessões simultâneas
- Um usuário pode ter múltiplas sessões ativas simultaneamente.
- O logout de todas as sessões é possível (invalidar todos os refresh tokens).
- Login em novo dispositivo não invalida sessões existentes.

---

## 7.2 Regras de Conexão de Redes Sociais

### RN-010: Uma conta por rede por usuário
- O usuário pode conectar apenas uma conta por rede social.
- Para trocar de conta, deve desconectar a atual e conectar a nova.
- Exceção futura: planos que permitam múltiplas contas por rede.

### RN-011: Requisitos da conta Instagram
- Somente contas **Business** ou **Creator** são aceitas.
- Contas pessoais devem ser convertidas antes de conectar.
- A conta deve estar vinculada a uma página do Facebook.

### RN-012: Renovação de tokens
- Tokens MUST ser renovados automaticamente antes de expirar.
- O job de renovação deve rodar com antecedência de segurança:
  - Instagram: renovar quando faltarem 7 dias (token dura 60 dias)
  - TikTok: renovar quando faltarem 7 dias (access token dura 24h, renovar diariamente)
  - YouTube: renovar quando faltarem 10 minutos (token dura 1h, renovar a cada 50min)

### RN-013: Falha na renovação
- Se a renovação falhar, tentar novamente em 1 hora.
- Após 3 falhas consecutivas, marcar conta como `expired`.
- Notificar o usuário por email com link para reconectar.
- Agendamentos pendentes para a conta são mantidos mas marcados como `at_risk`.

### RN-014: Desconexão de conta
- Ao desconectar, todos os agendamentos pendentes para a conta são cancelados.
- Dados históricos (analytics, comentários) são mantidos.
- Tokens são revogados no provider quando possível.

---

## 7.3 Regras de Campanhas

### RN-020: Nome de campanha único
- O nome da campanha deve ser único por usuário.
- A validação é case-insensitive.
- Campanhas excluídas (soft deleted) não contam para unicidade.

### RN-021: Período da campanha
- Data de início e fim são opcionais.
- Se informadas, data fim MUST ser posterior à data início.
- Peças podem ser agendadas fora do período da campanha (warning, não bloqueio).

### RN-022: Status da campanha
```
draft ──▶ active ──▶ completed
  │          │
  │          ▼
  │       paused ──▶ active
  │
  └──▶ (deleted)
```
- **draft:** Campanha em rascunho, peças podem ser editadas livremente.
- **active:** Campanha ativa, peças podem ser agendadas e publicadas.
- **paused:** Campanha pausada, novos agendamentos bloqueados, agendamentos existentes são mantidos.
- **completed:** Campanha finalizada, nenhuma nova publicação permitida.
- Transição para `completed` pode ser manual ou automática (quando `ends_at` é atingido).

### RN-023: Exclusão de campanha
- Soft delete apenas.
- Ao excluir, peças agendadas são canceladas automaticamente.
- Peças já publicadas mantêm registro histórico.
- Campanha excluída pode ser restaurada em até 30 dias.

### RN-024: Duplicação
- Duplica todas as peças de conteúdo com seus dados.
- Peças duplicadas ficam com status `draft`.
- Agendamentos não são duplicados.
- Mídias são referenciadas (não duplicadas fisicamente).
- Nome recebe sufixo " (Cópia)". Se já existir, incrementa: " (Cópia 2)".

---

## 7.4 Regras de Conteúdo

### RN-030: Status de conteúdo
```
draft ──▶ scheduled ──▶ publishing ──▶ published
  │          │              │
  │          ▼              ▼
  │       cancelled       failed ──▶ (retry) ──▶ publishing
  │
  └──▶ (deleted)
```
- **draft:** Editável, sem agendamento.
- **scheduled:** Agendado para publicação. Editável (exceto redes/horário, que requerem cancelamento e reagendamento).
- **publishing:** Em processo de publicação. Não editável.
- **published:** Publicado com sucesso em todas as redes agendadas.
- **failed:** Falhou em uma ou mais redes após todas as tentativas.
- **cancelled:** Agendamento cancelado pelo usuário.

### RN-031: Conteúdo parcialmente publicado
- Se o conteúdo foi agendado para 3 redes e publicou em 2:
  - Status geral do conteúdo: `failed`
  - Cada ScheduledPost tem seu próprio status
  - O usuário pode retentar apenas nas redes que falharam

### RN-032: Override por rede
- Cada peça pode ter título, descrição e hashtags customizados por rede.
- Se não houver override, usa o conteúdo padrão da peça.
- O override MUST respeitar os limites da rede específica.

### RN-033: Vinculação de mídia
- Uma peça pode ter múltiplas mídias (ex: carrossel do Instagram).
- A ordem das mídias é relevante.
- A mídia MUST ser compatível com todas as redes selecionadas.

---

## 7.5 Regras de Agendamento e Publicação

### RN-040: Horário mínimo de agendamento
- O agendamento MUST ser para no mínimo 5 minutos no futuro.
- O horário é baseado no timezone do usuário.
- O sistema converte e armazena em UTC internamente.

### RN-041: Validação de compatibilidade
Antes de agendar, o sistema MUST validar:

| Validação | Instagram | TikTok | YouTube |
|-----------|-----------|--------|---------|
| Imagem: JPG/PNG | Feed, Story | N/A | Thumbnail |
| Vídeo: MP4 | Reels, Story | Obrigatório | Obrigatório |
| Duração mín. vídeo | 3s | 1s | 1s |
| Duração máx. vídeo | 90min (Reels) | 10min | 12h |
| Tamanho máx. | 1GB | 4GB | 256GB |
| Caracteres título | N/A | 150 | 100 |
| Caracteres descrição | 2.200 | 4.000 | 5.000 |
| Máx. hashtags | 30 | ~5 (recomendado) | 15 (tags) |
| Carrossel | Sim (até 10) | Não | Não |

### RN-042: Limite diário por rede
- Instagram: máximo 25 publicações por dia por conta.
- TikTok: conforme limites do app approval.
- YouTube: ~6 vídeos por dia (limite de quota).
- O sistema MUST bloquear agendamento se o limite será excedido.

### RN-043: Janela de cancelamento
- Agendamento pode ser cancelado até 1 minuto antes do horário.
- Após esse prazo, o agendamento está "locked" para processamento.

### RN-044: Retry de publicação
- Retry automático apenas para erros transitórios:
  - HTTP 429 (Too Many Requests)
  - HTTP 500, 502, 503, 504 (Server Errors)
  - Timeout
- Sem retry para erros permanentes:
  - HTTP 400 (Bad Request — conteúdo inválido)
  - HTTP 401, 403 (Unauthorized/Forbidden — token inválido)
  - HTTP 404 (Recurso não encontrado)
- Backoff exponencial: tentativa 1 (1min), tentativa 2 (5min), tentativa 3 (15min).
- Máximo 3 tentativas.

### RN-045: Notificação de falha
- Após todas as tentativas falharem, o usuário é notificado.
- Notificação contém: peça de conteúdo, rede, erro resumido, link para retentar.
- Canal de notificação: email (e futuro push notification no frontend).

---

## 7.6 Regras de IA

### RN-050: Limites de caracteres por rede
A IA MUST gerar conteúdo dentro dos limites:

| Rede | Título | Descrição | Hashtags |
|------|--------|-----------|----------|
| Instagram | N/A | Até 2.200 chars | Até 30 |
| TikTok | Até 150 chars | Até 4.000 chars | Até 5 (recomendado) |
| YouTube | Até 100 chars | Até 5.000 chars | Até 15 (tags) |

### RN-051: Tom de voz
- Cada geração usa o tom de voz padrão do usuário, exceto se especificado na request.
- Tons disponíveis: professional, casual, fun, informative, inspirational, custom.
- Tom "custom" requer descrição textual (mín. 20 chars).

### RN-052: Rate limiting de IA
- Máximo 10 gerações por minuto por usuário.
- Máximo 500 gerações por dia por usuário (configurável por plano).
- Ao atingir o limite, retornar erro 429 com tempo de espera.

### RN-053: Registro de consumo
- Toda geração registra: tokens de input, tokens de output, modelo utilizado.
- Custo estimado é calculado com base nos preços do modelo.
- Usuário pode consultar consumo acumulado no mês.

---

## 7.7 Regras de Analytics

### RN-060: Períodos de relatório
- Períodos padrão: 7 dias, 30 dias, 90 dias.
- Período customizado: máximo 365 dias.
- Comparativo com período anterior usa o mesmo intervalo de dias.

### RN-061: Sincronização de métricas
- Métricas gerais: sincronização a cada 6 horas.
- Conteúdos recentes (< 48h): sincronização a cada 1 hora.
- Conteúdos antigos (> 48h): sincronização a cada 6 horas.
- Respeitar rate limits de cada API (ver seção 6.8).

### RN-062: Cálculo de engagement rate
```
engagement_rate = (likes + comments + shares + saves) / reach * 100
```
- Se reach = 0, engagement_rate = 0.
- Calculado por conteúdo e agregado por período.

### RN-063: Exportação
- Formato PDF: relatório visual com gráficos (gerado server-side).
- Formato CSV: dados brutos para análise.
- Processamento assíncrono via queue.
- Link de download válido por 24 horas.
- Máximo 5 exportações simultâneas por usuário.

---

## 7.8 Regras de Engajamento e Automação

### RN-070: Captura de comentários
- Polling a cada 30 minutos para conteúdos publicados nos últimos 30 dias.
- Para conteúdos publicados há mais de 30 dias: polling diário.
- Deduplicação baseada no `external_comment_id`.
- Comentários do próprio usuário (respostas da plataforma) são marcados mas não processados pelo motor de automação.

### RN-071: Classificação de sentimento
- Toda captura de comentário dispara classificação de sentimento via IA.
- Categorias: positive (> 0.6), neutral (0.4-0.6), negative (< 0.4).
- Score de confiança é armazenado junto com a classificação.
- Se a IA estiver indisponível, comentário é classificado como `neutral` por padrão.

### RN-072: Prioridade de regras de automação
- Regras são avaliadas em ordem de prioridade (campo `priority`, menor = mais prioritário).
- A primeira regra que casa é executada (stop on first match).
- Comentários que não casam com nenhuma regra não recebem resposta automática.

### RN-073: Delay de resposta
- Mínimo 30 segundos de delay antes de responder automaticamente.
- Delay padrão: 120 segundos (2 minutos).
- Delay máximo: 3.600 segundos (1 hora).
- O delay simula comportamento humano e evita parecer bot.

### RN-074: Limite diário de automação
- Limite padrão: 100 respostas automáticas por dia por usuário.
- Configurável pelo usuário (mín. 10, máx. 1.000).
- Ao atingir o limite, novas automações são enfileiradas para o dia seguinte.
- O usuário é notificado quando o limite é atingido.

### RN-075: Blacklist de automação
- Palavras na blacklist impedem resposta automática.
- O comentário é marcado para revisão manual.
- Blacklist padrão incluirá palavras ofensivas comuns.
- Usuário pode customizar a blacklist.

### RN-076: Templates de resposta
- Templates suportam variáveis:
  - `{author_name}` — Nome do autor do comentário
  - `{post_title}` — Título do post
  - `{campaign_name}` — Nome da campanha
- Variáveis são substituídas no momento do envio.
- Template com variável não resolvida: variável é removida do texto.

---

## 7.9 Regras de Mídia

### RN-080: Validação de upload
- Tipos aceitos: JPG, PNG, WEBP, GIF (imagem), MP4, MOV (vídeo).
- Tamanho máximo: 10MB (imagem), 500MB (vídeo).
- Verificação de MIME type real (não apenas extensão).
- Scan de malware obrigatório antes de persistir.

### RN-081: Compatibilidade cross-network
- Ao vincular mídia a uma peça com múltiplas redes, validar compatibilidade:
  - Instagram Feed: imagem ou vídeo (3s-60s)
  - Instagram Reels: vídeo (3s-90min)
  - Instagram Stories: imagem ou vídeo (até 60s)
  - TikTok: apenas vídeo (1s-10min)
  - YouTube: apenas vídeo (1s-12h)
- Se a mídia é incompatível com alguma rede selecionada, alertar o usuário.

### RN-082: Soft delete de mídia
- Mídia soft deleted fica indisponível para novas vinculações.
- Mídia vinculada a peça agendada NÃO pode ser excluída.
- Após 30 dias do soft delete, exclusão física do storage.
- Job de limpeza roda diariamente para purge de mídias expiradas.

### RN-083: Thumbnail automática
- Para vídeos: captura frame aos 2 segundos.
- Para imagens: redimensiona para 300x300 mantendo proporção.
- Thumbnails são armazenadas junto com a mídia original.
