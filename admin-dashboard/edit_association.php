<?php
// Start session
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);  // Display all errors for debugging

require_once '../libs/db-connection.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch association data based on the ID
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $id = mysqli_real_escape_string($conn, $id);

    // Get the existing association details from the database
    $sql = "SELECT * FROM alumni_associations WHERE id = '$id'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $association = $result->fetch_assoc();
    } else {
        // Handle case where no record is found
        die("No association found with the given ID.");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update the association details
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

    // Update query
    $updateSql = "UPDATE alumni_associations 
                  SET school_name = '$schoolName', association_name = '$associationName', short_name = '$shortName', 
                      address = '$address', bank_name = '$bankName', account_holder_name = '$accountHolderName', 
                      account_number = '$accountNumber', contact_person_name = '$contactPersonName', 
                      contact_person_email = '$contactPersonEmail', contact_person_name_2 = '$contactPersonName2', 
                      contact_person_email_2 = '$contactPersonEmail2'
                  WHERE id = '$id'";

    if ($conn->query($updateSql) === TRUE) {
        echo "<script>alert('Association updated successfully!'); window.location.href = 'alumni.php';</script>";
    } else {
        echo "<script>alert('Error updating record: " . $conn->error . "');</script>";
    }
}
?>
<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Alumni Association</title>
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
    <h2>Edit Alumni Association</h2>

    <!-- Form Section -->
    <div class="form-container">
        <form method="POST" action="edit_association.php?id=<?php echo $association['id']; ?>">
            <div class="form-group">
                <label for="school-name">School Name</label>
                <input type="text" id="school-name" name="school-name" value="<?php echo $association['school_name']; ?>" required>
            </div>

            <div class="form-group">
                <label for="association-name">Association Name</label>
                <input type="text" id="association-name" name="association-name" value="<?php echo $association['association_name']; ?>" required>
            </div>

            <div class="form-group">
                <label for="short-name">Short Name</label>
                <input type="text" id="short-name" name="short-name" value="<?php echo $association['short_name']; ?>" required>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" required><?php echo $association['address']; ?></textarea>
            </div>

            <div class="form-group">
                <label for="bank-name">Bank Name</label>
                <input type="text" id="bank-name" name="bank-name" value="<?php echo $association['bank_name']; ?>" required>
            </div>

            <div class="form-group">
                <label for="account-holder-name">Account Holder's Name</label>
                <input type="text" id="account-holder-name" name="account-holder-name" value="<?php echo $association['account_holder_name']; ?>" required>
            </div>

            <div class="form-group">
                <label for="account-number">Account Number</label>
                <input type="text" id="account-number" name="account-number" value="<?php echo $association['account_number']; ?>" required>
            </div>

            <div class="form-group">
                <label for="contact-person-name">Contact Person Name</label>
                <input type="text" id="contact-person-name" name="contact-person-name" value="<?php echo $association['contact_person_name']; ?>" required>
            </div>

            <div class="form-group">
                <label for="contact-person-email">Contact Person Email</label>
                <input type="email" id="contact-person-email" name="contact-person-email" value="<?php echo $association['contact_person_email']; ?>" required>
            </div>

            <div class="form-group">
                <label for="contact-person-name-2">Second Contact Person Name (Optional)</label>
                <input type="text" id="contact-person-name-2" name="contact-person-name-2" value="<?php echo $association['contact_person_name_2']; ?>">
            </div>

            <div class="form-group">
                <label for="contact-person-email-2">Second Contact Person Email (Optional)</label>
                <input type="email" id="contact-person-email-2" name="contact-person-email-2" value="<?php echo $association['contact_person_email_2']; ?>">
            </div>

            <button type="submit">Update Association</button>
        </form>
    </div>
</div>

</body>
</html>
