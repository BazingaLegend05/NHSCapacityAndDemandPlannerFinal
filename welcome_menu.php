<?php
    session_start();
    if($_SESSION['LoggedIn'] !== 1){
        echo "You are here by mistake";
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Latest compiled JavaScript -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <link href="MainBody.css" type="text/css" rel="stylesheet">
        <title>NHS Capacity Welcome</title>
    </head>
    <body>
        <nav class="navbar navbar-expand-sm mb-3">
            <div class="container-fluid">
                <ul class="navbar-nav d-flex flex-row justify-content-between">
                    <li class="nav-item mx-3">
                        <img src="NHSLogo.png" class="img-fluid nav-logo" alt="Responsive Image">
                    </li>
                </ul>
            </div>
        </nav>
        <div class="container-fluid ">
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <div>
                        <p>Welcome to the NHS capacity and demand planner. \nl You can select schedule 
                            to view and edit clinician schedules, or Statistics Dashboard to view progress on yearly targets.</p>
                    </div>
                    <div class="container-fluid d-flex justify-content-center p-5">
                        <input type="submit" id="Button" value="Schedule">
                    </div>
                    <div class="container-fluid d-flex justify-content-center p-5">
                        <input type="submit" id="Button" value="Dashboard">
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>