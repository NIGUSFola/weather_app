# Cron Jobs for Multi-Region Weather App

This folder contains scheduled job wrappers for cache health and refresh tasks.

## Files
- `check_cache.sh` → Runs cache health checks (user-based and global CLI).
- `refresh_cache.sh` → Refreshes forecasts and alerts periodically.
- `README.md` → Notes on setup.

## Setup

1. Make scripts executable:
   ```bash
   chmod +x cron/check_cache.sh
   chmod +x cron/refresh_cache.sh
