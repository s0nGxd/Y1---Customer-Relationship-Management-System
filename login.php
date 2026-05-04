<?php
session_start();
require 'db_connect.php';

$login_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Modified query to include name
    $stmt = $conn->prepare("SELECT user_id, username, password, role, name FROM Users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc(); // Fixed variable name
        
        if (password_verify($password, $user['password'])) {
            // Set all session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name']; // Added name to session
            
            if ($user['role'] == 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: salesDash.php");
            }
            exit();
        } else {
            $login_error = "Invalid username or password";
        }
    } else {
        $login_error = "Invalid username or password";
    }
    
    $stmt->close();
    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Customer Relationship Management ABB</title>

        <!-- STYLESHEET -->
        <link rel="stylesheet" href="login.css">

        <!-- MATERIAL CDN -->
        <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
        
        <!-- Font -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;
        0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Roboto:ital,wght@0,100..900;1,100..900&family=Tajawal
        :wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">

    </head>

    <body>
        <header class="header">
            
        </header>

        <div class="background"></div>

        <div class="container">
            <div class="content">
                <h2 class="logo"><i class='bx bxl-airbnb'></i></h2>

                <div class="text-sci">
                    <h2>ABB Robotics<br><span>Customer Relationship Management</span></h2>


                </div>

            </div>

            <div class="logreg-box">
                <div class="form-box login">
                    <form action="login.php" method="POST">
                        <h2>Sign In</h2>

                        <?php if(isset($login_error)) { ?>
                            <div class="error-msg" style="color: red; margin-bottom: 15px;">
                                <?php echo $login_error; ?>
                            </div>
                        <?php } ?>

                        <div class="input-box">
                            <span class="icon"><i class='bx bxs-user' ></i></span>
                            <input type="text" name="username" required>
                            <label>Username</label>
                        </div>

                        <div class="input-box">
                            <span class="icon"><i class='bx bxs-lock-alt' ></i></span>
                            <input type="password" name="password" required>
                            <label>Password</label>
                        </div>

                        <div class="remember-forgot">
                            <label><input type="checkbox">Remember me</label>
                            <a href="#">Forgot Password?</a>
                        </div>

                        <button type="submit" class="btn">Log In</button>

<!-- REMEMBER TO REMOVE SIGN UP CSS AND JAVA -->

                    </form>
                
                </div>

                
            </div>
        
        </div>

        <script src ="login.js"></script>
    </body>

</html>