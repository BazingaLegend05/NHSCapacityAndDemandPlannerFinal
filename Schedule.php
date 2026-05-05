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

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

        <!-- jQuery (FIXED) -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

        <!-- Moment.js -->
        <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>

        <!-- Daterangepicker -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
        <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

        <!-- Your CSS -->
        <link href="MainBody.css" rel="stylesheet">
        <title>NHS Capacity Schedule</title>
    </head>
    <body>
        <nav class="navbar navbar-expand-sm mb-3">
            <div class="container-fluid">
                <ul class="navbar-nav d-flex flex-row justify-content-between">
                    <li class="nav-item mx-3">
                        <img src="NHSLogo.png" class="img-fluid nav-logo" alt="Responsive Image">
                    </li>
                    <li>
                        
                    </li>
                </ul>
            </div>
        </nav>
        <div class="container-fulid">
            <div class="row">
                <div class="col-md-4">
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">Choose a Table To Display
                        <span class="caret"></span></button>
                        <ul class="dropdown-menu">
                            <li>Clinician Schedule</li>
                            <li>Theatre Schedule</li>
                            <li>Clinic Setup</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-4">
                    <div id="datapicker">
                        <input type="text" class="form-control" name="daterange">
                    </div>
                </div>
                <div class="col-md-4">
                    <button>Upload a file</button>
                </div>
                </div>
            </div>
        </div>
        <script>
            $(function() {
                $('input[name="daterange"]').daterangepicker({
                    opens: 'left',
                    locale: {
                        format: 'DD/MM/YYYY'
                    }
                }, function(start, end, label) {
                    console.log("Range selected: " + start.format('YYYY-MM-DD') + " to " + end.format('YYYY-MM-DD'));
                });
            });
        </script>
    </body>
</html>