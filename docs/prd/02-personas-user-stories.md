# 02 — Personas & User Stories

[← Voltar ao índice](00-index.md)

---

## 2.1 Personas

### Persona 1: Marina — Social Media Manager Freelancer

| Atributo | Detalhe |
|----------|---------|
| **Idade** | 28 anos |
| **Cargo** | Social Media Manager Freelancer |
| **Contexto** | Gerencia as redes de 8 clientes diferentes |
| **Dores** | Perde horas alternando entre plataformas; esquece de publicar em alguma rede; não consegue gerar relatórios consolidados |
| **Objetivos** | Agendar tudo de uma vez, gerar conteúdo rápido com IA, apresentar relatórios profissionais aos clientes |
| **Frase** | *"Preciso de uma ferramenta que me permita gerenciar todas as redes dos meus clientes sem enlouquecer."* |

### Persona 2: Rafael — Dono de Agência de Marketing

| Atributo | Detalhe |
|----------|---------|
| **Idade** | 35 anos |
| **Cargo** | CEO de agência de marketing digital |
| **Contexto** | Equipe de 12 pessoas atendendo 40+ marcas |
| **Dores** | Falta de padronização nos processos; dificuldade em monitorar entregas; sem visão unificada de resultados |
| **Objetivos** | Centralizar operações, ter métricas de performance por cliente, escalar atendimento |
| **Frase** | *"Preciso escalar minha operação sem aumentar o time proporcionalmente."* |

### Persona 3: Carla — Empreendedora

| Atributo | Detalhe |
|----------|---------|
| **Idade** | 42 anos |
| **Cargo** | Dona de loja de roupas online |
| **Contexto** | Gerencia sozinha as redes da loja |
| **Dores** | Não tem tempo nem criatividade para criar posts; não sabe os melhores horários; não acompanha resultados |
| **Objetivos** | Publicar com ajuda da IA, manter consistência, entender o que funciona |
| **Frase** | *"Quero focar no meu negócio e não perder tempo com redes sociais."* |

### Persona 4: Lucas — Criador de Conteúdo

| Atributo | Detalhe |
|----------|---------|
| **Idade** | 24 anos |
| **Cargo** | Influenciador digital (150k seguidores) |
| **Contexto** | Publica diariamente em Instagram, TikTok e YouTube |
| **Dores** | Responder comentários é impossível em escala; perde tempo adaptando conteúdo para cada rede; não tem visão clara de crescimento |
| **Objetivos** | Automatizar respostas, agendar conteúdo adaptado, acompanhar métricas de crescimento |
| **Frase** | *"Se eu pudesse automatizar o operacional, focaria só em criar conteúdo."* |

---

## 2.2 Épicos

| Código | Épico | Bounded Context |
|--------|-------|-----------------|
| **EP-01** | Autenticação e Gestão de Usuários | Identity & Access |
| **EP-02** | Onboarding e Conexão de Redes Sociais | Social Account |
| **EP-03** | Gestão de Campanhas | Campaign |
| **EP-04** | Criação de Conteúdo com IA | Content AI |
| **EP-05** | Agendamento e Publicação | Publishing |
| **EP-06** | Analytics e Relatórios | Analytics |
| **EP-07** | Engajamento e Automação | Engagement |
| **EP-08** | Gestão de Mídias | Media |

---

## 2.3 User Stories

### EP-01 — Autenticação e Gestão de Usuários

#### US-001: Registro de usuário
**Como** novo usuário,
**quero** me registrar na plataforma com email e senha,
**para** ter acesso ao sistema.

**Critérios de aceite:**
- [ ] Email deve ser único e válido
- [ ] Senha deve ter no mínimo 8 caracteres, 1 maiúscula, 1 número e 1 caractere especial
- [ ] Email de verificação deve ser enviado após o registro
- [ ] Conta fica inativa até verificação do email
- [ ] Rate limiting de 5 tentativas por minuto no endpoint de registro

#### US-002: Login
**Como** usuário registrado,
**quero** fazer login com email e senha,
**para** acessar minha conta.

**Critérios de aceite:**
- [ ] Autenticação via token JWT (access + refresh token)
- [ ] Access token expira em 15 minutos
- [ ] Refresh token expira em 7 dias
- [ ] Após 5 tentativas falhas, conta é bloqueada por 15 minutos
- [ ] Login registra IP, user agent e timestamp

#### US-003: Recuperação de senha
**Como** usuário que esqueceu a senha,
**quero** solicitar a redefinição via email,
**para** recuperar o acesso à minha conta.

**Critérios de aceite:**
- [ ] Link de redefinição expira em 1 hora
- [ ] Token de uso único (single-use)
- [ ] Email de notificação enviado após redefinição bem-sucedida
- [ ] Rate limiting de 3 solicitações por hora

#### US-004: Gerenciamento de perfil
**Como** usuário autenticado,
**quero** atualizar meus dados pessoais e foto de perfil,
**para** manter minha conta atualizada.

**Critérios de aceite:**
- [ ] Campos editáveis: nome, foto, telefone, timezone
- [ ] Email só pode ser alterado com re-verificação
- [ ] Foto de perfil aceita JPG/PNG até 2MB

#### US-005: Autenticação de dois fatores (2FA)
**Como** usuário preocupado com segurança,
**quero** ativar autenticação de dois fatores,
**para** proteger minha conta.

**Critérios de aceite:**
- [ ] Suporte a TOTP (Google Authenticator, Authy)
- [ ] Códigos de recuperação gerados na ativação (10 códigos)
- [ ] Possibilidade de desativar 2FA com confirmação de senha

---

### EP-02 — Onboarding e Conexão de Redes Sociais

#### US-010: Onboarding — Seleção de redes sociais
**Como** novo usuário no primeiro acesso,
**quero** escolher quais redes sociais desejo conectar,
**para** configurar minha conta rapidamente.

**Critérios de aceite:**
- [ ] Tela de onboarding exibe todas as redes suportadas
- [ ] Usuário pode selecionar uma ou mais redes
- [ ] Cada rede mostra quais permissões serão solicitadas
- [ ] Onboarding pode ser ignorado e completado depois

#### US-011: Conectar conta do Instagram
**Como** usuário,
**quero** conectar minha conta profissional do Instagram via OAuth,
**para** agendar e publicar conteúdos.

**Critérios de aceite:**
- [ ] Fluxo OAuth2 via Facebook Graph API
- [ ] Apenas contas profissionais (Business/Creator) são aceitas
- [ ] Permissões solicitadas: instagram_basic, instagram_content_publish, instagram_manage_comments, instagram_manage_insights, pages_show_list, pages_read_engagement
- [ ] Token armazenado de forma criptografada
- [ ] Refresh automático de long-lived tokens (antes de expirar)
- [ ] Feedback claro em caso de falha na conexão

#### US-012: Conectar conta do TikTok
**Como** usuário,
**quero** conectar minha conta do TikTok via OAuth,
**para** agendar e publicar vídeos.

**Critérios de aceite:**
- [ ] Fluxo OAuth2 via TikTok Login Kit
- [ ] Permissões: video.upload, video.publish, video.list, comment.list, comment.list.manage
- [ ] Token armazenado de forma criptografada
- [ ] Refresh automático antes da expiração

#### US-013: Conectar conta do YouTube
**Como** usuário,
**quero** conectar meu canal do YouTube via OAuth,
**para** agendar e publicar vídeos e shorts.

**Critérios de aceite:**
- [ ] Fluxo OAuth2 via Google
- [ ] Scopes: youtube.upload, youtube.readonly, youtube.force-ssl
- [ ] Token armazenado de forma criptografada
- [ ] Refresh automático via refresh token do Google

#### US-014: Desconectar rede social
**Como** usuário,
**quero** desconectar uma rede social da minha conta,
**para** revogar o acesso da plataforma.

**Critérios de aceite:**
- [ ] Revogar tokens na plataforma de origem quando possível
- [ ] Agendamentos pendentes para a rede são cancelados
- [ ] Dados históricos de analytics são mantidos
- [ ] Confirmação obrigatória antes da desconexão

#### US-015: Visualizar status das conexões
**Como** usuário,
**quero** ver o status de todas as minhas redes conectadas,
**para** saber se alguma conexão expirou ou falhou.

**Critérios de aceite:**
- [ ] Status por rede: conectada, expirada, erro, desconectada
- [ ] Data da última sincronização
- [ ] Botão para reconectar em caso de expiração
- [ ] Alerta por email quando um token expira

---

### EP-03 — Gestão de Campanhas

#### US-020: Criar campanha
**Como** usuário,
**quero** criar uma campanha com nome, descrição e período,
**para** organizar minhas publicações.

**Critérios de aceite:**
- [ ] Campos: nome (obrigatório), descrição, data início, data fim, tags
- [ ] Nome deve ser único por usuário
- [ ] Campanha pode ter status: rascunho, ativa, pausada, finalizada
- [ ] Validar que data fim é posterior à data início

#### US-021: Adicionar peças de conteúdo à campanha
**Como** usuário,
**quero** adicionar peças de conteúdo a uma campanha,
**para** organizar o que será publicado.

**Critérios de aceite:**
- [ ] Uma peça de conteúdo pertence a uma única campanha
- [ ] Peça contém: título, descrição, hashtags, mídia(s), redes destino
- [ ] Cada rede pode ter título/descrição/hashtags customizados
- [ ] Peça pode ter status: rascunho, agendada, publicada, falha

#### US-022: Listar campanhas
**Como** usuário,
**quero** listar minhas campanhas com filtros e paginação,
**para** encontrar e gerenciar rapidamente.

**Critérios de aceite:**
- [ ] Filtros: status, período, nome (busca parcial)
- [ ] Ordenação: data de criação, nome, data início
- [ ] Paginação com cursor-based pagination
- [ ] Exibir contadores: total de peças, publicadas, agendadas

#### US-023: Editar campanha
**Como** usuário,
**quero** editar os dados de uma campanha,
**para** ajustar planejamentos.

**Critérios de aceite:**
- [ ] Todos os campos são editáveis
- [ ] Alterar período não afeta peças já publicadas
- [ ] Histórico de alterações é registrado (audit log)

#### US-024: Excluir campanha
**Como** usuário,
**quero** excluir uma campanha,
**para** remover campanhas que não serão utilizadas.

**Critérios de aceite:**
- [ ] Soft delete (a campanha é marcada como excluída)
- [ ] Peças agendadas são canceladas automaticamente
- [ ] Peças já publicadas mantêm o registro histórico
- [ ] Confirmação obrigatória

#### US-025: Duplicar campanha
**Como** usuário,
**quero** duplicar uma campanha existente,
**para** reaproveitar estrutura e conteúdos.

**Critérios de aceite:**
- [ ] Duplica campanha com todas as peças
- [ ] Peças duplicadas ficam como rascunho
- [ ] Agendamentos não são duplicados
- [ ] Nome recebe sufixo " (Cópia)"

---

### EP-04 — Criação de Conteúdo com IA

#### US-030: Gerar título com IA
**Como** usuário,
**quero** gerar sugestões de títulos usando ChatGPT,
**para** criar títulos mais atrativos.

**Critérios de aceite:**
- [ ] Recebe como input: tema/assunto, rede social alvo, tom de voz desejado
- [ ] Gera 3-5 sugestões de título
- [ ] Títulos respeitam limites de caracteres da rede
- [ ] Usuário pode selecionar, editar ou regenerar
- [ ] Histórico de gerações é mantido

#### US-031: Gerar descrição com IA
**Como** usuário,
**quero** gerar descrições/legendas usando ChatGPT,
**para** criar textos otimizados para cada rede.

**Critérios de aceite:**
- [ ] Recebe: tema, rede social, tom de voz, palavras-chave
- [ ] Gera descrição otimizada para a rede selecionada
- [ ] Respeita limites de caracteres (Instagram: 2.200, TikTok: 4.000, YouTube: 5.000)
- [ ] Pode gerar versões diferentes para cada rede automaticamente
- [ ] Usuário pode refinar com follow-up prompts

#### US-032: Gerar hashtags com IA
**Como** usuário,
**quero** gerar hashtags relevantes usando ChatGPT,
**para** aumentar o alcance das publicações.

**Critérios de aceite:**
- [ ] Recebe: tema, nicho, rede social
- [ ] Gera mix de hashtags (alta competição, média, baixa, nicho)
- [ ] Quantidade sugerida por rede (Instagram: até 30, TikTok: até 5, YouTube: até 15)
- [ ] Hashtags são validadas (sem caracteres especiais, sem espaços)
- [ ] Usuário pode remover, adicionar ou regenerar

#### US-033: Gerar conteúdo completo com IA
**Como** usuário,
**quero** gerar título, descrição e hashtags de uma só vez,
**para** agilizar a criação de peças de conteúdo.

**Critérios de aceite:**
- [ ] Input único gera todo o conteúdo textual
- [ ] Adapta automaticamente para cada rede selecionada
- [ ] Preview lado a lado de como ficará em cada rede
- [ ] Salvar como rascunho diretamente na peça de conteúdo
- [ ] Contabilizar uso de tokens para controle de custo

#### US-034: Configurar tom de voz padrão
**Como** usuário,
**quero** definir um tom de voz padrão para a IA,
**para** manter consistência na comunicação.

**Critérios de aceite:**
- [ ] Opções pré-definidas: profissional, casual, divertido, informativo, inspiracional
- [ ] Opção de tom customizado com descrição livre
- [ ] Tom padrão é usado em todas as gerações, mas pode ser alterado por peça
- [ ] Pode ser definido por campanha

---

### EP-05 — Agendamento e Publicação

#### US-040: Agendar publicação em múltiplas redes
**Como** usuário,
**quero** agendar a publicação de uma peça em várias redes simultaneamente,
**para** publicar uma única vez em todos os canais.

**Critérios de aceite:**
- [ ] Selecionar uma ou mais redes destino
- [ ] Definir data e horário (respeitando timezone do usuário)
- [ ] Agendamento mínimo: 5 minutos no futuro
- [ ] Validar se o conteúdo é compatível com cada rede (formato, tamanho, duração)
- [ ] Confirmação com preview por rede antes de agendar

#### US-041: Publicação imediata
**Como** usuário,
**quero** publicar uma peça imediatamente em uma ou mais redes,
**para** conteúdos que não precisam de agendamento.

**Critérios de aceite:**
- [ ] Publicação entra na fila com prioridade alta
- [ ] Feedback em tempo real do status (publicando, publicado, falha)
- [ ] Em caso de falha parcial (ex: publicou no Instagram mas falhou no TikTok), reportar por rede
- [ ] Possibilidade de retentar apenas nas redes que falharam

#### US-042: Cancelar agendamento
**Como** usuário,
**quero** cancelar um agendamento pendente,
**para** impedir uma publicação que não desejo mais.

**Critérios de aceite:**
- [ ] Cancelamento possível até 1 minuto antes do horário agendado
- [ ] Cancelar para todas as redes ou redes específicas
- [ ] Peça volta ao status de rascunho
- [ ] Registro de quem cancelou e quando

#### US-043: Reagendar publicação
**Como** usuário,
**quero** alterar a data/hora de um agendamento,
**para** ajustar o cronograma.

**Critérios de aceite:**
- [ ] Novo horário deve ser no mínimo 5 minutos no futuro
- [ ] Possível reagendar redes individualmente
- [ ] Histórico de reagendamentos mantido

#### US-044: Visualizar calendário de publicações
**Como** usuário,
**quero** ver um calendário com todas as publicações agendadas,
**para** ter visão geral do cronograma.

**Critérios de aceite:**
- [ ] Visualização mensal, semanal e diária
- [ ] Filtro por rede social e por campanha
- [ ] Cores diferentes por status (agendada, publicada, falha)
- [ ] Drag-and-drop para reagendar (futuro — frontend)

#### US-045: Retry automático de publicação falha
**Como** sistema,
**quero** retentar automaticamente publicações que falharam,
**para** garantir a entrega do conteúdo.

**Critérios de aceite:**
- [ ] Até 3 tentativas com backoff exponencial (1min, 5min, 15min)
- [ ] Se todas as tentativas falharem, notificar o usuário
- [ ] Registro detalhado do erro em cada tentativa
- [ ] Não retentar em caso de erro de permissão ou conteúdo inválido (erro permanente)

---

### EP-06 — Analytics e Relatórios

#### US-050: Relatório de performance geral
**Como** usuário,
**quero** ver um dashboard com métricas consolidadas de todas as redes,
**para** ter uma visão geral da minha presença digital.

**Critérios de aceite:**
- [ ] Métricas: total de publicações, alcance total, engajamento total, crescimento de seguidores
- [ ] Período selecionável (7d, 30d, 90d, customizado)
- [ ] Comparativo com período anterior (crescimento %)
- [ ] Gráficos de tendência temporal

#### US-051: Relatório por rede social
**Como** usuário,
**quero** ver métricas detalhadas por rede social,
**para** entender a performance em cada plataforma.

**Critérios de aceite:**
- [ ] Métricas específicas por rede (ex: Instagram tem saves, TikTok tem shares)
- [ ] Top 5 conteúdos por engajamento
- [ ] Horários de melhor performance
- [ ] Evolução de seguidores

#### US-052: Relatório por conteúdo
**Como** usuário,
**quero** ver métricas detalhadas de um conteúdo específico,
**para** entender o que funciona melhor.

**Critérios de aceite:**
- [ ] Métricas: impressões, alcance, engajamento, cliques, saves, shares, comentários
- [ ] Comparativo entre redes onde foi publicado
- [ ] Evolução temporal (primeiras 24h, 48h, 7d)
- [ ] Se gerado por IA, exibir qual prompt/configuração foi usada

#### US-053: Exportar relatórios
**Como** usuário,
**quero** exportar relatórios em PDF e CSV,
**para** apresentar a clientes ou analisar externamente.

**Critérios de aceite:**
- [ ] Formatos: PDF (visual) e CSV (dados)
- [ ] PDF com branding do sistema (e futuro white-label)
- [ ] Geração assíncrona com notificação quando pronto
- [ ] Link de download válido por 24 horas

#### US-054: Sincronização de métricas
**Como** sistema,
**quero** sincronizar métricas das redes periodicamente,
**para** manter os relatórios atualizados.

**Critérios de aceite:**
- [ ] Sincronização a cada 6 horas para métricas gerais
- [ ] Sincronização a cada 1 hora para conteúdos publicados nas últimas 48h
- [ ] Rate limiting respeitando os limites de cada API
- [ ] Retry com backoff em caso de falha
- [ ] Registro de última sincronização bem-sucedida por rede

---

### EP-07 — Engajamento e Automação

#### US-060: Capturar comentários
**Como** sistema,
**quero** capturar automaticamente novos comentários das redes conectadas,
**para** centralizar as interações.

**Critérios de aceite:**
- [ ] Polling periódico ou webhook (quando disponível)
- [ ] Armazenar: autor, texto, data, rede, conteúdo relacionado
- [ ] Deduplicação de comentários
- [ ] Classificar sentimento automaticamente (positivo, neutro, negativo) via IA

#### US-061: Listar e filtrar comentários
**Como** usuário,
**quero** ver todos os comentários de todas as redes em um único lugar,
**para** gerenciar interações de forma centralizada.

**Critérios de aceite:**
- [ ] Filtros: rede, campanha, sentimento, status (respondido/não respondido), período
- [ ] Busca por texto no comentário
- [ ] Paginação com cursor
- [ ] Marcar como lido/não lido

#### US-062: Responder comentário manualmente
**Como** usuário,
**quero** responder um comentário diretamente pela plataforma,
**para** interagir sem acessar cada rede individualmente.

**Critérios de aceite:**
- [ ] Resposta publicada na rede de origem
- [ ] Sugestão de resposta via IA
- [ ] Registro de quem respondeu e quando
- [ ] Feedback de sucesso/falha na publicação da resposta

#### US-063: Configurar respostas automáticas
**Como** usuário,
**quero** criar regras de resposta automática para comentários,
**para** escalar o atendimento.

**Critérios de aceite:**
- [ ] Regras baseadas em: palavras-chave, sentimento, rede, campanha
- [ ] Tipos de resposta: texto fixo, template com variáveis, resposta gerada por IA
- [ ] Delay configurável antes de responder (parecer humano)
- [ ] Limite máximo de respostas automáticas por dia (configurável)
- [ ] Blacklist de palavras para não responder automaticamente
- [ ] Possibilidade de desativar por rede ou campanha

#### US-064: Integração com CRM
**Como** usuário,
**quero** enviar comentários e leads para meu CRM,
**para** alimentar meu funil de vendas.

**Critérios de aceite:**
- [ ] Webhook genérico configurável (URL + headers + payload template)
- [ ] Eventos que disparam: novo comentário, comentário com palavra-chave, lead identificado
- [ ] Retry em caso de falha no webhook (3 tentativas)
- [ ] Log de envios com status

---

### EP-08 — Gestão de Mídias

#### US-070: Upload de mídias
**Como** usuário,
**quero** fazer upload de imagens e vídeos,
**para** usar nas peças de conteúdo.

**Critérios de aceite:**
- [ ] Formatos aceitos: JPG, PNG, WEBP, GIF, MP4, MOV
- [ ] Tamanho máximo: imagens 10MB, vídeos 500MB
- [ ] Validação de dimensões mínimas por rede
- [ ] Upload com progress bar e resumable (tus protocol ou similar)
- [ ] Geração automática de thumbnails
- [ ] Scan de malware no upload

#### US-071: Biblioteca de mídias
**Como** usuário,
**quero** ter uma biblioteca com todas as mídias enviadas,
**para** reutilizar em diferentes peças.

**Critérios de aceite:**
- [ ] Listagem com preview (thumbnails)
- [ ] Filtros: tipo (imagem/vídeo), data, campanha
- [ ] Busca por nome do arquivo
- [ ] Informações: dimensões, tamanho, formato, data de upload
- [ ] Uma mídia pode ser usada em múltiplas peças

#### US-072: Excluir mídia
**Como** usuário,
**quero** excluir mídias da biblioteca,
**para** liberar espaço e manter organização.

**Critérios de aceite:**
- [ ] Não permitir exclusão se mídia está vinculada a uma peça agendada
- [ ] Soft delete com possibilidade de restauração por 30 dias
- [ ] Exclusão física do storage após 30 dias
