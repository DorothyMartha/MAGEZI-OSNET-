<?php
// assign_association_admin.php

// Start the session
session_start();

// Ensure only SuperAdmins can access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'SuperAdmin') {
    header("Location: /osnet/index.php"); // Redirect unauthorized users
    exit();
}

// Include the database connection
require_once '../libs/db-connection.php';

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch members who have an association_id and are still 'Member'
$sqlMembers = "SELECT u.id, u.full_name, a.school_name 
               FROM users u
               JOIN alumni_associations a ON u.association_id = a.id
               WHERE u.role = 'Member' AND u.association_id IS NOT NULL";
$resultMembers = $conn->query($sqlMembers);
$members = [];
if ($resultMembers->num_rows > 0) {
    while ($row = $resultMembers->fetch_assoc()) {
        $members[] = $row;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form inputs
    $member_id = intval($_POST['member_id']);

    // Validate input
    if ($member_id <= 0) {
        echo "Error: Invalid member selected.";
        exit;
    }

    // Update the member's role to 'AssociationAdmin'
    $sqlUpdate = "UPDATE users SET role = 'AssociationAdmin' WHERE id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("i", $member_id);
    if ($stmtUpdate->execute()) {
        // Success message
        echo "<script>alert('Member successfully promoted to Association Admin!');
              window.location.href = 'assign_association_admin.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
    $stmtUpdate->close();
}

$conn->close();
?>
<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/assignadmins.css">
</head>

<body>
    <div class="main-content">
        <div class="top-nav">
            <h2>Admin Dashboard</h2>
            <div class="user-info">
                <span>Welcome, Admin</span>
            </div>
        </div>

        <div class="content">
            <h2>Promote Members to Association Admin</h2>
            <p>Select a member to promote them as an admin of their existing association.</p>

            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Association</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $member): ?>
                        <tr>
                            <td><?= htmlspecialchars($member['full_name']); ?></td>
                            <td><?= htmlspecialchars($member['school_name']); ?></td>
                            <td>
                                <form method="post" action="assign_association_admin.php" style="display: inline;">
                                    <input type="hidden" name="member_id" value="<?= $member['id']; ?>">
                                    <button type="submit" class="promote-btn">Promote to Admin</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>