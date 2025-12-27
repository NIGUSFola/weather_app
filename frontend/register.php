<?php
// frontend/register.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/partials/header.php';

// Success/error banners
$error   = $_GET['error'] ?? null;
$success = $_GET['success'] ?? null;

// CSRF helper (correct path based on folder tree)
require_once __DIR__ . '/../backend/helpers/csrf.php';
$token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title data-i18n="register_title">Register - Ethiopia Weather</title>
   <link rel="stylesheet" href="/weather/frontend/partials/style.css">
</head>
<body>

<main class="page-auth">
    <section class="auth-section">
        <h2 data-i18n="register_title">Create Your Account</h2>

        <?php if ($error): ?>
            <div class="error-message" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-message" role="alert"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- ✅ Corrected action path -->
        <form action="http://localhost/weather/backend/auth/register.php" 
              method="POST" 
              onsubmit="return validateRegisterPassword();" 
              class="auth-form" novalidate>
            
            <div class="form-group">
                <label for="email" data-i18n="email_label">Email Address</label>
                <input type="email" name="email" id="email" required autocomplete="email" placeholder="Enter your email">
            </div>

            <div class="form-group">
                <label for="password" data-i18n="password_label">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" required autocomplete="new-password" placeholder="Create a strong password">
                    <button type="button" class="toggle-btn" onclick="togglePassword('password')" aria-label="Toggle password visibility">👁</button>
                </div>
                <div id="passwordError" class="error-message" aria-live="polite"></div>
            </div>

            <div class="form-group">
                <label for="confirm_password" data-i18n="confirm_password_label">Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" placeholder="Confirm your password">
                    <button type="button" class="toggle-btn" onclick="togglePassword('confirm_password')" aria-label="Toggle password visibility">👁</button>
                </div>
                <div id="confirmError" class="error-message" aria-live="polite"></div>
            </div>

            <!-- CSRF token -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token); ?>">

            <button type="submit" class="btn-primary" data-i18n="register_button">Register</button>
        </form>

        <p class="auth-link">
            <span data-i18n="login_link">Already have an account?</span>
            <a href="login.php" data-i18n="login_here">Login here</a>
        </p>
    </section>
</main>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    field.type = field.type === "password" ? "text" : "password";
}

function validateRegisterPassword() {
    const password = document.getElementById('password').value;
    const confirm  = document.getElementById('confirm_password').value;
    const errorDiv = document.getElementById('passwordError');
    const confirmDiv = document.getElementById('confirmError');

    const hasUpper = /[A-Z]/.test(password);
    const hasLower = /[a-z]/.test(password);
    const hasNumber = /[0-9]/.test(password);

    if (password.length < 8 || !hasUpper || !hasLower || !hasNumber) {
        errorDiv.textContent = "Password must be ≥ 8 chars, include uppercase, lowercase, and numbers.";
        return false;
    } else {
        errorDiv.textContent = "";
    }

    if (password !== confirm) {
        confirmDiv.textContent = "Passwords do not match.";
        return false;
    } else {
        confirmDiv.textContent = "";
    }

    return true;
}
</script>
</body>
</html>
