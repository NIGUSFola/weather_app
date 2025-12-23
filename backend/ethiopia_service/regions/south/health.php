<?php
// backend/ethiopia_service/regions/south/health.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ERROR | E_PARSE);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../../config/db.php';
require_once __DIR__ . '/../../../../backend/helpers/log.php';

$regionName = 'South';
$status = 'OK';

try {
    db()->query("SELECT 1");
} catch (Exception $e) {
    $status = 'FAIL';
    log_event("Health check failed for $regionName: " . $e->getMessage(), "ERROR", ['module'=>'health','region'=>$regionName]);
}

echo json_encode([
    'region'     => $regionName,
    'status'     => $status,
    'checked_at' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

exit;
