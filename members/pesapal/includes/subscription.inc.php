<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
require_once 'dbh.inc.php';
require_once 'functions.inc.php';

// https://mageziharvest.co.ug/?st=4&pl=5&status=successful&pesapal_transaction_tracking_id=8ba913d7-0de6-437d-8137-dd56fbdb8e29&pesapal_merchant_reference=17134406337828

if (isset($_GET['status'])) {
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    $plan = mysqli_real_escape_string($conn, $_GET['pl']);

    if ($status === 'successful') {
        $months = 0;

        $sql = 'SELECT duration FROM subscription_fees WHERE id = ?';
        $stmt = mysqli_stmt_init($conn);

        if (!mysqli_stmt_prepare($stmt, $sql)) {
            echo "SQL error: " . mysqli_error($conn);
        }

        mysqli_stmt_bind_param($stmt, "s", $plan);

        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        mysqli_stmt_bind_result($stmt, $duration);

        while (mysqli_stmt_fetch($stmt)) {
            if ($duration === "Monthly") {
                $months = 1;
            } else if ($duration === "Quarterly") {
                $months = 3;
            } else if ($duration === "Six Months") {
                $months = 6;
            } else if ($duration === "Yearly") {
                $months = 12;
            }
        }

        $student = mysqli_real_escape_string($conn, $_GET['st']);
        $plan = mysqli_real_escape_string($conn, $_GET['pl']);
        $tx_ref = isset($_GET['tx_ref']) ? mysqli_real_escape_string($conn, $_GET['tx_ref']) : mysqli_real_escape_string($conn, $_GET['pesapal_merchant_reference']);
        $transaction_id = isset($_GET['transaction_id']) ? mysqli_real_escape_string($conn, $_GET['transaction_id']) :  mysqli_real_escape_string($conn, $_GET['pesapal_transaction_tracking_id']);
        $start_date = date('Y-m-d H:i:s');
        $end_date = date('Y-m-d H:i:s', strtotime("+" . $months . " month", strtotime($start_date)));

        $sql = 'INSERT INTO subscriptions (student, plan, start_date, end_date, status, tx_ref, transaction_id) VALUES (?,?,?,?,?,?,?);';
        $stmt = mysqli_stmt_init($conn);

        if (!mysqli_stmt_prepare($stmt, $sql)) {
            echo "SQL error: " . mysqli_error($conn);
            exit();
        }

        mysqli_stmt_bind_param($stmt, "sssssss", $student, $plan, $start_date, $end_date, $status, $tx_ref, $transaction_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo '<script>alert("You have successfully subscribed.")</script>';
        echo '<script>window.location.href="/magezi_harvest/login.php";</script>';
        exit();
    } else {
        echo '<script>alert("Subscription failed, please try again.")</script>';
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update'])) {
        $status = $_POST["status"];
        $id = $_POST["update"];

        $sql = 'UPDATE subscriptions SET commission_status = ? WHERE id = ?';

        $stmt = mysqli_stmt_init($conn);

        if (!mysqli_stmt_prepare($stmt, $sql)) {
            echo "SQL error: " . mysqli_error($conn);
            exit();
        }

        mysqli_stmt_bind_param($stmt, "ss", $status, $id);

        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        mysqli_stmt_close($stmt);
        echo '<script>alert("Commission status updated successfully.")</script>';
        echo '<script>window.location.href="/magezi_harvest/pages/subscriptions/subscriptions.php";</script>';
        exit();
    }
}
