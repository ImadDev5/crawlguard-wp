#!/bin/bash
# Database Restoration Script
# This script will restore your complete database to any PostgreSQL instance

echo "🔄 Starting database restoration..."

# Set your target database connection details
DB_HOST="localhost"
DB_NAME="your_database_name"
DB_USER="your_username"
DB_PASS="your_password"

# Create database if it doesn't exist
createdb -h $DB_HOST -U $DB_USER $DB_NAME 2>/dev/null || true

# Restore schema
echo "📋 Restoring database schema..."
psql -h $DB_HOST -U $DB_USER -d $DB_NAME -f complete-schema.sql

# Restore data
echo "📊 Restoring database data..."
psql -h $DB_HOST -U $DB_USER -d $DB_NAME -f complete-data.sql

echo "✅ Database restoration completed!"
echo "📋 Check configuration-summary.json for your settings"
