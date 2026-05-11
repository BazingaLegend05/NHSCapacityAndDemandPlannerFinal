<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if($_SESSION['LoggedIn'] !== 1){

    echo "You are here by mistake";
    exit();

}
include 'db_connect.php';


/*
|--------------------------------------------------------------------------
| SELECTED WEEK
|--------------------------------------------------------------------------
*/

$selectedWeek = $_GET['week'] ?? '';


/*
|--------------------------------------------------------------------------
| GET ALL WEEKS
|--------------------------------------------------------------------------
*/

$weeksQuery = "
    SELECT DISTINCT week_start
    FROM clinician_timetable
    ORDER BY week_start DESC
";

$weeksStmt = $pdo->query($weeksQuery);


/*
|--------------------------------------------------------------------------
| DEFAULT TO LATEST WEEK
|--------------------------------------------------------------------------
*/

if($selectedWeek === ''){

    $latestWeekQuery = "
        SELECT MAX(week_start) AS latest_week
        FROM clinician_timetable
    ";

    $latestWeekStmt = $pdo->query($latestWeekQuery);

    $selectedWeek = $latestWeekStmt
        ->fetch(PDO::FETCH_ASSOC)['latest_week'];

}


/*
|--------------------------------------------------------------------------
| UPDATE WEIGHTINGS
|--------------------------------------------------------------------------
*/

if(isset($_POST['save_weights'])){

    foreach($_POST['weights'] as $activity => $weight){

        $update = $pdo->prepare("
            UPDATE activity_weights
            SET weighting = ?
            WHERE activity = ?
        ");

        $update->execute([
            $weight,
            $activity
        ]);

    }

    echo "

    <script>

        window.location.href =
        'Dashboard.php?week=$selectedWeek';

    </script>

    ";

}


/*
|--------------------------------------------------------------------------
| SAVE PLANNED CAPACITY
|--------------------------------------------------------------------------
*/

if(isset($_POST['planned_capacity'])){

    $newCapacity = $_POST['planned_capacity'];

    $checkTarget = $pdo->prepare("
        SELECT COUNT(*)
        FROM weekly_targets
        WHERE week_start = ?
    ");

    $checkTarget->execute([$selectedWeek]);

    if($checkTarget->fetchColumn() > 0){

        $updateTarget = $pdo->prepare("
            UPDATE weekly_targets
            SET planned_capacity = ?
            WHERE week_start = ?
        ");

        $updateTarget->execute([
            $newCapacity,
            $selectedWeek
        ]);

    }
    else{

        $insertTarget = $pdo->prepare("
            INSERT INTO weekly_targets
            (
                week_start,
                planned_capacity
            )
            VALUES (?, ?)
        ");

        $insertTarget->execute([
            $selectedWeek,
            $newCapacity
        ]);

    }

    echo "

    <script>

        window.location.href =
        'Dashboard.php?week=$selectedWeek';

    </script>

    ";

}


/*
|--------------------------------------------------------------------------
| SAVE SESSION
|--------------------------------------------------------------------------
*/

if(isset($_POST['add_session'])){

    $clinician = $_POST['clinician'];
    $date = $_POST['date'];
    $period = $_POST['period'];
    $activity = $_POST['activity'];

    $day = date(
        'D',
        strtotime($date)
    );

    /*
    |--------------------------------------------------------------------------
    | CHECK EXISTING SLOT
    |--------------------------------------------------------------------------
    */

    $check = $pdo->prepare("

        SELECT id

        FROM clinician_timetable

        WHERE clinician = ?
        AND date = ?
        AND period = ?

        LIMIT 1

    ");

    $check->execute([
        $clinician,
        $date,
        $period
    ]);

    $existing = $check->fetch(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | UPDATE EXISTING
    |--------------------------------------------------------------------------
    */

    if($existing){

        $update = $pdo->prepare("

            UPDATE clinician_timetable

            SET activity = ?,
                raw_activity = ?

            WHERE id = ?

        ");

        $update->execute([
            $activity,
            $activity,
            $existing['id']
        ]);

    }


    /*
    |--------------------------------------------------------------------------
    | INSERT NEW
    |--------------------------------------------------------------------------
    */

    else{

        $insert = $pdo->prepare("

            INSERT INTO clinician_timetable
            (
                week_start,
                date,
                clinician,
                day,
                period,
                activity,
                raw_activity
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)

        ");

        $insert->execute([
            $selectedWeek,
            $date,
            $clinician,
            $day,
            $period,
            $activity,
            $activity
        ]);

    }

    echo "

    <script>

        window.location.href =
        'Dashboard.php?week=$selectedWeek';

    </script>

    ";

}


/*
|--------------------------------------------------------------------------
| TOTAL CLINICIANS
|--------------------------------------------------------------------------
*/

$totalCliniciansQuery = "
    SELECT COUNT(DISTINCT clinician) AS total
    FROM clinician_timetable
    WHERE week_start = '$selectedWeek'
";

$totalCliniciansStmt = $pdo->query($totalCliniciansQuery);

$totalClinicians = $totalCliniciansStmt
    ->fetch(PDO::FETCH_ASSOC)['total'];


/*
|--------------------------------------------------------------------------
| THEATRE
|--------------------------------------------------------------------------
*/

$theatreQuery = "
    SELECT COUNT(*) AS total
    FROM clinician_timetable
    WHERE activity = 'THEATRE'
    AND week_start = '$selectedWeek'
";

$theatreStmt = $pdo->query($theatreQuery);

$totalTheatre = $theatreStmt
    ->fetch(PDO::FETCH_ASSOC)['total'];


/*
|--------------------------------------------------------------------------
| LEAVE
|--------------------------------------------------------------------------
*/

$leaveQuery = "
    SELECT COUNT(*) AS total
    FROM clinician_timetable
    WHERE activity IN
    (
        'ANNUAL_LEAVE',
        'STUDY_LEAVE',
        'SICK'
    )
    AND week_start = '$selectedWeek'
";

$leaveStmt = $pdo->query($leaveQuery);

$totalLeave = $leaveStmt
    ->fetch(PDO::FETCH_ASSOC)['total'];


/*
|--------------------------------------------------------------------------
| DELIVERED CAPACITY
|--------------------------------------------------------------------------
*/

$deliveredQuery = "

    SELECT SUM(w.weighting) AS total

    FROM clinician_timetable c

    JOIN activity_weights w
    ON c.activity = w.activity

    WHERE c.week_start = '$selectedWeek'

";

$deliveredStmt = $pdo->query($deliveredQuery);

$deliveredCapacity = $deliveredStmt
    ->fetch(PDO::FETCH_ASSOC)['total'];


/*
|--------------------------------------------------------------------------
| CURRENT TARGET
|--------------------------------------------------------------------------
*/

$targetQuery = $pdo->prepare("
    SELECT planned_capacity
    FROM weekly_targets
    WHERE week_start = ?
    LIMIT 1
");

$targetQuery->execute([$selectedWeek]);

$currentTarget = 0;

if(
    $targetRow = $targetQuery
        ->fetch(PDO::FETCH_ASSOC)
){

    $currentTarget =
        $targetRow['planned_capacity'];

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>NHS Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <link href="MainBody.css"
          rel="stylesheet">

</head>

<body>

    <nav class="navbar navbar-expand-sm mb-4 bg-primary">

        <div class="container-fluid">

            <ul class="navbar-nav d-flex flex-row align-items-center">

                <li class="nav-item mx-3">

                    <img src="NHSLogo.png"
                         class="img-fluid nav-logo"
                         alt="NHS Logo">

                </li>

                <li class="nav-item mx-2">

                    <a href="schedule.php"
                       class="btn btn-outline-light">

                        Schedule

                    </a>

                </li>

                <li class="nav-item mx-2">

                    <a href="logout.php"
                       class="btn btn-danger">

                        Logout

                    </a>

                </li>

            </ul>

        </div>

    </nav>


    <div class="container-fluid">


        <!-- WEEK SELECTOR -->

        <div class="row mb-4">

            <div class="col-md-4">

                <form method="GET">

                    <label class="form-label fw-bold">

                        Select Week

                    </label>

                    <select name="week"
                            class="form-select"
                            onchange="this.form.submit()">

                        <?php

                        while(
                            $weekRow = $weeksStmt
                                ->fetch(PDO::FETCH_ASSOC)
                        ){

                            $week = $weekRow['week_start'];

                            $selected = '';

                            if($week === $selectedWeek){

                                $selected = 'selected';

                            }

                            echo "

                            <option value='$week' $selected>

                                $week

                            </option>

                            ";

                        }

                        ?>

                    </select>

                </form>

            </div>

        </div>


        <!-- DASHBOARD CARDS -->

        <div class="row g-4">

            <div class="col-md-3">

                <div class="card shadow-lg p-4 text-center">

                    <h4>Total Clinicians</h4>

                    <h1 class="display-4">

                        <?php echo $totalClinicians; ?>

                    </h1>

                </div>

            </div>


            <div class="col-md-3">

                <div class="card shadow-lg p-4 text-center border-primary">

                    <h4>Theatre Sessions</h4>

                    <h1 class="display-4 text-primary">

                        <?php echo $totalTheatre; ?>

                    </h1>

                </div>

            </div>


            <div class="col-md-3">

                <div class="card shadow-lg p-4 text-center border-warning">

                    <h4>Total Leave</h4>

                    <h1 class="display-4 text-warning">

                        <?php echo $totalLeave; ?>

                    </h1>

                </div>

            </div>


            <div class="col-md-3">

                <div class="card shadow-lg p-4 text-center border-success">

                    <h4>Delivered Capacity</h4>

                    <h1 class="display-4 text-success">

                        <?php echo $deliveredCapacity; ?>

                    </h1>

                </div>

            </div>

        </div>


        <!-- PLANNED CAPACITY -->

        <div class="row mt-5">

            <div class="col-md-6">

                <div class="card shadow-lg p-4">

                    <h3 class="mb-4">

                        Planned Capacity

                    </h3>

                    <form method="POST">

                        <label class="form-label fw-bold">

                            Planned Capacity For Week:
                            <?php echo $selectedWeek; ?>

                        </label>

                        <input
                            type="number"
                            name="planned_capacity"
                            class="form-control mb-3"
                            value="<?php echo $currentTarget; ?>"
                        >

                        <button class="btn btn-success">

                            Save Capacity

                        </button>

                    </form>

                </div>

            </div>

        </div>


        <!-- ADD SESSION -->

        <div class="row mt-5">

            <div class="col-md-6">

                <div class="card shadow-lg p-4">

                    <h3 class="mb-4">

                        Add / Update Session

                    </h3>

                    <form method="POST">

                        <label class="form-label fw-bold">

                            Clinician

                        </label>

                        <input
                            type="text"
                            name="clinician"
                            class="form-control mb-3"
                            required
                        >


                        <label class="form-label fw-bold">

                            Date

                        </label>

                        <input
                            type="date"
                            name="date"
                            class="form-control mb-3"
                            required
                        >


                        <label class="form-label fw-bold">

                            Period

                        </label>

                        <select
                            name="period"
                            class="form-select mb-3"
                            required
                        >

                            <option value="AM">AM</option>
                            <option value="PM">PM</option>

                        </select>


                        <label class="form-label fw-bold">

                            Activity

                        </label>

                        <select
                            name="activity"
                            class="form-select mb-4"
                            required
                        >

                            <option value="THEATRE">
                                THEATRE
                            </option>

                            <option value="CLINIC">
                                CLINIC
                            </option>

                            <option value="MWM">
                                MWM
                            </option>

                            <option value="WASH">
                                WASH
                            </option>

                            <option value="OTHER">
                                OTHER
                            </option>

                            <option value="ANNUAL_LEAVE">
                                ANNUAL LEAVE
                            </option>

                            <option value="STUDY_LEAVE">
                                STUDY LEAVE
                            </option>

                            <option value="SICK">
                                SICK
                            </option>

                        </select>


                        <button
                            type="submit"
                            name="add_session"
                            class="btn btn-primary"
                        >

                            Save Session

                        </button>

                    </form>

                </div>

            </div>

        </div>


        <!-- WEIGHTINGS -->

        <div class="row mt-5">

            <div class="col-md-6">

                <div class="card shadow-lg p-4">

                    <h3 class="mb-4">

                        Activity Weightings

                    </h3>

                    <form method="POST">

                        <table class="table table-bordered">

                            <thead class="table-dark">

                                <tr>

                                    <th>Activity</th>
                                    <th>Weighting</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php

                                $weightsQuery = "

                                    SELECT *

                                    FROM activity_weights

                                    WHERE activity NOT IN
                                    (
                                        'ANNUAL_LEAVE',
                                        'STUDY_LEAVE',
                                        'SICK',
                                        'BLANK'
                                    )

                                    ORDER BY activity ASC

                                ";

                                $weightsStmt = $pdo->query($weightsQuery);

                                while(
                                    $weightRow = $weightsStmt
                                        ->fetch(PDO::FETCH_ASSOC)
                                ){

                                    echo "

                                    <tr>

                                        <td>

                                            {$weightRow['activity']}

                                        </td>

                                        <td>

                                            <input
                                                type='number'
                                                name='weights[{$weightRow['activity']}]'
                                                value='{$weightRow['weighting']}'
                                                class='form-control'
                                            >

                                        </td>

                                    </tr>

                                    ";

                                }

                                ?>

                            </tbody>

                        </table>

                        <button
                            type="submit"
                            name="save_weights"
                            class="btn btn-primary"
                        >

                            Save Weightings

                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</body>

</html>