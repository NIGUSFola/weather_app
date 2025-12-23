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
  <div class="logo">🌤️ Weather Aggregator</div>

  <div class="nav-center">
    <button id="themeToggle" title="Toggle theme">🌙</button>
  </div>

  <nav class="main-nav">
    <!-- ✅ Public navigation -->
    <a href="../frontend/index.php" class="<?= isActive('index.php') ?>">Home</a>
    <a href="../frontend/forecast.php" class="<?= isActive('forecast.php') ?>">Forecast</a>
    <a href="../frontend/radar.php" class="<?= isActive('radar.php') ?>">Radar</a>
    <a href="../frontend/alerts.php" class="<?= isActive('alerts.php') ?>">Alerts</a>
    <a href="../frontend/api.php" class="<?= isActive('api.php') ?>">API</a>

    <?php if ($user): ?>
      <?php if ($user['role'] === 'admin'): ?>
        <!-- ✅ Admin navigation -->
        <a href="../frontend/dashboard.php" class="<?= isActive('dashboard.php') ?>">Admin Dashboard</a>
      <?php elseif ($user['role'] === 'user'): ?>
        <!-- ✅ User navigation -->
        <a href="../frontend/user_dashboard.php" class="<?= isActive('user_dashboard.php') ?>">User Dashboard</a>
      <?php endif; ?>

      <!-- ✅ Show welcome + logout -->
      <span class="user-info">Welcome, <?= htmlspecialchars($user['email']); ?></span>
      <form action="../auth/logout.php" method="POST" style="display:inline;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()); ?>">
        <button type="submit" class="logout-btn">Logout</button>
      </form>
    <?php else: ?>
      <!-- ✅ Guest navigation -->
      <a href="../frontend/login.php" class="<?= isActive('login.php') ?>">Login</a>
    <?php endif; ?>
  </nav>
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
