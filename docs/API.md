##  Draft `API.md`

```markdown
# API Documentation — Multi‑Region Weather App 🌍🌤️

This document describes the key API endpoints for the system, including request/response formats, authentication, and error handling.

---

## 🔑 Authentication
- All endpoints require a valid session (`require_user()` or `require_admin()`).
- CSRF tokens must be included in POST requests.
- Role enforcement:
  - **User endpoints** → favorites, forecasts, alerts.
  - **Admin endpoints** → system monitoring, cache, logs, metrics.

---

## 🌦️ Forecast Endpoints

### `GET /backend/ethiopia_service/forecast.php?city_id={id}`
Fetch forecast for a specific city.

**Request:**
```http
GET /backend/ethiopia_service/forecast.php?city_id=1
Accept: application/json
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "days": [
      { "d": 1, "temp": 22, "cond": "Sunny" },
      { "d": 2, "temp": 20, "cond": "Cloudy" }
    ],
    "generated_at": "2025-12-25 11:40:00"
  },
  "source": "cache"
}
```

---

## ⚠️ Alerts Endpoints

### `GET /backend/ethiopia_service/alerts.php`
Fetch active alerts across regions.

**Response:**
```json
{
  "regions": {
    "Oromia": {
      "city": "Shashamane",
      "alerts": [
        { "event": "Flood", "severity": "high", "start": "2025-12-25", "end": "2025-12-27" }
      ]
    }
  }
}
```

---

## 📊 Aggregator Endpoints

### `GET /backend/aggregator/merge_feeds.php`
Merge forecasts + alerts from all regions.

**Response:**
```json
{
  "summary": {
    "total_alerts": 5,
    "generated_at": "2025-12-25 11:45:00"
  },
  "regions": {
    "Amhara": { "city": "Bahir Dar", "forecast": [...], "alerts": [...] }
  }
}
```

---

## 🩺 Health Endpoints

### `GET /backend/ethiopia_service/health.php`
System health check.

**Response:**
```json
{
  "status": "ok",
  "checks": {
    "db": true,
    "api_key": true,
    "session": true,
    "cache": true
  },
  "time": "2025-12-25 11:46:00"
}
```

---

## ⭐ Personalization Endpoints

### `POST /backend/actions/add_favorite.php`
Add a city to user favorites.

**Request:**
```http
POST /backend/actions/add_favorite.php
Content-Type: application/x-www-form-urlencoded

city_id=1&csrf_token=XYZ
```

**Response:**
```json
{ "status": "success", "message": "Addis Ababa added to favorites" }
```

---

### `POST /backend/actions/delete_favorite.php`
Remove a city from favorites.

**Response:**
```json
{ "status": "success", "message": "Addis Ababa removed from favorites" }
```

---

### `POST /backend/actions/set_default_city.php`
Set default city preference.

**Response:**
```json
{ "status": "success", "message": "Default city set to Addis Ababa" }
```

---

### `POST /backend/actions/set_theme.php`
Set theme preference.

**Response:**
```json
{ "status": "success", "message": "Theme set to dark" }
```

---

## ❌ Error Handling
- `400 Bad Request` → missing parameters.  
- `403 Forbidden` → invalid CSRF or unauthorized role.  
- `404 Not Found` → city or resource not found.  
- `500 Server Error` → unexpected backend failure.  

---

```

---
  

