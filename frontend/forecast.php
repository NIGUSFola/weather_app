<?php
// frontend/forecast.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/partials/header.php';

$success = $_GET['success'] ?? null;
$error   = $_GET['error'] ?? null;

$role    = $_SESSION['user']['role'] ?? null;
$userId  = $_SESSION['user']['id'] ?? null;

// ✅ CSRF helper for admin form
require_once __DIR__ . '/../backend/helpers/csrf.php';
$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>📅 Forecast | Ethiopia Weather</title>
  <link rel="stylesheet" href="/weather/frontend/style.css">
  <style>
    .status-badge {
      display: inline-block;
      padding: 2px 6px;
      border-radius: 4px;
      font-size: 0.85em;
      font-weight: bold;
      margin-left: 6px;
    }
    .status-OK { background: #4caf50; color: white; }
    .status-CACHED { background: #2196f3; color: white; }
    .status-STALE { background: #ff9800; color: white; }
    .status-FAIL { background: #f44336; color: white; }
    .status-NO_FORECAST { background: #9e9e9e; color: white; }
    .forecast-card {
      border: 1px solid #ddd;
      border-radius: 6px;
      padding: 8px;
      margin: 6px 0;
      background: #fafafa;
    }
    .region-block {
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <main class="page page-forecast">
    <section class="forecast-section">
      <h1>📅 Forecast</h1>

      <!-- Success/Error banners -->
      <?php if ($success): ?>
        <div class="success-message"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- Unified forecast from aggregator -->
      <h2>🌍 Regional Forecasts</h2>
      <div id="forecastContainer"><p>Loading forecasts...</p></div>

      <!-- User forecast -->
      <?php if ($userId && $role === 'user'): ?>
        <h2>👤 Your Favorite Cities Forecast</h2>
        <div id="userForecast"><p>Loading your forecast...</p></div>
      <?php endif; ?>

      <!-- Admin forecast -->
      <?php if ($userId && $role === 'admin'): ?>
        <h2>🛠 Admin Forecast Tools</h2>
        <div id="adminForecast"><p>Loading admin forecast...</p></div>
        <form method="post" action="/weather/backend/ethiopia_service/admin/admin_cache.php" 
              onsubmit="return confirm('Refresh cache for all regions?');">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
          <button type="submit">🔄 Refresh Cache</button>
        </form>
      <?php endif; ?>
    </section>
  </main>

  <?php include __DIR__ . '/partials/footer.php'; ?>

  <script>
    async function fetchJson(url) {
      const res = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return await res.json();
    }

    function renderForecastCard(entry) {
      return `<div class="forecast-card">
                <p><strong>${entry.date}</strong></p>
                <p>${entry.condition}</p>
                <p>${entry.min_temp}°C / ${entry.max_temp}°C</p>
                <img src="https://openweathermap.org/img/wn/${entry.icon}.png" alt="${entry.condition}">
              </div>`;
    }

    function renderStatusBadge(status) {
      return `<span class="status-badge status-${status}">${status}</span>`;
    }

    document.addEventListener("DOMContentLoaded", async () => {
      const container = document.getElementById('forecastContainer');
      container.innerHTML = "<p>Loading forecasts...</p>";

      try {
        // ✅ Regional forecasts
        const data = await fetchJson("/weather/backend/ethiopia_service/forecast.php");
        container.innerHTML = '';

        if (data.regions) {
          Object.entries(data.regions).forEach(([regionName, region]) => {
            const block = document.createElement('div');
            block.className = 'region-block';
            block.innerHTML = `<h3>${regionName} — ${region.city} ${renderStatusBadge(region.status)}</h3>`;

            if (region.forecast && region.forecast.length > 0) {
              region.forecast.forEach(entry => {
                block.innerHTML += renderForecastCard(entry);
              });
            } else {
              block.innerHTML += `<p class="error-message">No forecast available</p>`;
            }

            container.appendChild(block);
          });
        } else {
          container.innerHTML = `<div class="error-message">No forecast data found</div>`;
        }

        <?php if ($userId && $role === 'user'): ?>
        // ✅ Favorite forecasts
        try {
          const favData = await fetchJson("/weather/backend/actions/favorite_forecast.php");
          const userDiv = document.getElementById('userForecast');
          userDiv.innerHTML = '';

          if (favData.favorites && favData.favorites.length > 0) {
            favData.favorites.forEach(fav => {
              const block = document.createElement('div');
              block.className = 'region-block';
              block.innerHTML = `<h3>${fav.city} ${renderStatusBadge(fav.status)}</h3>`;

              if (fav.forecast && fav.forecast.length > 0) {
                fav.forecast.forEach(entry => {
                  block.innerHTML += renderForecastCard(entry);
                });
              } else {
                block.innerHTML += `<p class="error-message">No forecast available</p>`;
              }

              userDiv.appendChild(block);
            });
          } else {
            userDiv.innerHTML = "<p>No favorites yet.</p>";
          }
        } catch (err) {
          document.getElementById('userForecast').innerHTML = `<div class="error-message">Error loading favorite forecasts: ${err.message}</div>`;
        }
        <?php endif; ?>

        <?php if ($userId && $role === 'admin'): ?>
        // ✅ Admin summary
        const adminDiv = document.getElementById('adminForecast');
        adminDiv.innerHTML = `<p>Total days across regions: ${data.summary.total_days}</p>
                              <p>Generated at: ${data.summary.generated_at}</p>`;
        <?php endif; ?>

      } catch (err) {
        container.innerHTML = `<div class="error-message">Error loading forecasts: ${err.message}</div>`;
      }
    });
  </script>
</body>
</html>
