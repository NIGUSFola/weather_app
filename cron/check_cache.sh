#!/bin/bash
# check_cache.sh - Cron wrapper for cache health checks

# Navigate to project root
cd "$(dirname "$0")/.."

# Run PHP cache health check (user-based)
php backend/tests/cache_health.php >> logs/cache.log 2>&1

# Run global CLI cache health check
php backend/tests/cache_health_cli.php >> logs/cache.log 2>&1
