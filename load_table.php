<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connect.php';

$type = $_GET['type'] ?? '';
$week = $_GET['week'] ?? '';

if($type === 'clinician'){

    $sql = "
        SELECT *
        FROM clinician_timetable
        WHERE week_start = '$week'
        ORDER BY clinician, date ASC
    ";

    $stmt = $pdo->query($sql);


    /*
    |--------------------------------------------------------------------------
    | TARGET
    |--------------------------------------------------------------------------
    */

    $weekQuery = "
        SELECT planned_capacity
        FROM weekly_targets
        WHERE week_start = '$week'
        LIMIT 1
    ";

    $weekStmt = $pdo->query($weekQuery);

    $plannedCapacity = 0;

    if($weekRow = $weekStmt->fetch(PDO::FETCH_ASSOC)){

        $plannedCapacity = $weekRow['planned_capacity'];

    }


    /*
    |--------------------------------------------------------------------------
    | DELIVERED
    |--------------------------------------------------------------------------
    */

    $activityQuery = "

        SELECT SUM(w.weighting) AS total

        FROM clinician_timetable c

        JOIN activity_weights w
        ON c.activity = w.activity

        WHERE c.week_start = '$week'

    ";

    $activityStmt = $pdo->query($activityQuery);

    $deliveredActivity = $activityStmt
        ->fetch(PDO::FETCH_ASSOC)['total'];


    /*
    |--------------------------------------------------------------------------
    | VARIANCE
    |--------------------------------------------------------------------------
    */

    $variance = 0;

    if($plannedCapacity > 0){

        $variance = round(
            (
                ($plannedCapacity - $deliveredActivity)
                / $plannedCapacity
            ) * 100
        );

    }


    /*
    |--------------------------------------------------------------------------
    | STATUS
    |--------------------------------------------------------------------------
    */

    $status = 'GREEN';
    $statusClass = 'success';

    if($variance >= 10){

        $status = 'RED';
        $statusClass = 'danger';

    }
    elseif($variance >= 5){

        $status = 'AMBER';
        $statusClass = 'warning';

    }


    /*
    |--------------------------------------------------------------------------
    | SUMMARY CARDS
    |--------------------------------------------------------------------------
    */

    echo "

    <div class='row mb-4'>

        <div class='col-md-3'>

            <div class='card shadow-sm p-3'>

                <h5>Planned Capacity</h5>

                <p>$plannedCapacity</p>

            </div>

        </div>

        <div class='col-md-3'>

            <div class='card shadow-sm p-3'>

                <h5>Delivered Capacity</h5>

                <p>$deliveredActivity</p>

            </div>

        </div>

        <div class='col-md-3'>

            <div class='card shadow-sm p-3'>

                <h5>Variance</h5>

                <p>$variance%</p>

            </div>

        </div>

        <div class='col-md-3'>

            <div class='card border-$statusClass shadow-sm p-3'>

                <h5>Status</h5>

                <p class='text-$statusClass fw-bold'>

                    $status

                </p>

            </div>

        </div>

    </div>

    ";


    /*
    |--------------------------------------------------------------------------
    | TABLE
    |--------------------------------------------------------------------------
    */

    echo "

    <table class='table table-bordered table-striped'>

        <thead class='table-dark'>

            <tr>

                <th>Date</th>
                <th>Clinician</th>
                <th>Day</th>
                <th>Period</th>
                <th>Activity</th>
                <th>Raw Activity</th>

            </tr>

        </thead>

        <tbody>

    ";

    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){

        $class = '';

        switch($row['activity']){

            case 'THEATRE':
                $class = 'table-primary';
                break;

            case 'STUDY_LEAVE':
                $class = 'table-danger';
                break;

            case 'ANNUAL_LEAVE':
                $class = 'table-warning';
                break;

            case 'SICK':
                $class = 'table-success';
                break;

            case 'CLINIC':
                $class = 'table-info';
                break;

            case 'BLANK':
                $class = 'table-light';
                break;

        }

        echo "

        <tr class='$class'>

            <td>{$row['date']}</td>
            <td>{$row['clinician']}</td>
            <td>{$row['day']}</td>
            <td>{$row['period']}</td>
            <td>{$row['activity']}</td>
            <td>{$row['raw_activity']}</td>

        </tr>

        ";

    }

    echo "

        </tbody>

    </table>

    ";

}

?>