<?php
ini_set('display_errors', 1); // Display errors on screen (for testing)
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1); // Log errors to file
ini_set('error_log', '/home/mageziac/public_html/osnet/error_log'); // Specify log file path
ini_set('max_execution_time', 60); // Increase execution time for debugging
error_reporting(E_ALL);
session_start();
ob_start(); // Start output buffering

error_log("Script start: " . microtime(true));

require_once 'libs/db-connection.php'; // Assumes $conn is a MySQLi connection

// Check database connection
if (!$conn) {
    $error = "Database connection failed: " . mysqli_connect_error();
    error_log("Database connection failed: " . mysqli_connect_error());
    die($error); // Terminate with error message
} else {
    error_log("Database connection successful");
}

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    error_log("Token received: " . $token);

    // Query to find user by token
    $query = "SELECT id, email, token_expiry FROM users WHERE verification_token = ? AND token_expiry > NOW()";
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        $error = "Failed to prepare statement: " . mysqli_error($conn);
        error_log("Prepare statement failed: " . mysqli_error($conn));
        die($error); // Terminate with error message
    } else {
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user) {
            error_log("User found: ID=" . $user['id'] . ", Email=" . $user['email'] . ", Token Expiry=" . $user['token_expiry']);
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                error_log("POST processing start: " . microtime(true));
                $password = $_POST['password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';

                if ($password === $confirm_password) {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);

                    // Handle profile photo upload (comment out for testing)
                    $profile_photo = null;
                    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/osnet/registration/uploads/profile_photos/';
                        if (!file_exists($upload_dir)) {
                            if (!mkdir($upload_dir, 0777, true)) {
                                $error = "Failed to create upload directory: " . $upload_dir;
                                error_log($error);
                                die($error); // Terminate with error message
                            }
                        }

                        $file_name = uniqid() . "_" . basename($_FILES['profile_photo']['name']);
                        $upload_file = $upload_dir . $file_name;

                        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                        $file_type = mime_content_type($_FILES['profile_photo']['tmp_name']);

                        if (in_array($file_type, $allowed_types)) {
                            error_log("File move start: " . microtime(true));
                            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_file)) {
                                $profile_photo = "/osnet/registration/uploads/profile_photos/" . $file_name;
                                error_log("File move end: " . microtime(true));
                                error_log("Profile photo uploaded: " . $profile_photo);
                            } else {
                                $error = "Failed to upload profile photo to: " . $upload_file;
                                error_log($error);
                                die($error); // Terminate with error message
                            }
                        } else {
                            $error = "Invalid file type: " . $file_type . ". Only JPEG, PNG, and GIF are allowed.";
                            error_log($error);
                            die($error); // Terminate with error message
                        }
                    }

                    if (!isset($error)) {
                        $update_query = "UPDATE users SET password_hash = ?, verification_token = NULL, token_expiry = NULL, status = 'active'";
                        if ($profile_photo) {
                            $update_query .= ", profile_photo = ?";
                        }
                        $update_query .= " WHERE id = ?";
                        $stmt = mysqli_prepare($conn, $update_query);
                        if (!$stmt) {
                            $error = "Failed to prepare update statement: " . mysqli_error($conn);
                            error_log("Prepare update statement failed: " . mysqli_error($conn));
                            die($error); // Terminate with error message
                        } else {
                            if ($profile_photo) {
                                mysqli_stmt_bind_param($stmt, "ssi", $password_hash, $profile_photo, $user['id']);
                            } else {
                                mysqli_stmt_bind_param($stmt, "si", $password_hash, $user['id']);
                            }
                            error_log("Update query start: " . microtime(true));
                            if (mysqli_stmt_execute($stmt)) {
                                error_log("Update query end: " . microtime(true));
                                error_log("User updated successfully: ID=" . $user['id']);
                                $_SESSION['user_id'] = $user['id'];
                                mysqli_stmt_close($stmt);
                                header("Location: /osnet/members/dashboard.php");
                                exit();
                            } else {
                                $error = "Failed to update user: " . mysqli_error($conn);
                                error_log("Update failed: " . mysqli_error($conn));
                                die($error); // Terminate with error message
                            }
                            mysqli_stmt_close($stmt); // Remove duplicate close
                        }
                    }
                } else {
                    $error = "Passwords do not match.";
                }
            }
        } else {
            $error = "Invalid or expired token.";
            error_log("No user found for token: " . $token);
        }
    }
} else {
    $error = "No token provided.";
    error_log("No token provided in URL");
}

error_log("Script end: " . microtime(true));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Password - MAGEZI OSNET</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
            padding: 1rem;
        }

        .container {
            max-width: 480px;
            width: 100%;
            background: #ffffff;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 2rem;
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .header p {
            font-size: 1rem;
            color: #7f8c8d;
        }

        label {
            font-weight: 500;
            color: #34495e;
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        input[type="password"],
        input[type="file"] {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 1px solid #dfe6e9;
            border-radius: 10px;
            font-size: 1rem;
            color: #2c3e50;
            background: #f9fbfd;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        input[type="password"]:focus,
        input[type="file"]:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 8px rgba(52, 152, 219, 0.3);
            background: #ffffff;
        }

        .error-message {
            background: #ffebee;
            color: #c0392b;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 0.95rem;
            border: 1px solid #f5c6cb;
        }

        button {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(90deg, #3498db 0%, #9b59b6 100%);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        button:hover {
            background: linear-gradient(90deg, #2980b9 0%, #8e44ad 100%);
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }

        .decorative-line {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #3498db, #9b59b6);
            border-radius: 20px 20px 0 0;
        }

        @media (max-width: 480px) {
            .container {
                padding: 1.5rem;
                border-radius: 15px;
            }

            .header h1 {
                font-size: 1.75rem;
            }

            .header p {
                font-size: 0.9rem;
            }

            button {
                padding: 0.85rem;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="decorative-line"></div>
        <div class="header">
            <h1>Set Your Password</h1>
            <p>Secure your MAGEZI OSNET account</p>
        </div>
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php else: ?>
            <form method="post" enctype="multipart/form-data">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required placeholder="Create a strong password">

                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required placeholder="Re-enter your password">

                <label for="profile_photo">Profile Photo (Optional)</label>
                <input type="file" name="profile_photo" id="profile_photo" accept="image/*">

                <button type="submit">Set Password and Upload</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
mysqli_close($conn); // Close connection
ob_end_flush(); // Flush output buffer
error_log("Script end: " . microtime(true));
?>