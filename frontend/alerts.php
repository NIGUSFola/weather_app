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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>⚠️ Severe Weather Alerts - Ethiopia Weather</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .alert-item { border: 1px solid #ccc; padding: 1rem; margin-bottom: 1rem; border-radius: 6px; }
        .error-message { color: red; }
        .success-message { color: green; }
        .card { padding: 1rem; border: 1px solid #ccc; border-radius: 6px; margin-bottom: 1rem; }

        /* ✅ Severity badges */
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
        .severity-high { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<main class="page page-alerts">
    <section class="alerts-section">
        <h1>⚠️ Severe Weather Alerts</h1>

        <!-- Success/Error banners -->
        <?php if ($success): ?>
            <div class="success-message"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Public alerts preview -->
        <div id="publicAlerts" role="alert"><p>Loading public alerts...</p></div>

        <!-- Regional alerts summary -->
        <div id="regionalAlerts" role="alert"><p>Loading regional alerts...</p></div>

        <!-- User alerts -->
        <?php if ($userId && $role === 'user'): ?>
            <div id="userAlerts" role="alert"><p>Loading your favorite cities alerts...</p></div>
        <?php endif; ?>

        <!-- Admin alerts -->
        <?php if ($isAdmin): ?>
            <div id="adminAlerts" role="alert"><p>Loading admin alerts...</p></div>

            <!-- Admin-only Alerts Checker -->
            <div class="section">
                <h2>🔍 Admin Alerts Checker</h2>
                <form id="adminAlertsForm" onsubmit="onSubmitAdminAlerts(event)">
                    <select id="adminAlertsCityInput" required>
                        <option value="">-- Select City --</option>
                        <?php
                        require_once __DIR__ . '/../config/db.php';
                        $cities = db()->query("SELECT id, name FROM cities ORDER BY name")->fetchAll();
                        foreach ($cities as $c) {
                            echo '<option value="'.htmlspecialchars($c['id']).'">'.htmlspecialchars($c['name']).'</option>';
                        }
                        ?>
                    </select>
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
        // National alerts
        const data = await fetchJson("/weather_app/backend/ethiopia_service/alerts.php");

        // Regional alerts (aggregator)
        const regional = await fetchJson("/weather_app/backend/aggregator/merge_feeds.php");

        // Public alerts
        const publicContainer = document.getElementById("publicAlerts");
        if (data.public && Array.isArray(data.public.alerts)) {
            publicContainer.innerHTML = `<h2>🌍 Public Alerts Preview${data.city ? ' ('+data.city+')' : ''}</h2>`;
            if (data.public.alerts.length > 0) {
                data.public.alerts.forEach(alert => {
                    const div = document.createElement('div');
                    div.className = 'alert-item';
                    const severity = alert.severity || 'moderate';
                    div.innerHTML = `<strong>${alert.event}</strong> (${alert.start} → ${alert.end})
                                     <span class="severity-badge severity-${severity}">
                                       ${severity.toUpperCase()}
                                     </span><br>
                                     ${alert.description}<br>
                                     Source: ${alert.sender}`;
                    publicContainer.appendChild(div);
                });
            } else {
                publicContainer.innerHTML += `<p>No public alerts available.</p>`;
            }
        }

        // Regional alerts summary
        const regionalContainer = document.getElementById("regionalAlerts");
        if (regional.regions) {
            regionalContainer.innerHTML = "<h2>🇪🇹 Regional Alerts Summary</h2>";
            for (const [region, info] of Object.entries(regional.regions)) {
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
                                          ${alert.description}<br>
                                          Source: ${alert.sender}</p>`;
                    });
                } else {
                    div.innerHTML += "<p>No active alerts.</p>";
                }
                regionalContainer.appendChild(div);
            }
        }

        // User alerts
        <?php if ($userId && $role === 'user'): ?>
        const userContainer = document.getElementById("userAlerts");
        if (data.user) {
            userContainer.innerHTML = '<h2>👤 Your Favorite Cities Alerts</h2>';
            for (const [city, info] of Object.entries(data.user)) {
                const cityDiv = document.createElement('div');
                cityDiv.className = 'alert-item';
                cityDiv.innerHTML = `<h3>${city}</h3>`;
                if (Array.isArray(info.alerts) && info.alerts.length > 0) {
                    info.alerts.forEach(alert => {
                        const severity = alert.severity || 'moderate';
                        cityDiv.innerHTML += `<p><strong>${alert.event}</strong> (${alert.start} → ${alert.end})
                                              <span class="severity-badge severity-${severity}">
                                                ${severity.toUpperCase()}
                                              </span><br>
                                              ${alert.description}<br>
                                              Source: ${alert.sender}</p>`;
                    });
                    if (info.cached_at) {
                        cityDiv.innerHTML += `<p><em>Cached at: ${info.cached_at}</em></p>`;
                    }
                } else {
                    cityDiv.innerHTML += `<p>No active alerts.</p>`;
                }
                userContainer.appendChild(cityDiv);
            }
        }
        <?php endif; ?>

        // Admin alerts
        <?php if ($isAdmin): ?>
        const adminContainer = document.getElementById("adminAlerts");
        if (data.admin) {
            adminContainer.innerHTML = '<h2>🛠 Admin Alerts Tools</h2>';
            if (data.admin.meta && data.admin.meta.generated_at) {
                adminContainer.innerHTML += `<p><em>Last updated: ${data.admin.meta.generated_at}</em></p>`;
            }
        }
        <?php endif; ?>

    } catch (err) {
        document.getElementById("publicAlerts").innerHTML = `<div class="error-message">Error loading alerts: ${err.message}</div>`;
    }
});

// ✅ Admin alerts checker
<?php if ($isAdmin): ?>
async function fetchAdminAlerts(cityId) {
    const cards = document.getElementById('adminAlertsCards');
    cards.textContent = 'Loading alerts...';
    const url = `/weather_app/backend/ethiopia_service/alerts.php?city_id=${encodeURIComponent(cityId)}`;
    try {
        const data = await fetchJson(url);
        if (data.public && Array.isArray(data.public.alerts) && data.public.alerts.length > 0) {
                       cards.innerHTML = '';
            data.public.alerts.forEach(alert => {
                const severity = alert.severity || 'moderate';
                const card = document.createElement('div');
                card.className = 'card';
                card.innerHTML = `<h4>${alert.event}
                                  <span class="severity-badge severity-${severity}">
                                    ${severity.toUpperCase()}
                                  </span></h4>
                                  <p>${alert.description}</p>
                                  <small>${alert.start} → ${alert.end}</small>
                                  <p>Source: ${alert.sender}</p>`;
                cards.appendChild(card);
            });
        } else {
            cards.innerHTML = `<div class="error-message">${data.error || 'No active alerts for this city.'}</div>`;
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
