<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'header.php'; 
include 'sidebar.php'; 
require_once '../libs/db-connection.php'; 

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Error: User not logged in. Please log in again.");
}
$admin_user_id = $_SESSION['user_id'];

// Fetch association_id from users table
$stmt = $conn->prepare("SELECT association_id FROM users WHERE id = ? AND association_id IS NOT NULL");
$stmt->bind_param("i", $admin_user_id);
$stmt->execute();
$stmt->bind_result($admin_association_id);
$stmt->fetch();
$stmt->close();

if (!$admin_association_id) {
    die("Error: Association ID not found or the user is not assigned to an association. Please log in again.");
}

// Directory for uploads
$upload_dir = "uploads/association_$admin_association_id/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle file uploads
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_customization'])) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB

    // Profile picture/logo
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        if (in_array($_FILES['profile_picture']['type'], $allowed_types) && $_FILES['profile_picture']['size'] <= $max_size) {
            $profile_path = $upload_dir . 'profile_' . time() . '.' . pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            move_uploaded_file($_FILES['profile_picture']['tmp_name'], $profile_path);
            $stmt = $conn->prepare("UPDATE alumni_associations SET profile_picture = ? WHERE id = ?");
            $stmt->bind_param("si", $profile_path, $admin_association_id);
            $stmt->execute();
        } else {
            echo "<script>alert('Invalid profile picture format or size exceeds 5MB.');</script>";
        }
    }

    // Cover photo
    if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] == 0) {
        if (in_array($_FILES['cover_photo']['type'], $allowed_types) && $_FILES['cover_photo']['size'] <= $max_size) {
            $cover_path = $upload_dir . 'cover_' . time() . '.' . pathinfo($_FILES['cover_photo']['name'], PATHINFO_EXTENSION);
            move_uploaded_file($_FILES['cover_photo']['tmp_name'], $cover_path);
            $stmt = $conn->prepare("UPDATE alumni_associations SET cover_photo = ? WHERE id = ?");
            $stmt->bind_param("si", $cover_path, $admin_association_id);
            $stmt->execute();
        } else {
            echo "<script>alert('Invalid cover photo format or size exceeds 5MB.');</script>";
        }
    }

    // Additional images
    if (isset($_FILES['additional_images']) && !empty($_FILES['additional_images']['name'][0])) {
        $files = $_FILES['additional_images'];
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] == 0 && in_array($files['type'][$i], $allowed_types) && $files['size'][$i] <= $max_size) {
                $additional_path = $upload_dir . 'additional_' . time() . '_' . $i . '.' . pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                move_uploaded_file($files['tmp_name'][$i], $additional_path);
                $stmt = $conn->prepare("INSERT INTO alumni_association_images (association_id, image_path) VALUES (?, ?)");
                $stmt->bind_param("is", $admin_association_id, $additional_path);
                $stmt->execute();
            }
        }
    }

    header("Location: customize_association.php");
    exit();
}

// Fetch current customization data
$association = $conn->query("SELECT profile_picture, cover_photo FROM alumni_associations WHERE id = $admin_association_id")->fetch_assoc();
$additional_images = $conn->query("SELECT id, image_path FROM alumni_association_images WHERE association_id = $admin_association_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customize Association Page</title>
    <style>
        /* Existing unchanged styles */
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
            transition: background-color 0.3s ease;
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

        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
            max-width: 500px;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
        }

        label {
            font-weight: 600;
            color: #34495e;
            font-size: 14px;
            margin-bottom: 5px;
        }

        input[type="file"] {
            padding: 10px;
            border: 2px dashed #bdc3c7;
            border-radius: 6px;
            background-color: #f9f9f9;
            font-size: 14px;
            color: #7f8c8d;
            cursor: pointer;
            transition: border-color 0.3s ease;
        }

        input[type="file"]:hover {
            border-color: #3498db;
        }

        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 12px 24px;
            cursor: pointer;
            max-width: 150px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        button:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        /* Enhanced Preview Section Styles */
        .preview-section {
            margin-top: 40px;
        }

        .preview-section h3 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 600;
            border-bottom: 2px solid #3498db;
            padding-bottom: 6px;
            display: inline-block;
        }

        .preview-section h4 {
            font-size: 16px;
            color: #34495e;
            margin: 15px 0 10px;
            font-weight: 500;
        }

        .image-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .image-item {
            position: relative;
            background: #fff;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .image-item:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .image-item img {
            max-width: 200px;
            height: auto;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
        }

        .delete-link {
            color: #e74c3c;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
            display: inline-block;
            transition: color 0.3s ease;
        }

        .delete-link:hover {
            color: #c0392b;
            text-decoration: underline;
        }
        h3 {
            font-size: 24px;
            color: #333333; /* Dark grey */
            margin-bottom: 20px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding-bottom: 8px; /* No underline, removed border-bottom */
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

    <h3>Customize Association Page</h3>
    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data">
        <label>Profile Picture/Logo:</label>
        <input type="file" name="profile_picture" accept="image/*">
        
        <label>Cover Photo:</label>
        <input type="file" name="cover_photo" accept="image/*">
        
        <label>Additional Images:</label>
        <input type="file" name="additional_images[]" accept="image/*" multiple>
        
        <div>
            <button type="submit" name="save_customization">Save Changes</button>
            <a href="customize_association.php" class="cancel-button">Cancel</a>
        </div>
    </form>

    <div class="preview-section">
        <h3>Current Images</h3>
        <div class="image-container">
            <?php if ($association['profile_picture']) { ?>
                <div class="image-item">
                    <h4>Profile Picture</h4>
                    <img src="<?php echo $association['profile_picture']; ?>" alt="Profile Picture">
                </div>
            <?php } ?>
            
            <?php if ($association['cover_photo']) { ?>
                <div class="image-item">
                    <h4>Cover Photo</h4>
                    <img src="<?php echo $association['cover_photo']; ?>" alt="Cover Photo">
                </div>
            <?php } ?>
            
            <?php if ($additional_images->num_rows > 0) { ?>
                <?php while ($img = $additional_images->fetch_assoc()) { ?>
                    <div class="image-item">
                        <h4>Additional Image</h4>
                        <img src="<?php echo $img['image_path']; ?>" alt="Additional Image">
                        <a href="customize_association.php?delete_image=<?php echo $img['id']; ?>" class="delete-link" onclick="return confirm('Are you sure you want to delete this image?')">Delete</a>
                    </div>
                <?php } ?>
            <?php } ?>
        </div>
    </div>
</div>
</body>
</html>

<?php
// Handle image deletion
if (isset($_GET['delete_image']) && is_numeric($_GET['delete_image'])) {
    $image_id = intval($_GET['delete_image']);
    $stmt = $conn->prepare("SELECT image_path FROM alumni_association_images WHERE id = ? AND association_id = ?");
    $stmt->bind_param("ii", $image_id, $admin_association_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $image = $result->fetch_assoc();
        unlink($image['image_path']); // Delete file from server
        $conn->query("DELETE FROM alumni_association_images WHERE id = $image_id AND association_id = $admin_association_id");
    }
    header("Location: customize_association.php");
    exit();
}

$conn->close();
?>