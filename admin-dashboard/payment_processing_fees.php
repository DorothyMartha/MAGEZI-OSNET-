<?php 
// Include your header and sidebar
include 'header.php'; 
include 'sidebar.php'; 

require_once '../libs/db-connection.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the current fee percentage from the database
// Fetch the current fee percentage from the database
$fee_percentage = 0; // Default value if no fee is set
$sql = "SELECT setting_value FROM settings WHERE setting_key = 'payment_fee' LIMIT 1";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $fee_percentage = htmlspecialchars($row['setting_value']);
}

// Flag to check if the form has been submitted and data updated
$is_updated = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['fee_percentage'])) {
    $fee_percentage = $_POST['fee_percentage'];

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('payment_fee', ?) 
                            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->bind_param("s", $fee_percentage);

    if ($stmt->execute()) {
        // Set the flag that the update was successful
        $is_updated = true;
    } else {
        $error_message = "Error updating fee: " . $stmt->error;
    }
    
    $stmt->close();
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/withdrawals.css">
    <title>Set Payment Processing Fee</title>

    <!-- Add inline styles for notifications -->
    <style>
        .notification {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-weight: bold;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
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
        <h2>Set Payment Processing Fee</h2>

        <!-- Current Fee Display -->
        <div class="notification">
    <span>Current Processing Fee: <strong><?php echo $fee_percentage; ?>%</strong></span>
</div>

        <!-- Show success or error message -->
        <?php if (isset($is_updated) && $is_updated): ?>
            <div class="notification success">
                <span>Processing fee updated successfully!</span>
            </div>
        <?php elseif (isset($error_message)): ?>
            <div class="notification error">
                <span><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>

        <!-- Form to set the fee percentage -->
        <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="fee-form">
    <label for="fee-percentage">Set Payment Fee Percentage (%)</label>
    <input type="number" id="fee-percentage" name="fee_percentage" required min="0" max="100" step="0.01" 
           placeholder="Enter fee percentage" value="<?php echo $fee_percentage; ?>" />
    
    <button type="submit" class="btn-submit">Save Fee</button>
</form>

    </div>

</body>
</html>
