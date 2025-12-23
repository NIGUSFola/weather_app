<?php
// frontend/forecast.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/partials/header.php';

$cityId  = $_GET['city_id'] ?? 1; // default city_id (e.g., Addis Ababa)
$success = $_GET['success'] ?? null;
$error   = $_GET['error'] ?? null;

$role    = $_SESSION['user']['role'] ?? null;
$userId  = $_SESSION['user']['id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>📅 Forecast | Ethiopia Weather</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .forecast-card { border: 1px solid #ccc; padding: 1rem; margin: 0.5rem; border-radius: 6px; display: inline-block; }
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

      <!-- City input form -->
      <form method="get" action="/weather_app/frontend/forecast.php" onsubmit="onSubmitForecast(event)">
        <select name="city_id" id="cityInput" required>
          <option value="">-- Select City --</option>
          <?php
          require_once __DIR__ . '/../config/db.php';
          $cities = db()->query("SELECT id, name FROM cities ORDER BY name")->fetchAll();
          foreach ($cities as $c) {
              $selected = ($c['id'] == $cityId) ? 'selected' : '';
              echo '<option value="'.htmlspecialchars($c['id']).'" '.$selected.'>'.htmlspecialchars($c['name']).'</option>';
          }
          ?>
        </select>
        <button type="submit">Get Forecast</button>
      </form>

      <!-- Public forecast -->
      <h2>🌍 Free Forecast Preview</h2>
      <div id="publicForecast" class="forecast-cards"><p>Loading public forecast...</p></div>

      <!-- User forecast -->
      <?php if ($userId && $role === 'user'): ?>
        <h2>👤 Full Forecast (User Access)</h2>
        <div id="userForecast" class="forecast-cards"><p>Loading user forecast...</p></div>
      <?php endif; ?>

      <!-- Admin forecast -->
      <?php if ($userId && $role === 'admin'): ?>
        <h2>🛠 Admin Forecast Tools</h2>
        <div id="adminForecast" class="forecast-cards"><p>Loading admin forecast...</p></div>

        <!-- Refresh Cache Button -->
        <form method="post" action="/weather_app/backend/ethiopia_service/admin/admin_cache.php" 
              onsubmit="return confirm('Refresh cache for all cities?');">
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

      async function fetchForecast(cityId) {
        const url = `/weather_app/backend/ethiopia_service/forecast.php?city_id=${encodeURIComponent(cityId)}`;
        try {
          const data = await fetchJson(url);

          // Public forecast
          if (data.data) {
            const cards = document.getElementById('publicForecast');
            cards.innerHTML = '';
            data.data.days.forEach(day => {
              const card = document.createElement('div');
              card.className = 'forecast-card';
              card.innerHTML = `<h3>Day ${day.d}</h3>
                                <p>Temp: ${day.temp}°C</p>
                                <p>${day.cond}</p>`;
              cards.appendChild(card);
            });
            if (data.source === 'cache') {
              cards.innerHTML += `<p><em>Cached at: ${data.data.generated_at}</em></p>`;
            }
          } else if (data.error) {
            document.getElementById('publicForecast').innerHTML = `<div class="error-message">${data.error}</div>`;
          }

          // User forecast
          if (data.user) {
            const cards = document.getElementById('userForecast');
            cards.innerHTML = '';
            data.user.forecast.forEach(day => {
              const card = document.createElement('div');
              card.className = 'forecast-card';
              card.innerHTML = `<h3>${day.date}</h3>
                                <p>Min: ${day.min_temp}°C | Max: ${day.max_temp}°C</p>
                                <p>${day.condition}</p>`;
              cards.appendChild(card);
            });
          }

          // Admin forecast
          if (data.admin) {
            const cards = document.getElementById('adminForecast');
            cards.innerHTML = '';
            data.admin.forecast.forEach(day => {
              const card = document.createElement('div');
              card.className = 'forecast-card';
              card.innerHTML = `<h3>${day.date}</h3>
                                <p>Min: ${day.min_temp}°C | Max: ${day.max_temp}°C</p>
                                <p>${day.condition}</p>`;
              cards.appendChild(card);
            });
            if (data.admin.meta) {
              cards.innerHTML += `<p><em>Records: ${data.admin.meta.record_count}, Generated: ${data.admin.meta.generated_at}</em></p>`;
            }
          }

        } catch (err) {
          document.getElementById('publicForecast').innerHTML = `<div class="error-message">Error loading forecast: ${err.message}</div>`;
        }
      }

      window.onSubmitForecast = function(e) {
        e.preventDefault();
        const cityId = document.getElementById('cityInput').value;
        fetchForecast(cityId);
        history.replaceState({}, '', `?city_id=${encodeURIComponent(cityId)}`);
      }

      // Initial load
      const cityId = "<?= htmlspecialchars($cityId); ?>";
      fetchForecast(cityId);
    });
  </script>
</body>
</html>
