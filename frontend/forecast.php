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
  <link rel="stylesheet" href="/weather/frontend/partials/style.css">
  <style>
    .forecast-card { border: 1px solid #ccc; padding: 1rem; margin: 0.5rem; border-radius: 6px; display: inline-block; }
    .region-block { margin-bottom: 2rem; }
    .error-message { color: red; }
    .success-message { color: green; }
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
        <h2>👤 Extended Forecast (User Access)</h2>
        <div id="userForecast"><p>Loading extended forecast...</p></div>
      <?php endif; ?>

      <!-- Admin forecast -->
      <?php if ($userId && $role === 'admin'): ?>
        <h2>🛠 Admin Forecast Tools</h2>
        <div id="adminForecast"><p>Loading admin forecast...</p></div>
        <form method="post" action="/weather/backend/ethiopia_service/admin/admin_cache.php" 
              onsubmit="return confirm('Refresh cache for all regions?');">
          <!-- ✅ Added CSRF token -->
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
          <button type="submit">🔄 Refresh Cache</button>
        </form>
      <?php endif; ?>
    </section>
  </main>

  <?php include __DIR__ . '/partials/footer.php'; ?>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      async function fetchJson(url) {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
        const text = await res.text();
        try { return JSON.parse(text); }
        catch { throw new Error('Invalid server response'); }
      }

      async function loadForecasts() {
        const url = "/weather/backend/aggregator/merge_feeds.php";
        try {
          const data = await fetchJson(url);

          const container = document.getElementById('forecastContainer');
          container.innerHTML = '';

          if (data.regions) {
            Object.values(data.regions).forEach(region => {
              const block = document.createElement('div');
              block.className = 'region-block';
              block.innerHTML = `<h3>${region.city} (${region.region})</h3>`;

              if (region.forecast && region.forecast.length > 0) {
                region.forecast.forEach(entry => {
                  const card = document.createElement('div');
                  card.className = 'forecast-card';
                  card.innerHTML = `<p><strong>${entry.date}</strong></p>
                                    <p>${entry.condition}</p>
                                    <p>${entry.temp}°C</p>`;
                  block.appendChild(card);
                });
              } else {
                block.innerHTML += `<p class="error-message">No forecast available</p>`;
              }

              container.appendChild(block);
            });
          } else {
            container.innerHTML = `<div class="error-message">No forecast data found</div>`;
          }

          // User forecast (extended view)
          <?php if ($userId && $role === 'user'): ?>
          const userDiv = document.getElementById('userForecast');
          const userData = await fetchJson("/weather/backend/ethiopia_service/forecast.php?city_id=1");
          if (userData.user && userData.user.forecast) {
            userDiv.innerHTML = '';
            userData.user.forecast.forEach(day => {
              userDiv.innerHTML += `<div class="forecast-card">
                                      <p><strong>${day.date}</strong></p>
                                      <p>${day.condition}</p>
                                      <p>${day.min_temp}°C / ${day.max_temp}°C</p>
                                    </div>`;
            });
          }
          <?php endif; ?>

          // Admin forecast (summary)
          <?php if ($userId && $role === 'admin'): ?>
          const adminDiv = document.getElementById('adminForecast');
          adminDiv.innerHTML = `<p>Total alerts across regions: ${data.summary.total_alerts}</p>
                                <p>Generated at: ${data.summary.generated_at}</p>`;
          <?php endif; ?>

        } catch (err) {
          document.getElementById('forecastContainer').innerHTML = `<div class="error-message">Error loading forecasts: ${err.message}</div>`;
        }
      }

      // Initial load
      loadForecasts();
    });
  </script>
</body>
</html>
