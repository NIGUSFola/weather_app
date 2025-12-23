<?php
// seed_admin.php
// Run this once to insert a default admin user into the database

// Update with your actual DB credentials
$host = "localhost";
$db   = "weather_app";
$user = "root";       // replace with your MySQL username
$pass = "";           // replace with your MySQL password

try {
    // ✅ Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ✅ Generate secure hash for the admin password
    $password = "Admin@123";
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // ✅ Insert admin user
    $stmt = $pdo->prepare(
        "INSERT INTO users (email, password_hash, role, created_at) 
         VALUES (?, ?, 'admin', NOW())"
    );
    $stmt->execute(['admin@gmail.com', $hash]);

    echo "✅ Admin user created successfully:\n";
    echo "   Email: admin@gmail.com\n";
    echo "   Password: $password\n";
    echo "   Role: admin\n";

} catch (Exception $e) {
    echo "❌ Error seeding admin: " . $e->getMessage();
}
