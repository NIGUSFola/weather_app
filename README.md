# Multi-Region Weather App 🌍🌤️

A distributed, reproducible weather alerts and forecast system with **multi-region support**, role‑based dashboards, cache refresh, and health monitoring.  
Built with **essential‑only principles**: no duplication, clean separation of backend/frontend, reproducible onboarding, and centralized logging.

---


### 1. Clone & Configure
```bash
git clone <your-repo-url>
cd multi_region_weather_app

### 2. Database Setup
Run schema + migrations:

bash
mysql -u root -p < backend/db/schema.sql
mysql -u root -p < backend/db/migrations/001_add_type_enum.sql
mysql -u root -p < backend/db/migrations/002_add_indexes.sql
mysql -u root -p < backend/db/migrations/003_add_roles.sql
### 3. Seed Essential Data
bash
mysql -u root -p < backend/db/seeds/cities_ethiopia.sql
mysql -u root -p < backend/db/seeds/sample_alerts.sql
php backend/install.php
Admin account seeded automatically

Cities: Addis Ababa, Shashamane, Hawassa, Bahir Dar, Wolaita, Hossana

Demo alerts for presentations

### 4. Refresh Cache
Run cron jobs:

bash
bash cron/refresh_cache.sh
bash cron/check_cache.sh
Logs written to logs/cache.log and logs/cron.log.

Got it Nigus 👌 — let’s continue and finish the **README.md** we were regenerating. We stopped at the **Verify Health** section. Here’s the full continuation and completion:

---

## 📂 `README.md` (continued)

```markdown
### 5. Verify Health
Check system status:

```bash
php backend/ethiopia_service/health.php
```

Expected JSON:
```json
{
  "status": "ok",
  "checks": {
    "db": true,
    "api_key": true,
    "session": true,
    "cache": true
  },
  "time": "2025-12-23 11:20:00"
}
```

---

### 6. Dashboards
- **Admin Dashboard** → manage users, view health, monitor cache/logs.  
- **User Dashboard** → view forecasts, alerts, favorites, and system health.  
- **Frontend Health Page** → quick visual check of system status.  

---

## 🗂️ Project Structure

```
multi_region_weather_app/
├── auth/                      # Authentication + session handling
├── backend/                   # Core backend logic
│   ├── db/                    # schema, migrations, seeds
│   ├── helpers/               # middleware, csrf, logging, validation
│   ├── aggregator/            # merge feeds, normalize payloads
│   ├── ethiopia_service/      # national service + regions
│   └── admin/                 # admin-only endpoints
├── actions/                   # user personalization actions
├── tests/                     # automated health + concurrency checks
├── config/                    # db + api config
├── cron/                      # scheduled jobs
├── frontend/                  # user-facing pages + partials
├── uploads/                   # optional user uploads
├── docs/                      # documentation + diagrams
└── logs/                      # centralized logging
```

---

## 🎯 Demo Flow
1. **Run seeds** → admin + cities + sample alerts + lock.  
2. **Run cron jobs** → refresh cache, check cache health.  
3. **Check health** → endpoint returns `"status": "ok"`.  
4. **Login as admin** → view dashboard, logs, metrics.  
5. **Login as user** → add favorites, view forecasts + alerts.  
6. **Show logs** → reproducible events across `system.log`, `auth.log`, `cache.log`, `cron.log`.  

---

## ✅ Essential‑Only Principle
- No duplication across helpers.  
- Clean separation of backend/frontend.  
- Distributed lock + cache refresh for resilience.  
- Centralized logging for reproducibility.  
- Multi‑region stubs ensure scalability (Oromia, South, Amhara).  

---

## 📖 Documentation
- `docs/README.md` → project overview  
- `docs/API.md` → API usage docs  
- `docs/DEPLOYMENT.md` → distributed deployment notes  
- `docs/COLLABORATION.md` → team roles + collaboration notes  
- `docs/diagrams/architecture.png` → architecture diagram  
- `docs/diagrams/sequence_flows.md` → request → cache → failover → logs → health  

---

## 🎉 Conclusion
This project is **exam‑ready and reproducible**. With seeds, cron jobs, health checks, dashboards, and centralized logs, anyone can set up, demo, and maintain the system with confidence.
```

---