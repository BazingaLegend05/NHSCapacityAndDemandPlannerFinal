<?php
session_start();
// increases memory limit for larger excel files
ini_set('memory_limit', '1024M');
set_time_limit(300);
// enables php error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
// checks user is admin
if (!isset($_SESSION['Role']) || $_SESSION['Role'] !== 'admin') {
    http_response_code(403);
    exit("Forbidden");
}
// checks if a file was uploaded
if (!isset($_FILES['file'])) {
    exit("No file uploaded");
}
// conects to databse
include 'db_connect.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$file = $_FILES['file'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
// checks allowed file types
if (!in_array($ext, ['xls', 'xlsx'])) {
    exit("Invalid file type");
}
// creates temp upload path
$tempPath = sys_get_temp_dir() . '/' . uniqid() . '.' . $ext;
// moves uploaded file into temp folder
if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
    exit("Upload failed");
}
// loads and proceses excel file
try {
    $spreadsheet = IOFactory::load($tempPath);
    foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
        $sheetName = trim($sheet->getTitle());
        // converts sheet name into date
        $weekStartObj = DateTime::createFromFormat('d.m.y', $sheetName);
        if (!$weekStartObj) {
            continue;
        }
        $weekStart = $weekStartObj->format('Y-m-d');
        // prevents duplicate uploads
        $check = $pdo->prepare("
            SELECT COUNT(*)
            FROM clinician_timetable
            WHERE week_start = ?
        ");
        $check->execute([$weekStart]);
        if ($check->fetchColumn() > 0) {
            continue;
        }
        processSheet($sheet, $weekStart, $pdo);
    }
    echo "Upload complete";
}
catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
// deletes temp file after upload
unlink($tempPath);
// proceses each excel sheet
function processSheet($sheet, $weekStart, $pdo){
    $highestRow = $sheet->getHighestRow();
    $startRow = null;
    // finds where clinician data starts
    for($i = 1; $i <= $highestRow; $i++){
        $value = trim((string)$sheet->getCell('A'.$i)->getValue());
        if(str_contains($value, 'Clinician')){
            $startRow = $i + 1;
            break;
        }
    }
    if($startRow === null){
        return;
    }
    // loops through clinician rows
    for($row = $startRow; $row <= $highestRow; $row++){
        $clinician = trim((string)$sheet->getCell('A'.$row)->getValue());
        if($clinician === ''){
            continue;
        }
        $upperClinician = strtoupper($clinician);
        // skips junk rows and key labels
        if(
            str_contains($clinician, 'KEY') ||
            str_contains($clinician, 'Study') ||
            str_contains($clinician, 'Clinic') ||
            str_contains($clinician, 'Annual') ||
            str_contains($upperClinician, 'SICK') ||
            str_contains($upperClinician, 'THEATRE') ||
            str_contains($clinician, 'Enter term') ||
            str_contains($clinician, 'state activity')
        ){
            continue;
        }
        parseClinicianRow(
            $sheet,
            $row,
            $clinician,
            $weekStart,
            $pdo
        );
    }
}
// parses each clinician row into am and pm slots
function parseClinicianRow(
    $sheet,
    $rowNum,
    $clinician,
    $weekStart,
    $pdo
){
    $days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
    $col = 2;
    foreach($days as $dayIndex => $day){
        // processes am slot
        processSlot(
            $sheet,
            $rowNum,
            $col,
            $clinician,
            $weekStart,
            $dayIndex,
            $day,
            'AM',
            $pdo
        );
        // proceses pm slot
        processSlot(
            $sheet,
            $rowNum,
            $col + 1,
            $clinician,
            $weekStart,
            $dayIndex,
            $day,
            'PM',
            $pdo
        );
        $col += 2;
    }
}
// handles indivdual timetable slot
function processSlot(
    $sheet,
    $rowNum,
    $colNum,
    $clinician,
    $weekStart,
    $dayIndex,
    $day,
    $period,
    $pdo
){
    $cellReference = Coordinate::stringFromColumnIndex($colNum) . $rowNum;
    $cell = $sheet->getCell($cellReference);
    $value = trim((string)$cell->getValue());
    $fill = $cell->getStyle()->getFill();
    $color = $fill->getStartColor()->getRGB();
    // fallback colour check
    if(!$color){
        $color = $fill->getEndColor()->getRGB();
    }
    // sets default activity type
    $type = 'BLANK';
    // blue = theatre
    if($color === '0000FF'){
        $type = 'THEATRE';
    }
    // red = study leave
    else if($color === 'FF0000'){
        $type = 'STUDY_LEAVE';
    }
    // yellow = annual leave
    else if($color === 'FFFF00'){
        $type = 'ANNUAL_LEAVE';
    }
    // pink = clinic
    else if($color === 'FF00FF'){
        $type = 'CLINIC';
    }
    // green = sick
    else if($color === '00FF00'){
        $type = 'SICK';
    }
    // fallback text parsing if no colour found
    else if(str_contains(strtoupper($value), 'THEATRE')){
        $type = 'THEATRE';
    }
    else if(str_contains(strtoupper($value), 'CLINIC')){
        $type = 'CLINIC';
    }
    else if(str_contains(strtoupper($value), 'LEAVE')){
        $type = 'ANNUAL_LEAVE';
    }
    else if(str_contains(strtoupper($value), 'SICK')){
        $type = 'SICK';
    }
    else if($value !== ''){
        $type = 'OTHER';
    }
    // calculates actual date from week start
    $date = new DateTime($weekStart);
    $date->modify("+$dayIndex days");
    $finalDate = $date->format('Y-m-d');
    // inserts timetable row into databse
    $stmt = $pdo->prepare("
        INSERT INTO clinician_timetable
        (
            week_start,
            date,
            clinician,
            day,
            period,
            activity,
            raw_activity,
            colour_code
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $weekStart,
        $finalDate,
        $clinician,
        $day,
        $period,
        $type,
        $value,
        $color
    ]);
    // inserts theatre sessions into theatre table
    if ($type === 'THEATRE') {
        $stmt2 = $pdo->prepare("
            INSERT INTO theatre_timetable
            (
                week_start,
                date,
                theatre,
                period,
                activity
            )
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt2->execute([
            $weekStart,
            $finalDate,
            $value,
            $period,
            $type
        ]);
    }
}
?>