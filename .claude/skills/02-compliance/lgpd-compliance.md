# LGPD Compliance — Social Media Manager API

## Objetivo

Garantir conformidade com a **Lei Geral de Proteção de Dados (LGPD)** em toda a plataforma, desde a coleta até a exclusão de dados pessoais.

---

## Princípios da LGPD Aplicados

| Princípio | Aplicação no Projeto |
|-----------|---------------------|
| **Finalidade** | Todo dado coletado tem propósito declarado |
| **Adequação** | Dados compatíveis com a finalidade informada |
| **Necessidade** | Coleta limitada ao mínimo necessário |
| **Livre acesso** | Titular pode consultar seus dados a qualquer momento |
| **Qualidade** | Dados mantidos atualizados e corretos |
| **Transparência** | Informação clara sobre tratamento de dados |
| **Segurança** | Proteção técnica e administrativa dos dados |
| **Prevenção** | Medidas para evitar danos ao titular |
| **Não discriminação** | Dados não usados para fins discriminatórios |
| **Responsabilização** | Capacidade de demonstrar conformidade |

---

## Bases Legais Utilizadas

### Consentimento
- Comunicações de marketing.
- Geração de conteúdo por IA (o usuário inicia a ação).
- Integração com CRMs via webhooks (configurado pelo usuário).

### Execução de Contrato
- Gerenciamento de contas sociais (core do serviço).
- Agendamento e publicação de conteúdo.
- Armazenamento de mídia.
- Analytics de performance.

### Interesse Legítimo
- Logs de segurança e auditoria.
- Monitoramento de performance.
- Prevenção de fraude.

---

## Direitos do Titular — Implementação

### Acesso (GET /api/v1/profile)
- Usuário pode visualizar todos os dados pessoais armazenados.
- Inclui: nome, email, data de criação, contas conectadas.

### Correção (PUT /api/v1/profile)
- Usuário pode atualizar dados pessoais a qualquer momento.
- Alterações registradas no audit log.

### Exclusão (DELETE /api/v1/profile)
- Requer 2FA (se habilitado) + confirmação.
- Dispara fluxo de anonimização (não exclusão imediata).
- Grace period de 30 dias para cancelamento.

### Portabilidade (POST /api/v1/profile/data-export)
- Exportação assíncrona de todos os dados do usuário em JSON.
- Inclui: perfil, campanhas, conteúdos, mídias (URLs), analytics, configurações.
- Download disponível por 24 horas.
- Rate limited: 1 exportação por dia.

---

## Fluxo de Anonimização (Account Deletion)

### Contexto Multi-Tenancy

- Um usuário pode pertencer a múltiplas organizações.
- A exclusão de conta do **usuário** anonimiza seus dados pessoais, mas não afeta dados da organização.
- Dados da organização (campanhas, conteúdos, mídias) pertencem à organização, não ao usuário.
- Ao remover um usuário, seus registros de autoria (`created_by`) são anonimizados, mas os recursos permanecem na org.
- Se o usuário for o **owner** da organização e o único membro, a exclusão dispara também a exclusão da organização.

### Imediato (ao confirmar exclusão do usuário)
1. Marcar conta como `deletion_requested`.
2. Revogar todos os tokens JWT e refresh tokens.
3. Remover o usuário de todas as organizações.
4. Se for último owner de uma org: transferir ownership ou marcar org para exclusão.
5. Cancelar todos os agendamentos pendentes criados pelo usuário.

### Após 30 dias (grace period do usuário)
1. **Dados pessoais**: nome → `"Usuário Removido"`, email → hash irreversível.
2. **Autoria em recursos**: `created_by` → null (recursos permanecem na org).
3. **AI generations do usuário**: anonimizar (remover user_id, manter para melhoria do serviço).
4. **Audit logs**: manter por 2 anos com user_id anonimizado.
5. **Login history**: deletar.

### Exclusão de Organização
1. **Imediato**: destruir tokens de redes sociais, desconectar contas, cancelar agendamentos.
2. **Após 30 dias**: hard delete de campanhas, conteúdos, mídias, analytics, comentários, automações, webhooks.
3. Membros são notificados e desassociados (não deletados).

### Cancelamento da exclusão
- Dentro dos 30 dias: usuário pode fazer login e cancelar.
- Restaura acesso completo.
- Contas sociais precisam ser reconectadas (tokens já destruídos).

---

## Retenção de Dados por Propósito

| Dado | Propósito | Retenção |
|------|-----------|----------|
| Perfil do usuário | Serviço | Enquanto ativo + 30d |
| Tokens sociais | Publicação | Enquanto conectado |
| Campanhas/Conteúdos | Serviço | Enquanto ativo + 30d soft delete |
| Mídias | Serviço | Enquanto ativo + 30d soft delete |
| Analytics | Relatórios | 2 anos |
| Logs de auditoria | Segurança | 2 anos |
| Login history | Segurança | 1 ano |
| AI generations | Melhoria | 6 meses |

---

## Compartilhamento com Terceiros

### APIs de Redes Sociais (Instagram, TikTok, YouTube)
- Apenas dados necessários para publicação.
- Autenticação via OAuth (tokens do usuário).
- Dados das redes são armazenados localmente (analytics, comentários).

### API de IA (OpenAI via Prism)
- Conteúdo do tópico e parâmetros de geração.
- **Não enviar** dados pessoais do usuário (nome, email).
- Anonimizar quando possível.
- Registrar uso (tokens, modelo, custo).

### Webhooks (CRM Integration)
- Configurados explicitamente pelo usuário.
- Dados enviados são os que o usuário define.
- Webhook URL validada (HTTPS obrigatório).
- Delivery registrado para auditoria.

---

## Privacy by Design

- Criptografia de tokens sociais (AES-256-GCM).
- Senhas com bcrypt (cost 12).
- Soft delete com período de carência.
- Índices parciais excluem registros deletados.
- Dados mascarados em logs e respostas de erro.
- Consentimento registrável e revogável.

---

## Anti-Patterns

- Coletar dados sem propósito declarado.
- Retenção indefinida sem justificativa.
- Compartilhar PII com terceiros sem consentimento.
- Expor dados pessoais em logs ou mensagens de erro.
- Tornar exclusão de conta difícil ou impossível.
- Ignorar período de carência na exclusão.
- Manter tokens de redes sociais após desconexão.
