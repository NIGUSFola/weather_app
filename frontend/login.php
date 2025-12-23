<?php
// frontend/login.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Include header
require_once __DIR__ . '/partials/header.php';

// ✅ Success/error via GET parameters
$success = $_GET['success'] ?? null;
$error   = $_GET['error'] ?? null;

// ✅ Include CSRF helper
require_once __DIR__ . '/../backend/helpers/csrf.php';
$token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Ethiopia Weather</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="page-auth">
    <section class="auth-section">
        <h2>Login to Your Account</h2>

        <!-- ✅ Feedback messages -->
        <?php if ($success): ?>
            <div class="success-message" role="alert"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error-message" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- ✅ Login form -->
        <form method="POST" action="../auth/login.php" novalidate>
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
                        autocomplete="current-password"
                        placeholder="Enter your password">
                    <button 
                        type="button" 
                        class="toggle-btn" 
                        onclick="togglePassword()" 
                        aria-label="Toggle password visibility">👁</button>
                </div>
            </div>

            <!-- ✅ CSRF token -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token); ?>">

            <button type="submit" class="btn-primary">Login</button>
        </form>

        <!-- ✅ Auth links -->
        <p class="auth-link">Don’t have an account? <a href="register.php">Register here</a></p>
        <p class="auth-link"><a href="reset.php">Forgot Password?</a></p>
    </section>
</main>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

<script>
// ✅ Toggle password visibility
function togglePassword() {
    const field = document.getElementById('password');
    field.type = field.type === "password" ? "text" : "password";
}
</script>
</body>
</html>
