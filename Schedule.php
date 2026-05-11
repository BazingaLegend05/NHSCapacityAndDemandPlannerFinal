<?php
    session_start();
    // checks if user is logged in
    if($_SESSION['LoggedIn'] !== 1){
        echo "You are here by mistake";
        exit();
    }
    // gets user role from session
    $Role = $_SESSION['Role'];
    // conects to databse
    include 'db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible"
          content="ie=edge">
    <!-- bootstrap css -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <!-- bootstrap js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- main css -->
    <link href="MainBody.css"
          rel="stylesheet">
    <title>nhs capacity schedule</title>
</head>
<body>
    <!-- navbar -->
    <nav class="navbar navbar-expand-sm mb-3 bg-primary">
        <div class="container-fluid">
            <ul class="navbar-nav d-flex flex-row align-items-center">
                <!-- nhs logo -->
                <li class="nav-item mx-3">
                    <img src="NHSLogo.png"
                         class="img-fluid nav-logo"
                         alt="NHS Logo">
                </li>
                <!-- back buton -->
                <li class="nav-item mx-2">
                    <button class="btn btn-outline-light"
                            onclick="history.back()">
                        ← Back
                    </button>
                </li>
                <!-- logout button -->
                <li class="nav-item mx-2">
                    <a href="logout.php"
                       class="btn btn-danger">
                        logout
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    <!-- main page content -->
    <div class="container-fluid">
        <div class="row">
            <!-- table selector -->
            <div class="col-md-4">
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle"
                            type="button"
                            data-bs-toggle="dropdown">
                        choose a table to display
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <button type="button"
                                    class="dropdown-item"
                                    onclick="loadTable('clinician')">
                                clinician schedule
                            </button>
                        </li>
                        <li>
                            <button type="button"
                                    class="dropdown-item"
                                    onclick="loadTable('theatre')">
                                theatre schedule
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
            <!-- week selector -->
            <div class="col-md-4">
                <select id="weekSelect"
                        class="form-select">
                    <option value="">
                        select week
                    </option>
                    <?php
                        // gets all uploaded weeks
                        $weekQuery = "
                            SELECT DISTINCT week_start
                            FROM clinician_timetable
                            ORDER BY week_start ASC
                        ";
                        $weekStmt = $pdo->query($weekQuery);
                        while($week = $weekStmt->fetch(PDO::FETCH_ASSOC)){
                            echo "
                            <option value='{$week['week_start']}'>
                                Week Starting {$week['week_start']}
                            </option>
                            ";
                        }
                    ?>
                </select>
            </div>
            <!-- upload button -->
            <div class="col-md-4">
                <?php if ($Role === 'admin'): ?>
                    <button onclick="openUploadPanel()"
                            class="btn btn-primary">
                        upload a file
                    </button>
                <?php endif; ?>
                <!-- upload pannel -->
                <div id="uploadPanel"
                     class="upload-panel"
                     style="display:none;">
                    <div class="upload-box">
                        <h5>upload excel file</h5>
                        <div id="dropZone"
                             class="drop-zone">
                            Drag & drop Excel file here
                            <br>
                            or click to select
                            <input type="file"
                                   id="fileInput"
                                   accept=".xlsx,.xls"
                                   hidden>
                        </div>
                        <button class="btn btn-success mt-2"
                                onclick="uploadFile()">
                            upload
                        </button>
                        <button class="btn btn-secondary mt-2"
                                onclick="closeUploadPanel()">
                            cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- table output -->
    <div class="container mt-4">
        <div id="tableContainer">
        </div>
    </div>
    <!-- upload script -->
    <script>
        // opens upload pannel
        function openUploadPanel(){
            document.getElementById("uploadPanel").style.display = "block";
        }
        // closes upload pannel
        function closeUploadPanel(){
            document.getElementById("uploadPanel").style.display = "none";
        }
        const dropZone = document.getElementById("dropZone");
        const fileInput = document.getElementById("fileInput");
        // opens file selector when clicked
        dropZone.addEventListener("click", () => fileInput.click());
        // handles drag over effect
        dropZone.addEventListener("dragover", (e) => {
            e.preventDefault();
            dropZone.style.background = "#eef";
        });
        // resets colour when leaving drop zone
        dropZone.addEventListener("dragleave", () => {
            dropZone.style.background = "white";
        });
        // handles dropped files
        dropZone.addEventListener("drop", (e) => {
            e.preventDefault();
            fileInput.files = e.dataTransfer.files;
            dropZone.style.background = "white";
        });
        // uploads selected excel file
        function uploadFile(){
            const file = fileInput.files[0];
            if(!file){
                alert("Please select a file");
                return;
            }
            const formData = new FormData();
            formData.append("file", file);
            fetch("upload.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.text())
            .then(data => {
                alert(data);
                closeUploadPanel();
                location.reload();
            });
        }
    </script>
    <!-- table loader -->
    <script>
        // loads selected table and week
        function loadTable(type){
            const selectedWeek =
                document.getElementById("weekSelect").value;
            if(selectedWeek === ''){
                alert("Please select a week");
                return;
            }
            fetch(
                "load_table.php?type="
                + type
                + "&week="
                + selectedWeek
            )
            .then(response => response.text())
            .then(data => {
                document.getElementById("tableContainer").innerHTML = data;
            })
            .catch(error => {
                console.log(error);
                alert("FETCH ERROR");
            });
        }
    </script>
</body>
</html>