<?php
// Check if session has already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../libs/db-connection.php';// Ensure the correct path

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /osnet/index.php");
    exit();
}

// Fetch user details
$user_id = $_SESSION['user_id'];
$sql = "SELECT full_name, profile_photo FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sidebar</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>

    <style>
/* Profile Picture */
.profile-pic {
    width: 100px; /* Ensure this matches your design, adjust if needed */
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 20px;
}

/* User Name */
.sidebar h3 {
    color: #fff;
    font-size: 16px;
    margin-bottom: 10px;
    text-align: center;
}

/* Sidebar Links */
.sidebar ul {
    list-style: none;
    padding: 0;
    width: 100%;
    margin: 0;
}

.sidebar ul li {
    width: 100%;
    margin-bottom: 2px;
}

.sidebar ul li a {
    display: flex;
    align-items: center;
    padding: 4px;
    font-size: 14px;
    color: #fff;
    text-decoration: none;
    transition: background 0.3s ease-in-out, color 0.3s;
    border-radius: 5px;
    line-height: 1;
}

/* Sidebar Icons */
.sidebar ul li a i {
    margin-right: 15px !important;
    font-size: 24px !important;
}

/* Hover Effect with #1f3d7a */
.sidebar ul li a:hover {
    background-color: #1f3d7a;
    color: #ffffff;
}

/* Logout Button Styling */
.logout-btn {
    color: #d9534f;
    font-weight: 600;
}

.logout-btn:hover {
    background-color: #f8d7da;
}
    </style>
</head>
<body>
    <div class="sidebar">
        <?php if (!empty($user['profile_photo'])): ?>
            <img src="/osnet/registration/uploads/profile_photos/<?= htmlspecialchars(basename($user['profile_photo'])) ?>" alt="Profile Picture" class="profile-pic">
        <?php else: ?>
            <img src="/osnet/members/assets/images/social_icon.jpg" alt="Default Profile Picture" class="profile-pic">
        <?php endif; ?>

        <h3>Hey, <?php echo htmlspecialchars($user['full_name']); ?></h3>
        

        <ul>
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="pesapal/subscription/subscription.php"><i class="fas fa-arrow-up"></i> Upgrade</a></li>
            <li><a href="pesapal/donations/donate.php"><i class="fas fa-hand-holding-usd"></i> Donate Funds</a></li>
            <li><a href="profile.php"><i class="fas fa-lock"></i> Privacy Settings</a></li>
            <li><a href="edit_profile.php"><i class="fas fa-user"></i>Edit Profile</a></li>
            <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
</body>
</html>