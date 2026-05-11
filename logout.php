<?php
session_start();
session_destroy();
header("Location: NHSlogin.php");
exit();
?>