<?php
session_start();
use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($_SESSION['Role']) || $_SESSION['Role'] !== 'admin') {
    http_response_code(403);
    exit("Forbidden");
}

if (!isset($_FILES['file'])) {
    exit("No file uploaded");
}

$file = $_FILES['file'];

// Basic validation
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['xls', 'xlsx'])) {
    exit("Invalid file type");
}

// Move to temp location
$tempPath = sys_get_temp_dir() . '/' . uniqid() . '.' . $ext;

if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
    exit("Upload failed");
}

try {
    require 'vendor/autoload.php';

    $spreadsheet = IOFactory::load($tempPath);
    $data = $spreadsheet->getActiveSheet()->toArray();

    // TEMP DEBUG
    echo "<pre>";
    print_r($data);
    echo "</pre>";

} catch (Exception $e) {
    echo "Error processing file";
}

// Always delete the file
if (file_exists($tempPath)) {
    unlink($tempPath);
}