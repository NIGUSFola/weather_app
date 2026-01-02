<?php
// frontend/radar.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/partials/header.php';

// Default center: Addis Ababa (lat 9.0, lon 38.7)
$lat     = isset($_GET['lat']) ? floatval($_GET['lat']) : 9.0;
$lon     = isset($_GET['lon']) ? floatval($_GET['lon']) : 38.7;
$zoom    = isset($_GET['zoom']) ? intval($_GET['zoom']) : 6;
$level   = $_GET['level'] ?? 'surface';
$overlay = $_GET['overlay'] ?? 'rain';

$role   = $_SESSION['user']['role'] ?? null;
$userId = $_SESSION['user']['id'] ?? null;

// ✅ CSRF helper for admin cache form
require_once __DIR__ . '/../backend/helpers/csrf.php';
$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>🌧️ Radar | Ethiopia Weather</title>
 <link rel="stylesheet" href="/weather/frontend/style.css">

</head>
<body>
  <div class="layout">

    <main class="page page-radar">
      <h1>Live Weather Radar — Ethiopia</h1>

      <!-- Public section: always visible -->
      <section class="radar-map">
        <iframe
          id="windyEmbed"
          src="https://embed.windy.com/embed2.html?lat=<?= htmlspecialchars($lat); ?>&lon=<?= htmlspecialchars($lon); ?>&zoom=<?= htmlspecialchars($zoom); ?>&level=<?= htmlspecialchars($level); ?>&overlay=<?= htmlspecialchars($overlay); ?>"
          title="Windy radar map">
        </iframe>
      </section>

      <!-- Backend radar snapshot -->
      <section class="snapshot" id="radarSnapshot">
        <h3>📡 Backend Radar Snapshot</h3>
        <p>Loading radar data...</p>
      </section>

      <!-- Regional overlays (aggregator snapshot) -->
      <section class="region-overlay" id="regionOverlay">
        <h3>⚠️ Regional Alerts Overlay</h3>
        <p>Loading regional alerts...</p>
      </section>

      <!-- User-only controls -->
      <?php if ($userId && $role === 'user'): ?>
        <div class="card controls">
          <h2>User Radar Controls</h2>
          <form id="radarControls" onsubmit="onUpdateRadar(event)">
            <label>Lat <input type="number" step="0.01" id="latInput" name="lat" value="<?= htmlspecialchars($lat); ?>"></label>
            <label>Lon <input type="number" step="0.01" id="lonInput" name="lon" value="<?= htmlspecialchars($lon); ?>"></label>
            <label>Zoom <input type="number" min="3" max="12" id="zoomInput" name="zoom" value="<?= htmlspecialchars($zoom); ?>"></label>
            <label>Level
              <select id="levelInput" name="level">
                <option value="surface" <?= $level === 'surface' ? 'selected' : '' ?>>Surface</option>
                <option value="850h" <?= $level === '850h' ? 'selected' : '' ?>>850hPa</option>
                <option value="500h" <?= $level === '500h' ? 'selected' : '' ?>>500hPa</option>
              </select>
            </label>
            <label>Overlay
              <select id="overlayInput" name="overlay">
                <option value="rain" <?= $overlay === 'rain' ? 'selected' : '' ?>>Rain</option>
                <option value="wind" <?= $overlay === 'wind' ? 'selected' : '' ?>>Wind</option>
                <option value="temp" <?= $overlay === 'temp' ? 'selected' : '' ?>>Temperature</option>
                <option value="clouds" <?= $overlay === 'clouds' ? 'selected' : '' ?>>Clouds</option>
              </select>
            </label>
            <button type="submit">Update</button>
          </form>
          <button type="button" id="resetBtn">Reset to Addis Ababa</button>
        </div>
      <?php endif; ?>

      <!-- Admin-only section -->
      <?php if ($userId && $role === 'admin'): ?>
        <div class="card">
          <h2>Admin Radar Tools</h2>
          <p>Admins can view cache metadata or manage radar settings here.</p>
          <p><em>Last updated: <?= date('Y-m-d H:i:s'); ?></em></p>
          <form method="post" action="/weather/backend/ethiopia_service/admin/admin_cache.php"
                onsubmit="return confirm('Refresh radar cache?');">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
            <button type="submit">🔄 Refresh Radar Cache</button>
          </form>
        </div>
      <?php endif; ?>
    </main>

    <?php include __DIR__ . '/partials/footer.php'; ?>
  </div>

  <script>
    function buildWindyUrl({ lat, lon, zoom, level, overlay }) {
      const base = "https://embed.windy.com/embed2.html";
      const params = new URLSearchParams({ lat, lon, zoom, level, overlay });
      return `${base}?${params.toString()}`;
    }

    function onUpdateRadar(e) {
      e.preventDefault();
      const lat = parseFloat(document.getElementById('latInput').value);
      const lon = parseFloat(document.getElementById('lonInput').value);
      const zoom = parseInt(document.getElementById('zoomInput').value, 10);
      const level = document.getElementById('levelInput').value;
      const overlay = document.getElementById('overlayInput').value;

      const iframe = document.getElementById('windyEmbed');
      iframe.src = buildWindyUrl({ lat, lon, zoom, level, overlay });

      const qs = new URLSearchParams({ lat, lon, zoom, level, overlay }).toString();
      history.replaceState({}, '', `?${qs}`);
    }

    const resetBtn = document.getElementById('resetBtn');
    if (resetBtn) {
      resetBtn.addEventListener('click', () => {
        const defaults = { lat: 9.0, lon: 38.7, zoom: 6, level: 'surface', overlay: 'rain' };
        document.getElementById('latInput').value = defaults.lat;
        document.getElementById('lonInput').value = defaults.lon;
        document.getElementById('zoomInput').value = defaults.zoom;
        document.getElementById('levelInput').value = defaults.level;
        document.getElementById('overlayInput').value = defaults.overlay;

        const iframe = document.getElementById('windyEmbed');
        iframe.src = buildWindyUrl(defaults);

        const qs = new URLSearchParams(defaults).toString();
        history.replaceState({}, '', `?${qs}`);
      });
    }

    // Backend radar snapshot
    async function fetchRadarSnapshot() {
      try {
        const res = await fetch("/weather/backend/ethiopia_service/radar.php?city=Addis%20Ababa", {
          headers: { 'Accept': 'application/json' },
          credentials: 'same-origin'
        });
        const data = await res.json();
        const container = document.getElementById("radarSnapshot");
        if (data.public && data.public.radar) {
          container.innerHTML = `<h3>📡 Backend Radar Snapshot</h3>
                                 <p>City: ${data.city}</p>
                                 <img src="${data.public.radar.tile_url}" alt="Radar tile">
                                 <p>Zoom: ${data.public.radar.zoom}, Lat: ${data.public.radar.lat}, Lon: ${data.public.radar.lon}</p>`;
        } else {
          container.innerHTML = "<p>No radar data available.</p>";
        }
      } catch (err) {
        document.getElementById("radarSnapshot").innerHTML =
          `<div class="error-message">Error loading radar snapshot: ${err.message}</div>`;
      }
    }
    // Regional alerts overlay
    async function fetchRegionalAlerts() {
      try {
        const res = await fetch("/weather/backend/aggregator/merge_feeds.php", {
          headers: { 'Accept': 'application/json' },
          credentials: 'same-origin'
        });
        const data = await res.json();
        const container = document.getElementById("regionOverlay");
        container.innerHTML = "<h3>⚠️ Regional Alerts Overlay</h3>";
        if (data.regions) {
          const list = document.createElement('ul');
          for (const [region, info] of Object.entries(data.regions)) {
            const li = document.createElement('li');
            li.innerHTML = `<strong>${region}</strong>: ${info.alerts && info.alerts.length > 0 
              ? info.alerts.map(a => a.event).join(", ") 
              : "No active alerts"}`;
            list.appendChild(li);
          }
          container.appendChild(list);
        } else {
          container.innerHTML += "<p>No regional alerts available.</p>";
        }
      } catch (err) {
        document.getElementById("regionOverlay").innerHTML =
          `<div class="error-message">Error loading regional alerts: ${err.message}</div>`;
      }
    }

    // Initial load
    document.addEventListener("DOMContentLoaded", () => {
      fetchRadarSnapshot();
      fetchRegionalAlerts();
    });
  </script>
</body>
</html>
