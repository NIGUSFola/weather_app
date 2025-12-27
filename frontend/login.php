<?php
// frontend/login.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/partials/header.php';

// Success/error banners
$success = $_GET['success'] ?? null;
$error   = $_GET['error'] ?? null;

// CSRF helper (correct path based on folder tree)
require_once __DIR__ . '/../backend/helpers/csrf.php';
$token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title data-i18n="login_title">Login - Ethiopia Weather</title>
    <link rel="stylesheet" href="/weather/frontend/partials/style.css">
</head>
<body>


<main class="page-auth">
    <section class="auth-section">
        <h2 data-i18n="login_title">Login to Your Account</h2>

        <?php if ($success): ?>
            <div class="success-message" role="alert"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error-message" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- ✅ Corrected action path -->
        <form method="POST" action="http://localhost/weather/backend/auth/login.php" novalidate>
            <div class="form-group">
                <label for="email" data-i18n="email_label">Email Address</label>
                <input type="email" name="email" id="email" required autocomplete="email" placeholder="Enter your email">
            </div>

            <div class="form-group">
                <label for="password" data-i18n="password_label">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
                    <button type="button" class="toggle-btn" onclick="togglePassword()" aria-label="Toggle password visibility">👁</button>
                </div>
            </div>

            <!-- CSRF token -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token); ?>">

            <button type="submit" class="btn-primary" data-i18n="login_button">Login</button>
        </form>

        <p class="auth-link">
            <span data-i18n="register_link">Don’t have an account?</span>
            <a href="register.php" data-i18n="register_here">Register here</a>
        </p>
        <p class="auth-link"><a href="reset.php" data-i18n="forgot_password">Forgot Password?</a></p>
    </section>
</main>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

<script>
function togglePassword() {
    const field = document.getElementById('password');
    field.type = field.type === "password" ? "text" : "password";
}
</script>
</body>
</html>
