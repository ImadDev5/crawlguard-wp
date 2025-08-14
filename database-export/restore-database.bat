@echo off
REM Database Restoration Script for Windows
REM This script will restore your complete database to any PostgreSQL instance

echo Starting database restoration...

REM Set your target database connection details
set DB_HOST=localhost
set DB_NAME=your_database_name
set DB_USER=your_username
set PGPASSWORD=your_password

REM Create database if it doesn't exist
createdb -h %DB_HOST% -U %DB_USER% %DB_NAME% 2>nul

REM Restore schema
echo Restoring database schema...
psql -h %DB_HOST% -U %DB_USER% -d %DB_NAME% -f complete-schema.sql

REM Restore data
echo Restoring database data...
psql -h %DB_HOST% -U %DB_USER% -d %DB_NAME% -f complete-data.sql

echo Database restoration completed!
echo Check configuration-summary.json for your settings
pause
