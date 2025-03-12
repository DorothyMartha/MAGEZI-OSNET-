<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../libs/db-connection.php'; 

// Add logging include
require_once 'editprofilefunction.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /osnet/index.php?error=not_logged_in");
    exit();
}

// Add logging call with user_id check
logActivity($conn, $_SESSION['user_id'], "Viewed edit profile page", $_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_URI'], null, 'success', session_id(), $_SERVER['HTTP_USER_AGENT'] ?? null);

// Fetch user details
$user_id = $_SESSION['user_id'];
$error = "";

try {
    // Fetch user data from users table
    $sqlUser = "SELECT full_name, email, phone_number, occupation, country_of_residence, marital_status, profile_photo 
                FROM users WHERE id = ?";
    $stmtUser = $conn->prepare($sqlUser);
    if ($stmtUser) {
        $stmtUser->bind_param("i", $user_id);
        $stmtUser->execute();
        $resultUser = $stmtUser->get_result();
        $user = $resultUser->fetch_assoc();
        $stmtUser->close();

        if (!$user) {
            die("User not found.");
        }
    } else {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // Fetch countries
    $sqlCountries = "SELECT name FROM country";
    $resultCountries = $conn->query($sqlCountries);
    if ($resultCountries) {
        $countries = [];
        while ($row = $resultCountries->fetch_assoc()) {
            $countries[] = $row;
        }
    } else {
        throw new Exception("Query failed: " . $conn->error);
    }

} catch (Exception $e) {
    $error = "Error fetching user data: " . $e->getMessage();
}

// Handle form submission for editing profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name           = trim($_POST['full_name']);
    $email               = trim($_POST['email']);
    $phone_number        = trim($_POST['phone_number']);
    $occupation          = trim($_POST['occupation']);
    $country_of_residence = trim($_POST['country_of_residence']);
    $marital_status      = $_POST['marital_status'];

    // Handle profile photo upload (optional)
    $profile_photo = $user['profile_photo'] ?? '';
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../registration/uploads/profile_photos/';

        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = uniqid() . "_" . basename($_FILES['profile_photo']['name']);
        $upload_file = $upload_dir . $file_name;

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($_FILES['profile_photo']['tmp_name']);

        if (in_array($file_type, $allowed_types)) {
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_file)) {
                $upload_message = "Profile photo updated successfully.";
                $profile_photo = "../registration/uploads/profile_photos/" . $file_name;
                if ($user['profile_photo'] && $user['profile_photo'] !== $profile_photo && file_exists($user['profile_photo'])) {
                    unlink($user['profile_photo']);
                }
            } else {
                $upload_message = "Error uploading new profile photo.";
            }
        } else {
            $upload_message = "Invalid file type for profile photo.";
        }
    }

    // Basic validation
    if (empty($full_name) || empty($email) || empty($phone_number) || empty($occupation) || empty($country_of_residence) || empty($marital_status)) {
        $error = "All fields are required.";
    } elseif (strpos($full_name, ' ') !== false) {
        $error = "Username cannot contain spaces.";
    } else {
        // Check if email or username is already taken by another user
        $sqlCheck = "SELECT id FROM users WHERE (email = ? OR full_name = ?) AND id != ?";
        $stmtCheck = $conn->prepare($sqlCheck);
        if ($stmtCheck) {
            $stmtCheck->bind_param("ssi", $email, $full_name, $user_id);
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->get_result();
            if ($resultCheck->num_rows > 0) {
                $error = "Email or username is already in use by another account.";
            }
            $stmtCheck->close();
        } else {
            $error = "Error checking duplicates: " . $conn->error;
        }
    }

    // Update profile if no errors
    if (empty($error)) {
        try {
            $sqlUpdateUser = "UPDATE users SET full_name = ?, email = ?, phone_number = ?, occupation = ?, 
                             country_of_residence = ?, marital_status = ?, profile_photo = ? 
                             WHERE id = ?";
            $stmtUpdateUser = $conn->prepare($sqlUpdateUser);
            if ($stmtUpdateUser) {
                $stmtUpdateUser->bind_param("sssssssi", $full_name, $email, $phone_number, $occupation, $country_of_residence, $marital_status, $profile_photo, $user_id);
                $stmtUpdateUser->execute();
                $stmtUpdateUser->close();

                // Log the update
                logActivity($conn, $user_id, "Updated profile details", $_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_URI'], null, 'success', session_id(), $_SERVER['HTTP_USER_AGENT'] ?? null);

                header("Location: /osnet/members/dashboard.php?edit=success");
                exit();
            } else {
                throw new Exception("Prepare failed: " . $conn->error);
            }
        } catch (Exception $e) {
            $error = "Error updating profile: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAGEZI OSNET - Edit Profile</title>
    <link rel="stylesheet" href="../assets/css/registration.css">
    <style>
        /* General Styles */
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: url('assets/images/background.jpeg') no-repeat center center/cover;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 600px;
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .header p {
            color: #7f8c8d;
            font-size: 16px;
            margin-bottom: 25px;
        }

        /* Form Styles */
        form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .form-section {
            width: 100%;
        }

        label {
            display: block;
            font-weight: 500;
            margin: 15px 0 8px;
            text-align: left;
            color: #34495e;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #dcdcdc;
            border-radius: 8px;
            font-size: 16px;
            outline: none;
            transition: border-color 0.3s, box-shadow 0.3s;
            background: #f9f9f9;
        }

        input:focus, select:focus {
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }

        .submit-btn {
            width: 100%;
            background: linear-gradient(90deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
            transition: background 0.3s, transform 0.2s;
        }

        .submit-btn:hover {
            background: linear-gradient(90deg, #2980b9, #20638f);
            transform: translateY(-2px);
        }

        .cancel-btn {
            width: 100%;
            background: linear-gradient(90deg, #e74c3c, #c0392b);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s, transform 0.2s;
        }

        .cancel-btn:hover {
            background: linear-gradient(90deg, #c0392b, #992d22);
            transform: translateY(-2px);
        }

        /* Error and Upload Messages */
        .error-message, .upload-message {
            background: #ffe6e6;
            color: #e74c3c;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .upload-message {
            background: #e6ffe6;
            color: #27ae60;
        }

        /* Current Profile Photo */
        .current-photo {
            margin: 10px 0;
        }

        .current-photo p {
            font-size: 14px;
            color: #7f8c8d;
            margin: 0;
        }

        .current-photo a {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s;
        }

        .current-photo a:hover {
            background: #2980b9;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Edit Your Profile</h1>
            <p>Update your profile details and stay connected</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($upload_message)): ?>
            <div class="upload-message"><?php echo $upload_message; ?></div>
        <?php endif; ?>

        <form method="post" action="edit_profile.php" enctype="multipart/form-data">
            <div class="form-section">
                <label for="full_name">Username</label>
                <input type="text" name="full_name" id="full_name" required value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" placeholder="Enter your Username">

                <label for="email">Email</label>
                <input type="email" name="email" id="email" required value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="your.email@example.com">

                <label for="phone_number">Phone Number</label>
                <input type="tel" name="phone_number" id="phone_number" required value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>" placeholder="Enter your phone number">

                <label for="profile_photo" data-tooltip="Upload a JPG, PNG, or GIF (max 5MB)">Profile Photo (Optional)</label>
                <input type="file" name="profile_photo" id="profile_photo">
                <?php if ($user['profile_photo']): ?>
                    <div class="current-photo">
                        <p>Current Photo: <a href="/osnet/registration/uploads/profile_photos/<?= htmlspecialchars(basename($user['profile_photo'])) ?>" target="_blank">View Photo</a></p>
                        <input type="hidden" name="current_profile_photo" value="<?= htmlspecialchars($user['profile_photo']) ?>">
                    </div>
                <?php endif; ?>

                <label for="occupation">Occupation</label>
                <input type="text" name="occupation" id="occupation" required value="<?= htmlspecialchars($user['occupation'] ?? '') ?>" placeholder="Current occupation">

                <label for="country_of_residence">Country of Residence</label>
                <select name="country_of_residence" id="country_of_residence" required>
                    <option value="">Select your country</option>
                    <?php foreach ($countries as $country): ?>
                        <option value="<?= htmlspecialchars($country['name']); ?>" <?= ($user['country_of_residence'] === $country['name']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($country['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="marital_status">Marital Status</label>
                <select name="marital_status" id="marital_status" required>
                    <option value="">Select status</option>
                    <option value="Single" <?= ($user['marital_status'] === 'Single') ? 'selected' : '' ?>>Single</option>
                    <option value="Married" <?= ($user['marital_status'] === 'Married') ? 'selected' : '' ?>>Married</option>
                    <option value="Other" <?= ($user['marital_status'] === 'Other') ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            <button type="submit" class="submit-btn">Update Profile</button>
            <a href="/osnet/members/dashboard.php" class="cancel-btn">Cancel</a>
        </form>
    </div>

    <script>
        // No association-related JavaScript needed
    </script>
</body>
</html>
<?php
// Close the connection
if (isset($conn)) $conn->close();
?>