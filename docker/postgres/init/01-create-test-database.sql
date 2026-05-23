-- Create test database for integration tests
-- This runs automatically on first PostgreSQL initialization
SELECT 'CREATE DATABASE database_test'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'database_test')\gexec
