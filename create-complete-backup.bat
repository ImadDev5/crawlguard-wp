@echo off
echo 🔄 Creating complete database backup from Neon...
echo.

REM Set connection string
set CONNECTION_STRING=postgresql://neondb_owner:npg_nf1TKzFajLV2@ep-steep-resonance-adkp2zt6-pooler.c-2.us-east-1.aws.neon.tech/neondb?sslmode=require^&channel_binding=require

echo 📦 Creating full database dump...
pg_dump "%CONNECTION_STRING%" > database-export\complete-neon-backup.sql

if %ERRORLEVEL% EQU 0 (
    echo ✅ Complete backup created successfully!
    echo 📁 Location: database-export\complete-neon-backup.sql
    echo.
    echo 🔄 TO RESTORE TO YOUR OLD SETUP:
    echo 1. Set up your old PostgreSQL database
    echo 2. Run: psql -h your_host -U your_user -d your_database ^< complete-neon-backup.sql
    echo 3. Your complete setup will be restored!
) else (
    echo ❌ Backup failed. Make sure pg_dump is installed.
    echo 💡 Alternative: Use the essential-config.sql file instead
)

echo.
echo 📋 Available backup files:
dir database-export\*.sql /b
echo.
pause
