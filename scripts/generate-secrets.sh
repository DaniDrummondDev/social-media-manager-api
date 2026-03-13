#!/bin/bash
###############################################################################
# Social Media Manager — Secret Generation Script
#
# This script generates all required secrets for production deployment.
# Run this script once during initial setup, then store secrets securely.
#
# Usage:
#   chmod +x scripts/generate-secrets.sh
#   ./scripts/generate-secrets.sh > .env.secrets
#
# Then copy the values to your .env file or secrets manager.
###############################################################################

set -e

echo "###############################################################################"
echo "# Social Media Manager — Generated Secrets"
echo "# Generated at: $(date -Iseconds)"
echo "# SECURITY: Store these secrets securely and never commit to version control"
echo "###############################################################################"
echo ""

# Database password
echo "# Database"
echo "DB_PASSWORD=$(openssl rand -base64 32 | tr -d '\n')"
echo ""

# Redis password
echo "# Redis"
echo "REDIS_PASSWORD=$(openssl rand -base64 32 | tr -d '\n')"
echo ""

# MinIO credentials
echo "# MinIO (S3-compatible storage)"
MINIO_USER=$(openssl rand -hex 16)
MINIO_PASS=$(openssl rand -base64 32 | tr -d '\n')
echo "MINIO_ROOT_USER=${MINIO_USER}"
echo "MINIO_ROOT_PASSWORD=${MINIO_PASS}"
echo "AWS_ACCESS_KEY_ID=${MINIO_USER}"
echo "AWS_SECRET_ACCESS_KEY=${MINIO_PASS}"
echo ""

# JWT RSA Keys
echo "# JWT Authentication (RS256)"
echo "# Generating RSA 4096-bit key pair..."

# Create temporary directory
TMPDIR=$(mktemp -d)
trap "rm -rf $TMPDIR" EXIT

# Generate RSA key pair
ssh-keygen -t rsa -b 4096 -m PEM -f "$TMPDIR/jwt.key" -N "" -q
openssl rsa -in "$TMPDIR/jwt.key" -pubout -outform PEM -out "$TMPDIR/jwt.key.pub" 2>/dev/null

# Base64 encode for .env
JWT_PRIVATE=$(cat "$TMPDIR/jwt.key" | base64 -w 0)
JWT_PUBLIC=$(cat "$TMPDIR/jwt.key.pub" | base64 -w 0)

echo "JWT_PRIVATE_KEY=\"${JWT_PRIVATE}\""
echo "JWT_PUBLIC_KEY=\"${JWT_PUBLIC}\""
echo ""

# Encryption keys
echo "# Encryption Keys (AES-256-GCM)"
echo "SOCIAL_TOKEN_ENCRYPTION_KEY=$(openssl rand -base64 32 | tr -d '\n')"
echo "AD_TOKEN_ENCRYPTION_KEY=$(openssl rand -base64 32 | tr -d '\n')"
echo "CRM_TOKEN_ENCRYPTION_KEY=$(openssl rand -base64 32 | tr -d '\n')"
echo ""

# AI Agents internal secret
echo "# AI Agents Microservice"
echo "AI_AGENTS_INTERNAL_SECRET=$(openssl rand -hex 32)"
echo ""

# Laravel APP_KEY
echo "# Laravel Application"
echo "APP_KEY=base64:$(openssl rand -base64 32 | tr -d '\n')"
echo ""

echo "###############################################################################"
echo "# INSTRUCTIONS:"
echo "# 1. Copy these values to your .env file"
echo "# 2. For production, use a secrets manager (AWS Secrets Manager, Vault, etc.)"
echo "# 3. Never commit real secrets to version control"
echo "# 4. Rotate secrets periodically"
echo "###############################################################################"
