<?php
session_start();  // Always start the session at the top of the file
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../libs/db-connection.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step 2: Retrieve and sanitize form data
    $schoolName = mysqli_real_escape_string($conn, $_POST['school-name']);
    $associationName = mysqli_real_escape_string($conn, $_POST['association-name']);
    $shortName = mysqli_real_escape_string($conn, $_POST['short-name']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $bankName = mysqli_real_escape_string($conn, $_POST['bank-name']);
    $accountHolderName = mysqli_real_escape_string($conn, $_POST['account-holder-name']);
    $accountNumber = mysqli_real_escape_string($conn, $_POST['account-number']);
    $contactPersonName = mysqli_real_escape_string($conn, $_POST['contact-person-name']);
    $contactPersonEmail = mysqli_real_escape_string($conn, $_POST['contact-person-email']);
    $contactPersonName2 = isset($_POST['contact-person-name-2']) ? mysqli_real_escape_string($conn, $_POST['contact-person-name-2']) : '';
    $contactPersonEmail2 = isset($_POST['contact-person-email-2']) ? mysqli_real_escape_string($conn, $_POST['contact-person-email-2']) : '';

    // Step 3: SQL insert query without user_id
    $sql = "INSERT INTO alumni_associations (school_name, association_name, short_name, address, bank_name, account_holder_name, account_number, contact_person_name, contact_person_email, contact_person_name_2, contact_person_email_2)
            VALUES ('$schoolName', '$associationName', '$shortName', '$address', '$bankName', '$accountHolderName', '$accountNumber', '$contactPersonName', '$contactPersonEmail', '$contactPersonName2', '$contactPersonEmail2')";

    // Step 4: Execute SQL query and handle result
    if ($conn->query($sql) === TRUE) {
        $successMessage = "Alumni Association added successfully!";
        // After successful insert, reset form fields by redirecting (with a refresh)
        header("Location: ".$_SERVER['PHP_SELF']); 
        exit();
    } else {
        $errorMessage = "Error: " . $conn->error;
    }

    // Close connection after operation
    $conn->close();
}
// Handle delete request
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $id = mysqli_real_escape_string($conn, $id);

    // Delete query
    $deleteSql = "DELETE FROM alumni_associations WHERE id = '$id'";

    if ($conn->query($deleteSql) === TRUE) {
        // Success message or redirection
        echo "<script>alert('Association deleted successfully!'); window.location.href = 'alumni.php';</script>";
    } else {
        // Error message
        echo "<script>alert('Error deleting record: " . $conn->error . "'); window.location.href = 'alumni.php';</script>";
    }
}
// Fetch existing alumni associations from the database
$sql = "SELECT * FROM alumni_associations";
$result = $conn->query($sql);
?>

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Alumni Association</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/alumni.css">
</head>
<body>

<div class="main-content">
    <div class="top-nav">
        <h2>Admin Dashboard</h2>
        <div class="user-info">
            <span>Welcome, Admin</span>
        </div>
    </div>

    <h2>Add a School Alumni Association</h2>

    <!-- Display success or error message -->
    <?php if (isset($successMessage)): ?>
        <div style="color: green; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin-bottom: 20px; border-radius: 5px;"><?= $successMessage; ?></div>
    <?php elseif (isset($errorMessage)): ?>
        <div style="color: red; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin-bottom: 20px; border-radius: 5px;"><?= $errorMessage; ?></div>
    <?php endif; ?>

    <!-- Form Section -->
    <div class="form-container">
        <form action="<?= $_SERVER['PHP_SELF']; ?>" method="POST">
            <div class="form-group">
                <label for="school-name">School Name</label>
                <input type="text" id="school-name" name="school-name" required>
            </div>

            <div class="form-group">
                <label for="association-name">Alumni Association Name</label>
                <input type="text" id="association-name" name="association-name" required>
            </div>

            <div class="form-group">
                <label for="short-name">Short Name of Association</label>
                <input type="text" id="short-name" name="short-name" required>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" required></textarea>
            </div>

            <div class="form-group">
                <label for="bank-name">Bank Name</label>
                <input type="text" id="bank-name" name="bank-name" required>
            </div>

            <div class="form-group">
                <label for="account-holder-name">Account Holder's Name</label>
                <input type="text" id="account-holder-name" name="account-holder-name" required>
            </div>

            <div class="form-group">
                <label for="account-number">Account Number</label>
                <input type="text" id="account-number" name="account-number" required>
            </div>

            <div class="form-group">
                <label for="contact-person-name">Contact Person Name</label>
                <input type="text" id="contact-person-name" name="contact-person-name" required>
            </div>

            <div class="form-group">
                <label for="contact-person-email">Contact Person Email</label>
                <input type="email" id="contact-person-email" name="contact-person-email" required>
            </div>

            <div class="form-group">
                <label for="contact-person-name-2">Second Contact Person Name (Optional)</label>
                <input type="text" id="contact-person-name-2" name="contact-person-name-2">
            </div>

            <div class="form-group">
                <label for="contact-person-email-2">Second Contact Person Email (Optional)</label>
                <input type="email" id="contact-person-email-2" name="contact-person-email-2">
            </div>

            <!-- Submit Button -->
            <button type="submit">Add Association</button>
        </form>
    </div>

    <!-- Table Section with Search Bar -->
    <div class="table-container">
        <?php
        // Fetch existing alumni associations from the database
        if ($result->num_rows > 0) {
            echo '<h2>Existing Alumni Associations</h2>';
            echo '<div class="search-container">';
            echo '<input type="text" id="associationSearch" placeholder="Search by School Name, Association Name, or Short Name...">';
            echo '</div>';
            echo '<table id="associationsTable">';
            echo '<tr><th>School Name</th><th>Association Name</th><th>Short Name</th><th>Address</th><th>Bank Name</th><th>Account Holder Name</th><th>Account Number</th><th>Contact People</th><th>Actions</th></tr>';

            // Loop through the associations and display each row
            while($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['school_name'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['association_name'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['short_name'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['address'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['bank_name'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['account_holder_name'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['account_number'] ?? '') . '</td>';
                echo '<td>';
                echo '<span class="contact-people">' . htmlspecialchars($row['contact_person_name'] ?? '') . '<br>' . htmlspecialchars($row['contact_person_email'] ?? '') . '</span>';
                if (!empty($row['contact_person_name_2'])) {
                    echo '<br><span class="contact-people">' . htmlspecialchars($row['contact_person_name_2'] ?? '') . '<br>' . htmlspecialchars($row['contact_person_email_2'] ?? '') . '</span>';
                }
                echo '</td>';
                echo '<td>';
                echo '<a href="edit_association.php?id=' . $row['id'] . '" class="edit-button">Edit</a> | ';
                echo '<a href="?delete=' . $row['id'] . '" onclick="return confirm(\'Are you sure you want to delete this association?\')" class="delete-button">Delete</a>';
                echo '</td>';
                echo '</tr>';
            }

            echo '</table>';
        } else {
            echo '<p>No associations found.</p>';
        }
        ?>
    </div>
</div>

<script>
    // JavaScript for search functionality
    document.getElementById('associationSearch').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#associationsTable tr:not(:first-child)'); // Skip header row

        rows.forEach(row => {
            const schoolName = row.cells[0].textContent.toLowerCase();     // School Name column
            const associationName = row.cells[1].textContent.toLowerCase(); // Association Name column
            const shortName = row.cells[2].textContent.toLowerCase();       // Short Name column

            // Show row if it matches search term in any of the specified columns
            if (schoolName.includes(searchTerm) || associationName.includes(searchTerm) || shortName.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>

</body>
</html>