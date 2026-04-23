<?php
require_once 'config.php';
header('Content-Type: application/json; charset=UTF-8');

$stmt = $pdo->query('SELECT update_note, update_time, admin FROM monitoring_pengadaan ORDER BY id DESC LIMIT 1');
$meta = $stmt->fetch();

echo json_encode($meta ?: new stdClass(), JSON_UNESCAPED_UNICODE);
