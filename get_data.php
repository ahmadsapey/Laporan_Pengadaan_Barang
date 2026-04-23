<?php
require_once 'config.php';
header('Content-Type: application/json; charset=UTF-8');

$stmt = $pdo->query('SELECT file_path FROM monitoring_pengadaan ORDER BY id DESC LIMIT 1');
$latest = $stmt->fetch();

$data = [];
if ($latest && !empty($latest['file_path']) && file_exists($latest['file_path'])) {
    $json = file_get_contents($latest['file_path']);
    $decoded = json_decode($json, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $data = $decoded;
    }
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
