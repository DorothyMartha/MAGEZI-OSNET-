<?php
$servername = "pdb1992.awardspace.com";
$username = "ivnape_osnet";  
$password = "?K4mp4l4311";     
$dbname = "ivnape_osnet"; 

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data from POST request
$schoolName = $_POST['school-name'];
$associationName = $_POST['association-name'];
$shortName = $_POST['short-name'];
$address = $_POST['address'];
$bankName = $_POST['bank-name'];
$accountHolderName = $_POST['account-holder-name'];
$accountNumber = $_POST['account-number'];
$contactPersonName = $_POST['contact-person-name'];
$contactPersonEmail = $_POST['contact-person-email'];
$contactPersonName2 = isset($_POST['contact-person-name-2']) ? $_POST['contact-person-name-2'] : '';
$contactPersonEmail2 = isset($_POST['contact-person-email-2']) ? $_POST['contact-person-email-2'] : '';

// Insert data into the database
$sql = "INSERT INTO alumni_associations (school_name, association_name, short_name, address, bank_name, account_holder_name, account_number, contact_person_name, contact_person_email, contact_person_name_2, contact_person_email_2) 
        VALUES ('$schoolName', '$associationName', '$shortName', '$address', '$bankName', '$accountHolderName', '$accountNumber', '$contactPersonName', '$contactPersonEmail', '$contactPersonName2', '$contactPersonEmail2')";

if ($conn->query($sql) === TRUE) {
    echo "New Alumni Association added successfully";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
