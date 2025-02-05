<?php
// Start the session
session_start();

// Database connection 
$servername = "localhost";
$username = "root";  
$password = "";     
$dbname = "magezi_osnet"; 

// Creating connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Checking connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Checking if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = $_POST['username']; // Get username/email from form
    $pass = $_POST['password']; // Get password from form

    // Query to find the user by username or email
    $sql = "SELECT * FROM users WHERE email = '$user' OR phone_number = '$user'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // User found, check the password
        $row = $result->fetch_assoc();
        if (password_verify($pass, $row['password_hash'])) {
            // Password is correct, start session and redirect
            $_SESSION['user_id'] = $row['id']; // Store user ID in session
            $_SESSION['user'] = $row['full_name']; // Store full name in session
            header("Location: dashboard.php"); // Redirect to dashboard or homepage
            exit();
        } else {
            // Invalid password
            echo "Invalid password.";
        }
    } else {
        // User not found
        echo "No user found with that username/email.";
    }
}

// Close the database connection
$conn->close();
?>
