#!/bin/bash
###############################################################################
# PostgreSQL SSL Certificate Generator
#
# This script generates self-signed SSL certificates for PostgreSQL.
# For DEVELOPMENT use only. In production, use CA-signed certificates.
###############################################################################

set -e

CERT_FILE="$PGDATA/server.crt"
KEY_FILE="$PGDATA/server.key"

echo "=== PostgreSQL SSL Setup ==="

# Only generate if certificates don't exist
if [ ! -f "$CERT_FILE" ] || [ ! -f "$KEY_FILE" ]; then
    echo "Generating self-signed SSL certificates..."

    # Generate private key and self-signed certificate in one command
    openssl req -new -x509 -days 365 -nodes \
        -out "$CERT_FILE" \
        -keyout "$KEY_FILE" \
        -subj "/C=BR/ST=SP/L=SaoPaulo/O=SocialMediaManager/CN=postgres"

    # Set correct permissions (PostgreSQL requires specific permissions)
    chmod 600 "$KEY_FILE"
    chmod 644 "$CERT_FILE"

    echo "SSL certificates generated successfully."
    echo "WARNING: These are self-signed certificates for development only."
else
    echo "SSL certificates already exist. Skipping generation."
fi

echo "=== SSL Setup Complete ==="
