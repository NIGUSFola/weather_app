<?php
// frontend/alerts.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/partials/header.php';

$success = $_GET['success'] ?? null;
$error   = $_GET['error'] ?? null;
$role    = $_SESSION['user']['role'] ?? null;
$userId  = $_SESSION['user']['id'] ?? null;
$isAdmin = ($role === 'admin');

require_once __DIR__ . '/../backend/helpers/csrf.php';
$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>⚠️ Severe Weather Alerts - Ethiopia Weather</title>
    <link rel="stylesheet" href="/weather/frontend/style.css">
</head>
<body>
<main class="page page-alerts">
    <section class="alerts-section">
        <h1>⚠️ Severe Weather Alerts</h1>

        <?php if ($success): ?><div class="success-message"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error-message"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <!-- Regional alerts -->
        <div id="regionalAlerts"><p>Loading regional alerts...</p></div>

        <!-- User favorites -->
        <?php if ($userId && $role === 'user'): ?>
            <div id="userAlerts"><p>Loading your favorite cities alerts...</p></div>
        <?php endif; ?>

        <!-- Admin tools -->
        <?php if ($isAdmin): ?>
            <div id="adminAlerts"><p>Loading admin alerts...</p></div>
            <div class="section">
                <h2>🔍 Admin Alerts Checker</h2>
                <form id="adminAlertsForm" onsubmit="onSubmitAdminAlerts(event)" method="POST" action="/weather/backend/ethiopia_service/admin/admin_alerts.php">
                    <select id="adminAlertsCityInput" name="city" required>
                        <option value="">-- Select City --</option>
                        <?php
                        require_once __DIR__ . '/../config/db.php';
                        $cities = db()->query("SELECT name FROM cities ORDER BY name")->fetchAll();
                        foreach ($cities as $c) {
                            echo '<option value="'.htmlspecialchars($c['name']).'">'.htmlspecialchars($c['name']).'</option>';
                        }
                        ?>
                    </select>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                    <button type="submit">Get Alerts</button>
                </form>
                <div id="adminAlertsCards">Select a city to view alerts.</div>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

<script>
async function fetchJson(url) {
    try {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return await res.json();
    } catch (err) {
        throw new Error(`Failed to fetch ${url}: ${err.message}`);
    }
}

function formatTime(ts) {
    if (!ts) return '';
    // handle both numeric timestamps and string dates
    if (!isNaN(ts)) return new Date(Number(ts) * 1000).toLocaleString();
    return new Date(ts).toLocaleString();
}

document.addEventListener("DOMContentLoaded", async () => {
    const regionalContainer = document.getElementById("regionalAlerts");
    regionalContainer.innerHTML = "<p>Loading regional alerts...</p>";

    try {
        // ✅ Regional alerts
        const data = await fetchJson("/weather/backend/ethiopia_service/alerts.php");
        regionalContainer.innerHTML = "<h2>🇪🇹 Regional Alerts Summary</h2>";

        for (const [region, info] of Object.entries(data.regions)) {
            const div = document.createElement('div');
            div.className = 'alert-item';
            div.innerHTML = `<h3>${region} (${info.city})</h3>`;

            if (Array.isArray(info.alerts) && info.alerts.length > 0) {
                info.alerts.forEach(alert => {
                    const severity = alert.severity || 'moderate';
                    div.innerHTML += `<p><strong>${alert.event}</strong>
                                      (${formatTime(alert.start)} → ${formatTime(alert.end)})
                                      <span class="severity-badge severity-${severity}">
                                        ${severity.toUpperCase()}
                                      </span><br>${alert.description}</p>`;
                });
            } else {
                div.innerHTML += "<p>No active alerts.</p>";
            }
            regionalContainer.appendChild(div);
        }

        <?php if ($userId && $role === 'user'): ?>
        // ✅ Favorite alerts
        try {
            const favData = await fetchJson("/weather/backend/actions/favorite_alerts.php");
            const userContainer = document.getElementById("userAlerts");
            userContainer.innerHTML = "<h2>👤 Your Favorite Cities Alerts</h2>";

            if (favData.favorites && favData.favorites.length > 0) {
                favData.favorites.forEach(fav => {
                    const div = document.createElement('div');
                    div.className = 'alert-item';
                    div.innerHTML = `<h3>${fav.city} (${fav.status})</h3>`;

                    if (Array.isArray(fav.alerts) && fav.alerts.length > 0) {
                        fav.alerts.forEach(alert => {
                            const severity = alert.severity || 'moderate';
                            div.innerHTML += `<p><strong>${alert.event}</strong>
                                              (${formatTime(alert.start)} → ${formatTime(alert.end)})
                                              <span class="severity-badge severity-${severity}">
                                                ${severity.toUpperCase()}
                                              </span><br>${alert.description}</p>`;
                        });
                    } else {
                        div.innerHTML += "<p>No active alerts.</p>";
                    }
                    userContainer.appendChild(div);
                });
            } else {
                userContainer.innerHTML += "<p>No favorites yet.</p>";
            }
        } catch (err) {
            const userContainer = document.getElementById("userAlerts");
            userContainer.innerHTML = `<div class="error-message">Error loading favorite alerts: ${err.message}</div>`;
        }
        <?php endif; ?>

        <?php if ($isAdmin): ?>
        // ✅ Admin summary
        const adminContainer = document.getElementById("adminAlerts");
        adminContainer.innerHTML = `<h2>🛠 Admin Alerts Tools</h2>
                                    <p>Total alerts across regions: ${data.summary.total_alerts}</p>
                                    <p>Generated at: ${data.summary.generated_at}</p>`;
        <?php endif; ?>
    } catch (err) {
        regionalContainer.innerHTML = `<div class="error-message">Error loading alerts: ${err.message}</div>`;
    }
});

<?php if ($isAdmin): ?>
async function fetchAdminAlerts(cityName) {
    const cards = document.getElementById('adminAlertsCards');
    cards.textContent = 'Loading alerts...';
    try {
        const data = await fetchJson("/weather/backend/ethiopia_service/alerts.php");
        cards.innerHTML = '';
        if (data.regions) {
            for (const [region, info] of Object.entries(data.regions)) {
                if (info.city === cityName) {
                    if (Array.isArray(info.alerts) && info.alerts.length > 0) {
                        info.alerts.forEach(alert => {
                            const severity = alert.severity || 'moderate';
                            const card = document.createElement('div');
                            card.className = 'card';
                            card.innerHTML = `<h4>${alert.event}
                                              <span class="severity-badge severity-${severity}">
                                                ${severity.toUpperCase()}
                                              </span></h4>
                                              <p>${alert.description}</p>
                                              <small>${formatTime(alert.start)} → ${formatTime(alert.end)}</small>`;
                            cards.appendChild(card);
                        });
                    } else {
                        cards.innerHTML = `<div class="error-message">No active alerts for this city.</div>`;
                    }
                }
            }
        }
    } catch (err) {
        cards.innerHTML = `<div class="error-message">Error loading alerts: ${err.message}</div>`;
    }
}

function onSubmitAdminAlerts(e) {
    e.preventDefault();
    const cityName = document.getElementById('adminAlertsCityInput').value;
    if (cityName) fetchAdminAlerts(cityName);
}
<?php endif; ?>
</script>
</body>
</html>
