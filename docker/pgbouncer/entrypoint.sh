#!/bin/sh
set -e

# Generate userlist.txt from environment variables
# For SCRAM-SHA-256, we need to get the hash from PostgreSQL
# For simplicity in development, we use plain text authentication with PgBouncer
# and let PgBouncer handle the SCRAM auth to PostgreSQL

echo "\"${DB_USERNAME}\" \"${DB_PASSWORD}\"" > /etc/pgbouncer/userlist.txt
chmod 600 /etc/pgbouncer/userlist.txt

# Start PgBouncer
exec /usr/bin/pgbouncer /etc/pgbouncer/pgbouncer.ini
