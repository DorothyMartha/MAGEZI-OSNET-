<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>
<?php
// Start output buffering
ob_start();

require_once '../libs/db-connection.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to log activity
function logActivity($conn, $user_id, $action, $ip_address, $page_url, $details = null, $status = 'success', $session_id = null, $user_agent = null, $error_message = null) {
    $sql = "INSERT INTO access_logs (user_id, action, ip_address, status, session_id, user_agent, page_url, details, error_message) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('issssssss', $user_id, $action, $ip_address, $status, $session_id, $user_agent, $page_url, $details, $error_message);
    $stmt->execute();
    $stmt->close();
}

// Handle profile update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $user_id = $_SESSION['user_id'] ?? 1; // Default to 1 if session not set
    $action = "Updated user profile";
    $page_url = $_SERVER['REQUEST_URI'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $session_id = session_id(); // Get current session ID
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null; // Browser/device info

    // Fetch current (old) data before update
    $old_data_query = "SELECT full_name, email, phone_number FROM users WHERE id = ?";
    $stmt = $conn->prepare($old_data_query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $old_data = $result->fetch_assoc();

    // Capture new data from the form
    $new_full_name = $_POST['full_name'] ?? $old_data['full_name'];
    $new_email = $_POST['email'] ?? $old_data['email'];
    $new_phone_number = $_POST['phone_number'] ?? $old_data['phone_number'];

    // Prepare details for logging
    $details = json_encode([
        'old' => [
            'full_name' => $old_data['full_name'],
            'email' => $old_data['email'],
            'phone_number' => $old_data['phone_number']
        ],
        'new' => [
            'full_name' => $new_full_name,
            'email' => $new_email,
            'phone_number' => $new_phone_number
        ]
    ]);

    // Update profile data
    $update_query = "UPDATE users SET full_name = ?, email = ?, phone_number = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('sssi', $new_full_name, $new_email, $new_phone_number, $user_id);
    if ($stmt->execute()) {
        // Log successful update
        logActivity($conn, $user_id, $action, $ip_address, $page_url, $details, 'success', $session_id, $user_agent);
        echo "Profile updated successfully!";
    } else {
        // Log failure with error message
        $error_message = $conn->error;
        logActivity($conn, $user_id, $action, $ip_address, $page_url, $details, 'failure', $session_id, $user_agent, $error_message);
        echo "Error updating profile: " . $error_message;
    }
    $stmt->close();
}

// Log other actions (examples)
$user_id = $_SESSION['user_id'] ?? 1;
$ip_address = $_SERVER['REMOTE_ADDR'];
$page_url = $_SERVER['REQUEST_URI'];
$session_id = session_id();
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

if (isset($_POST['login'])) {
    logActivity($conn, $user_id, "Logged in", $ip_address, $page_url, null, 'success', $session_id, $user_agent);
} elseif (isset($_POST['logout'])) {
    logActivity($conn, $user_id, "Logged out", $ip_address, $page_url, null, 'success', $session_id, $user_agent);
} elseif (isset($_POST['transaction'])) {
    $details = json_encode(['transaction_data' => $_POST['transaction']]);
    logActivity($conn, $user_id, "Made a transaction", $ip_address, $page_url, $details, 'success', $session_id, $user_agent);
} elseif (isset($_POST['withdraw'])) {
    $details = json_encode(['withdrawal_data' => $_POST['withdraw']]);
    logActivity($conn, $user_id, "Made a withdrawal", $ip_address, $page_url, $details, 'success', $session_id, $user_agent);
} elseif (isset($_POST['add_association'])) {
    $details = json_encode(['association_data' => $_POST['association']]);
    logActivity($conn, $user_id, "Added new alumni association", $ip_address, $page_url, $details, 'success', $session_id, $user_agent);
}

// Fetch access logs for display
$sql = "SELECT id, user_id, action, ip_address, status, session_id, user_agent, page_url, details, error_message, logged_at 
        FROM access_logs ORDER BY logged_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/accesslogs.css">
    <title>Access Logs</title>
</head>
<body>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-nav">
            <h2>Admin Dashboard</h2>
            <div class="user-info">
                <span>Welcome, Admin</span>
            </div>
        </div>

        <!-- Page Content -->
        <h2>Access Logs</h2>
        <table class="logs-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User ID</th>
                    <th>Action</th>
                    <th>IP Address</th>
                    <th>Status</th>
                    <th>Session ID</th>
                    <th>User Agent</th>
                    <th>Page URL</th>
                    <th>Details</th>
                    <th>Error Message</th>
                    <th>Logged At</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['id']}</td>
                                <td>{$row['user_id']}</td>
                                <td>{$row['action']}</td>
                                <td>{$row['ip_address']}</td>
                                <td>{$row['status']}</td>
                                <td>{$row['session_id']}</td>
                                <td>{$row['user_agent']}</td>
                                <td>{$row['page_url']}</td>
                                <td>{$row['details']}</td>
                                <td>{$row['error_message']}</td>
                                <td>{$row['logged_at']}</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='11'>No access logs found</td></tr>";
                }
                $conn->close();
                ob_end_flush();
                ?>
            </tbody>
        </table>
    </div>

</body>
</html>