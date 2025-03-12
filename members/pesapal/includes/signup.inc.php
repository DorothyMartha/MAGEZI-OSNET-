<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
require_once 'dbh.inc.php';
require_once 'functions.inc.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $first_name = $_POST["first_name"];
    $last_name = $_POST["last_name"];
    $email = $_POST["email"];
    $contact = $_POST["contact"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $role = 2;
    $level = $_POST["level"];
    $status = "active";
    $created_by = 0;

    $sql = 'SELECT * FROM users WHERE contact = ? AND email = ? AND first_name = ? AND last_name = ?';
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL error: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "ssss", $contact, $email, $first_name, $last_name);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        $_SESSION['user_first_name'] = $_POST["first_name"];
        $_SESSION['user_last_name'] = $_POST["last_name"];
        $_SESSION['user_email']  = $_POST["email"];
        $_SESSION['user_contact']  = $_POST["contact"];
        $_SESSION['user_password']  = $_POST["password"];
        $_SESSION['user_confirm_password']  = $_POST["confirm_password"];
        $_SESSION['user_level']  = $_POST["level"];
        echo '<script>alert("User with the same contact, email, first name and last name aready exists.")</script>';
        echo '<script>window.location.href="/magezi_harvest/signup.php";</script>';
    } else {

        if ($password === $confirm_password) {

            $code_sql = 'SELECT code FROM codes';
            $code_stmt = mysqli_stmt_init($conn);

            if (!mysqli_stmt_prepare($code_stmt, $code_sql)) {
                echo "SQL error: " . mysqli_error($conn);
            }

            mysqli_stmt_execute($code_stmt);
            mysqli_stmt_store_result($code_stmt);

            mysqli_stmt_bind_result($code_stmt, $code);

            while (mysqli_stmt_fetch($code_stmt)) {
                $student_sql = "SELECT student_code FROM users WHERE student_code like '" . $code . "%' ORDER BY student_code DESC LIMIT 1";
                $student_stmt = mysqli_stmt_init($conn);

                if (!mysqli_stmt_prepare($student_stmt, $student_sql)) {
                    echo "SQL error: " . mysqli_error($conn);
                }

                mysqli_stmt_execute($student_stmt);
                mysqli_stmt_store_result($student_stmt);

                mysqli_stmt_bind_result($student_stmt, $student_code);

                if (mysqli_stmt_num_rows($student_stmt) > 0) {
                    while (mysqli_stmt_fetch($student_stmt)) {
                        $new_code = intval($student_code) + 1;
                    }
                } else {
                    $new_code = $code . "10001";
                }
                mysqli_stmt_close($student_stmt);
            }
            mysqli_stmt_close($code_stmt);


            $sql = 'INSERT INTO users (first_name, last_name, email, contact, role, level, student_code, status, password, created_by) VALUES (?,?,?,?,?,?,?,?,?,?);';
            $stmt = mysqli_stmt_init($conn);

            if (!mysqli_stmt_prepare($stmt, $sql)) {
                echo "SQL error: " . mysqli_error($conn);
                exit();
            }

            $hashedPwd = md5($password);

            mysqli_stmt_bind_param($stmt, "ssssssssss", $first_name, $last_name, $email, $contact, $role, $level, $new_code, $status, $hashedPwd, $created_by);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            unset($_SESSION['user_first_name']);
            unset($_SESSION['user_last_name']);
            unset($_SESSION['user_email']);
            unset($_SESSION['user_contact']);
            unset($_SESSION['user_password']);
            unset($_SESSION['user_confirm_password']);
            unset($_SESSION['user_level']);

            $to = $email;
            $subject = "ACCOUNT CREATION";
            $message = "
                  <html>
                    <p> Your account has been created successfully</p>
                    <table>
                       <tr>
                         <td>Student Code</td>
                         <td>" . $new_code . "</td>
                      <tr>
                      <tr>
                        <td>Password</td>
                        <td>" . $password . "</td>
                      </tr>
                    </table>
                  </html>
                ";
            // Always set content-type when sending HTML email
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            // More headers
            $headers .= 'From: <admin@learn.mageziharvest.co.ug>' . "\r\n";

            mail($to, $subject, $message, $headers);

            echo '<script>alert("User added successfully.")</script>';
            echo '<script>window.location.href="/magezi_harvest/login.php";</script>';
            exit();
        } else {
            $_SESSION['user_first_name'] = $_POST["first_name"];
            $_SESSION['user_last_name'] = $_POST["last_name"];
            $_SESSION['user_email']  = $_POST["email"];
            $_SESSION['user_contact']  = $_POST["contact"];
            $_SESSION['user_password']  = $_POST["password"];
            $_SESSION['user_confirm_password']  = $_POST["confirm_password"];
            $_SESSION['user_level']  = $_POST["level"];
            echo '<script>alert("Password mismatch.")</script>';
            echo '<script>window.location.href="/magezi_harvest/signup.php";</script>';
        }
    }
}
