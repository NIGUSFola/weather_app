<?php
session_start();
if (!isset($_SESSION['default_city'])) {
    $_SESSION['default_city'] = 'Addis Ababa';
}
$role = $_SESSION['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title data-i18n="page_title">Weather Aggregator — Ethiopia</title>
  <link rel="stylesheet" href="/weather/frontend/partials/style.css">
</head>
<body>
  <div class="layout">
    <?php include __DIR__ . '/partials/header.php'; ?>

  

    <main class="page page-home">
      <!-- Live summary -->
      <section class="live-summary" aria-live="polite" role="status">
        <div class="location">
          <span id="summaryLocation"><?= htmlspecialchars($_SESSION['default_city']); ?>, Ethiopia</span>
        </div>
        <div class="temperature" id="summaryTemp">--°C</div>
        <div class="condition" id="summaryCondition" data-i18n="loading_condition">Loading...</div>
      </section>

      <!-- Alerts banner -->
      <div class="alert-banner" id="alertBanner" hidden role="alert">
        <span id="alertText" data-i18n="loading_alerts">Loading alerts...</span>
      </div>

      <!-- Hero -->
      <section class="hero">
        <h1 data-i18n="hero_title">Real-time weather across Ethiopia</h1>
        <p data-i18n="hero_subtitle">Track conditions in your cities and regions with clarity and speed.</p>
        <div class="buttons">
          <button id="getStartedBtn" data-i18n="btn_get_started">Get started</button>
          <button class="outline" id="viewMapBtn" data-i18n="btn_view_map">View Ethiopia map</button>
        </div>
      </section>

      <!-- Regional Alerts Snapshot -->
      <section class="alerts-overlay">
        <h2 data-i18n="regional_alerts_title">⚠️ Regional Alerts Snapshot</h2>
        <div id="regionalAlerts"><p data-i18n="loading_regional_alerts">Loading regional alerts...</p></div>
      </section>
    </main>

    <?php include __DIR__ . '/partials/footer.php'; ?>
  </div>

  <script>
  // Theme toggle
  (function themeInit() {
    const key = 'theme';
    const saved = localStorage.getItem(key) || 'light';
    document.documentElement.setAttribute('data-theme', saved);
    const btn = document.getElementById('themeToggle');
    if (!btn) return;
    btn.textContent = saved === 'dark' ? '☀️' : '🌙';
    btn.addEventListener('click', () => {
      const current = document.documentElement.getAttribute('data-theme');
      const next = current === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      localStorage.setItem(key, next);
      btn.textContent = next === 'dark' ? '☀️' : '🌙';
    });
  })();

  // Navigation buttons
  const role = "<?= htmlspecialchars($role) ?>";
  document.getElementById('getStartedBtn')?.addEventListener('click', () => {
    if (!role) {
      window.location.href = '/weather/frontend/login.php';
    } else if (role === 'admin') {
      window.location.href = '/weather/frontend/dashboard.php';
    } else {
      window.location.href = '/weather/frontend/user_dashboard.php';
    }
  });
  document.getElementById('viewMapBtn')?.addEventListener('click', () => {
    window.location.href = '/weather_app/frontend/radar.php';
  });

  // Regional alerts preview via unified feed
  async function fetchRegionalAlerts() {
    try {
      const res = await fetch("/weather/frontend/api.php?action=feed", {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
      });
      const data = await res.json();
      const container = document.getElementById("regionalAlerts");
      container.innerHTML = '';
      if (data.regions) {
        for (const [region, info] of Object.entries(data.regions)) {
          const div = document.createElement('div');
          div.className = 'alert-card';
          div.innerHTML = `<h3>${region} (${info.city})</h3>`;
          if (Array.isArray(info.alerts) && info.alerts.length > 0) {
            info.alerts.forEach(alert => {
              const severity = alert.severity || 'moderate';
              div.innerHTML += `<p><strong>${alert.event}</strong> (${alert.start} → ${alert.end})
                                <span class="severity-badge severity-${severity}">
                                  ${severity.toUpperCase()}
                                </span><br>
                                ${alert.description}</p>`;
            });
          } else {
            div.innerHTML += "<p data-i18n='no_active_alerts'>No active alerts.</p>";
          }
          container.appendChild(div);
        }
      } else {
        container.innerHTML = "<p data-i18n='no_regional_alerts'>No regional alerts available.</p>";
      }
    } catch (err) {
      document.getElementById("regionalAlerts").innerHTML =
        `<div class="error-message" data-i18n="error_loading_alerts">Error loading regional alerts: ${err.message}</div>`;
    }
  }

  // Live summary from default city
  async function fetchSummary() {
    try {
      const res = await fetch("/weather/frontend/api.php?action=feed");
      const data = await res.json();
      const city = "<?= htmlspecialchars($_SESSION['default_city']); ?>";
      const regionInfo = Object.values(data.regions).find(r => r.city === city);
      if (regionInfo && Array.isArray(regionInfo.forecast) && regionInfo.forecast.length > 0) {
        document.getElementById("summaryTemp").textContent =
          `${regionInfo.forecast[0].temp}°C`;
        document.getElementById("summaryCondition").textContent =
          regionInfo.forecast[0].weather;
      }
    } catch (err) {
      document.getElementById("summaryCondition").textContent = "Error loading summary";
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    fetchRegionalAlerts();
    fetchSummary();
  });
  </script>
</body>
</html>
