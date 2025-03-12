<?php
// Start or resume the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include functions.php (remove duplicate function definition from this file)
require_once 'functions.php';

require_once '../libs/db-connection.php';

// Check connection
if ($conn->connect_error) {
    // Log to file instead of dying if DB fails, so logout still proceeds
    file_put_contents('/srv/disk2/ivnape/www/magezi.ac.ug/osnet/error.log', 
        "Connection failed: " . $conn->connect_error . " - " . date('Y-m-d H:i:s') . "\n", 
        FILE_APPEND
    );
}

// Capture data before session destruction
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
        file_put_contents('/srv/disk2/ivnape/www/magezi.ac.ug/osnet/error.log', 
            "Log activity error: " . $e->getMessage() . " - " . date('Y-m-d H:i:s') . "\n", 
            FILE_APPEND
        );
    }
}

// Destroy all session data
session_unset();
session_destroy();

// Close database connection if it exists
if (isset($conn) && !$conn->connect_error) {
    $conn->close();
}

// Redirect to index.php
header("Location: /osnet/index.php");
exit();
?>