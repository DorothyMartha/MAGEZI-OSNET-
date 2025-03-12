<?php
session_start();

require_once '../libs/db-connection.php'; 
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (!isset($_SESSION['user_id'])) {
    echo "Please log in first.";
    exit();
}
 include 'header.php';
include 'sidebar.php';
// Fetch the logged-in admin's association ID (assuming role is 'AssociationAdmin' in users table)
$user_id = $_SESSION['user_id'];
$admin_association_query = "SELECT association_id FROM users WHERE id = ? AND role = 'AssociationAdmin'";
$stmt = $conn->prepare($admin_association_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($admin_association_id);
$stmt->fetch();
$stmt->close();

// If no association is found or the user isn't an AssociationAdmin, prevent further access
if (!$admin_association_id) {
    echo "You are not linked to any association or not an Association Admin.";
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fundraising Campaigns</title>
    <style>
        /* Existing CSS */

        .cancel-button {
            display: inline-block;
            padding: 10px 20px;
            margin-left: 10px;
            background-color: #dc3545; /* Red color */
            color: #fff; /* White text for contrast */
            text-decoration: none;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .cancel-button:hover {
            background-color: #c82333; /* Darker red on hover */
            color: #fff; /* Keep text white */
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa; /* Light grey background */
        }

        .main-content {
            padding: 20px;
            max-width: 1200px; /* Limit width for better readability */
            margin: 0 auto; /* Center content */
        }

        h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            overflow: hidden;
        }

        th, td {
            padding: 8px 12px;
            text-align: left;
            font-size: 12px;
            border-bottom: 1px solid #e0e0e0;
            color: #555;
        }

        th {
            background-color: #666;
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        td a {
            margin: 0 5px;
        }

        a {
            color: #666;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        form {
            margin-top: 20px;
        }

        label {
            display: block;
            margin-top: 10px;
            color: #333;
        }

        input[type="text"],
        input[type="number"],
        input[type="date"],
        textarea,
        select {
            width: 100%;
            max-width: 400px;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        textarea {
            height: 100px;
        }

        input[type="submit"] {
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .top-nav {
    background-color: #1f3d7a;
    width: 90%;
    max-width: 910px;
    color: #F1F1F1;
    font-family: 'Arial', sans-serif;
    font-size: 14px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 18px;
    left: 270px;
    z-index: 1000;
    box-shadow: 0px 3px 10px rgba(0, 0, 0, 0.1);
    border-bottom: 1px solid #ddd;
    height: 40px;
    position: fixed; /* Use sticky instead of fixed */
    top: 10px; /* Adjust the space from the top */
    border-radius: 8px;
}

    
    .top-nav h2 {
    font-size: 16px;
    font-weight: 700;
    margin: 0;
    color: #F1F1F1;
    }
    
    .top-nav .user-info span {
    font-size: 14px;
    color: #E0E0E0;
    }
    .main-content {
        margin-left: 220px;
        padding: 20px;
    }
    body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f4f7fa;
        }

        .main-content {
            margin-left: 250px; /* Adjust based on sidebar width */
            padding: 20px;
            max-width: 1000px;
            margin-top: 80px;
        }
         /* Enhanced Form Styles */
         h2 {
            font-size: 24px;
            color: #fff;
            margin-bottom: 20px;
            text-decoration: none;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid #1f3d7a;
            padding-bottom: 8px;
        }
    </style>
</head>
<body>
<div class="main-content">
    <div class="top-nav">
        <h2>Admin Dashboard</h2>
        <div class="user-info">
            <span>Welcome, Admin</span>
        </div>
    </div>
    <?php
    if (isset($_GET['delete_campaign'])) {
        $campaign_id = $_GET['delete_campaign'];
        // Delete the campaign from the database
        $delete_query = "DELETE FROM fundraising_campaigns WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $campaign_id);
        $stmt->execute();
        $stmt->close();
        
        // Redirect to prevent form resubmission on page refresh
        echo "<script>window.location.href='fundraising_campaigns.php';</script>";
        exit();
    }

    // Handle Create Campaign (check if form is submitted)
    if (isset($_POST['create_campaign'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $status = $_POST['status'];
        $association_id = $admin_association_id;  // Automatically set to the admin's association
        $school_name = "";
        $user_id = $_SESSION['user_id'];
        $created_by = $_SESSION['user_id']; 

        $current_date = date('Y-m-d'); // Get today's date
        if ($start_date < $current_date) {
            $_SESSION['message'] = 'The start date cannot be in the past.';
            echo "<script>window.location.href='fundraising_campaigns.php?create=true';</script>";
            exit();
        }

        // Fetch the school_name from alumni_associations based on association_id
        $school_name_query = "SELECT school_name FROM alumni_associations WHERE id = ?";
        $school_name_stmt = $conn->prepare($school_name_query);
        $school_name_stmt->bind_param("i", $association_id);
        $school_name_stmt->execute();
        $school_name_stmt->bind_result($school_name);
        $school_name_stmt->fetch();
        $school_name_stmt->close();

        // Insert the new campaign into the database
        $fee = isset($_POST['fee']) ? (float)$_POST['fee'] : 0; // Get fee amount

        $insert_query = "INSERT INTO fundraising_campaigns (title, description, start_date, end_date, status, association_id, name, created_by, fee) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ssssssisd", $title, $description, $start_date, $end_date, $status, $association_id, $school_name, $created_by, $fee);
        $stmt->execute();
        $stmt->close();

        // Logging the campaign creation
        $action = "Created new campaign";
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $status_log = "success";
        $session_id = session_id();
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $page_url = "http" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $details = "Campaign Title: " . $title . ", Association: " . $school_name;

        $log_query = "INSERT INTO access_logs (user_id, action, ip_address, status, session_id, user_agent, page_url, details) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bind_param("isssssss", $user_id, $action, $ip_address, $status_log, $session_id, $user_agent, $page_url, $details);
        $log_stmt->execute();
        $log_stmt->close();

        $_SESSION['message'] = 'Campaign created successfully!';
        echo "<script>window.location.href='fundraising_campaigns.php';</script>";
        exit();
    }

    // Handle Edit Campaign (check if form is submitted for an update)
    if (isset($_POST['edit_campaign'])) {
        $campaign_id = (int)$_POST['campaign_id'];

        // Fetch the existing campaign data to preserve unchanged fields
        $fetch_query = "SELECT title, description, start_date, end_date, status, fee FROM fundraising_campaigns WHERE id = ?";
        $fetch_stmt = $conn->prepare($fetch_query);
        $fetch_stmt->bind_param("i", $campaign_id);
        $fetch_stmt->execute();
        $result = $fetch_stmt->get_result();
        $existing_campaign = $result->fetch_assoc();
        $fetch_stmt->close();

        // Use existing values if fields are empty or not provided
        $title = !empty(trim($_POST['title'])) ? trim($_POST['title']) : $existing_campaign['title'];
        $description = !empty(trim($_POST['description'])) ? trim($_POST['description']) : $existing_campaign['description'];
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : $existing_campaign['start_date'];
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : $existing_campaign['end_date'];
        $status = !empty($_POST['status']) ? $_POST['status'] : $existing_campaign['status'];
        $fee = isset($_POST['fee']) && $_POST['fee'] !== '' ? (float)$_POST['fee'] : $existing_campaign['fee'];
        $association_id = $admin_association_id;

        // Update the campaign details in the database
        $update_query = "UPDATE fundraising_campaigns 
                        SET title = ?, description = ?, start_date = ?, end_date = ?, status = ?, fee = ?, association_id = ?
                        WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssssidi", $title, $description, $start_date, $end_date, $status, $fee, $association_id, $campaign_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['message'] = 'Campaign updated successfully!';
        echo "<script>window.location.href='fundraising_campaigns.php';</script>";
        exit();
    }

    // Automatically update campaign status to 'Inactive' if the end date has passed
    $current_date = date('Y-m-d');
    $update_status_query = "UPDATE fundraising_campaigns 
                            SET status = 'Inactive' 
                            WHERE end_date < ? AND status = 'Active'";
    $stmt = $conn->prepare($update_status_query);
    $stmt->bind_param("s", $current_date);
    $stmt->execute();
    $stmt->close();

    // Display list of campaigns by default
    if (!isset($_GET['create']) && !isset($_GET['edit'])) {
        $query = "SELECT fc.*, aa.school_name as association_name 
                FROM fundraising_campaigns fc
                LEFT JOIN alumni_associations aa ON fc.association_id = aa.id 
                WHERE fc.association_id = ? AND fc.status = 'Active' 
                ORDER BY fc.start_date DESC";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $admin_association_id);
        $stmt->execute();
        $result = $stmt->get_result();
    ?>
    <a href="fundraising_campaigns.php?create=true">Create New Campaign</a>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status</th>
                <th>Association</th>
                <th>Amount</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                    <td><?php echo htmlspecialchars($row['start_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['end_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                    <td><?php echo htmlspecialchars($row['association_name'] ?? ''); ?></td>
                    <td><?php echo number_format($row['fee'], 2); ?> UGX</td>
                    <td>
                        <a href="fundraising_campaigns.php?edit=true&id=<?php echo $row['id']; ?>" style="margin-right: 10px; margin-top: 5px; margin-bottom: 5px; display: inline-block;">Edit</a> |
                        <a href="fundraising_campaigns.php?delete_campaign=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this campaign?');" style="margin-left: 6px; margin-bottom: 5px; display: inline-block;">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php
    }

    // Show Create Campaign Form (with Cancel button)
    if (isset($_GET['create']) && $_GET['create'] == 'true') {
    ?>
    <h2>Create Fundraising Campaign</h2>
    <form method="POST" action="fundraising_campaigns.php">
        <!-- Title input -->
        <label for="title">Title</label>
        <input type="text" name="title" required><br>

        <!-- Description input -->
        <label for="description">Description</label>
        <textarea name="description"></textarea><br>

        <!-- Default Contribution Amount (UGX) -->
        <label for="fee">Default Contribution Amount (UGX)</label>
        <input type="number" name="fee" step="0.01" required><br>

        <!-- Start Date input -->
        <label for="start_date">Start Date</label>
        <input type="date" name="start_date" required><br>

        <!-- End Date input -->
        <label for="end_date">End Date</label>
        <input type="date" name="end_date" required><br>

        <!-- Status dropdown -->
        <label for="status">Status</label>
        <select name="status" required>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
        </select><br>

        <!-- Submit and Cancel buttons -->
        <input type="submit" name="create_campaign" value="Create Campaign">
        <a href="fundraising_campaigns.php" class="cancel-button">Cancel</a>
    </form>
    <?php
    }

    // Show Edit Campaign Form (with Cancel button, no required fields)
    if (isset($_GET['edit']) && $_GET['edit'] == 'true' && isset($_GET['id'])) {
        $campaign_id = $_GET['id'];
        $query = "SELECT * FROM fundraising_campaigns WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $campaign_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $campaign = $result->fetch_assoc();
        $stmt->close();
    ?>
    <h2>Edit Fundraising Campaign</h2>
    <form method="POST" action="fundraising_campaigns.php">
        <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">

        <label for="title">Title</label>
        <input type="text" name="title" value="<?php echo htmlspecialchars($campaign['title']); ?>"><br>

        <label for="description">Description</label>
        <textarea name="description"><?php echo htmlspecialchars($campaign['description']); ?></textarea><br>

        <label for="fee">Default Contribution Amount (UGX)</label>
        <input type="number" name="fee" step="0.01" value="<?php echo htmlspecialchars($campaign['fee']); ?>"><br>

        <label for="start_date">Start Date</label>
        <input type="date" name="start_date" value="<?php echo $campaign['start_date']; ?>"><br>

        <label for="end_date">End Date</label>
        <input type="date" name="end_date" value="<?php echo $campaign['end_date']; ?>"><br>

        <label for="status">Status</label>
        <select name="status">
            <option value="Active" <?php echo ($campaign['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
            <option value="Inactive" <?php echo ($campaign['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
        </select><br>

        <!-- Submit and Cancel buttons -->
        <input type="submit" name="edit_campaign" value="Update Campaign">
        <a href="fundraising_campaigns.php" class="cancel-button">Cancel</a>
    </form>
    <?php
    }
    ?>
    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        const createStartDateInput = document.querySelector('form[action="fundraising_campaigns.php"] input[name="start_date"]');
        
        if (createStartDateInput && window.location.search.includes('create=true')) {
            // Set the min attribute of the start date input to today's date for create form only
            const today = new Date().toISOString().split('T')[0];
            createStartDateInput.setAttribute('min', today);

            // Check for past date on create form submission only
            document.querySelector('form[action="fundraising_campaigns.php"]').addEventListener('submit', function(event) {
                if (window.location.search.includes('create=true')) {
                    const startDateValue = createStartDateInput.value;
                    if (startDateValue < today) {
                        alert('The start date cannot be in the past.');
                        event.preventDefault();  // Prevent form submission
                    }
                }
            });
        }
    });
    </script>
</div>
</body>
</html>

