<?php

session_start();
header('Content-Type: application/json');

require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}


if (!isset($_SESSION['operator_id'])) {
    echo json_encode(['error' => 'Unauthorized']); exit;
}

if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'No video uploaded or upload error']); exit;
}

$file     = $_FILES['video'];
$ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed  = ['mp4','avi','mov','mkv','webm'];

if (!in_array($ext, $allowed)) {
    echo json_encode(['error' => 'Invalid file type']); exit;
}

// Save uploaded video
$uploadDir = __DIR__ . '/../uploads/videos/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$filename  = 'vid_' . uniqid() . '.' . $ext;
$filepath  = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode(['error' => 'Failed to save video']); exit;
}

//Python detection model
$python = "py"; 

$scriptPath = realpath(__DIR__ . '/../model/detect.py');
$videoPath  = realpath($filepath);

if (!$scriptPath || !$videoPath) {
    echo json_encode(['error' => 'Path resolution failed']);
    exit;
}

// Build command safely
$cmd = escapeshellcmd($python) . ' ' .
       escapeshellarg($scriptPath) . ' ' .
       escapeshellarg($videoPath);

// Execute
$output = shell_exec($cmd . ' 2>&1');

// Decode Python JSON output
$result = json_decode(trim($output), true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($result['accident'])) {
    echo json_encode([
        'accident' => false,
        'confidence' => 0.0,
        'error' => 'Model execution failed',
        'raw_output' => $output
    ]);
    exit;
}


// Log to database
$conn    = getDB();
$op_id   = $_SESSION['operator_id'];
$acc     = $result['accident'] ? 1 : 0;
$conf    = floatval($result['confidence']);
$fname   = $file['name'];

$stmt = $conn->prepare(
    "INSERT INTO detection_logs (operator_id, filename, accident_detected, confidence, detected_at)
     VALUES (?, ?, ?, ?, NOW())"
);
$stmt->bind_param("isid", $op_id, $fname, $acc, $conf);
$stmt->execute();
$stmt->close();
$conn->close();

echo json_encode($result);
?>
