<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Guard isActive to avoid redeclare errors
if (!function_exists('isActive')) {
    function isActive($page) {
        return basename($_SERVER['PHP_SELF']) === $page ? 'active' : '';
    }
}

require_once __DIR__ . '/../../backend/helpers/csrf.php';

$user = $_SESSION['user'] ?? null;
?>
<header class="navbar">
  <div class="nav-left">
    <a href="/weather_app/frontend/index.php" class="logo">🌤️ Weather Aggregator</a>
  </div>

  <div class="nav-center">
    <nav class="main-nav">
      <!-- ✅ Public navigation -->
      <a href="/weather_app/frontend/index.php" class="<?= isActive('index.php') ?>">Home</a>
      <a href="/weather_app/frontend/forecast.php" class="<?= isActive('forecast.php') ?>">Forecast</a>
      <a href="/weather_app/frontend/radar.php" class="<?= isActive('radar.php') ?>">Radar</a>
      <a href="/weather_app/frontend/alerts.php" class="<?= isActive('alerts.php') ?>">Alerts</a>
      <a href="/weather_app/frontend/api.php" class="<?= isActive('api.php') ?>">API</a>

      <?php if ($user): ?>
        <?php if ($user['role'] === 'admin'): ?>
          <a href="/weather_app/frontend/dashboard.php" class="<?= isActive('dashboard.php') ?>">Admin Dashboard</a>
        <?php elseif ($user['role'] === 'user'): ?>
          <a href="/weather_app/frontend/user_dashboard.php" class="<?= isActive('user_dashboard.php') ?>">User Dashboard</a>
        <?php endif; ?>
      <?php endif; ?>
    </nav>
  </div>

  <div class="nav-right">

    <?php if ($user): ?>
      <span class="user-info">Welcome, <?= htmlspecialchars($user['email']); ?></span>
      <form action="/weather_app/auth/logout.php" method="POST" class="logout-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()); ?>">
        <button type="submit" class="logout-btn">Logout</button>
      </form>
    <?php else: ?>
      <a href="/weather_app/frontend/login.php" class="<?= isActive('login.php') ?>">Login</a>
    <?php endif; ?>

    <button id="themeToggle" title="Toggle theme">🌙</button>
  </div>
</header>

<script>
// ✅ Theme toggle with persistence
document.addEventListener('DOMContentLoaded', () => {
  const themeBtn = document.getElementById('themeToggle');
  const savedTheme = localStorage.getItem('theme') || 'light';
  if (savedTheme === 'dark') {
    document.body.classList.add('dark-theme');
    themeBtn.textContent = '☀️';
  } else {
    themeBtn.textContent = '🌙';
  }

  themeBtn.addEventListener('click', () => {
    document.body.classList.toggle('dark-theme');
    const isDark = document.body.classList.contains('dark-theme');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    themeBtn.textContent = isDark ? '☀️' : '🌙';
  });
});
</script>
