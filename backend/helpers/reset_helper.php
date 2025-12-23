<?php
// backend/helpers/reset_helper.php

/**
 * Generate a secure random reset token.
 *
 * @param int $length Length of the token (default 32 chars).
 * @return string
 */
function generate_reset_token($length = 32) {
    return bin2hex(random_bytes($length / 2)); // cryptographically secure
}

/**
 * Create a reset request: store token + expiry in DB.
 *
 * @param PDO $pdo Database connection
 * @param string $email User email
 * @return string Generated token
 */
function create_reset_request($pdo, $email) {
    $token   = generate_reset_token();
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
    $stmt->execute([$token, $expires, $email]);

    return $token;
}

/**
 * Send reset email with HTML template.
 *
 * @param string $email Recipient email
 * @param string $token Reset token
 * @return bool Success/failure of mail() function
 */
function send_reset_email($email, $token) {
    // Link to frontend confirmation page
    $resetLink = "http://yourdomain.com/frontend/reset_confirmation.php?token=" . urlencode($token);

    $subject = "Password Reset Request - Ethiopia Weather";

    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background-color: #f9f9f9; }
            .container { max-width: 600px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; }
            h2 { color: #2c3e50; }
            p { font-size: 14px; color: #333; }
            a.button { display: inline-block; padding: 10px 20px; background: #3498db; color: #fff; text-decoration: none; border-radius: 4px; }
            a.button:hover { background: #2980b9; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>🔐 Password Reset</h2>
            <p>Hello,</p>
            <p>We received a request to reset your password for <strong>Ethiopia Weather</strong>.</p>
            <p>Click the button below to reset your password:</p>
            <p><a href='$resetLink' class='button'>Reset Password</a></p>
            <p>If you did not request this, please ignore this email.</p>
            <p>Thanks,<br>The Ethiopia Weather Team</p>
        </div>
    </body>
    </html>
    ";

    $headers  = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: no-reply@yourdomain.com\r\n";

    return mail($email, $subject, $message, $headers);
}
