# ADR-003: PostgreSQL com pgvector

[← Voltar ao índice](00-index.md)

---

- **Status:** Accepted
- **Data:** 2026-02-15
- **Decisores:** Equipe de arquitetura

## Contexto

O sistema precisa de um banco de dados relacional que suporte:
- Dados estruturados com integridade referencial (usuários, campanhas, conteúdos)
- Busca textual eficiente (comentários, conteúdos)
- Armazenamento de séries temporais (métricas de analytics)
- Busca semântica por similaridade (conteúdos gerados por IA, sugestões)
- Alta concorrência de leitura e escrita
- Particionamento de dados para escalabilidade

## Decisão

Adotar **PostgreSQL** como banco de dados principal com a extensão **pgvector**
para funcionalidades de busca vetorial/semântica.

### Uso do PostgreSQL

| Funcionalidade | Recurso do PostgreSQL |
|----------------|----------------------|
| Dados transacionais | ACID compliance, foreign keys |
| Busca textual | Full-text search nativo (tsvector/tsquery) |
| Dados JSON | JSONB para metadados flexíveis (social_network_overrides) |
| Séries temporais | Particionamento por range de data (tabelas de métricas) |
| UUIDs | Tipo nativo uuid, geração com gen_random_uuid() |
| Enums | Tipos enum nativos para status |
| Índices avançados | GIN (full-text), GiST (range), B-tree (padrão) |

### Uso do pgvector

| Caso de uso | Descrição |
|-------------|-----------|
| **Busca de conteúdo similar** | Encontrar peças de conteúdo similares baseado em embeddings |
| **Deduplicação inteligente** | Detectar conteúdos duplicados ou muito parecidos |
| **Sugestão de respostas** | Buscar respostas anteriores similares para sugerir ao motor de automação |
| **Classificação de comentários** | Agrupar comentários por similaridade semântica |

### Configuração pgvector

```sql
CREATE EXTENSION vector;

-- Exemplo: embedding de conteúdo para busca semântica
ALTER TABLE contents ADD COLUMN embedding vector(1536);

-- Índice para busca por similaridade (IVFFlat para performance)
CREATE INDEX ON contents
  USING ivfflat (embedding vector_cosine_ops)
  WITH (lists = 100);
```

### Estratégia de particionamento

```sql
-- Métricas de conteúdo particionadas por mês
CREATE TABLE content_metrics (
    id UUID PRIMARY KEY,
    content_id UUID NOT NULL,
    synced_at TIMESTAMPTZ NOT NULL,
    ...
) PARTITION BY RANGE (synced_at);

-- Criar partições automaticamente via job mensal
CREATE TABLE content_metrics_2026_01
    PARTITION OF content_metrics
    FOR VALUES FROM ('2026-01-01') TO ('2026-02-01');
```

### Connection pooling

- **PgBouncer** na frente do PostgreSQL
- Mode: transaction pooling
- Pool size: 20 conexões por worker (ajustável)
- Timeout: 30 segundos para aquisição de conexão

## Alternativas consideradas

### 1. MySQL 8
- **Prós:** Mais popular, amplo suporte em hospedagens, familiar para a maioria dos devs Laravel
- **Contras:** Sem extensão vetorial nativa, full-text search inferior, sem particionamento nativo tão robusto, sem tipos JSONB tão eficientes
- **Por que descartado:** Ausência de suporte a busca vetorial eliminaria funcionalidades de IA importantes

### 2. PostgreSQL + Banco vetorial separado (Pinecone/Weaviate)
- **Prós:** Bancos vetoriais dedicados são mais otimizados para busca semântica
- **Contras:** Mais um serviço para gerenciar, sincronização de dados, custo adicional, complexidade operacional
- **Por que descartado:** pgvector é suficiente para nosso volume. Se necessário, migrar para banco vetorial dedicado no futuro é viável

### 3. MongoDB
- **Prós:** Flexível, Atlas Vector Search
- **Contras:** Sem integridade referencial, Eloquent suporte limitado, complexidade em queries relacionais
- **Por que descartado:** Nosso modelo é altamente relacional; MongoDB adicionaria complexidade sem benefício claro

## Consequências

### Positivas
- Banco único para dados transacionais e vetoriais — menos complexidade operacional
- Full-text search nativo elimina necessidade de Elasticsearch para busca básica
- JSONB permite flexibilidade em metadados sem sacrificar performance
- Particionamento nativo para tabelas de alto volume (métricas)
- Suporte completo no Eloquent ORM do Laravel
- Ecossistema maduro com ferramentas de backup, replicação e monitoramento

### Negativas
- pgvector tem performance inferior a bancos vetoriais dedicados em volumes muito altos (>10M vetores)
- PostgreSQL consome mais memória que MySQL para workloads simples
- Particionamento requer gestão manual de partições (automação via job)
- Menos opções de hospedagem gerenciada barata comparado ao MySQL

### Riscos
- Volume de embeddings pode crescer além da capacidade do pgvector — monitorar e planejar migração para banco vetorial se necessário
- Particionamento de métricas pode se tornar complexo — automatizar criação de partições
