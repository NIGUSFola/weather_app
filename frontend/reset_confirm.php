<?php
// frontend/reset_confirmation.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/partials/header.php';

$error   = $_GET['error'] ?? null;
$success = $_GET['success'] ?? null;

// ✅ Include CSRF helper
require_once __DIR__ . '/../backend/helpers/csrf.php';
$token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Reset - Ethiopia Weather</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="page-auth">
    <section class="auth-section">
        <h2>Confirm Password Reset</h2>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-message"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form action="../auth/reset_confirmation.php" method="POST" class="auth-form">
            <!-- ✅ CSRF token -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token); ?>">

            <div class="form-group">
                <label for="token">Reset Token</label>
                <input type="text" name="token" id="token" required>
            </div>

            <div class="form-group">
                <label for="password">New Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" required autocomplete="new-password">
                    <button type="button" class="toggle-btn" onclick="togglePassword('password')" aria-label="Toggle password visibility">👁</button>
                </div>
                <div id="passwordError" class="error-message" aria-live="polite"></div>
            </div>

            <button type="submit">Reset Password</button>
        </form>

        <p class="auth-link">Back to <a href="login.php">Login</a></p>
    </section>
</main>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

<script>
// ✅ Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    field.type = field.type === "password" ? "text" : "password";
}
</script>
</body>
</html>
