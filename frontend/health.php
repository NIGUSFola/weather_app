<?php
// frontend/health.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/partials/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🩺 System Health Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .health-checks { display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 2rem; }
        .check-card { flex: 1 1 220px; border: 1px solid #ccc; border-radius: 6px; padding: 1rem; text-align: center; }
        .status-ok { color: green; font-weight: bold; }
        .status-fail { color: red; font-weight: bold; }
        .overall-status { font-size: 1.2rem; margin-top: 1rem; }
    </style>
</head>
<body>
<main class="page page-health">
    <h1>🩺 System Health Dashboard</h1>
    <div id="healthStatus">Loading health checks...</div>
    <div class="health-checks" id="healthChecks"></div>
</main>
<?php require_once __DIR__ . '/partials/footer.php'; ?>

<script>
async function fetchRegionHealth(region, url) {
    try {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
        const text = await res.text();
        return { region, data: JSON.parse(text) };
    } catch (err) {
        return { region, data: { status: 'fail', error: err.message } };
    }
}

document.addEventListener("DOMContentLoaded", async () => {
    const endpoints = {
        "National": "/weather_app/backend/ethiopia_service/health.php",
        "Oromia": "/weather_app/backend/ethiopia_service/regions/oromia/health.php",
        "South": "/weather_app/backend/ethiopia_service/regions/south/health.php",
        "Amhara": "/weather_app/backend/ethiopia_service/regions/amhara/health.php"
    };

    const results = await Promise.all(
        Object.entries(endpoints).map(([region, url]) => fetchRegionHealth(region, url))
    );

    // Overall status: if any region fails, mark system as degraded
    const overallOk = results.every(r => r.data.status === 'ok');
    const statusDiv = document.getElementById("healthStatus");
    statusDiv.innerHTML = `<div class="overall-status">System Status: 
        <span class="${overallOk ? 'status-ok' : 'status-fail'}">
            ${overallOk ? 'OK' : 'DEGRADED'}
        </span> (checked at ${new Date().toLocaleString()})
    </div>`;

    // Individual region checks
    const checksDiv = document.getElementById("healthChecks");
    checksDiv.innerHTML = '';
    results.forEach(r => {
        const card = document.createElement('div');
        card.className = 'check-card';
        if (r.data.error) {
            card.innerHTML = `<h3>${r.region}</h3>
                              <p class="status-fail">❌ Error: ${r.data.error}</p>`;
        } else {
            card.innerHTML = `<h3>${r.region}</h3>
                              <p class="${r.data.status === 'ok' ? 'status-ok' : 'status-fail'}">
                                ${r.data.status === 'ok' ? '✅ OK' : '❌ Failed'}
                              </p>`;
            if (r.data.checks) {
                card.innerHTML += "<ul>" + Object.entries(r.data.checks)
                    .map(([k,v]) => `<li>${k}: ${v ? '✅' : '❌'}</li>`).join("") + "</ul>";
            }
        }
        checksDiv.appendChild(card);
    });
});
</script>
</body>
</html>
