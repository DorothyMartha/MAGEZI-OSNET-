<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);

require_once 'functions.inc.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST['add'])) {
        $duration = $_POST["duration"];
        $amount = $_POST["amount"];

        $sql = 'INSERT INTO subscription_fees (duration, amount, created_by) VALUES (?,?,?);';
        $stmt = mysqli_stmt_init($conn);

        if (!mysqli_stmt_prepare($stmt, $sql)) {
            echo "SQL error: " . mysqli_error($conn);
            exit();
        }

        mysqli_stmt_bind_param($stmt, "sss", $duration, $amount, $_SESSION['id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo '<script>alert("Fee added successfully.")</script>';
        echo '<script>window.location.href="/magezi_harvest/pages/subscriptions/subscription_fees.php";</script>';
        exit();
   
    } else if (isset($_POST['delete'])) {
        $sql = 'DELETE FROM subscription_fees WHERE id = ?';
        $stmt = mysqli_stmt_init($conn);

        if (!mysqli_stmt_prepare($stmt, $sql)) {
            echo "SQL error: " . mysqli_error($conn);
        }

        mysqli_stmt_bind_param($stmt, "s", $_POST['delete']);

        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        mysqli_stmt_close($stmt);

        echo '<script>alert("Fee deleted successfully.")</script>';
        echo '<script>window.location.href="/magezi_harvest/pages/subscriptions/subscription_fees.php";</script>';
   
    } else if (isset($_POST['update'])) {
        $duration = $_POST["duration"];
        $amount = $_POST["amount"];
        $id = $_POST["update"];

        $sql = 'UPDATE subscription_fees SET duration = ?, amount = ? WHERE id = ?';

        $stmt = mysqli_stmt_init($conn);

        if (!mysqli_stmt_prepare($stmt, $sql)) {
            echo "SQL error: " . mysqli_error($conn);
            exit();
        }

        mysqli_stmt_bind_param($stmt, "sss", $duration, $amount, $id);

        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        mysqli_stmt_close($stmt);
        echo '<script>alert("Fee updated successfully.")</script>';
        echo '<script>window.location.href="/magezi_harvest/pages/subscriptions/subscription_fees.php";</script>';
        exit();
    }
}
