<?php
// Start or resume the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include dependencies
require_once 'functions.php';
require_once '../libs/db-connection.php';

// Check database connection
$connection_failed = $conn->connect_error;
if ($connection_failed) {
    file_put_contents('/home/mageziac/public_html/osnet/error.log', 
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
if ($user_id && !$connection_failed) {
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
        file_put_contents('/home/mageziac/public_html/osnet/error.log', 
            "Log activity error: " . $e->getMessage() . " - " . date('Y-m-d H:i:s') . "\n", 
            FILE_APPEND
        );
    }
}

// Regenerate session ID for security before destroying
session_regenerate_id(true);

// Destroy all session data
session_unset();
session_destroy();

// Close database connection if it was successful
if (!$connection_failed && isset($conn)) {
    $conn->close();
}

// Redirect to index.php
header("Location: /osnet/index.php");
exit();
?>