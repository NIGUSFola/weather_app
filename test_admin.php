<?php
// test_admin.php
// Verify that the admin user exists and check their role

require_once __DIR__ . '/config/db.php';

try {
    $pdo = db();

    // ✅ Fetch admin user by email
    $stmt = $pdo->prepare("SELECT id, email, role, password_hash FROM users WHERE email = ?");
    $stmt->execute(['admin@gmail.com']);
    $admin = $stmt->fetch();

    if ($admin) {
        echo "✅ Admin user found:<br>";
        echo "ID: " . htmlspecialchars($admin['id']) . "<br>";
        echo "Email: " . htmlspecialchars($admin['email']) . "<br>";
        echo "Role: " . htmlspecialchars($admin['role']) . "<br>";
        echo "Password Hash: " . htmlspecialchars($admin['password_hash']) . "<br>";
    } else {
        echo "❌ No admin user found with email admin@gmail.com";
    }
} catch (Exception $e) {
    echo "❌ Database test failed: " . $e->getMessage();
}
