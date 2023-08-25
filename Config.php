<?php
$server='localhost';
$username='root';
$password='';
$database='gpa';

$conn=mysqli_connect($server,$username,$password,$database);

if($conn->connect_error)
{
    die("connection failed".$conn->connect_error);
}

//session_start();

if(isset($_POST['signUp'])){
    $name=$_POST['username'];
    $email=$_POST['email'];
    $password=$_POST['password'];
    $cat=$_POST['cat'];
    $imgno=$_POST['imgno'];
    $grid1=$_POST['grid1'];
    $grid2=$_POST['grid2'];
    // Check if the email already exists in the database
    $check_query = "SELECT * FROM login WHERE email = '$email'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        // Email already exists
        echo '<script>alert("Email already exists!");</script>';
    } else {
    $otp = rand(10000, 99999);
    $sql="INSERT INTO `login`(`email`,`password`,`username`,`cat`,`imgno`,`grid1`,`grid2`,`otp`) VALUES ('$email','$password','$name','$cat','$imgno','$grid1','$grid2','$otp')";
    
    if(mysqli_query($conn,$sql))
    {
        header("location:index.php");
        echo "Records inserted succesfully.";
    }
    else
    {
        echo "ERROR:could not able to execute $sql." . mysqli_error($conn);
    }
  } 
}

session_start();
$maximum_login_attempts = 3; // Maximum allowed login attempts
$lockout_duration = 3600; // Lockout duration in seconds (1 hour)

if(isset($_POST['signIn'])){
    $email=$_POST['email'];
    $password=$_POST['password'];
    $cat=$_POST['cat'];
    $imgno=$_POST['imgnoA'];
    $grid1=$_POST['grid1A'];
    $grid2=$_POST['grid2A'];

   // Check if the account is locked
    $lockout_query = "SELECT lst_lg_ats FROM login WHERE email='$email' AND lg_ats >= $maximum_login_attempts";
    $lockout_result = mysqli_query($conn, $lockout_query);
    if (mysqli_num_rows($lockout_result) > 0) {
        $row = mysqli_fetch_assoc($lockout_result);
        $lst_lg_ats = strtotime($row['lst_lg_ats']);
        $current_time = time();
        $remaining_time = $lockout_duration - ($current_time - $lst_lg_ats);
        if ($remaining_time > 0) {
            // Account is locked
            echo '<script>
                    alert("Account is locked due to too many unsuccessful login attempts. Please try again later !");
                    window.location.href = "index.php";
                  </script>';
            exit();
        } else {
            // Reset login_attempts and last_login_attempt since the lockout duration is over
            $reset_lockout_query = "UPDATE login SET lg_ats = 0, lst_lg_ats = NULL WHERE email='$email'";
            mysqli_query($conn, $reset_lockout_query);
        }
    }

   $query="SELECT * FROM  login WHERE `email`='$email' AND `password`='$password' AND `cat`='$cat' AND `imgno`='$imgno' AND `grid1`='$grid1' AND `grid2`='$grid2'";
    $result=mysqli_query($conn,$query);
    $row=mysqli_fetch_array($result,MYSQLI_ASSOC);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        // Reset login_attempts and last_login_attempt on successful login
        $reset_attempts_query = "UPDATE login SET lg_ats = 0, lst_lg_ats = NULL WHERE email='$email'";
        mysqli_query($conn, $reset_attempts_query);
        header("location:https://matrusri.edu.in/");
    } else {
        // Increment login_attempts and set last_login_attempt on unsuccessful login
        $increment_attempts_query = "UPDATE login SET lg_ats = lg_ats + 1, lst_lg_ats = NOW() WHERE email='$email'";
        mysqli_query($conn, $increment_attempts_query);

        echo '<script>
                alert("Email ID or password is incorrect");
                window.location.href = "index.php";
              </script>';
    }
}

require 'mailer.php';
if (isset($_POST['enterOtp'])) {
    $email = $_POST['email'];
    $_SESSION['email'] = $email;
    $query = "SELECT * FROM login WHERE email='$email'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        // Email exists in the database
        $otp = rand(10000, 99999); // Generate OTP
        $sql = "UPDATE login SET otp='$otp' WHERE email='$email'";
        mysqli_query($conn, $sql);

        // Send the OTP to the user's email
        if (sendOTP($email, $otp)) {
            // Redirect to verify.php
            header("Location: verify.php?email=$email");
            exit();
        } else {
            echo '<script>alert("Failed to send OTP. Please try again.");</script>';
        }
    } else {
        // Email does not exist in the database
        echo '<script>alert("Email does not exist.");
            window.location.href = "index.php";
            window.close();
         </script>';
    }
}

if (isset($_POST['verifyOtp'])) {
    $email = $_SESSION['email']; // Retrieve email from the session
    $enteredOtp = $_POST['otp1'] . $_POST['otp2'] . $_POST['otp3'] . $_POST['otp4'] . $_POST['otp5'];

    // Retrieve the stored OTP from the database for the given email
    $query = "SELECT otp FROM login WHERE email='$email'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $storedOtp = $row['otp'];

        if ($enteredOtp == $storedOtp) {
            // Entered OTP matches the stored OTP
            // Redirect to ResetPswd.php with the email value
            header("Location: ResetPswd.php?email=$email"); // Add a space after "Location:"
            exit();
        } else {
            // Entered OTP does not match the stored OTP
            echo '<script>alert("Invalid OTP. Please try again.");
                window.location.href = "verify.php?email='.$email.'";
            </script>';
        }
    } else {
        // Email not found in the database
        echo '<script>alert("Email not found.");
            window.location.href = "index.php";
            window.close();
        </script>';
    }
}


if(isset($_POST['resetButton'])){
    $email=$_SESSION['email'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['conf_password'];
    $cat=$_POST['cat'];
    $img=$_POST['imgnoA'];
    $grid1=$_POST['grid1'];
    $grid2=$_POST['grid2'];
     if ($newPassword !== $confirmPassword) {
        echo '<script>alert("New Password and Confirm Password do not match. Please try again.");</script>';
    } else {
        // Update the password and other fields in the database
        $query = "UPDATE login SET `password`='$newPassword', `cat`='$cat', `imgno`='$img', `grid1`='$grid1', `grid2`='$grid2' WHERE `email`='$email'";
        $result = mysqli_query($conn, $query);
 
    if ($result) {
            // Update successful
            echo '<script>alert("Password updated successfully.");</script>';
            // Redirect to index.php and close the current page
            echo '<script>window.location.href="index.php"; window.close();</script>';
            exit();
        } else {
            // Update failed
            echo '<script>alert("Failed to update information. Please try again.");</script>';
        }
    }
}

?>