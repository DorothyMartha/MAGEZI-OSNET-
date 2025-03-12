<?php
// Start output buffering to prevent headers already sent issues
ob_start();

// Start or resume the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include required files
require_once 'functions.php'; // Assuming this contains logActivity()
require_once '../libs/db-connection.php';

// Check database connection
if ($conn->connect_error) {
    // Log to file instead of dying, allowing logout to proceed
    file_put_contents(__DIR__ . '/error.log', 
        "Connection failed: " . $conn->connect_error . " - " . date('Y-m-d H:i:s') . "\n", 
        FILE_APPEND
    );
}

// Capture session and request data before destruction
$user_id = $_SESSION['user_id'] ?? null;
$ip_address = $_SERVER['REMOTE_ADDR'];
$page_url = $_SERVER['REQUEST_URI'];
$session_id = session_id();
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

// Log the logout action if user_id exists and DB is connected
if ($user_id && !$conn->connect_error) {
    try {
        logActivity(
            $conn,
            $user_id,
            "Logged out",
            $ip_address,
            $page_url,
            null,
            'success',
            $session_id,
            $user_agent
        );
    } catch (Exception $e) {
        // Log error to file if logging fails
        file_put_contents(__DIR__ . '/error.log', 
            "Log activity error: " . $e->getMessage() . " - " . date('Y-m-d H:i:s') . "\n", 
            FILE_APPEND
        );
    }
}

// Destroy all session data
session_unset(); // Clear all session variables
session_destroy(); // Destroy the session

// Close database connection if it exists
if (isset($conn) && !$conn->connect_error) {
    $conn->close();
}

// Clear output buffer before redirect
ob_clean();

// Redirect to index.php with absolute path (adjust as needed)
header("Location: https://www.magezi.ac.ug/osnet/index.php"); // Replace 'yourdomain.com' with your actual domain
// Fallback JavaScript redirect
echo "<script>window.location.href = 'https://www.magezi.ac.ug/osnet/index.php';</script>";
ob_end_flush();
exit();