<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'header.php'; 
include 'sidebar.php';
require_once '../libs/db-connection.php';

// Check if the database connection is working
if (!$conn) {
    die("<p style='color: red;'>Connection failed: " . mysqli_connect_error() . "</p>");
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['country_name']);
    $code = trim($_POST['country_code']);

    if (!empty($name) && !empty($code)) {
        $sql = "INSERT INTO country (name, country_code) VALUES (?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ss", $name, $code);
            
            if (!$stmt->execute()) {
                echo "<p style='color: red;'>SQL Error: " . $stmt->error . "</p>";
            } else {
                echo "<p style='color: green;'>Country added successfully!</p>";
            }
            $stmt->close();
        } else {
            echo "<p style='color: red;'>SQL Prepare Error: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: red;'>Both fields are required!</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Country Codes</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/withdrawals.css">
</head>
<body>
    <div class="main-content">
        <div class="top-nav">
            <h2>Admin Dashboard</h2>
            <div class="user-info">
                <span>Welcome, Admin</span>
            </div>
        </div>
        
        <div class="container">
            <h2>Set Country Codes</h2>

            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <label for="country_name">Country Name:</label>
                <input type="text" id="country_name" name="country_name" required>

                <label for="country_code">Country Code:</label>
                <input type="text" id="country_code" name="country_code" required>

                <button type="submit">Add Country</button>
            </form>

            <h3>Existing Countries</h3>
            <table border="1">
                <thead>
                    <tr>
                        <th>Country Name</th>
                        <th>Country Code</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT name, country_code FROM country");
                    if ($result) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr><td>" . htmlspecialchars($row["name"]) . "</td><td>" . htmlspecialchars($row["country_code"]) . "</td></tr>";
                        }
                    } else {
                        echo "<tr><td colspan='2'>No data found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>


