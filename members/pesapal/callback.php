<?php
// Start output buffering to prevent partial output before redirects
ob_start();

// Check if session has already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection safely
$include_path = dirname(__FILE__) . '../../libs/db-connection.php';
if (!file_exists($include_path)) {
    $_SESSION['error_message'] = "Database file not found.";
    header("Location: https://magezi.ac.ug/osnet/members/dashboard.php");
    exit();
}
require_once $include_path;

if (!isset($conn) || $conn->connect_error) {
    $_SESSION['error_message'] = "Database connection failed.";
    header("Location: https://magezi.ac.ug/osnet/members/dashboard.php");
    exit();
}

// Get Pesapal callback data
$tracking_id = $_GET['pesapal_transaction_tracking_id'] ?? '';
$reference = $_GET['pesapal_merchant_reference'] ?? '';

// Only update transaction_tracking_id if data is valid
if ($tracking_id && $reference) {
    $query = "UPDATE subscriptions SET transaction_tracking_id = ? WHERE merchant_reference = ?";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        $_SESSION['error_message'] = "Error updating transaction tracking ID: " . $conn->error;
    } else {
        $stmt->bind_param("ss", $tracking_id, $reference);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Payment initiated successfully! Awaiting confirmation.";
        } else {
            $_SESSION['error_message'] = "Failed to update transaction tracking ID: " . $stmt->error;
        }
        $stmt->close();
    }
} else {
    $_SESSION['error_message'] = "Invalid payment data received from Pesapal.";
}

// Redirect to dashboard
header("Location: https://magezi.ac.ug/osnet/members/dashboard.php");
ob_end_flush();
exit();
?>