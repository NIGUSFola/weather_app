<?php
// frontend/user_dashboard.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/../backend/helpers/auth_middleware.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../backend/helpers/csrf.php';

require_user(); // ✅ Only logged-in users can access

// Flash + query messages
$flash   = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$success = $_GET['success'] ?? null;
$error   = $_GET['error'] ?? null;

// ✅ Fetch user favorites
$userId = $_SESSION['user']['id'];
$stmt = db()->prepare("
    SELECT c.id AS city_id, c.name AS city
    FROM favorites f
    JOIN cities c ON f.city_id = c.id
    WHERE f.user_id = ?
");
$stmt->execute([$userId]);
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Generate CSRF token
$csrfToken = generate_csrf_token();

// ✅ Fetch unified alerts from aggregator (personalized by merge_feeds.php)
$alertsData = [];
try {
    $data = file_get_contents(__DIR__ . '/../backend/aggregator/merge_feeds.php');
    $alertsData = json_decode($data, true);
} catch (Exception $e) {
    $alertsData = ['error' => $e->getMessage()];
}

// ✅ Fetch health status JSON
$healthData = [];
try {
    $healthJson = file_get_contents(__DIR__ . '/../backend/ethiopia_service/health.php');
    $healthData = json_decode($healthJson, true);
} catch (Exception $e) {
    $healthData = ['status' => 'degraded', 'checks' => [], 'time' => date('Y-m-d H:i:s')];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard - Ethiopia Weather</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="page-dashboard">
    <section class="dashboard-section">
        <h1>🌤️ User Dashboard</h1>

        <!-- ✅ Messages -->
        <?php if (!empty($flash)): ?><div class="success-message"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success-message"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error-message"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <!-- ✅ Welcome -->
        <div class="card">
            <h2>Welcome, <?= htmlspecialchars($_SESSION['user']['email'] ?? 'User #'.$_SESSION['user']['id']); ?></h2>
            <p>Here’s your personalized weather dashboard.</p>
        </div>

        <!-- ✅ Favorites -->
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
                <button type="submit">Add Favorite</button>
            </form>

            <?php if ($favorites): ?>
                <ul>
                    <?php foreach ($favorites as $fav): ?>
                        <li>
                            <?= htmlspecialchars($fav['city']); ?>
                            <form method="post" action="../backend/actions/delete_favorite.php" style="display:inline">
                                <input type="hidden" name="city_id" value="<?= htmlspecialchars($fav['city_id']); ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <button type="submit">❌ Remove</button>
                            </form>
                            <div class="forecast-cards" id="forecast-<?= $fav['city_id']; ?>">
                                <p>Loading forecast for <?= htmlspecialchars($fav['city']); ?>...</p>
                            </div>
                            <script>
                                (async () => {
                                    const cards = document.getElementById("forecast-<?= $fav['city_id']; ?>");
                                    try {
                                        const res = await fetch("../backend/ethiopia_service/forecast.php?city_id=<?= $fav['city_id']; ?>", {
                                            headers: { 'Accept': 'application/json' },
                                            credentials: 'same-origin'
                                        });
                                        const data = await res.json();
                                        const forecast = data.data?.days || [];
                                        if (forecast.length > 0) {
                                            cards.innerHTML = '';
                                            forecast.forEach(day => {
                                                const card = document.createElement('div');
                                                card.className = 'forecast-card';
                                                card.innerHTML = `<h4>Day ${day.d}</h4>
                                                                  <p>Temp: ${day.temp}°C</p>
                                                                  <p>${day.cond}</p>`;
                                                cards.appendChild(card);
                                            });
                                            if (data.source === 'cache' && data.data.generated_at) {
                                                cards.innerHTML += `<p><em>Cached at: ${data.data.generated_at}</em></p>`;
                                            }
                                        } else {
                                            cards.textContent = data.error || 'No forecast data.';
                                        }
                                    } catch (err) {
                                        cards.textContent = 'Error loading forecast.';
                                    }
                                })();
                            </script>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No favorites yet. Add a city above!</p>
            <?php endif; ?>
        </div>

        <!-- ✅ Regional Alerts (personalized) -->
        <div class="section alerts-overlay">
            <h3>⚠️ Your City Alerts</h3>
            <?php if (!empty($alertsData['regions'])): ?>
                <?php foreach ($alertsData['regions'] as $region => $info): ?>
                    <div class="alert-card">
                        <h4><?= htmlspecialchars($region); ?> (<?= htmlspecialchars($info['city'] ?? ''); ?>)</h4>
                        <?php if (!empty($info['alerts'])): ?>
                            <?php foreach ($info['alerts'] as $alert): ?>
                                <p><strong><?= htmlspecialchars($alert['event']); ?></strong>
                                   (<?= htmlspecialchars($alert['start']); ?> → <?= htmlspecialchars($alert['end']); ?>)
                                   <span class="severity-badge severity-<?= htmlspecialchars($alert['severity'] ?? 'moderate'); ?>">
                                       <?= strtoupper(htmlspecialchars($alert['severity'] ?? 'moderate')); ?>
                                   </span><br>
                                   <?= htmlspecialchars($alert['description']); ?>
                                </p>
                            <?php endforeach; ?>
                        <?php else: ?><p>No active alerts.</p><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?><p>No alerts available.</p><?php endif; ?>
        </div>

        <!-- ✅ System Health -->
        <div class="section">
            <h3>🩺 System Health</h3>
            <?php if (!empty($healthData['checks'])): ?>
                <table>
                    <thead><tr><th>Check</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($healthData['checks'] as $check => $value): ?>
                            <tr>
                                <td><?= htmlspecialchars(strtoupper($check)); ?></td>
                                <td class="<?= $value ? 'status-ok' : 'status-fail'; ?>">
                                    <?= $value ? 'OK' : 'FAIL'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td><strong>Overall</strong></td>
                            <td class="<?= ($healthData['status'] === 'ok') ? 'status-ok' : 'status-fail'; ?>">
                                <?= htmlspecialchars(strtoupper($healthData['status'])); ?> (<?= htmlspecialchars($healthData['time']); ?>)
                            </td>
                        </tr>
                    </tbody>
                </table>
            <?php else: ?><p>No health data available.</p><?php endif; ?>
        </div>

        <!-- ✅ Account Info -->
        <div class="section">
            <h3>📊 Account Info</h3>
            <p>Email: <?= htmlspecialchars($_SESSION['user']['email'] ?? 'Unknown'); ?></p>
            <p>Role: <?= htmlspecialchars($_SESSION['user']['role'] ?? 'user'); ?></p>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
