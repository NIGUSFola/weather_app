<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Reset Request - Ethiopia Weather</title>
      <link rel="stylesheet" href="/weather/frontend/partials/style.css">
</head>
<body>
<div class="container">
    <h1>🔑 Request Password Reset</h1>

    <?php if (isset($_GET['error'])): ?>
        <div class="error-message"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php elseif (isset($_GET['success'])): ?>
        <div class="success-message"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>

    <form method="POST" action="../auth/reset_request.php">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
        
        <label for="email">Email Address:</label>
        <input type="email" id="email" name="email" required>
        
        <button type="submit">Send Reset Link</button>
    </form>
</div>
</body>
</html>
