<?php
// frontend/reset.php

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
    <title>Reset Password - Ethiopia Weather</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="page-auth">
    <section class="auth-section">
        <h2>Reset Your Password</h2>

        <!-- ✅ Feedback messages -->
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-message"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- ✅ Reset form -->
        <form action="../auth/reset.php" method="POST" class="auth-form">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" required autocomplete="email">
            </div>

            <!-- ✅ CSRF token -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token); ?>">

            <button type="submit">Send Reset Link</button>
        </form>

        <!-- ✅ Link back to login -->
        <p class="auth-link">Remembered your password? <a href="login.php">Login here</a></p>
    </section>
</main>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
