<?php
// if (isset($_POST["submit"])) {
//     $email = $_POST["email"];
//     $pwd = $_POST["pwd"];

//     require_once 'dbh.inc.php';
//     require_once 'functions.inc.php';

//     if (emptyInputsLogin($email, $pwd) !== false) {
//         // header("location: ../signup.php?errors=emptyinputs");
//         echo '<script>window.location.href="../signup.php??errors=emptyinputs";</script>';

//         exit();
//     }

//     loginUser($conn, $email, $pwd);
// } else {
//     header("location: ../signup.php");
//     exit();
// }
?>

<?php
require_once 'dbh.inc.php';
require_once 'functions.inc.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    if ($email != "" && $password != "") {
        $hashPwd = md5($password);

        $sql = 'SELECT id, first_name, last_name, email, contact, role, level, status  FROM users WHERE email = ? AND password = ?';
        $stmt = mysqli_stmt_init($conn);

        if (!mysqli_stmt_prepare($stmt, $sql)) {
            echo "SQL error: " . mysqli_error($conn);
            exit();
        }

        mysqli_stmt_bind_param($stmt, "ss", $email, $hashPwd);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            if (mysqli_stmt_num_rows($stmt) > 1) {
                echo '<script>alert("This email is attached to multiple students, please use the student code to login.")</script>';
                echo '<script>window.location.href="../login.php";</script>';
                exit();
            }

            mysqli_stmt_bind_result($stmt, $id, $first_name, $last_name, $email, $contact, $role, $level, $status);
            $status = null;

            while (mysqli_stmt_fetch($stmt)) {
                $_SESSION["id"] = $id;
                $_SESSION["name"] = $first_name . " " . $last_name;
                $_SESSION["email"] = $email;
                $_SESSION["contact"] = $contact;
                $_SESSION["role"] = $role;
                $_SESSION["level"] = $level;
                $status = $status;
            }

            if ($status === "suspended") {
                echo '<script>alert("This account has been suspended, please contact admin for more information.")</script>';
                echo '<script>window.location.href="../index.php";</script>';
            } else if ($status === "inactive") {
                echo '<script>alert("This account is inactive.")</script>';
                echo '<script>window.location.href="../index.php";</script>';
            } else {
        
                if ($role === 1 ||  $role === 4 ||  $role === 3 ||  $role === 5) {
                    echo '<script>window.location.href="/ebt/pages/tickets/ticket_sales.php";</script>';
                } else if ( $role === 2 ) {
                    $today = date('Y-m-d H:i:s');

                    $sql = 'SELECT end_date FROM subscriptions WHERE student = ? AND end_date >= ?';
                    $stmt = mysqli_stmt_init($conn);
            
                    if (!mysqli_stmt_prepare($stmt, $sql)) {
                        echo "SQL error: " . mysqli_error($conn);
                        exit();
                    }
            
                    mysqli_stmt_bind_param($stmt, "ss", $id, $today);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_store_result($stmt);
            
                    if (mysqli_stmt_num_rows($stmt) > 0) {
                        mysqli_stmt_bind_result($stmt, $end_date);
                        while (mysqli_stmt_fetch($stmt)) {
                            $_SESSION["expiry_date"] = $end_date;
                        }
                        $_SESSION["paid"] = true;
                    }

                    if (isset($_SESSION['search_url'])) {
                        echo $_SESSION['search_url'];
                    } else {
                        echo '<script>window.location.href="/ebt/pages/tickets/ticket_sales.php";</script>';
                    }
                }
            }

        } else {
            echo '<script>alert("Wrong email or password.")</script>';
            echo '<script>window.location.href="/ebt/login.php";</script>';
        }

        mysqli_stmt_close($stmt);
        exit();
    }
}
