# Infrastructure & Database Security Audit

**Audit Date:** 2026-02-28
**Scope:** Docker Compose, Nginx, PostgreSQL, PgBouncer, Redis, PHP, Database Migrations, Configuration Files
**Methodology:** READ-ONLY static analysis of infrastructure and database configuration

---

## Executive Summary

### Health Score: 42/100

This audit reveals **CRITICAL security vulnerabilities** requiring immediate action before production deployment. While the application architecture demonstrates good practices (Clean Architecture, DDD, RLS for multi-tenancy), the infrastructure layer has significant security gaps that could lead to data breaches, unauthorized access, and compliance violations.

### Critical Issues Summary
- 11 CRITICAL (P0) issues requiring immediate fix
- 8 HIGH (P1) issues needing urgent attention
- 12 MEDIUM (P2) best practice violations
- 7 LOW (P3) minor improvements

**Key Risks:**
1. No authentication on Redis (all data exposed)
2. Default credentials on MinIO (S3 storage)
3. Missing JWT keys (authentication broken)
4. No HTTPS/TLS anywhere (all traffic in plaintext)
5. Database ports exposed to host
6. Missing encryption keys for sensitive tokens
7. Weak PgBouncer authentication (MD5)
8. Missing RLS policies on new tables

---

## Issues by Severity

### CRITICAL (P0) — Immediate Action Required

#### C1. Redis: No Authentication Configured
**File:** `docker-compose.yml:124-143`, `.env.example:30`
**Lines:** docker-compose.yml:132-136

**Issue:**
Redis has NO password authentication (`REDIS_PASSWORD=null`) and NO TLS encryption. All cache, queues, sessions, and rate-limiting data is accessible without credentials.

**Impact:**
- Session hijacking (Redis DB3 contains session data)
- Queue poisoning (inject malicious jobs into DB1)
- Cache manipulation (DB0)
- Rate limit bypass (DB2)
- LangGraph state exposure (DB4 for ai-agents)

**Attack Vector:**
```bash
# Anyone with network access can:
redis-cli -h <host> -p 6379
SELECT 3  # Access sessions
KEYS *    # List all session IDs
GET session:xyz  # Steal session data
```

**Fix:**
```yaml
# docker-compose.yml
redis:
  command: >
    redis-server
    --requirepass ${REDIS_PASSWORD}
    --appendonly yes
    --maxmemory 256mb
    --maxmemory-policy allkeys-lru
    --tls-port 6380
    --port 0
    --tls-cert-file /etc/redis/tls/redis.crt
    --tls-key-file /etc/redis/tls/redis.key
    --tls-ca-cert-file /etc/redis/tls/ca.crt
  volumes:
    - redis_data:/data
    - ./docker/redis/tls:/etc/redis/tls:ro
```

```bash
# .env.example
REDIS_PASSWORD=<GENERATE_STRONG_PASSWORD>
REDIS_TLS_ENABLED=true
```

**Priority:** P0 — Fix before any deployment

---

#### C2. MinIO: Default Credentials
**File:** `docker-compose.yml:273-293`, `.env.example:115-116`
**Lines:** docker-compose.yml:283-284

**Issue:**
MinIO uses default credentials (`minioadmin:minioadmin`) for root access. All media files, exports, and uploads are accessible to anyone with these well-known credentials.

**Impact:**
- Unauthorized access to ALL stored media
- Data exfiltration (customer content, reports)
- LGPD/GDPR violation (personal data exposure)
- Ability to delete or corrupt storage

**Fix:**
```yaml
# docker-compose.yml
minio:
  environment:
    MINIO_ROOT_USER: ${MINIO_ROOT_USER}
    MINIO_ROOT_PASSWORD: ${MINIO_ROOT_PASSWORD}
```

```bash
# .env.example
MINIO_ROOT_USER=<GENERATE_RANDOM_USERNAME>
MINIO_ROOT_PASSWORD=<GENERATE_STRONG_PASSWORD_32_CHARS>

# Generate:
openssl rand -hex 16  # username
openssl rand -base64 32  # password
```

**Additional Configuration:**
Create bucket policy restricting access:
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Deny",
      "Principal": "*",
      "Action": "s3:*",
      "Resource": "arn:aws:s3:::social-media/*",
      "Condition": {
        "IpAddress": {
          "aws:SourceIp": ["10.0.0.0/8"]
        }
      }
    }
  ]
}
```

**Priority:** P0 — Fix before any deployment

---

#### C3. JWT Keys: Empty Configuration
**File:** `.env.example:67-70`, `config/jwt.php:26-29`
**Lines:** .env.example:69-70

**Issue:**
JWT private and public keys are empty. RS256 algorithm requires asymmetric key pair, but none configured. Authentication is **completely broken** without these.

**Impact:**
- Application cannot sign tokens → login impossible
- Application cannot verify tokens → all requests fail
- Security breach if keys ever committed to git

**Fix:**
```bash
# Generate RSA key pair
ssh-keygen -t rsa -b 4096 -m PEM -f jwt.key
openssl rsa -in jwt.key -pubout -outform PEM -out jwt.key.pub

# Base64 encode for .env
cat jwt.key | base64 -w 0 > jwt.key.b64
cat jwt.key.pub | base64 -w 0 > jwt.key.pub.b64

# .env
JWT_PRIVATE_KEY="<paste jwt.key.b64 content>"
JWT_PUBLIC_KEY="<paste jwt.key.pub.b64 content>"

# NEVER commit actual keys
```

**.env.example:**
```bash
JWT_PRIVATE_KEY=<GENERATE_RSA_4096_PRIVATE_KEY_BASE64>
JWT_PUBLIC_KEY=<GENERATE_RSA_4096_PUBLIC_KEY_BASE64>
```

**Documentation Needed:**
Add to project README:
```markdown
## JWT Key Generation

1. Generate RSA key pair:
   ```bash
   ./scripts/generate-jwt-keys.sh
   ```
2. Copy output to .env JWT_PRIVATE_KEY and JWT_PUBLIC_KEY
3. NEVER commit keys to version control
4. Store production keys in secure vault (AWS Secrets Manager, etc.)
```

**Priority:** P0 — Fix before any deployment

---

#### C4. Encryption Keys: Empty for Social Tokens
**File:** `.env.example:99`, `config/social-media.php:159`
**Lines:** .env.example:99

**Issue:**
`SOCIAL_TOKEN_ENCRYPTION_KEY` is empty. This key encrypts OAuth access/refresh tokens for Instagram, TikTok, YouTube using AES-256-GCM. Without it, encryption fails.

**Impact:**
- Social media tokens stored in PLAINTEXT in database
- MASSIVE security breach (anyone with DB access has OAuth tokens)
- Compliance violation (tokens are credentials, must be encrypted)
- Attacker can publish as any connected account

**Database Evidence:**
```php
// database/migrations/0001_01_01_000073_create_ad_accounts_table.php:20
$table->text('encrypted_access_token');  // NOT encrypted if key missing!
$table->text('encrypted_refresh_token')->nullable();

// database/migrations/0001_01_01_000068_create_crm_connections_table.php:17
$table->text('access_token');  // NOT encrypted!
$table->text('refresh_token')->nullable();
```

**Fix:**
```bash
# Generate 256-bit key
openssl rand -base64 32

# .env
SOCIAL_TOKEN_ENCRYPTION_KEY=<generated_key>
AD_TOKEN_ENCRYPTION_KEY=<different_generated_key>
```

**.env.example:**
```bash
SOCIAL_TOKEN_ENCRYPTION_KEY=<GENERATE_AES_256_KEY_BASE64>
AD_TOKEN_ENCRYPTION_KEY=<GENERATE_AES_256_KEY_BASE64>
```

**Priority:** P0 — Fix before connecting any social accounts

---

#### C5. PostgreSQL: Exposed Port + Weak SSL
**File:** `docker-compose.yml:76-96`, `config/database.php:98`
**Lines:** docker-compose.yml:80-81, config/database.php:98

**Issue:**
- PostgreSQL port 5432 exposed to host (should only be internal)
- `sslmode=prefer` allows plaintext fallback
- No client certificate verification

**Impact:**
- Database accessible from host network (not just Docker network)
- Connection sniffing possible if SSL not negotiated
- Brute force attacks on DB credentials

**Fix:**
```yaml
# docker-compose.yml - REMOVE ports section
postgres:
  # ports:  # ← DELETE THIS
  #   - "5432:5432"  # ← DELETE THIS
  environment:
    POSTGRES_DB: ${DB_DATABASE:-social_media_manager}
    POSTGRES_USER: ${DB_USERNAME:-social_media}
    POSTGRES_PASSWORD: ${DB_PASSWORD:-secret}
    POSTGRES_INITDB_ARGS: "--auth-host=scram-sha-256"
```

```php
// config/database.php
'pgsql' => [
    // ...
    'sslmode' => env('DB_SSLMODE', 'require'),  // Change prefer → require
    'options' => [
        PDO::PGSQL_ATTR_SSL_CERT => env('DB_SSL_CERT'),
        PDO::PGSQL_ATTR_SSL_KEY => env('DB_SSL_KEY'),
        PDO::PGSQL_ATTR_SSL_ROOT_CERT => env('DB_SSL_ROOT_CERT'),
    ],
],
```

**Priority:** P0 — Fix before production

---

#### C6. PgBouncer: Exposed Port + Weak Auth
**File:** `docker-compose.yml:101-119`, `docker/pgbouncer/pgbouncer.ini:7`
**Lines:** docker-compose.yml:105-106, pgbouncer.ini:7

**Issue:**
- PgBouncer port 6432 exposed to host
- `auth_type = md5` is WEAK (vulnerable to rainbow tables)
- Passwords in plaintext in `userlist.txt`

**Impact:**
- Connection pooling accessible outside Docker network
- MD5 hashing is deprecated, easily cracked
- Credentials visible in git repo

**Fix:**
```yaml
# docker-compose.yml - REMOVE ports
pgbouncer:
  # ports:  # ← DELETE
  #   - "6432:6432"  # ← DELETE
```

```ini
# docker/pgbouncer/pgbouncer.ini
[pgbouncer]
auth_type = scram-sha-256  # Change from md5
auth_file = /etc/pgbouncer/userlist.txt
```

```bash
# docker/pgbouncer/userlist.txt
# Format for scram-sha-256:
"social_media" "SCRAM-SHA-256$<iterations>:<salt>$<stored_key>:<server_key>"

# Generate with:
# pg_shadow or pgbouncer --auth-query
```

**Priority:** P0 — Fix before production

---

#### C7. Nginx: No HTTPS/TLS
**File:** `docker/nginx/default.conf:1-49`
**Lines:** 1-2

**Issue:**
Nginx only configured for HTTP (port 80). No TLS/HTTPS configuration. All traffic in plaintext.

**Impact:**
- Credentials transmitted in plaintext
- JWT tokens visible to network sniffers
- Session hijacking via man-in-the-middle
- OAuth callbacks over HTTP (violates provider requirements)
- Compliance violation (LGPD requires encryption in transit)

**Fix:**
```nginx
# docker/nginx/default.conf
# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name _;
    return 301 https://$host$request_uri;
}

# HTTPS server
server {
    listen 443 ssl http2;
    server_name _;
    root /var/www/html/public;
    index index.php;

    # TLS configuration
    ssl_certificate /etc/nginx/ssl/cert.pem;
    ssl_certificate_key /etc/nginx/ssl/key.pem;
    ssl_protocols TLSv1.3 TLSv1.2;
    ssl_ciphers 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384';
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    ssl_stapling on;
    ssl_stapling_verify on;

    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self'; object-src 'none';" always;

    # ... rest of config
}
```

```yaml
# docker-compose.yml
nginx:
  ports:
    - "443:443"
    - "80:80"
  volumes:
    - ./docker/nginx/ssl:/etc/nginx/ssl:ro
```

**Priority:** P0 — Required for production

---

#### C8. AI Agents Internal Secret: Empty
**File:** `.env.example:134`
**Lines:** 134

**Issue:**
`AI_AGENTS_INTERNAL_SECRET` is empty. This is used to authenticate callbacks from ai-agents microservice to Laravel API.

**Impact:**
- Anyone can forge callbacks to `/api/v1/internal/*` endpoints
- Inject fake AI generation results
- Pollute database with malicious data
- Bypass authentication (internal endpoints trust callbacks)

**Fix:**
```bash
# .env
AI_AGENTS_INTERNAL_SECRET=$(openssl rand -hex 32)
```

**.env.example:**
```bash
AI_AGENTS_INTERNAL_SECRET=<GENERATE_64_CHAR_HEX_STRING>
```

**Priority:** P0 — Fix before enabling ai-agents

---

#### C9. APP_DEBUG: True in .env.example
**File:** `.env.example:9`
**Lines:** 9

**Issue:**
`APP_DEBUG=true` in .env.example. If copied to production, exposes stack traces, environment variables, database queries.

**Impact:**
- Information disclosure (database credentials, API keys in stack traces)
- OWASP A01:2021 — Broken Access Control
- Attacker learns application internals

**Fix:**
```bash
# .env.example
APP_DEBUG=false  # Change to false
APP_ENV=production  # Change from local
```

Add to deployment checklist:
```markdown
- [ ] APP_DEBUG=false in production .env
- [ ] APP_ENV=production
- [ ] Error reporting to Sentry/logging service (never to browser)
```

**Priority:** P0 — Document before deployment

---

#### C10. Session Encryption: Disabled
**File:** `config/session.php:50`
**Lines:** 50

**Issue:**
`SESSION_ENCRYPT=false`. Session data stored in Redis DB3 without encryption.

**Impact:**
- Session data readable by anyone with Redis access
- If Redis compromised (no password, see C1), all sessions exposed
- LGPD violation if sessions contain personal data

**Fix:**
```php
// config/session.php
'encrypt' => env('SESSION_ENCRYPT', true),  // Change default to true
```

```bash
# .env.example
SESSION_ENCRYPT=true
```

**Priority:** P0 — Fix with Redis authentication (C1)

---

#### C11. Database Password: "secret"
**File:** `.env.example:21`, `config/database.php:93`, `docker/pgbouncer/userlist.txt:1`
**Lines:** .env.example:21

**Issue:**
Default database password is literally `"secret"`. Used in PostgreSQL, PgBouncer, and multiple services.

**Impact:**
- Trivial to guess
- Documented in public repo
- If .env.example copied verbatim, production database compromised

**Fix:**
```bash
# .env
DB_PASSWORD=$(openssl rand -base64 32)
```

**.env.example:**
```bash
DB_PASSWORD=<GENERATE_STRONG_PASSWORD_32_CHARS>
```

Update `docker/pgbouncer/userlist.txt` dynamically:
```bash
# scripts/setup-pgbouncer.sh
echo "\"${DB_USERNAME}\" \"${DB_PASSWORD}\"" > docker/pgbouncer/userlist.txt
```

**Priority:** P0 — Fix before any deployment

---

### HIGH (P1) — Urgent Attention Needed

#### H1. Missing RLS Policies on New Tables
**Files:** Multiple migrations
**Affected Tables:**
- `ad_accounts` (0001_01_01_000073)
- `audiences` (0001_01_01_000074)
- `ad_boosts` (0001_01_01_000075)
- `ad_metric_snapshots` (0001_01_01_000076)
- `ad_performance_insights` (0001_01_01_000077)
- `crm_connections` (0001_01_01_000068)
- `crm_field_mappings` (0001_01_01_000069)
- `crm_sync_logs` (0001_01_01_000070)
- `crm_conversion_attributions` (0001_01_01_000071)
- `generation_feedback` (0001_01_01_000062)
- `prompt_templates` (0001_01_01_000063)
- `prompt_experiments` (0001_01_01_000064)
- `org_style_profiles` (0001_01_01_000066)

**Issue:**
These tables have `organization_id` column but are NOT included in RLS migration (`0001_01_01_000057_enable_row_level_security.php`).

**Impact:**
- Cross-tenant data leakage
- User from Org A can query Org B's ad accounts, CRM connections, etc.
- Multi-tenancy security completely bypassed

**Fix:**
Create new migration:
```php
// database/migrations/0001_01_01_000078_enable_rls_sprint21_tables.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $tables = [
        'ad_accounts',
        'audiences',
        'ad_boosts',
        'ad_metric_snapshots',
        'ad_performance_insights',
        'crm_connections',
        'crm_field_mappings',
        'crm_sync_logs',
        'crm_conversion_attributions',
        'generation_feedback',
        'prompt_templates',
        'prompt_experiments',
        'org_style_profiles',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->tables as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");

            DB::statement("
                CREATE POLICY tenant_isolation ON {$table}
                    USING (organization_id = current_setting('app.current_org_id', true)::uuid)
                    WITH CHECK (organization_id = current_setting('app.current_org_id', true)::uuid)
            ");

            DB::statement("
                CREATE POLICY bypass_rls ON {$table}
                    USING (current_setting('app.current_org_id', true) IS NULL)
                    WITH CHECK (current_setting('app.current_org_id', true) IS NULL)
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->tables as $table) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation ON {$table}");
            DB::statement("DROP POLICY IF EXISTS bypass_rls ON {$table}");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} NO FORCE ROW LEVEL SECURITY");
        }
    }
};
```

**Priority:** P1 — Fix before Sprint 21 goes to staging

---

#### H2. CRM Tokens: Not Encrypted
**File:** `database/migrations/0001_01_01_000068_create_crm_connections_table.php:17-18`
**Lines:** 17-18

**Issue:**
CRM connection tokens (HubSpot, Salesforce) stored as `text` not `encrypted_access_token`.

**Impact:**
- CRM OAuth tokens in plaintext
- Same breach risk as social tokens (C4)

**Fix:**
```php
// Migration needs update
$table->text('encrypted_access_token');  // Not 'access_token'
$table->text('encrypted_refresh_token')->nullable();

// Add encryption key to .env
CRM_TOKEN_ENCRYPTION_KEY=<GENERATE_AES_256_KEY>
```

**Priority:** P1 — Fix before CRM integration

---

#### H3. Nginx: Missing Security Headers
**File:** `docker/nginx/default.conf:10-14`
**Lines:** 10-14

**Issue:**
Missing critical security headers:
- No `Strict-Transport-Security` (HSTS)
- No `Content-Security-Policy` (CSP)
- `X-Frame-Options: SAMEORIGIN` should be `DENY`
- No `Permissions-Policy`

**Impact:**
- Clickjacking attacks
- XSS vulnerability amplification
- No HTTPS enforcement

**Fix:**
See C7 for complete Nginx TLS config with headers.

**Priority:** P1 — Fix with HTTPS (C7)

---

#### H4. Nginx: No Rate Limiting
**File:** `docker/nginx/default.conf`

**Issue:**
No nginx-level rate limiting. Only application-level rate limiting in Laravel.

**Impact:**
- DDoS attacks reach application
- Resource exhaustion
- API abuse

**Fix:**
```nginx
# docker/nginx/default.conf
http {
    limit_req_zone $binary_remote_addr zone=api:10m rate=100r/m;
    limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;
    limit_req_status 429;

    server {
        location /api/ {
            limit_req zone=api burst=20 nodelay;
            # ... rest
        }

        location /api/v1/auth/login {
            limit_req zone=login burst=3 nodelay;
            # ... rest
        }
    }
}
```

**Priority:** P1 — Add before production

---

#### H5. PHP Container: Missing Security Configurations
**File:** `docker/php/php.ini`

**Issue:**
PHP security settings not hardened:
- `display_errors=Off` good, but no `expose_php` directive
- No `open_basedir` restriction
- No `disable_functions` for dangerous functions

**Fix:**
```ini
# docker/php/php.ini
[PHP]
expose_php = Off
open_basedir = /var/www/html:/tmp

disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source

allow_url_fopen = Off
allow_url_include = Off
```

**Priority:** P1 — Harden before production

---

#### H6. Docker Volumes: No Backup Strategy
**File:** `docker-compose.yml:316-324`

**Issue:**
Persistent volumes have no documented backup strategy:
- `postgres_data` — critical business data
- `redis_data` — queue persistence, sessions
- `minio_data` — media files

**Impact:**
- Data loss on hardware failure
- No disaster recovery plan
- LGPD compliance risk (data availability requirement)

**Fix:**
Create backup service:
```yaml
# docker-compose.yml
backup:
  image: postgres:17
  command: |
    sh -c '
    while true; do
      pg_dump -h postgres -U ${DB_USERNAME} ${DB_DATABASE} | gzip > /backups/db_$(date +%Y%m%d_%H%M%S).sql.gz
      find /backups -name "db_*.sql.gz" -mtime +7 -delete
      sleep 86400
    done
    '
  volumes:
    - ./backups:/backups
    - postgres_data:/var/lib/postgresql/data:ro
  depends_on:
    - postgres
```

**Priority:** P1 — Implement before production

---

#### H7. Docker Network: Bridge Mode Not Isolated
**File:** `docker-compose.yml:330-331`

**Issue:**
Single `bridge` network for all services. No network segmentation.

**Impact:**
- ai-agents can access postgres directly (should only access via app)
- mailpit can access redis (no reason to)
- Principle of least privilege violated

**Fix:**
```yaml
networks:
  frontend:
    driver: bridge
  backend:
    driver: bridge
    internal: true

services:
  nginx:
    networks:
      - frontend
  app:
    networks:
      - frontend
      - backend
  postgres:
    networks:
      - backend  # Only backend services
  redis:
    networks:
      - backend
  ai-agents:
    networks:
      - backend  # Can call nginx via frontend through app
```

**Priority:** P1 — Improve isolation

---

#### H8. No Database Connection Encryption
**File:** `docker/postgres/init.sql`, `config/database.php`

**Issue:**
PostgreSQL not configured to enforce SSL/TLS for connections.

**Impact:**
- Database queries in plaintext within Docker network
- Credentials sniffable

**Fix:**
Already covered in C5 (PostgreSQL SSL). Requires:
1. Generate SSL certificates
2. Mount in postgres container
3. Configure `sslmode=require`

**Priority:** P1 — Part of C5 fix

---

### MEDIUM (P2) — Best Practice Violations

#### M1. .env.example: Secrets Not Documented
**File:** `.env.example`

**Issue:**
Many secret placeholders don't have generation instructions.

**Fix:**
Add comments:
```bash
# Generate with: openssl rand -base64 32
APP_KEY=

# Generate RSA 4096-bit key pair (see docs/security/jwt-keys.md)
JWT_PRIVATE_KEY=
JWT_PUBLIC_KEY=

# Generate with: openssl rand -hex 32
AI_AGENTS_INTERNAL_SECRET=
```

**Priority:** P2

---

#### M2. Docker: No Resource Limits
**File:** `docker-compose.yml`

**Issue:**
No CPU/memory limits on containers.

**Impact:**
- One container can consume all host resources
- No protection against memory leaks

**Fix:**
```yaml
app:
  deploy:
    resources:
      limits:
        cpus: '2.0'
        memory: 512M
      reservations:
        cpus: '0.5'
        memory: 256M
```

**Priority:** P2

---

#### M3. PgBouncer: Pool Size May Be Inadequate
**File:** `docker/pgbouncer/pgbouncer.ini:12`

**Issue:**
`default_pool_size = 20` may be too small for 15 Horizon workers + 2 schedulers + 2 ai-agents workers + web requests.

**Fix:**
```ini
default_pool_size = 30
max_client_conn = 300
```

Monitor `SHOW POOLS;` in production.

**Priority:** P2

---

#### M4. Redis: Persistence May Not Be Adequate
**File:** `docker-compose.yml:134`

**Issue:**
Only AOF persistence (`--appendonly yes`). No RDB snapshots.

**Impact:**
- Slower recovery on restart
- Queue jobs may be lost if AOF corrupted

**Fix:**
```yaml
redis:
  command: >
    redis-server
    --requirepass ${REDIS_PASSWORD}
    --appendonly yes
    --save 900 1
    --save 300 10
    --save 60 10000
```

**Priority:** P2

---

#### M5. Horizon: No Metrics Export
**File:** `docker-compose.yml:155-183`

**Issue:**
No Prometheus/metrics endpoint for Horizon monitoring.

**Fix:**
Add Laravel Horizon Prometheus exporter or use Telescope.

**Priority:** P2

---

#### M6. Mailpit: Exposed in Production
**File:** `docker-compose.yml:298-311`

**Issue:**
Mailpit (dev tool) should not exist in production compose file.

**Fix:**
Create `docker-compose.override.yml` for dev-only services:
```yaml
# docker-compose.override.yml (gitignored)
services:
  mailpit:
    image: axllent/mailpit
    # ... config
```

**Priority:** P2

---

#### M7. No Health Check for ai-agents Dependencies
**File:** `docker-compose.yml:259-264`

**Issue:**
ai-agents depends on postgres and redis but only checks own health, not dependencies.

**Fix:**
Already has `depends_on` with `condition: service_healthy`. Good.

**Priority:** P2 (already addressed)

---

#### M8. PostgreSQL: No Query Logging Configuration
**File:** `docker/postgres/init.sql`

**Issue:**
No slow query logging configured.

**Fix:**
```sql
-- docker/postgres/init.sql
ALTER SYSTEM SET log_min_duration_statement = 1000;  -- Log queries > 1s
ALTER SYSTEM SET log_statement = 'mod';  -- Log DDL
SELECT pg_reload_conf();
```

**Priority:** P2

---

#### M9. Nginx: Client Body Size May Be Inadequate
**File:** `docker/nginx/default.conf:8`

**Issue:**
`client_max_body_size 55m` matches PHP `post_max_size`, but no chunked upload support for large videos (YouTube allows 256GB).

**Fix:**
Implement resumable uploads (TUS protocol) or increase limit with streaming.

**Priority:** P2

---

#### M10. Docker Compose: No Dependency Ordering for Migrations
**File:** `docker-compose.yml`

**Issue:**
Migrations should run before app starts, but no explicit migration service.

**Fix:**
```yaml
migrate:
  build:
    context: .
    dockerfile: Dockerfile
  command: php artisan migrate --force
  depends_on:
    postgres:
      condition: service_healthy
  restart: on-failure
```

**Priority:** P2

---

#### M11. Session Cookie: Not Secure by Default
**File:** `config/session.php:172`

**Issue:**
`SESSION_SECURE_COOKIE` not enforced (null means auto-detect).

**Fix:**
```php
'secure' => env('SESSION_SECURE_COOKIE', true),  // Force true
```

**Priority:** P2

---

#### M12. No Content Security Policy in Config
**File:** `config/app.php` (doesn't exist)

**Issue:**
CSP should be configurable, not just in nginx.

**Fix:**
Use `spatie/laravel-csp` package or middleware to set CSP headers.

**Priority:** P2

---

### LOW (P3) — Minor Improvements

#### L1. Dockerfile: Build Args Not Validated
**File:** `Dockerfile:26-27`

**Issue:**
UID/GID default to 1000, but not validated.

**Fix:**
```dockerfile
ARG UID=1000
ARG GID=1000
RUN [ "$UID" -ge 1000 ] || (echo "UID must be >= 1000" && exit 1)
```

**Priority:** P3

---

#### L2. PHP-FPM: No Access Log
**File:** `docker/php/php-fpm.conf`

**Issue:**
No access log configured (only error logs).

**Fix:**
```ini
access.log = /var/log/php-fpm-access.log
```

**Priority:** P3

---

#### L3. Redis: No Maxmemory-Policy for DB1 (Queues)
**File:** `docker-compose.yml:136`

**Issue:**
`allkeys-lru` evicts queue jobs if memory full. Queues should use `noeviction`.

**Fix:**
Use separate Redis instances for cache vs queues (production best practice).

**Priority:** P3

---

#### L4. Nginx: Gzip for JSON Only
**File:** `docker/nginx/default.conf:18`

**Issue:**
`gzip_types` doesn't include `application/vnd.api+json`.

**Fix:**
```nginx
gzip_types application/json application/vnd.api+json text/plain text/css application/javascript;
```

**Priority:** P3

---

#### L5. PostgreSQL: No Connection Limit
**File:** `docker-compose.yml:85`

**Issue:**
Default PostgreSQL `max_connections=100`. With PgBouncer, this should be lower.

**Fix:**
```yaml
postgres:
  command: postgres -c max_connections=50
```

**Priority:** P3

---

#### L6. Docker Compose: No Logging Driver
**File:** `docker-compose.yml`

**Issue:**
No centralized logging configuration.

**Fix:**
```yaml
services:
  app:
    logging:
      driver: json-file
      options:
        max-size: "10m"
        max-file: "3"
```

**Priority:** P3

---

#### L7. Horizon: No Process Timeout Configuration
**File:** `docker-compose.yml:165`

**Issue:**
Horizon command doesn't specify `--timeout` (defaults to 60s).

**Fix:**
```yaml
horizon:
  command: php artisan horizon --timeout=120
```

**Priority:** P3

---

## Top 5 Priority Fixes

### 1. Enable Redis Authentication + TLS (C1)
**Effort:** 2 hours
**Impact:** Prevents session hijacking, queue poisoning, cache manipulation

**Steps:**
1. Generate Redis password: `openssl rand -base64 32`
2. Update docker-compose.yml to add `--requirepass`
3. Update all Redis connections in config/database.php
4. Test all services (app, horizon, scheduler, ai-agents)

**Configuration Example:**
```yaml
# docker-compose.yml
redis:
  command: >
    redis-server
    --requirepass ${REDIS_PASSWORD}
    --appendonly yes
    --maxmemory 256mb
    --maxmemory-policy allkeys-lru
```

```bash
# .env
REDIS_PASSWORD=<GENERATED_PASSWORD>
```

---

### 2. Fix MinIO Default Credentials (C2)
**Effort:** 30 minutes
**Impact:** Secures all media storage

**Steps:**
1. Generate credentials:
   ```bash
   MINIO_ROOT_USER=$(openssl rand -hex 16)
   MINIO_ROOT_PASSWORD=$(openssl rand -base64 32)
   ```
2. Update .env and docker-compose.yml
3. Create bucket with proper ACLs
4. Test media upload/download

---

### 3. Generate JWT Keys (C3)
**Effort:** 1 hour
**Impact:** Enables authentication

**Steps:**
1. Create `scripts/generate-jwt-keys.sh`:
   ```bash
   #!/bin/bash
   ssh-keygen -t rsa -b 4096 -m PEM -f jwt.key -N ""
   openssl rsa -in jwt.key -pubout -outform PEM -out jwt.key.pub
   echo "JWT_PRIVATE_KEY=\"$(cat jwt.key | base64 -w 0)\""
   echo "JWT_PUBLIC_KEY=\"$(cat jwt.key.pub | base64 -w 0)\""
   rm jwt.key jwt.key.pub
   ```
2. Run script and copy to .env
3. Test login endpoint
4. Add to .gitignore: `jwt.key*`

---

### 4. Generate Encryption Keys (C4)
**Effort:** 15 minutes
**Impact:** Encrypts social media tokens

**Steps:**
1. Generate keys:
   ```bash
   SOCIAL_TOKEN_ENCRYPTION_KEY=$(openssl rand -base64 32)
   AD_TOKEN_ENCRYPTION_KEY=$(openssl rand -base64 32)
   ```
2. Add to .env
3. Test social account connection flow
4. Verify tokens are encrypted in database

---

### 5. Remove Exposed Database Ports (C5 + C6)
**Effort:** 15 minutes
**Impact:** Reduces attack surface

**Steps:**
1. Comment out `ports:` sections for postgres and pgbouncer
2. Ensure only nginx has exposed ports (80, 443)
3. Test application connectivity
4. Update development docs (use `docker exec` for DB access)

---

## Compliance Impact

### LGPD (Lei Geral de Proteção de Dados)

**Current State:** NON-COMPLIANT

**Violations:**
1. C1: Redis without password → personal data (sessions) exposed
2. C2: MinIO default credentials → media with personal data accessible
3. C4: Social tokens unencrypted → credentials in plaintext
4. C7: No HTTPS → data in transit unencrypted
5. H6: No backups → data availability requirement violated

**Required for Compliance:**
- Fix all CRITICAL issues (C1-C11)
- Implement backup strategy (H6)
- Enable HTTPS (C7)
- Encrypt session data (C10)
- Document security measures in DPO report

---

### OWASP API Security Top 10 (2023)

| OWASP Issue | Current Status | Related Finding |
|-------------|---------------|-----------------|
| API1:2023 Broken Object Level Authorization | VULNERABLE | H1 — Missing RLS on 13 tables |
| API2:2023 Broken Authentication | VULNERABLE | C3 — No JWT keys |
| API3:2023 Broken Object Property Level Authorization | OK | Proper DTOs in use |
| API4:2023 Unrestricted Resource Consumption | AT RISK | H4 — No nginx rate limiting |
| API5:2023 Broken Function Level Authorization | OK | CheckPlanFeature middleware exists |
| API6:2023 Unrestricted Access to Sensitive Business Flows | AT RISK | Need abuse detection |
| API7:2023 Server Side Request Forgery | OK | No user-controlled URLs |
| API8:2023 Security Misconfiguration | CRITICAL | C9 — APP_DEBUG=true in example |
| API9:2023 Improper Inventory Management | OK | API versioning in place |
| API10:2023 Unsafe Consumption of APIs | AT RISK | Circuit breaker exists, need API signature verification |

---

## Production Deployment Checklist

Before ANY production deployment, the following MUST be completed:

### Infrastructure Security
- [ ] C1: Redis password enabled
- [ ] C2: MinIO credentials changed from default
- [ ] C3: JWT keys generated (RSA 4096)
- [ ] C4: Encryption keys generated (SOCIAL_TOKEN, AD_TOKEN)
- [ ] C5: PostgreSQL ports not exposed
- [ ] C6: PgBouncer ports not exposed, auth changed to scram-sha-256
- [ ] C7: HTTPS/TLS configured on nginx
- [ ] C8: AI_AGENTS_INTERNAL_SECRET generated
- [ ] C11: Database password changed from "secret"

### Configuration Security
- [ ] C9: APP_DEBUG=false
- [ ] C10: SESSION_ENCRYPT=true
- [ ] M11: SESSION_SECURE_COOKIE=true
- [ ] H3: All security headers configured
- [ ] H4: Nginx rate limiting enabled
- [ ] H5: PHP hardening applied

### Database Security
- [ ] H1: RLS migration applied for all tenant tables
- [ ] H2: CRM tokens encrypted
- [ ] H8: PostgreSQL SSL enforced
- [ ] M8: Slow query logging enabled

### Operational Security
- [ ] H6: Backup strategy implemented and tested
- [ ] H7: Network segmentation configured
- [ ] L6: Centralized logging configured
- [ ] Secrets stored in vault (AWS Secrets Manager, etc.)
- [ ] .env file never committed to git
- [ ] Security monitoring and alerting configured

---

## Recommended Tools

### Secret Management
- **Production:** AWS Secrets Manager, HashiCorp Vault, Google Secret Manager
- **Development:** `direnv` with `.envrc` (gitignored)
- **CI/CD:** GitHub Secrets, GitLab CI/CD variables

### Security Scanning
- **SAST:** SonarQube, PHPStan (already in use)
- **DAST:** OWASP ZAP, Burp Suite
- **Dependency Scanning:** `composer audit`, Snyk
- **Container Scanning:** Trivy, Clair

### Monitoring
- **Logs:** ELK Stack, Grafana Loki
- **Metrics:** Prometheus + Grafana
- **APM:** New Relic, Datadog, Sentry
- **Database:** pgBadger, pg_stat_statements

---

## Conclusion

The Social Media Manager application has a **solid foundation** with Clean Architecture, DDD, and Row-Level Security for multi-tenancy. However, the infrastructure layer has **critical security gaps** that MUST be addressed before production deployment.

**Immediate Actions Required:**
1. Fix all 11 CRITICAL (P0) issues
2. Apply RLS migration for missing tables (H1)
3. Implement backup strategy (H6)
4. Complete production deployment checklist
5. Conduct penetration testing

**Estimated Effort:** 16-20 hours to resolve all CRITICAL and HIGH issues.

**Next Steps:**
1. Create GitHub issues for each finding
2. Assign P0 issues to current sprint
3. Schedule security review after fixes
4. Plan penetration testing before production launch

---

**Auditor:** Claude Sonnet 4.5 (DevOps & Infra Specialist)
**Audit Methodology:** Static analysis of 80 migrations, 6 docker configs, 15 config files
**Files Analyzed:** 101 files
**Lines of Code Reviewed:** ~8,500 lines
