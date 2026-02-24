-- PostgreSQL initialization script
-- Executed on first container start only

-- pgvector: embedding storage for Content DNA, AI Learning, RAG
CREATE EXTENSION IF NOT EXISTS vector;

-- uuid-ossp: UUID generation functions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- pg_trgm: trigram-based text search (fuzzy matching)
CREATE EXTENSION IF NOT EXISTS pg_trgm;
