<?php
// frontend/register.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/partials/header.php';

// ✅ Success/error via GET parameters
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
    <title>Register - Ethiopia Weather</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="page-auth">
    <section class="auth-section">
        <h2>Create Your Account</h2>

        <!-- ✅ Feedback messages -->
        <?php if ($error): ?>
            <div class="error-message" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-message" role="alert"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- ✅ Registration form -->
        <form action="../auth/register.php" method="POST" onsubmit="return validateRegisterPassword();" class="auth-form" novalidate>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    name="email" 
                    id="email" 
                    required 
                    autocomplete="email"
                    placeholder="Enter your email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        autocomplete="new-password"
                        placeholder="Create a strong password">
                    <button 
                        type="button" 
                        class="toggle-btn" 
                        onclick="togglePassword('password')" 
                        aria-label="Toggle password visibility">👁</button>
                </div>
                <div id="passwordError" class="error-message" aria-live="polite"></div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-wrapper">
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required 
                        autocomplete="new-password"
                        placeholder="Confirm your password">
                    <button 
                        type="button" 
                        class="toggle-btn" 
                        onclick="togglePassword('confirm_password')" 
                        aria-label="Toggle password visibility">👁</button>
                </div>
                <div id="confirmError" class="error-message" aria-live="polite"></div>
            </div>

            <!-- ✅ CSRF token -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token); ?>">

            <button type="submit" class="btn-primary">Register</button>
        </form>

        <!-- ✅ Link back to login -->
        <p class="auth-link">Already have an account? <a href="login.php">Login here</a></p>
    </section>
</main>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

<script>
// ✅ Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    field.type = field.type === "password" ? "text" : "password";
}

// ✅ Strong password validation with confirmation
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
