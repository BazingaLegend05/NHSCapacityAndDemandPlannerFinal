<?php
    session_start();
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    require 'vendor/autoload.php';

    use PhpOffice\PhpSpreadsheet\IOFactory;

    if (!isset($_SESSION['Role']) || $_SESSION['Role'] !== 'admin') {
        http_response_code(403);
        exit("Forbidden");
    }

    if (!isset($_FILES['file'])) {
        exit("No file uploaded");
    }

    $file = $_FILES['file'];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['xls', 'xlsx'])) {
        exit("Invalid file type");
    }

    $tempPath = sys_get_temp_dir() . '/' . uniqid() . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
        exit("Upload failed");
    }

    // =====================
    // DB CONNECTION (EDIT THIS)
    // =====================
    include 'db_connect.php';
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // =====================
    // PROCESS FILE
    // =====================
    try {

        $spreadsheet = IOFactory::load($tempPath);

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $sheetName = $sheet->getTitle();

            // 🔍 DEBUG: show exact sheet name
            echo "Sheet name raw: [" . $sheetName . "]\n";
            var_dump($sheetName);

            $cleanName = trim($sheetName);

            $weekStartObj = DateTime::createFromFormat('d.m.y', $cleanName);

            if (!$weekStartObj) {
                echo "FAILED parsing date: [$cleanName]\n";
                continue; // skip bad sheets
            }

            $weekStart = $weekStartObj->format('Y-m-d');

            // 🚨 DUPLICATE PROTECTION (clinician table = enough check)
            $check = $pdo->prepare("
                SELECT COUNT(*) FROM clinician_timetable WHERE week_start = ?
            ");
            $check->execute([$weekStart]);

            if ($check->fetchColumn() > 0) {
                continue; // skip already imported week
            }

            processSheet($sheet->toArray(), $weekStart, $pdo);
        }

        echo "Upload + processing complete";

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }

    // cleanup
    unlink($tempPath);



    // =====================
    // SHEET PROCESSOR
    // =====================
    function processSheet($data, $weekStart, $pdo) {

        $startRow = null;

        foreach ($data as $i => $row) {
            if (!empty($row[0]) && str_contains($row[0], 'Clinician')) {
                $startRow = $i + 1;
                break;
            }
        }

        if ($startRow === null) return;

        for ($i = $startRow; $i < count($data); $i++) {

            $row = $data[$i];

            if (empty($row[0])) continue;

            $clinician = trim($row[0]);

            if ($clinician === '') break;

            parseClinicianRow($row, $clinician, $weekStart, $pdo);
        }
    }



    // =====================
    // ROW PARSER
    // =====================
    function parseClinicianRow($row, $clinician, $weekStart, $pdo) {

        $days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
        $col = 1;

        foreach ($days as $dayIndex => $day) {

            $am = $row[$col] ?? '';
            $pm = $row[$col + 1] ?? '';

            processSlot($clinician, $weekStart, $dayIndex, $day, 'AM', $am, $pdo);
            processSlot($clinician, $weekStart, $dayIndex, $day, 'PM', $pm, $pdo);

            $col += 2;
        }
    }



    // =====================
    // SLOT PROCESSOR (INSERT CORE)
    // =====================
    function processSlot($clinician, $weekStart, $dayIndex, $day, $period, $value, $pdo) {

        if (empty($value)) return;

        $value = trim($value);

        // classify activity
        $type = 'OTHER';

        if (strpos($value, 'SRH') !== false) {
            $type = 'SRH';
        } elseif (strpos($value, 'Theatre') !== false) {
            $type = 'THEATRE';
        } elseif (strpos($value, 'Wash') !== false) {
            $type = 'WASH';
        } elseif (strpos($value, 'MW') !== false) {
            $type = 'MWM';
        }

        // calculate real date
        $date = new DateTime($weekStart);
        $date->modify("+$dayIndex days");
        $finalDate = $date->format('Y-m-d');

        // =====================
        // INSERT CLINICIAN
        // =====================
        $stmt = $pdo->prepare("
            INSERT INTO clinician_timetable
            (week_start, date, clinician, day, period, activity)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $weekStart,
            $finalDate,
            $clinician,
            $day,
            $period,
            $type
        ]);

        // =====================
        // INSERT THEATRE (optional logic)
        // =====================
        if ($type === 'THEATRE') {

            $stmt2 = $pdo->prepare("
                INSERT INTO theatre_timetable
                (week_start, date, theatre, period, activity)
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