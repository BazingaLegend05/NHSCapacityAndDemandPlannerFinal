<?php
    session_start();
    if($_SESSION['LoggedIn'] !== 1){
        echo "You are here by mistake";
        exit();
    }
    else{
        $Role = $_SESSION['Role'];
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
                    <?php if ($Role === 'admin'): ?>
                        <button onclick='openUploadPanel()'class="btn btn-primary">Upload a file</button>
                        <script></script>
                    <?php endif; ?>
                    <div id="uploadPanel" class="upload-panel" style="display:none;">
                        <div class="upload-box">
                            <h5>Upload Excel File</h5>

                            <div id="dropZone" class="drop-zone">
                                Drag & drop Excel file here<br>or click to select
                                <input type="file" id="fileInput" accept=".xlsx,.xls" hidden>
                            </div>

                            <button class="btn btn-success mt-2" onclick="uploadFile()">Upload</button>
                            <button class="btn btn-secondary mt-2" onclick="closeUploadPanel()">Cancel</button>
                        </div>
                    </div>
                    
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
        <script>
            function openUploadPanel() {
                document.getElementById("uploadPanel").style.display = "block";
            }

            function closeUploadPanel() {
                document.getElementById("uploadPanel").style.display = "none";
            }

            const dropZone = document.getElementById("dropZone");
            const fileInput = document.getElementById("fileInput");

            dropZone.addEventListener("click", () => fileInput.click());

            dropZone.addEventListener("dragover", (e) => {
                e.preventDefault();
                dropZone.style.background = "#eef";
            });

            dropZone.addEventListener("dragleave", () => {
                dropZone.style.background = "white";
            });

            dropZone.addEventListener("drop", (e) => {
                e.preventDefault();
                fileInput.files = e.dataTransfer.files;
                dropZone.style.background = "white";
            });

            function uploadFile() {
                alert("uploadFile triggered");
                const file = fileInput.files[0];

                if (!file) {
                    alert("Please select a file");
                    return;
                }

                // Frontend validation
                if (!file.name.endsWith(".xlsx") && !file.name.endsWith(".xls")) {
                    alert("Only Excel files allowed");
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
                })
                .catch(err => console.error(err));
            }
            </script>
    </body>
</html>