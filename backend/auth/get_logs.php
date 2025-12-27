<?php
// auth/get_logs.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../backend/config/db.php';
require_once __DIR__ . '/../helpers/log.php';


// ✅ Protect admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("HTTP/1.1 403 Forbidden");
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$endpoint = $_GET['endpoint'] ?? '';
$status   = $_GET['status'] ?? '';
$limit    = 10;
$offset   = ($page - 1) * $limit;

$where  = [];
$params = [];

if ($endpoint !== '') {
    $where[]  = "endpoint LIKE ?";
    $params[] = "%$endpoint%";
}
if ($status !== '') {
    $where[]  = "status = ?";
    $params[] = $status;
}

$whereSql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

try {
    $pdo = db();

    // ✅ Get logs
    $stmt = $pdo->prepare("SELECT * FROM api_logs $whereSql ORDER BY request_time DESC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ✅ Count total
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM api_logs $whereSql");
    $stmt->execute($params);
    $total       = $stmt->fetchColumn();
    $total_pages = ceil($total / $limit);

    header('Content-Type: application/json');
    echo json_encode([
        'logs'        => $logs,
        'total_pages' => $total_pages
    ]);
} catch (Exception $e) {
    log_event("Error fetching logs: " . $e->getMessage(), "ERROR");
    header("HTTP/1.1 500 Internal Server Error");
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Server error']);
}
