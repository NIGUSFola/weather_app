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

// ✅ CSRF helper for admin form
require_once __DIR__ . '/../backend/helpers/csrf.php';
$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>⚠️ Severe Weather Alerts - Ethiopia Weather</title>
    <link rel="stylesheet" href="/weather/frontend/partials/style.css">
    <style>
        .alert-item { border: 1px solid #ccc; padding: 1rem; margin-bottom: 1rem; border-radius: 6px; }
        .error-message { color: red; }
        .success-message { color: green; }
        .card { padding: 1rem; border: 1px solid #ccc; border-radius: 6px; margin-bottom: 1rem; }

        .severity-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: bold;
            margin-left: 0.5rem;
        }
        .severity-low { background-color: #d4edda; color: #155724; }
        .severity-moderate { background-color: #fff3cd; color: #856404; }
        .severity-severe { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<main class="page page-alerts">
    <section class="alerts-section">
        <h1>⚠️ Severe Weather Alerts</h1>

        <?php if ($success): ?>
            <div class="success-message"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Regional alerts -->
        <div id="regionalAlerts"><p>Loading regional alerts...</p></div>

        <!-- User alerts -->
        <?php if ($userId && $role === 'user'): ?>
            <div id="userAlerts"><p>Loading your favorite cities alerts...</p></div>
        <?php endif; ?>

        <!-- Admin alerts -->
        <?php if ($isAdmin): ?>
            <div id="adminAlerts"><p>Loading admin alerts...</p></div>
            <div class="section">
                <h2>🔍 Admin Alerts Checker</h2>
                <form id="adminAlertsForm" onsubmit="onSubmitAdminAlerts(event)" method="POST" action="/weather/backend/ethiopia_service/admin/admin_alerts.php">
                    <select id="adminAlertsCityInput" name="city_id" required>
                        <option value="">-- Select City --</option>
                        <?php
                        require_once __DIR__ . '/../config/db.php';
                        $cities = db()->query("SELECT id, name FROM cities ORDER BY name")->fetchAll();
                        foreach ($cities as $c) {
                            echo '<option value="'.htmlspecialchars($c['id']).'">'.htmlspecialchars($c['name']).'</option>';
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
    const res = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
    const text = await res.text();
    try { return JSON.parse(text); } catch { throw new Error('Invalid server response'); }
}

document.addEventListener("DOMContentLoaded", async () => {
    try {
        // ✅ Regional alerts aggregator
        const data = await fetchJson("/weather/backend/ethiopia_service/alerts.php");

        const regionalContainer = document.getElementById("regionalAlerts");
        if (data.regions) {
            regionalContainer.innerHTML = "<h2>🇪🇹 Regional Alerts Summary</h2>";
            for (const [region, info] of Object.entries(data.regions)) {
                const div = document.createElement('div');
                div.className = 'alert-item';
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
                    div.innerHTML += "<p>No active alerts.</p>";
                }
                regionalContainer.appendChild(div);
            }
        }

        <?php if ($userId && $role === 'user'): ?>
        // ✅ Favorite alerts
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
                        div.innerHTML += `<p><strong>${alert.event}</strong> (${alert.start} → ${alert.end})
                                          <span class="severity-badge severity-${severity}">
                                            ${severity.toUpperCase()}
                                          </span><br>
                                          ${alert.description}</p>`;
                    });
                } else {
                    div.innerHTML += "<p>No active alerts.</p>";
                }

                userContainer.appendChild(div);
            });
        } else {
            userContainer.innerHTML += "<p>No favorites yet.</p>";
        }
        <?php endif; ?>

        <?php if ($isAdmin): ?>
        const adminContainer = document.getElementById("adminAlerts");
        adminContainer.innerHTML = `<h2>🛠 Admin Alerts Tools</h2>
                                    <p>Total alerts across regions: ${data.summary.total_alerts}</p>
                                    <p>Generated at: ${data.summary.generated_at}</p>`;
        <?php endif; ?>

    } catch (err) {
        document.getElementById("regionalAlerts").innerHTML = `<div class="error-message">Error loading alerts: ${err.message}</div>`;
    }
}

<?php if ($isAdmin): ?>
async function fetchAdminAlerts(cityId) {
    const cards = document.getElementById('adminAlertsCards');
    cards.textContent = 'Loading alerts...';
    const url = "/weather/backend/ethiopia_service/alerts.php";
    try {
        const data = await fetchJson(url);
        cards.innerHTML = '';
        if (data.regions) {
            for (const [region, info] of Object.entries(data.regions)) {
                if (info.city_id == cityId || info.city == cityId) {
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
                                              <small>${alert.start} → ${alert.end}</small>`;
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
    const cityId = document.getElementById('adminAlertsCityInput').value;
    if (cityId) fetchAdminAlerts(cityId);
}
<?php endif; ?>
</script>
</body>
</html>
