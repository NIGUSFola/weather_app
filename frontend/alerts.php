<?php
// weather_app/frontend/alerts.php
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
    <link rel="stylesheet" href="/weather_app/frontend/style.css">
</head>
<body>
<main class="page page-alerts container">
    <section class="alerts-section">
        <h1>⚠️ Severe Weather Alerts</h1>

        <?php if ($success): ?>
            <div class="alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div id="countrySummary" class="card" role="region"><p>Loading country summary...</p></div>
        <div id="publicAlerts" class="card" role="region"><p>Loading public alerts...</p></div>
        <div id="regionalAlerts" class="card" role="region"><p>Loading regional alerts...</p></div>

        <?php if ($userId && $role === 'user'): ?>
            <div id="userAlerts" class="card" role="region"><p>Loading your favorite cities alerts...</p></div>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
            <div id="adminAlerts" class="card" role="region"><p>Loading admin alerts...</p></div>

            <div class="section card">
                <h2>🔍 Admin Alerts Checker</h2>
                <form id="adminAlertsForm" onsubmit="onSubmitAdminAlerts(event)">
                    <select id="adminAlertsCityInput" required>
                        <option value="">-- Select City --</option>
                        <?php
                        require_once __DIR__ . '/../config/db.php';
                        $cities = db()->query("SELECT id, name FROM cities ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($cities as $c) {
                            echo '<option value="'.htmlspecialchars($c['id']).'">'.htmlspecialchars($c['name']).'</option>';
                        }
                        ?>
                    </select>
                    <button type="submit" class="btn">Get Alerts</button>
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

function renderAlertItem(container, alert) {
    const severity = alert.severity || 'moderate';
    const div = document.createElement('div');
    div.className = 'alert-item';
    div.innerHTML = `<strong>${alert.event}</strong> (${alert.start} → ${alert.end})
                     <span class="severity-badge severity-${severity}">${severity.toUpperCase()}</span><br>
                     ${alert.description}<br>
                     Source: ${alert.sender}`;
    container.appendChild(div);
}

function renderForecastList(container, forecast) {
    // forecast is expected as an array; render a compact preview
    if (!Array.isArray(forecast) || forecast.length === 0) {
        container.innerHTML += '<p><em>No forecast data available.</em></p>';
        return;
    }
    const list = document.createElement('ul');
    list.className = 'forecast-list';
    forecast.slice(0, 5).forEach(f => {
        const li = document.createElement('li');
        const date = f.date || f.day || '';
        const temp = (f.temp_min && f.temp_max) ? `${f.temp_min}–${f.temp_max}°C` : (f.temp ? `${f.temp}°C` : '');
        const cond = f.condition || f.summary || '';
        li.textContent = `${date} ${cond ? '— ' + cond : ''} ${temp ? ' (' + temp + ')' : ''}`;
        list.appendChild(li);
    });
    container.appendChild(list);
}

function healthBadge(status) {
    const s = (status || '').toUpperCase();
    const cls = s === 'OK' ? 'health-ok' : 'health-fail';
    return `<span class="health-badge ${cls}">${s || 'UNKNOWN'}</span>`;
}

document.addEventListener("DOMContentLoaded", async () => {
    try {
        // National/public alerts + user/admin meta
        const data = await fetchJson("/weather_app/backend/ethiopia_service/alerts.php");

        // Country + regions aggregator (alerts + optional forecast + health)
        const regional = await fetchJson("/weather_app/backend/aggregator/merge_feeds.php");

        // Country summary
        const summaryContainer = document.getElementById("countrySummary");
        const total = regional?.summary?.total_alerts ?? 0;
        const up    = regional?.summary?.regions_up ?? 0;
        const down  = regional?.summary?.regions_down ?? 0;
        summaryContainer.innerHTML = `<h2>🇪🇹 Country Summary</h2>
                                      <p><strong>Total active alerts:</strong> ${total}</p>
                                      <p><strong>Regions up:</strong> ${up} &nbsp; <strong>Regions down:</strong> ${down}</p>
                                      <p><em>Checked at:</em> ${regional?.checked_at || ''}</p>`;

        // Public alerts
        const publicContainer = document.getElementById("publicAlerts");
        publicContainer.innerHTML = `<h2>🌍 Public Alerts Preview${data.public?.city ? ' ('+data.public.city+')' : ''}</h2>`;
        if (data.public && Array.isArray(data.public.alerts) && data.public.alerts.length > 0) {
            data.public.alerts.forEach(alert => renderAlertItem(publicContainer, alert));
        } else {
            publicContainer.innerHTML += `<p>No public alerts available.</p>`;
        }

        // Regional alerts + health + forecast
        const regionalContainer = document.getElementById("regionalAlerts");
        regionalContainer.innerHTML = "<h2>🇪🇹 Regional Alerts & Forecasts</h2>";
        if (regional.regions && typeof regional.regions === 'object') {
            Object.entries(regional.regions).forEach(([region, info]) => {
                const card = document.createElement('div');
                card.className = 'card';
                const cityText = info.city ? ` (${info.city})` : '';
                card.innerHTML = `<h3>${region}${cityText} ${healthBadge(info?.health?.status)}</h3>`;

                // Alerts section
                if (Array.isArray(info.alerts) && info.alerts.length > 0) {
                    info.alerts.forEach(alert => renderAlertItem(card, alert));
                } else {
                    card.innerHTML += "<p>No active alerts.</p>";
                }

                // Forecast preview
                if (Array.isArray(info.forecast) && info.forecast.length > 0) {
                    const fcHeader = document.createElement('h4');
                    fcHeader.textContent = '📅 Forecast Preview';
                    card.appendChild(fcHeader);
                    renderForecastList(card, info.forecast);
                }

                regionalContainer.appendChild(card);
            });
        } else {
            regionalContainer.innerHTML += "<p>Unable to load regional summaries.</p>";
        }

        // User alerts
        <?php if ($userId && $role === 'user'): ?>
        const userContainer = document.getElementById("userAlerts");
        if (data.user && typeof data.user === 'object') {
            userContainer.innerHTML = '<h2>👤 Your Favorite Cities Alerts</h2>';
            Object.entries(data.user).forEach(([city, info]) => {
                const cityDiv = document.createElement('div');
                cityDiv.className = 'alert-item';
                cityDiv.innerHTML = `<h3>${city}</h3>`;
                if (Array.isArray(info.alerts) && info.alerts.length > 0) {
                    info.alerts.forEach(alert => renderAlertItem(cityDiv, alert));
                    if (info.cached_at) {
                        cityDiv.innerHTML += `<p><em>Cached at: ${info.cached_at}</em></p>`;
                    }
                } else {
                    cityDiv.innerHTML += `<p>No active alerts.</p>`;
                }
                userContainer.appendChild(cityDiv);
            });
        } else {
            userContainer.innerHTML = '<p>No favorites found.</p>';
        }
        <?php endif; ?>

        // Admin alerts meta
        <?php if ($isAdmin): ?>
        const adminContainer = document.getElementById("adminAlerts");
        adminContainer.innerHTML = '<h2>🛠 Admin Alerts Tools</h2>';
        if (data.admin?.meta?.generated_at) {
            adminContainer.innerHTML += `<p><em>Last updated: ${data.admin.meta.generated_at}</em></p>`;
        }
        <?php endif; ?>

    } catch (err) {
        document.getElementById("publicAlerts").innerHTML = `<div class="alert-error">Error loading alerts: ${err.message}</div>`;
        document.getElementById("countrySummary").innerHTML = `<div class="alert-error">Error loading summary: ${err.message}</div>`;
    }
});

// Admin Alerts Checker
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
                card.innerHTML = `<h4>${alert.event} <span class="severity-badge severity-${severity}">${severity.toUpperCase()}</span></h4>
                                  <p>${alert.description}</p>
                                  <small>${alert.start} → ${alert.end}</small>
                                  <p>Source: ${alert.sender}</p>`;
                cards.appendChild(card);
            });
        } else {
            cards.innerHTML = `<div class="alert-error">${data.error || 'No active alerts for this city.'}</div>`;
        }
    } catch (err) {
        cards.innerHTML = `<div class="alert-error">Error loading alerts: ${err.message}</div>`;
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
