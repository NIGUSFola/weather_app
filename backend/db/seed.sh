#!/bin/bash
# ✅ Seed script for Ethiopia Weather DB
# Run with: bash seed.sh

DB_NAME="weather_app"
DB_USER="root"
DB_PASS="your_password"

echo "🌱 Seeding database: $DB_NAME"

# 1. Seed cities
mysql -u $DB_USER -p$DB_PASS $DB_NAME < ./seeds/cities_ethiopia.sql
echo "✅ Cities seeded."

# 2. Seed sample alerts
mysql -u $DB_USER -p$DB_PASS $DB_NAME < ./seeds/sample_alerts.sql
echo "✅ Sample alerts seeded."

echo "🎉 Seeding complete!"
