<?php
// frontend/api.php
// Bridge between frontend and backend aggregator + API key management UI

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ JSON feed mode
if (isset($_GET['action']) && $_GET['action'] === 'feed') {
    header('Content-Type: application/json; charset=utf-8');
    require __DIR__ . '/../backend/aggregator/merge_feeds.php';
    exit;
}

// ✅ Otherwise: render API key management UI
require_once __DIR__ . '/partials/header.php';

// CSRF token setup
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf    = $_SESSION['csrf_token'];
$success = $_GET['success'] ?? null;
$error   = $_GET['error'] ?? null;
$role    = $_SESSION['user']['role'] ?? null;
$userId  = $_SESSION['user']['id'] ?? null;
$isAdmin = ($role === 'admin');
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <title>API Access | Ethiopia Weather</title>
  <link rel="stylesheet" href="/weather/frontend/partials/style.css">
</head>
<body>
  <main class="page page-api">
    <section class="section">
      <h1>API Access</h1>

      <?php if ($success): ?>
        <div class="success-message"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- Public section -->
      <div class="card">
        <h2>🌍 Public API Information</h2>
        <p class="muted">Ethiopia Weather provides JSON endpoints for forecasts and alerts.</p>
        <p><strong>Unified feed:</strong> <code>/weather/frontend/api.php?action=feed</code></p>
      </div>

      <!-- User-only section -->
      <?php if ($userId && $role === 'user'): ?>
      <div class="card">
        <h2>Manage Your Keys</h2>
        <form method="post" action="/weather/backend/ethiopia_service/api.php?action=create">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf); ?>">
          <button type="submit" class="btn">Generate New API Key</button>
        </form>
      </div>

      <div class="card">
        <h2>Your API Keys</h2>
        <div id="keysList" class="keys"><p>Loading keys...</p></div>
      </div>
      <?php endif; ?>

      <!-- Admin-only section -->
      <?php if ($isAdmin): ?>
      <div class="card">
        <h2>🛠 Admin API Tools</h2>
        <p>Admins can view system‑wide API usage and manage user keys.</p>
        <a href="/weather/backend/ethiopia_service/admin/admin_api.php" class="btn">Manage All Keys</a>
      </div>

      <div class="card">
        <h2>Your API Keys (Admin)</h2>
        <div id="keysList" class="keys"><p>Loading keys...</p></div>
      </div>
      <?php endif; ?>
    </section>
  </main>

  <?php include __DIR__ . '/partials/footer.php'; ?>

  <?php if ($userId): ?>
  <script>
    function maskKey(k) {
      if (!k || k.length < 12) return k;
      return `${k.slice(0, 6)}••••••••${k.slice(-4)}`;
    }

    async function loadKeys() {
      const list = document.getElementById('keysList');
      if (!list) return;
      try {
        const res = await fetch('/weather/backend/ethiopia_service/api.php?action=list', {
          headers: { 'Accept': 'application/json' },
          credentials: 'same-origin'
        });
        if (!res.ok) throw new Error('Bad response');
        const data = await res.json();

        const csrf = data.csrf_token || '<?= htmlspecialchars($csrf); ?>';
        const keys = Array.isArray(data.keys) ? data.keys : [];

        list.innerHTML = '';
        if (keys.length === 0) {
          list.innerHTML = '<p>No API keys yet. Generate one above.</p>';
          return;
        }

        keys.forEach((keyObj) => {
          const item = document.createElement('div');
          item.className = 'key-item';

          const header = document.createElement('div');
          header.className = 'key-header';
          header.textContent = `${keyObj.key_name} (created ${keyObj.created_at})`;

          const value = document.createElement('span');
          value.className = 'key-value';
          value.textContent = maskKey(keyObj.api_key);
          value.dataset.full = keyObj.api_key;
          value.dataset.masked = value.textContent;

          const toggle = document.createElement('button');
          toggle.type = 'button';
          toggle.className = 'btn';
          toggle.textContent = 'Reveal';
          toggle.addEventListener('click', () => {
            const showingMasked = value.textContent === value.dataset.masked;
            value.textContent = showingMasked ? value.dataset.full : value.dataset.masked;
            toggle.textContent = showingMasked ? 'Hide' : 'Reveal';
          });

          const copy = document.createElement('button');
          copy.type = 'button';
          copy.className = 'btn';
          copy.textContent = 'Copy';
          copy.addEventListener('click', async () => {
            try {
              await navigator.clipboard.writeText(value.dataset.full);
              copy.textContent = 'Copied';
              setTimeout(() => (copy.textContent = 'Copy'), 1200);
            } catch {
              alert('Copy failed. Please copy manually.');
            }
          });

          const actions = document.createElement('div');
          actions.className = 'actions';

          const form = document.createElement('form');
          form.method = 'post';
          form.action = '/weather/backend/ethiopia_service/api.php?action=delete';
          form.innerHTML = `
            <input type="hidden" name="csrf_token" value="${csrf}">
            <input type="hidden" name="key" value="${keyObj.api_key}">
            <button type="submit" class="btn btn-danger">❌ Delete</button>
          `;
          form.addEventListener('submit', (e) => {
            const ok = confirm('Delete this API key? This cannot be undone.');
            if (!ok) e.preventDefault();
          });

          item.append(header, value, toggle, copy, actions);
          actions.appendChild(form);
          list.appendChild(item);
        });
      } catch (err) {
        list.textContent = 'Error loading keys.';
      }
    }

    document.addEventListener('DOMContentLoaded', loadKeys);
  </script>
  <?php endif; ?>
</body>
</html>
