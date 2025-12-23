<?php
// frontend/api.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/partials/header.php';

// ✅ CSRF token setup
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>API Access | Ethiopia Weather</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .section { margin-bottom: 2rem; }
    .card { padding: 1rem; border: 1px solid #ccc; border-radius: 6px; margin-bottom: 1rem; }
    .success-message { color: green; }
    .error-message { color: red; }
    .keys { display: grid; gap: 0.75rem; }
    .key-item { border: 1px solid #ddd; border-radius: 6px; padding: 0.75rem; }
    .key-header { font-weight: 600; margin-bottom: 0.25rem; }
    .key-value { font-family: monospace; background: rgba(0,0,0,0.05); padding: 0.25rem 0.5rem; border-radius: 4px; display: inline-block; }
    .actions { margin-top: 0.5rem; display: flex; gap: 0.5rem; }
    .btn { padding: 0.35rem 0.6rem; border: 1px solid #ccc; border-radius: 4px; background: #f8f8f8; cursor: pointer; }
    .btn-danger { border-color: #d33; color: #d33; background: #fff; }
    .muted { color: #666; }
  </style>
</head>
<body>
  <main class="page page-api">
    <section class="section">
      <h1>API Access</h1>

      <?php if ($success): ?>
        <div class="success-message" aria-live="polite"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="error-message" aria-live="polite"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- Public section -->
      <div class="card">
        <h2>🌍 Public API Information</h2>
        <p class="muted">Ethiopia Weather provides JSON endpoints for forecasts, alerts, and radar data.</p>
        <p>Visitors can explore the API. To generate personal API keys, please log in.</p>
      </div>

      <!-- User-only section -->
      <?php if ($userId && $role === 'user'): ?>
      <div class="card">
        <h2>Manage Keys</h2>
        <p class="muted">Generate and manage your API keys to access the Ethiopia Weather JSON endpoints.</p>
        <form method="post" action="/weather_app/backend/ethiopia_service/api.php?action=create">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf); ?>">
          <button type="submit" class="btn" aria-label="Generate new API key">Generate New API Key</button>
        </form>
      </div>

      <div class="card">
        <h2>Your API Keys</h2>
        <div id="keysList" class="keys" aria-live="polite"><p>Loading keys...</p></div>
      </div>
      <?php endif; ?>

      <!-- Admin-only section -->
      <?php if ($isAdmin): ?>
      <div class="card">
        <h2>🛠 Admin API Tools</h2>
        <p>Admins can view system‑wide API usage and manage user keys.</p>
        <a href="/weather_app/backend/ethiopia_service/admin/admin_api.php">Manage All Keys</a>
      </div>
      <?php endif; ?>
    </section>
  </main>

  <?php include __DIR__ . '/partials/footer.php'; ?>

  <?php if ($userId && $role === 'user'): ?>
  <script>
    function maskKey(k) {
      if (!k || k.length < 12) return k;
      return `${k.slice(0, 6)}••••••••${k.slice(-4)}`;
    }

    async function loadKeys() {
      const list = document.getElementById('keysList');
      try {
        const res = await fetch('/weather_app/backend/ethiopia_service/api.php?action=list', {
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
          toggle.setAttribute('aria-label', 'Reveal or hide API key');
          toggle.addEventListener('click', () => {
            const showingMasked = value.textContent === value.dataset.masked;
            value.textContent = showingMasked ? value.dataset.full : value.dataset.masked;
            toggle.textContent = showingMasked ? 'Hide' : 'Reveal';
          });

          const copy = document.createElement('button');
          copy.type = 'button';
          copy.className = 'btn';
          copy.textContent = 'Copy';
          copy.setAttribute('aria-label', 'Copy API key to clipboard');
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
          form.action = '/weather_app/backend/ethiopia_service/api.php?action=delete';
          form.innerHTML = `
            <input type="hidden" name="csrf_token" value="${csrf}">
            <input type="hidden" name="key" value="${keyObj.api_key}">
            <button type="submit" class="btn btn-danger" aria-label="Delete API key">❌ Delete</button>
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
