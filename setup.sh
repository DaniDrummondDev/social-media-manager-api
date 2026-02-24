#!/bin/bash
###############################################################################
# setup.sh — Bootstrap do ambiente de desenvolvimento
#
# Uso: ./setup.sh
#
# Este script configura o ambiente completo do Social Media Manager:
#   1. Copia .env
#   2. Build e sobe containers
#   3. Instala dependências PHP
#   4. Gera chaves (APP_KEY + JWT RS256)
#   5. Executa migrations e seeds
#   6. Cria bucket no MinIO
#   7. Instala Horizon
#   8. Roda testes de arquitetura
#   9. Health check
###############################################################################

set -euo pipefail

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_step() {
    echo -e "\n${BLUE}[$(date +'%H:%M:%S')]${NC} ${GREEN}▸ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}  ⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}  ✗ $1${NC}"
}

print_success() {
    echo -e "${GREEN}  ✓ $1${NC}"
}

# ---------------------------------------------------------------------------
# 1. Environment file
# ---------------------------------------------------------------------------
print_step "Configurando .env..."

if [ ! -f .env ]; then
    cp .env.example .env
    print_success ".env criado a partir de .env.example"
else
    print_warning ".env já existe, mantendo arquivo atual"
fi

# ---------------------------------------------------------------------------
# 2. Build containers
# ---------------------------------------------------------------------------
print_step "Fazendo build dos containers..."

docker compose build --no-cache

# ---------------------------------------------------------------------------
# 3. Start containers
# ---------------------------------------------------------------------------
print_step "Subindo containers..."

docker compose up -d

# ---------------------------------------------------------------------------
# 4. Wait for services
# ---------------------------------------------------------------------------
print_step "Aguardando serviços ficarem saudáveis..."

echo -n "  PostgreSQL"
until docker compose exec -T postgres pg_isready -U social_media -d social_media_manager > /dev/null 2>&1; do
    echo -n "."
    sleep 2
done
echo -e " ${GREEN}OK${NC}"

echo -n "  Redis"
until docker compose exec -T redis redis-cli ping > /dev/null 2>&1; do
    echo -n "."
    sleep 2
done
echo -e " ${GREEN}OK${NC}"

echo -n "  MinIO"
until docker compose exec -T minio mc ready local > /dev/null 2>&1; do
    echo -n "."
    sleep 3
done
echo -e " ${GREEN}OK${NC}"

# ---------------------------------------------------------------------------
# 5. Install PHP dependencies
# ---------------------------------------------------------------------------
print_step "Instalando dependências PHP..."

docker compose exec -T app composer install --no-interaction --prefer-dist

# ---------------------------------------------------------------------------
# 6. Generate keys
# ---------------------------------------------------------------------------
print_step "Gerando chaves da aplicação..."

docker compose exec -T app php artisan key:generate
print_success "APP_KEY gerada"

print_step "Gerando chaves JWT (RS256)..."

docker compose exec -T app php artisan jwt:generate-keys
print_success "JWT keypair RS256 gerada"

# ---------------------------------------------------------------------------
# 7. Database
# ---------------------------------------------------------------------------
print_step "Executando migrations..."

docker compose exec -T app php artisan migrate --force
print_success "Migrations executadas"

print_step "Executando seeds..."

docker compose exec -T app php artisan db:seed --force
print_success "Seeds executados"

# ---------------------------------------------------------------------------
# 8. MinIO bucket
# ---------------------------------------------------------------------------
print_step "Criando bucket no MinIO..."

docker compose exec -T minio mc alias set local http://localhost:9000 minioadmin minioadmin > /dev/null 2>&1
docker compose exec -T minio mc mb local/social-media --ignore-existing > /dev/null 2>&1
print_success "Bucket 'social-media' criado"

# ---------------------------------------------------------------------------
# 9. Horizon
# ---------------------------------------------------------------------------
print_step "Instalando Horizon..."

docker compose exec -T app php artisan horizon:install
print_success "Horizon instalado"

# ---------------------------------------------------------------------------
# 10. Architecture tests
# ---------------------------------------------------------------------------
print_step "Rodando testes de arquitetura..."

if docker compose exec -T app php artisan test --filter=Architecture 2>/dev/null; then
    print_success "Testes de arquitetura passaram"
else
    print_warning "Testes de arquitetura falharam (esperado se ainda não foram criados)"
fi

# ---------------------------------------------------------------------------
# 11. Health check
# ---------------------------------------------------------------------------
print_step "Verificando health check..."

sleep 3

if curl -sf http://localhost:${APP_PORT:-8080}/api/health > /dev/null 2>&1; then
    print_success "Health check OK"
else
    print_warning "Health check não disponível (endpoint ainda não implementado)"
fi

# ---------------------------------------------------------------------------
# Summary
# ---------------------------------------------------------------------------
echo ""
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  Social Media Manager — Ambiente Pronto!${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo ""
echo -e "  ${BLUE}Aplicação:${NC}     http://localhost:${APP_PORT:-8080}"
echo -e "  ${BLUE}Mailpit:${NC}       http://localhost:8025"
echo -e "  ${BLUE}MinIO Console:${NC} http://localhost:9001"
echo -e "  ${BLUE}PostgreSQL:${NC}    localhost:5432"
echo -e "  ${BLUE}PgBouncer:${NC}     localhost:6432"
echo -e "  ${BLUE}Redis:${NC}         localhost:6379"
echo ""
echo -e "  ${YELLOW}Horizon:${NC}       docker compose logs -f horizon"
echo -e "  ${YELLOW}Scheduler:${NC}    docker compose logs -f scheduler"
echo ""
echo -e "  ${BLUE}Comandos úteis:${NC}"
echo -e "    docker compose exec app php artisan tinker"
echo -e "    docker compose exec app php artisan test"
echo -e "    docker compose logs -f"
echo ""
