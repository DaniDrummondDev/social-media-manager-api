# Media Management — Social Media Manager API

## Objetivo

Definir as regras de domínio para **upload**, **validação**, **scan de segurança** e **compatibilidade** de mídias.

---

## Conceito

### Media (Aggregate Root)

Representa um arquivo de mídia (imagem ou vídeo) enviado pelo usuário, com metadados, scan de segurança e cálculo de compatibilidade por rede.

---

## Campos

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | UUID | Identificador |
| `organization_id` | UUID | Organização (tenant) |
| `file_name` | string | Nome gerado (UUID-based) |
| `original_name` | string | Nome original do upload |
| `mime_type` | enum | image/jpeg, image/png, image/webp, image/gif, video/mp4, video/quicktime |
| `file_size` | integer | Bytes |
| `width` | integer | Pixels |
| `height` | integer | Pixels |
| `duration_seconds` | integer | Duração (vídeo) ou null (imagem) |
| `thumbnail_url` | string | URL da thumbnail gerada |
| `url` | string | URL do arquivo original |
| `checksum` | string | SHA-256 do arquivo |
| `scan_status` | enum | pending, clean, rejected |
| `scanned_at` | datetime | Quando foi escaneado |
| `compatibility` | JSON | Compatibilidade por rede |
| `usage_count` | integer | Quantos conteúdos usam esta mídia |
| `created_at` | datetime | Upload timestamp |
| `deleted_at` | datetime | Soft delete |

---

## Formatos Aceitos

### Imagens

| Formato | MIME Type | Tamanho Máximo |
|---------|-----------|---------------|
| JPEG | image/jpeg | 10 MB |
| PNG | image/png | 10 MB |
| WebP | image/webp | 10 MB |
| GIF | image/gif | 10 MB |

### Vídeos

| Formato | MIME Type | Tamanho Máximo |
|---------|-----------|---------------|
| MP4 | video/mp4 | 500 MB |
| MOV | video/quicktime | 500 MB |

---

## Fluxo de Upload

```
1. POST /api/v1/media (multipart/form-data)
2. Validar formato e tamanho
3. Gerar nome único (UUID-based)
4. Extrair metadados (dimensões, duração)
5. Calcular checksum (SHA-256)
6. Upload para object storage
7. Gerar thumbnail
8. Calcular compatibilidade por rede
9. Salvar registro no banco (scan_status: pending)
10. Dispatch ScanMediaJob
11. Retornar Media com status pending
```

---

## Scan de Segurança

### Fluxo

```
MediaUploaded event → ScanMediaJob (fila: media-processing)
                            ↓
                   Executar scan (antivírus, content moderation)
                            ↓
              scan_status: clean → mídia disponível para uso
              scan_status: rejected → mídia bloqueada
```

### Regras

- **RN-MED-01**: Mídia com `scan_status: pending` não pode ser associada a conteúdo.
- **RN-MED-02**: Mídia com `scan_status: rejected` é excluída automaticamente.
- **RN-MED-03**: Scan deve completar em até 5 minutos (timeout → retry).
- **RN-MED-04**: Mídia só é utilizável quando `scan_status = clean`.

---

## Compatibilidade por Rede

### Cálculo

Baseado em tipo, dimensões e duração vs limites de cada rede:

```json
{
  "instagram_feed": true,
  "instagram_story": true,
  "instagram_reel": false,
  "tiktok": false,
  "youtube": false,
  "youtube_short": false,
  "youtube_thumbnail": true
}
```

### Regras por Rede

| Rede | Tipo | Dimensões | Duração |
|------|------|-----------|---------|
| Instagram Feed | Imagem/Vídeo | 1:1, 4:5, 16:9 | Vídeo ≤ 60s |
| Instagram Story | Imagem/Vídeo | 9:16 | Vídeo ≤ 15s |
| Instagram Reel | Vídeo | 9:16 | 15-90s |
| TikTok | Vídeo | 9:16 | 15-180s |
| YouTube | Vídeo | 16:9 | ≤ 12h |
| YouTube Short | Vídeo | 9:16 | ≤ 60s |
| YouTube Thumbnail | Imagem | 16:9, ≥ 1280x720 | — |

### Regras

- **RN-MED-05**: Compatibilidade é calculada automaticamente no upload.
- **RN-MED-06**: Ao agendar publicação, verificar compatibilidade com a rede destino.
- **RN-MED-07**: Compatibilidade é recalculada se metadados mudarem (futuro: crop/resize).

---

## Associação com Conteúdo

### Regras

- **RN-MED-08**: Um conteúdo pode ter múltiplas mídias (carousel Instagram).
- **RN-MED-09**: Uma mídia pode ser usada em múltiplos conteúdos (`usage_count` tracked).
- **RN-MED-10**: Mídia vinculada a conteúdo com agendamento pendente não pode ser excluída (HTTP 409).
- **RN-MED-11**: Ao excluir conteúdo, `usage_count` é decrementado.

---

## Soft Delete e Storage

### Exclusão

```
DELETE /api/v1/media/{id}
       ↓
Verificar se vinculada a conteúdo agendado (se sim → 409)
       ↓
Soft delete (deleted_at = now())
       ↓
Após 30 dias: PurgeExpiredRecordsJob
       ↓
Hard delete: remover arquivo + thumbnail do storage + registro do banco
```

### Restauração

```
POST /api/v1/media/{id}/restore
       ↓
Verificar se dentro do grace period (30 dias)
       ↓
Se expirado → 410 Gone
Se válido → restaurar (deleted_at = null)
```

---

## Storage

### Estrutura

```
storage/
├── media/
│   ├── {uuid}.jpg       (arquivo original)
│   ├── {uuid}.mp4
│   └── ...
└── thumbnails/
    ├── {uuid}.jpg       (thumbnail gerada)
    └── ...
```

### Regras

- **RN-MED-12**: Thumbnails são geradas automaticamente no upload.
- **RN-MED-13**: Para vídeos, thumbnail é o primeiro frame.
- **RN-MED-14**: URLs são assinadas com tempo de expiração (presigned URLs).
- **RN-MED-15**: Checksum permite detectar duplicatas (informativo, não bloqueia).

---

## Quota de Armazenamento

Response da listagem inclui `storage_used`:

```json
{
  "storage_used": {
    "total_bytes": 15245000,
    "images_bytes": 245000,
    "videos_bytes": 15000000,
    "total_files": 2
  }
}
```

---

## Domain Events

| Evento | Quando | Dados |
|--------|--------|-------|
| `MediaUploaded` | Upload concluído | media_id, mime_type, file_size |
| `MediaScanned` | Scan finalizado | media_id, scan_status |
| `MediaDeleted` | Soft delete | media_id, organization_id |

---

## Anti-Patterns

- Upload síncrono de scan (bloqueia response).
- Permitir uso de mídia com scan pendente.
- Excluir mídia vinculada a agendamento pendente.
- Arquivo no storage sem registro no banco (orphan files).
- Registro no banco sem arquivo no storage (referência quebrada).
- Thumbnail não gerada (listagem sem preview).
- URLs sem expiração (acesso público permanente).
