<?php
    $host = 'db';
    $dbname = 'mariadb';
    $username = 'mariadb';
    $password = 'mariadb';

    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password);
        
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } 
    catch (PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
    }
?>