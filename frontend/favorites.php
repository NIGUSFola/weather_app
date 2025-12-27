<?php
// frontend/favorites.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/../backend/helpers/auth_middleware.php';
require_user(); // ✅ enforce login

$success = $_GET['success'] ?? null;
$error   = $_GET['error'] ?? null;

// ✅ CSRF token setup
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>⭐ Favorites | Ethiopia Weather</title>
    <link rel="stylesheet" href="/weather/frontend/partials/style.css">
    <style>
        .favorites-card { border: 1px solid #ccc; padding: 1rem; margin-bottom: 1rem; border-radius: 6px; }
        .form-group { margin-bottom: 0.5rem; }
        .success-message { color: green; }
        .error-message { color: red; }
        ul { list-style: none; padding: 0; }
        li { margin-bottom: 0.5rem; }
        button { margin-left: 0.5rem; }
    </style>
</head>
<body>
<main class="page-favorites">
    <section class="favorites-section">
        <h1>⭐ Your Favorite Cities</h1>

        <!-- Success/Error banners -->
        <?php if ($success): ?>
            <div class="success-message"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Add Favorite -->
        <div class="favorites-card">
            <h2>Add a City</h2>
            <form method="POST" action="/weather/backend/actions/add_favorite.php">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="form-group">
                    <label for="city_id">Select City</label>
                    <select id="city_id" name="city_id" required>
                        <option value="">-- Select City --</option>
                        <?php
                        require_once __DIR__ . '/../config/db.php';
                        $cities = db()->query("SELECT id, name FROM cities ORDER BY name")->fetchAll();
                        foreach ($cities as $c) {
                            echo '<option value="'.htmlspecialchars($c['id']).'">'.htmlspecialchars($c['name']).'</option>';
                        }
                        ?>
                    </select>
                </div>
                <button type="submit">Add to Favorites</button>
            </form>
        </div>

        <!-- List Favorites -->
        <div class="favorites-card">
            <h2>Saved Cities</h2>
            <ul>
                <?php
                try {
                    if (isset($_SESSION['user']['id'])) {
                        $stmt = db()->prepare("
                            SELECT c.id, c.name 
                            FROM favorites f
                            JOIN cities c ON f.city_id = c.id
                            WHERE f.user_id = :user_id
                        ");
                        $stmt->execute([':user_id' => $_SESSION['user']['id']]);
                        $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if ($favorites) {
                            foreach ($favorites as $fav) {
                                $cityName = htmlspecialchars($fav['name']);
                                $cityId   = (int)$fav['id'];
                                echo "<li>
                                        {$cityName}
                                        <form method='POST' action='/weather/backend/actions/delete_favorite.php' style='display:inline'>
                                            <input type='hidden' name='csrf_token' value='{$csrfToken}'>
                                            <input type='hidden' name='city_id' value='{$cityId}'>
                                            <button type='submit'>Remove</button>
                                        </form>
                                        <form method='POST' action='/weather/backend/actions/set_default_city.php' style='display:inline'>
                                            <input type='hidden' name='csrf_token' value='{$csrfToken}'>
                                            <input type='hidden' name='city_id' value='{$cityId}'>
                                            <button type='submit'>Set Default</button>
                                        </form>
                                      </li>";
                            }
                        } else {
                            echo "<li>No favorites yet. Add a city above!</li>";
                        }
                    } else {
                        echo "<li>Please login to manage favorites.</li>";
                    }
                } catch (Exception $e) {
                    echo "<li>Error loading favorites: " . htmlspecialchars($e->getMessage()) . "</li>";
                }
                ?>
            </ul>
        </div>

        <!-- Forecasts for Favorites -->
        <div class="favorites-card">
            <h2>Forecasts for Favorites</h2>
            <div id="favoritesForecast"><p>Loading forecasts...</p></div>
        </div>

        <!-- Theme toggle -->
        <div class="favorites-card">
            <h2>Theme</h2>
            <form method="POST" action="/weather/backend/actions/set_theme.php">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <select name="theme">
                    <option value="light">Light</option>
                    <option value="dark">Dark</option>
                </select>
                <button type="submit">Apply</button>
            </form>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", async () => {
  async function fetchJson(url) {
    const res = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
    const text = await res.text();
    try { return JSON.parse(text); }
    catch { throw new Error('Invalid server response'); }
  }

  async function loadFavoriteForecasts() {
    try {
      const data = await fetchJson("/weather/backend/actions/favorite_forecast.php");
      const container = document.getElementById('favoritesForecast');
      container.innerHTML = '';

      if (data.favorites && data.favorites.length > 0) {
        data.favorites.forEach(fav => {
          const block = document.createElement('div');
          block.className = 'favorites-card';
          block.innerHTML = `<h3>${fav.city} (${fav.status})</h3>`;

          if (fav.forecast && fav.forecast.length > 0) {
            fav.forecast.forEach(entry => {
              block.innerHTML += `<p><strong>${entry.date}</strong> - ${entry.condition}, ${entry.temp}°C</p>`;
            });
          } else {
            block.innerHTML += `<p class="error-message">No forecast available</p>`;
          }

          container.appendChild(block);
        });
      } else {
        container.innerHTML = `<p>No favorites yet</p>`;
      }
    } catch (err) {
      document.getElementById('favoritesForecast').innerHTML = `<p class="error-message">Error loading forecasts: ${err.message}</p>`;
    }
  }

  loadFavoriteForecasts();
});
</script>
</body>
</html>
