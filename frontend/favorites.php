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
    <link rel="stylesheet" href="style.css">
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
            <form method="POST" action="/weather_app/backend/actions/add_favorite.php">
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
                                        <form method='POST' action='/weather_app/backend/actions/delete_favorite.php' style='display:inline'>
                                            <input type='hidden' name='csrf_token' value='{$csrfToken}'>
                                            <input type='hidden' name='city_id' value='{$cityId}'>
                                            <button type='submit'>Remove</button>
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
    </section>
</main>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
