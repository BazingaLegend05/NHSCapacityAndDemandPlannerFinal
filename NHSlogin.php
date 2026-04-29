<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    
</head>
<body>
    <?php
        
        include 'db_connect.php';
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST'){
            $Username = $_POST['username'];
            $Password = $_POST['password'];
            try {
                $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password); //building a new connection object
                // set the PDO error mode to exception
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $sql = $conn->prepare("SELECT * FROM users_table WHERE username = :username AND password = :password");
                $sql->bindParam(':username', $Username);
                $sql->bindParam(':password', $Password);
                $sql-> execute();

                if($sql->rowCount()){
                    $_SESSION['LoggedIn'] = 1;
                    $row = $sql->fetch();
                    $_SESSION['UserID'] = $row['id']; 
                    
                    header("Location: admin_menu.php");
                    exit();
                }
                else{
                    echo "Wrong username or password";
                }
                
                }
            catch(PDOException $e)
                {
                echo $e->getMessage(); //If we are not successful in connecting or running the query we will see an error
                }
        }
        else{
            echo "You're here by mistake" ;
        }
        ?>


</body>
</html>