<?php
    session_start();
?>
<?php
    
    include 'db_connect.php';
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST'){
        $Username = $_POST['username'];
        $Password = $_POST['password'];
        try {
            $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = $conn->prepare("SELECT * FROM users_table WHERE username = :username AND password = :password");
            $sql->bindParam(':username', $Username);
            $sql->bindParam(':password', $Password);
            $sql-> execute();
            if($sql->rowCount()){
                $_SESSION['LoggedIn'] = 1;
                $row = $sql->fetch();
                $_SESSION['UserID'] = $row['UserID']; 
                $_SESSION['Role'] = $row['Role'];
                header("Location: welcome_menu.php");
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