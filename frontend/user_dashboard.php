<?php
// frontend/user_dashboard.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/../backend/helpers/auth_middleware.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../backend/helpers/csrf.php';

require_user(); // Only logged-in users can access

// Flash + query messages
$flash   = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$success = $_GET['success'] ?? null;
$error   = $_GET['error'] ?? null;

// Fetch user favorites
$userId = $_SESSION['user']['id'];
$stmt = db()->prepare("
    SELECT c.id AS city_id, c.name AS city
    FROM favorites f
    JOIN cities c ON f.city_id = c.id
    WHERE f.user_id = ?
");
$stmt->execute([$userId]);
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

// CSRF token
$csrfToken = generate_csrf_token();

// Fetch unified alerts (server-side fallback)
$alertsData = [];
try {
    $data = file_get_contents(__DIR__ . '/../backend/aggregator/merge_feeds.php');
    $alertsData = json_decode($data, true);
} catch (Exception $e) {
    $alertsData = ['error' => $e->getMessage()];
}

// Fetch health status
$healthData = [];
try {
    $healthJson = file_get_contents(__DIR__ . '/../backend/ethiopia_service/health.php');
    $healthData = json_decode($healthJson, true);
} catch (Exception $e) {
    $healthData = ['status' => 'degraded', 'health' => [], 'checked_at' => date('Y-m-d H:i:s')];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars($_SESSION['theme'] ?? 'light'); ?>">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard - Ethiopia Weather</title>
    <link rel="stylesheet" href="/weather/frontend/partials/style.css">
</head>
<body>
<main class="page-dashboard">
    <section class="dashboard-section">
        <h1>🌤️ User Dashboard</h1>

        <!-- Messages -->
        <?php if (!empty($flash)): ?><div class="success-message"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success-message"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error-message"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <!-- Welcome -->
        <div class="card">
            <h2>Welcome, <?= htmlspecialchars($_SESSION['user']['email'] ?? 'User #'.$_SESSION['user']['id']); ?></h2>
            <p>Here’s your personalized weather dashboard.</p>
        </div>

        <!-- Favorites -->
        <div class="section">
            <h3>⭐ Your Favorite Cities</h3>
            <form method="post" action="../backend/actions/add_favorite.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <select name="city_id" required>
                    <option value="">-- Select City --</option>
                    <?php
                    $cities = db()->query("SELECT id, name FROM cities ORDER BY name")->fetchAll();
                    foreach ($cities as $c) {
                        echo '<option value="'.htmlspecialchars($c['id']).'">'.htmlspecialchars($c['name']).'</option>';
                    }
                    ?>
                </select>
                <button type="submit">➕ Add Favorite</button>
            </form>

            <?php if ($favorites): ?>
                <ul>
                    <?php foreach ($favorites as $fav): ?>
                        <li>
                            <strong><?= htmlspecialchars($fav['city']); ?></strong>
                            <form method="post" action="../backend/actions/delete_favorite.php" style="display:inline">
                                <input type="hidden" name="city_id" value="<?= htmlspecialchars($fav['city_id']); ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <button type="submit">❌ Remove</button>
                            </form>
                            <div class="forecast-cards" id="forecast-<?= $fav['city_id']; ?>">
                                <p>Loading forecast for <?= htmlspecialchars($fav['city']); ?>...</p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No favorites yet. Add a city above!</p>
            <?php endif; ?>
        </div>

        <!-- Radar Snapshot -->
        <div class="section">
            <h3>🌍 Live Weather Radar</h3>
            <div id="radar-snapshot">
                <p>Loading radar snapshot...</p>
            </div>
        </div>

        <!-- Regional Alerts Overlay -->
        <div class="section">
            <h3>⚠️ Regional Alerts Overlay</h3>
            <div id="regional-alerts-overlay">
                <p>Loading regional alerts...</p>
            </div>
        </div>

        <!-- System Health -->
        <div class="section">
            <h3>🩺 System Health</h3>
            <?php if (!empty($healthData['health'])): ?>
                <table>
                    <thead><tr><th>Check</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($healthData['health'] as $check => $value): ?>
                            <tr>
                                <td><?= htmlspecialchars(strtoupper($check)); ?></td>
                                <td class="<?= $value ? 'status-ok' : 'status-fail'; ?>">
                                    <?= $value ? 'OK' : 'FAIL'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td><strong>Overall</strong></td>
                            <td class="<?= ($healthData['status'] === 'OK') ? 'status-ok' : 'status-fail'; ?>">
                                <?= htmlspecialchars($healthData['status']); ?> (<?= htmlspecialchars($healthData['checked_at']); ?>)
                            </td>
                        </tr>
                    </tbody>
                </table>
            <?php else: ?><p>No health data available.</p><?php endif; ?>
        </div>

        <!-- Account Info -->
        <div class="section">
            <h3>📊 Account Info</h3>
            <p>Email: <?= htmlspecialchars($_SESSION['user']['email'] ?? 'Unknown'); ?></p>
            <p>Role: <?= htmlspecialchars($_SESSION['user']['role'] ?? 'user'); ?></p>
            <p>Default City: <?= htmlspecialchars($_SESSION['default_city'] ?? 'Addis Ababa'); ?></p>
            <form method="post" action="../backend/actions/set_default_city.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <select name="city_id" required>
                    <option value="">-- Select Default City --</option>
                    <?php
                    $cities = db()->query("SELECT id, name FROM cities ORDER BY name")->fetchAll();
                    foreach ($cities as $c) {
                        echo '<option value="'.htmlspecialchars($c['id']).'">'.htmlspecialchars($c['name']).'</option>';
                    }
                    ?>
                </select>
                <button type="submit">🌍 Set Default City</button>
            </form>
        </div>

        <!-- Theme Preference -->
        <div class="section">
            <h3>🎨 Theme Preference</h3>
            <button id="themeToggle">Toggle Theme</button>
            <form method="post" action="../backend/actions/set_theme.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <select name="theme" onchange="this.form.submit()">
                    <option value="light" <?= ($_SESSION['theme'] ?? 'light') === 'light' ? 'selected' : '' ?>>Light</option>
                    <option value="dark" <?= ($_SESSION['theme'] ?? 'light') === 'dark' ? 'selected' : '' ?>>Dark</option>
                </select>
            </form>
        </div>
    </section>
</main>

<!-- JS for Radar -->
<script>
(async () => {
  const radarContainer = document.getElementById("radar-snapshot");
  try {
    const res = await fetch("../backend/ethiopia_service/radar.php", {
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin'
    });
    let data;
    try { data = await res.json(); }
    catch { radarContainer.textContent = "⚠️ Radar unavailable (invalid response)."; return; }
    if (data?.public?.radar?.tile_url) {
          radarContainer.innerHTML = `
        <h4>Radar Snapshot for ${data.city}</h4>
        <img src="${data.public.radar.tile_url}" alt="Radar tile" />
        ${data.cached_at ? `<p><em>Cached at: ${data.cached_at}</em></p>` : ""}
      `;
    } else {
      radarContainer.textContent = "⚠️ No radar data available.";
    }
  } catch { 
    radarContainer.textContent = "⚠️ Error loading radar snapshot."; 
  }
})();
</script>

<!-- JS for Forecasts -->
<script>
(async () => {
  // Loop through all forecast placeholders
  document.querySelectorAll(".forecast-cards").forEach(async (container) => {
    const cityId = container.id.replace("forecast-", "");
    try {
      // Call backend forecast endpoint for this city
      const res = await fetch(`../backend/ethiopia_service/forecast.php?city_id=${cityId}`, {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
      });
      let data;
      try { data = await res.json(); }
      catch { container.textContent = "⚠️ Forecast unavailable (invalid response)."; return; }

      // Normalize forecast list depending on backend structure
      let forecastList = [];
      if (data?.forecast && data.forecast.length > 0) {
        // Region endpoints
        forecastList = data.forecast;
      } else if (data?.data?.days && data.data.days.length > 0) {
        // Router endpoint
        forecastList = data.user?.forecast || data.data.days;
      }

      if (forecastList.length > 0) {
        container.innerHTML = "<h4>Forecast</h4>";
        const cards = document.createElement("div");
        cards.className = "forecast-card-container";

        // Show first 5 forecast entries
        forecastList.slice(0, 5).forEach(entry => {
          const card = document.createElement("div");
          card.className = "forecast-card";
          card.innerHTML = `
            <p><strong>${entry.date}</strong></p>
            <p>${entry.temperature || entry.min_temp}°C</p>
            <p>${entry.condition}</p>
            ${entry.icon ? `<img src="https://openweathermap.org/img/wn/${entry.icon}@2x.png" alt="${entry.condition}" />` : ""}
          `;
          cards.appendChild(card);
        });

        container.appendChild(cards);
      } else {
        container.textContent = "⚠️ No forecast data available.";
      }
    } catch {
      container.textContent = "⚠️ Error loading forecast.";
    }
  });
})();
</script>


<!-- JS for Regional Alerts -->
<script>
(async () => {
  const alertsContainer = document.getElementById("regional-alerts-overlay");
  try {
    const res = await fetch("../backend/aggregator/merge_feeds.php", {
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin'
    });

    let data;
    try { data = await res.json(); }
    catch { alertsContainer.textContent = "⚠️ Alerts unavailable (invalid response)."; return; }

    if (data?.regions) {
      alertsContainer.innerHTML = "<h4>⚠️ Regional Alerts Overlay</h4>";
      const table = document.createElement("table");
      table.innerHTML = `
        <thead><tr><th>Region</th><th>City</th><th>Alerts</th></tr></thead>
        <tbody></tbody>
      `;
      const tbody = table.querySelector("tbody");

      Object.entries(data.regions).forEach(([region, info]) => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${region}</td>
          <td>${info.city || ""}</td>
          <td>
            ${
              info.alerts && info.alerts.length > 0
              ? `<ul>${info.alerts.map(a =>
                  `<li>
                    <strong>${a.title}</strong> 
                    (${a.issued_at} → ${a.expires_at || "?"}) 
                    <span class="severity-badge severity-${a.severity}">
                      ${a.severity.toUpperCase()}
                    </span>
                    <br><em>${a.description}</em> 
                    ${a.sender ? `— ${a.sender}` : ""}
                  </li>`
                ).join("")}</ul>`
              : "<em>No alerts</em>"
            }
          </td>
        `;
        tbody.appendChild(tr);
      });

      alertsContainer.appendChild(table);
    } else {
      alertsContainer.textContent = "⚠️ No regional alerts available.";
    }
  } catch {
    alertsContainer.textContent = "⚠️ Error loading regional alerts.";
  }
})();
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
</body>
</html>