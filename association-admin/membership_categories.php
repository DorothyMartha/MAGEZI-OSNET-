<?php
ob_start(); // Start output buffering to prevent header issues
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../libs/db-connection.php'; 

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Error: User not logged in. Please log in again.");
}
$admin_user_id = $_SESSION['user_id'];

// Fetch the association_id from the users table
$stmt = $conn->prepare("SELECT association_id FROM users WHERE id = ? AND association_id IS NOT NULL");
$stmt->bind_param("i", $admin_user_id);
$stmt->execute();
$stmt->bind_result($admin_association_id);
$stmt->fetch();
$stmt->close();

if (!$admin_association_id) {
    die("Error: Association ID not found or the user is not assigned to an association. Please log in again.");
}

// Ensure default category exists
$default_category_name = "Free Membership";
$default_fee = 0.00;
$check_default_query = "SELECT id FROM membership_categories WHERE is_default = 1 AND association_id = ?";
$stmt = $conn->prepare($check_default_query);
$stmt->bind_param("i", $admin_association_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $insert_default_query = "INSERT INTO membership_categories (category_name, fee, is_default, association_id) VALUES (?, ?, 1, ?)";
    $stmt = $conn->prepare($insert_default_query);
    $stmt->bind_param("sdi", $default_category_name, $default_fee, $admin_association_id);
    $stmt->execute();
}

// Handle delete request (before any output)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $check_query = $conn->prepare("SELECT is_default FROM membership_categories WHERE id = ? AND association_id = ?");
    $check_query->bind_param("ii", $delete_id, $admin_association_id);
    $check_query->execute();
    $check_result = $check_query->get_result();
    
    if ($check_result->num_rows > 0) {
        $category = $check_result->fetch_assoc();
        if ($category['is_default'] == 1) {
            echo "<script>alert('The default Free Membership category cannot be deleted.');</script>";
        } else {
            $conn->query("DELETE FROM membership_categories WHERE id=$delete_id AND association_id=$admin_association_id");
        }
    }
    header("Location: membership_categories.php");
    exit();
}

// Handle category creation/update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_category'])) {
    $category_id = $_POST['category_id'] ?? null;
    $category_name = trim($_POST['category_name']);
    $fee = floatval($_POST['fee']);
    
    if (!empty($category_id)) {
        $check_query = $conn->prepare("SELECT is_default FROM membership_categories WHERE id = ? AND association_id = ?");
        $check_query->bind_param("ii", $category_id, $admin_association_id);
        $check_query->execute();
        $check_result = $check_query->get_result();
        
        if ($check_result->num_rows > 0) {
            $category = $check_result->fetch_assoc();
            if ($category['is_default'] == 1) {
                echo "<script>alert('The default Free Membership category cannot be edited.');</script>";
                header("Location: membership_categories.php");
                exit();
            }
        }

        $sql = "UPDATE membership_categories SET category_name=?, fee=? WHERE id=? AND association_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdii", $category_name, $fee, $category_id, $admin_association_id);
    } else {
        $sql = "INSERT INTO membership_categories (category_name, fee, is_default, association_id) VALUES (?, ?, 0, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdi", $category_name, $fee, $admin_association_id);
    }
    
    if ($stmt->execute()) {
        header("Location: membership_categories.php");
        exit();
    }
}

// Fetch categories
$categories = $conn->query("SELECT * FROM membership_categories WHERE association_id = $admin_association_id ORDER BY id");

// Handle edit form display
$edit_category = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_query = $conn->prepare("SELECT * FROM membership_categories WHERE id = ? AND association_id = ?");
    $edit_query->bind_param("ii", $edit_id, $admin_association_id);
    $edit_query->execute();
    $edit_result = $edit_query->get_result();
    if ($edit_result->num_rows > 0) {
        $edit_category = $edit_result->fetch_assoc();
    }
}

include 'header.php'; 
include 'sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Categories</title>
    <style>
        .cancel-button {
            display: inline-block;
            padding: 10px 20px;
            margin-left: 10px;
            background-color: #dc3545;
            color: #fff;
            text-decoration: none;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .cancel-button:hover {
            background-color: #c82333;
            color: #fff;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f4f7fa;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            max-width: 1000px;
            margin-top: 80px;
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
            position: fixed;
            top: 10px;
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

    <h2>Membership Categories</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Fee</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $categories->fetch_assoc()) { ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                <td><?php echo number_format($row['fee'], 2); ?></td>
                <td>
                    <?php if ($row['is_default'] == 0) { ?>
                        <a href="membership_categories.php?edit=<?php echo $row['id']; ?>">Edit</a>
                        <a href="membership_categories.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
                    <?php } else { ?>
                        <span style="color: grey;">Default (Locked)</span>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
    </table>

    <?php if ($edit_category) { ?>
        <h2>Edit Membership Category</h2>
        <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <input type="hidden" name="category_id" value="<?php echo $edit_category['id']; ?>">
            <label>Name:</label>
            <input type="text" name="category_name" value="<?php echo htmlspecialchars($edit_category['category_name']); ?>">
            <label>Fee:</label>
            <input type="number" step="0.01" name="fee" value="<?php echo $edit_category['fee']; ?>">
            <input type="submit" name="save_category" value="Update">
            <a href="membership_categories.php" class="cancel-button">Cancel</a>
        </form>
    <?php } else { ?>
        <h2>Add Membership Category</h2>
        <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <label>Name:</label>
            <input type="text" name="category_name" required>
            <label>Fee:</label>
            <input type="number" step="0.01" name="fee" required>
            <input type="submit" name="save_category" value="Save">
            <a href="membership_categories.php" class="cancel-button">Cancel</a>
        </form>
    <?php } ?>
</div>
</body>
</html>

<?php 
$conn->close();
ob_end_flush(); // Flush the output buffer and send content
?>