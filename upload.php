<?php
/**
 * Upload Handler untuk Dashboard Monitoring
 * Menerima file JSON upload dan menyimpannya ke data_monitoring.json
 * Response berisi metadata untuk disimpan di client
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Hanya terima POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit();
}

// Validate file ada
if (!isset($_FILES['jsonFile']) || $_FILES['jsonFile']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File tidak dikirim atau error']);
    exit();
}

// Get admin name dari form
$adminName = isset($_POST['adminName']) ? trim($_POST['adminName']) : 'Administrator';

try {
    $file = $_FILES['jsonFile'];
    $filePath = $file['tmp_name'];
    
    // Read file content
    $fileContent = file_get_contents($filePath);
    if ($fileContent === false) {
        throw new Exception('Gagal membaca file');
    }
    
    // Validate JSON format
    $jsonData = json_decode($fileContent, true);
    if ($jsonData === null) {
        throw new Exception('File bukan format JSON yang valid');
    }
    
    // Validate harus array
    if (!is_array($jsonData)) {
        throw new Exception('File harus berisi array JSON');
    }
    
    // Validate punya data
    if (empty($jsonData)) {
        throw new Exception('File kosong atau tidak ada data');
    }
    
    // Path untuk save data
    $outputPath = __DIR__ . '/data_monitoring.json';
    
    // Save ke file dengan pretty print
    $jsonOutput = json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (file_put_contents($outputPath, $jsonOutput) === false) {
        throw new Exception('Gagal menyimpan file ke server. Pastikan folder punya permission write.');
    }
    
    // Prepare metadata untuk client
    $uploadTime = date('c'); // ISO 8601 format
    $metadata = [
        'success' => true,
        'message' => 'File berhasil diupload dan disimpan ke server',
        'data' => [
            'uploadedTime' => $uploadTime,
            'uploadedBy' => $adminName,
            'totalRecords' => count($jsonData),
            'fileName' => $file['name'],
            'uploadedAt' => date('Y-m-d H:i:s')
        ]
    ];
    
    http_response_code(200);
    echo json_encode($metadata);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
