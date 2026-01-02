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

// ✅ CSRF helper
require_once __DIR__ . '/../../backend/helpers/csrf.php';

$user = $_SESSION['user'] ?? null;
?>
<header class="navbar">
  <div class="nav-left">
    <h1 class="logo">🌤️ Ethiopia Weather</h1>
  </div>

  <div class="nav-center">
    <nav class="main-nav">
      <!-- ✅ Public navigation -->
      <a href="/weather/frontend/index.php" class="<?= isActive('index.php') ?>">Home</a>
      <a href="/weather/frontend/forecast.php" class="<?= isActive('forecast.php') ?>">Forecast</a>
      <a href="/weather/frontend/radar.php" class="<?= isActive('radar.php') ?>">Radar</a>
      <a href="/weather/frontend/alerts.php" class="<?= isActive('alerts.php') ?>">Alerts</a>
      <a href="/weather/frontend/api.php" class="<?= isActive('api.php') ?>">API</a>

      <?php if ($user): ?>
        <?php if ($user['role'] === 'admin'): ?>
          <a href="/weather/frontend/dashboard.php" class="<?= isActive('dashboard.php') ?>">Admin Dashboard</a>
        <?php elseif ($user['role'] === 'user'): ?>
          <a href="/weather/frontend/user_dashboard.php" class="<?= isActive('user_dashboard.php') ?>">User Dashboard</a>
        <?php endif; ?>
      <?php else: ?>
        <a href="/weather/frontend/login.php" class="<?= isActive('login.php') ?>">Login</a>
      <?php endif; ?>
    </nav>
  </div>

  <div class="nav-right">
    <!-- ✅ Theme toggle -->
    <button id="themeToggle" title="Toggle theme">🌙</button>


    <?php if ($user): ?>
      <span class="user-info">Welcome, <?= htmlspecialchars($user['email']); ?></span>
      <form action="/weather/backend/auth/logout.php" method="POST" class="logout-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()); ?>">
        <button type="submit" class="logout-btn">🚪 Logout</button>
      </form>
    <?php endif; ?>
  </div>
</header>

<script>
// ✅ Theme toggle with persistence
const themeBtn = document.getElementById('themeToggle');
themeBtn.addEventListener('click', () => {
  document.body.classList.toggle('dark-theme');
  localStorage.setItem('theme', document.body.classList.contains('dark-theme') ? 'dark' : 'light');
});
window.addEventListener('DOMContentLoaded', () => {
  if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark-theme');
  }
});

</script>
