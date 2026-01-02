<?php
// backend/ethiopia_service/admin/admin_cache.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../helpers/auth_middleware.php';
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../helpers/log.php';
require_once __DIR__ . '/../../helpers/csrf.php';
require_once __DIR__ . '/../../helpers/weather_api.php';
require_once __DIR__ . '/../../helpers/forecast.php';

require_admin();

$success = $_GET['success'] ?? null;
$error   = $_GET['error'] ?? null;
$csrfToken = generate_csrf_token();

// ✅ Handle cache actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header("Location: admin_cache.php?error=Invalid+CSRF+token");
        exit;
    }

    $action = $_POST['action'] ?? null;
    $cityId = intval($_POST['city_id'] ?? 0);

    try {
        if ($action === 'clear_all') {
            db()->exec("DELETE FROM weather_cache");
            log_event("Cache cleared by admin", "INFO", ['module'=>'admin_cache','admin'=>$_SESSION['user']['id']]);
            header("Location: admin_cache.php?success=All+cache+entries+cleared");
            exit;
        }
        if ($action === 'clear_city' && $cityId > 0) {
            $stmt = db()->prepare("DELETE FROM weather_cache WHERE city_id=:city_id");
            $stmt->execute([':city_id'=>$cityId]);
            log_event("Cache cleared for city_id {$cityId}", "INFO", ['module'=>'admin_cache','admin'=>$_SESSION['user']['id'],'city_id'=>$cityId]);
            header("Location: admin_cache.php?success=Cache+cleared+for+city+ID+{$cityId}");
            exit;
        }
        if ($action === 'refresh_city' && $cityId > 0) {
            $stmt = db()->prepare("SELECT name FROM cities WHERE id=:id");
            $stmt->execute([':id'=>$cityId]);
            $city = $stmt->fetchColumn();
            if ($city) {
                $apiConfig = require __DIR__ . '/../../../config/api.php';
                $apiKey    = $apiConfig['openweathermap'] ?? '';
                $forecast  = getForecastForCity($city, $apiKey, 'en', 'metric');
                if ($forecast) {
                    $stmtCache = db()->prepare("REPLACE INTO weather_cache(city_id,type,payload,updated_at) VALUES(?,?,?,NOW())");
                    $stmtCache->execute([$cityId,'forecast',json_encode($forecast)]);
                    log_event("Cache refreshed for city {$city}", "INFO", ['module'=>'admin_cache','admin'=>$_SESSION['user']['id'],'city_id'=>$cityId]);
                    header("Location: admin_cache.php?success=Cache+refreshed+for+{$city}");
                    exit;
                }
            }
        }
        if ($action === 'refresh_all') {
            $cities = db()->query("SELECT id,name FROM cities")->fetchAll(PDO::FETCH_ASSOC);
            $apiConfig = require __DIR__ . '/../../../config/api.php';
            $apiKey    = $apiConfig['openweathermap'] ?? '';
            foreach ($cities as $c) {
                $forecast = getForecastForCity($c['name'], $apiKey, 'en', 'metric');
                if ($forecast) {
                    $stmtCache = db()->prepare("REPLACE INTO weather_cache(city_id,type,payload,updated_at) VALUES(?,?,?,NOW())");
                    $stmtCache->execute([$c['id'],'forecast',json_encode($forecast)]);
                }
            }
            log_event("All cache refreshed by admin", "INFO", ['module'=>'admin_cache','admin'=>$_SESSION['user']['id']]);
            header("Location: admin_cache.php?success=All+cache+entries+refreshed");
            exit;
        }
    } catch (Exception $e) {
        header("Location: admin_cache.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}

// ✅ Fetch cache entries
try {
    $stmt = db()->query("SELECT wc.city_id, c.name AS city_name, wc.type, wc.updated_at
                         FROM weather_cache wc
                         LEFT JOIN cities c ON wc.city_id=c.id
                         ORDER BY wc.updated_at DESC");
    $cacheEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $cacheEntries = [];
    log_event("Failed to fetch cache entries: ".$e->getMessage(), "ERROR", ['module'=>'admin_cache']);
}

// ✅ Fetch cities for dropdown
$cities = [];
try {
    $cities = db()->query("SELECT id,name FROM cities ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Cache Management</title>
<link rel="stylesheet" href="/weather/frontend/style.css">
<style>
table { border-collapse: collapse; width: 100%; margin-top: 1rem; }
th, td { border: 1px solid #ccc; padding: 8px; }
th { background: #f0f0f0; }
.success-message { color: green; }
.error-message { color: red; }
.badge { padding:4px 8px; border-radius:4px; font-weight:bold; }
.status-fresh { background:#2ecc71; color:white; }
.status-stale { background:#f39c12; color:white; }
.status-missing { background:#e74c3c; color:white; }
</style>
</head>
<body>
<div class="container">
<h1>🗄 Cache Management</h1>
<?php include __DIR__ . '/../../../frontend/partials/admin_nav.php'; ?>

<?php if ($success): ?><div class="success-message"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="error-message"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div>
  <form method="POST" onsubmit="return confirm('Clear all cache entries?');">
    <input type="hidden" name="action" value="clear_all">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <button type="submit">Clear All Cache</button>
  </form>
  <form method="POST" onsubmit="return confirm('Refresh all cache entries?');">
    <input type="hidden" name="action" value="refresh_all">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <button type="submit">Refresh All Cache</button>
  </form>
</div>

<div>
  <h3>Clear Cache for Specific City</h3>
  <form method="POST" onsubmit="return confirm('Clear cache for this city?');">
    <input type="hidden" name="action" value="clear_city">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <select name="city_id" required>
      <option value="">-- Select City --</option>
      <?php foreach ($cities as $c): ?>
        <option value="<?= htmlspecialchars($c['id']) ?>"><?= htmlspecialchars($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit">Clear City Cache</button>
  </form>
</div>

<div>
  <h3>Existing Cache Entries</h3>
  <table>
    <tr><th>City</th><th>Type</th><th>Last Updated</th><th>Status</th><th>Actions</th></tr>
    <?php if (empty($cacheEntries)): ?>
      <tr><td colspan="5">No cache entries found.</td></tr>
    <?php else: ?>
      <?php foreach ($cacheEntries as $entry): 
        $status = 'MISSING';
        if ($entry['updated_at']) {
          $age = time() - strtotime($entry['updated_at']);
          if ($age < 600) $status = 'FRESH';
          elseif ($age < 3600) $status = 'STALE';
        }
        $cls = strtolower("status-$status");
      ?>
        <tr>
          <td><?= htmlspecialchars($entry['city_name'] ?? 'Unknown') ?></td>
          <td><?= htmlspecialchars($entry['type']) ?></td>
          <td><?= htmlspecialchars($entry['updated_at'] ?? '-') ?></td>
          <td><span class="badge <?= $cls ?>"><?= $status ?></span></td>
          <td>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="refresh_city">
              <input type="hidden" name="city_id" value="<?= $entry['city_id'] ?>">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                          <button type="submit">Refresh</button>
            </form>
            <form method="POST" style="display:inline" onsubmit="return confirm('Clear cache for this city?');">
              <input type="hidden" name="action" value="clear_city">
              <input type="hidden" name="city_id" value="<?= $entry['city_id'] ?>">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
              <button type="submit">Clear</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </table>
</div>

</div>
</body>
</html>
