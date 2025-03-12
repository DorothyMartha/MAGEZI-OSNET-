<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = 'pdb1992.awardspace.com';
$db   = 'ivnape_osnet';
$db_user = 'ivnape_osnet';
$db_pass = "?K4mp4l4311";
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Ensure user_id, association_ids, and years_of_attendance are set from session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['association_ids'])) {
    header("Location: registration.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$association_ids = $_SESSION['association_ids'];

// Fetch years_of_attendance from session (if available)
$years_of_attendance = isset($_SESSION['years_of_attendance']) ? $_SESSION['years_of_attendance'] : [];
// Debugging: Verify years data
var_dump($years_of_attendance);

// Fetch user details to display
$sqlUser = "SELECT full_name FROM users WHERE id = ?";
$stmtUser = $pdo->prepare($sqlUser);
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch();

if (!$user) {
    die("User not found.");
}

// Add new associations to user_associations with years of attendance
if (!empty($association_ids)) {
    $sqlAssoc = "INSERT IGNORE INTO user_associations (user_id, association_id, years_of_attendance) VALUES (:user_id, :association_id, :years_of_attendance)";
    $stmtAssoc = $pdo->prepare($sqlAssoc);
    foreach ($association_ids as $assoc_id) {
        $years = isset($years_of_attendance[$assoc_id]) && is_array($years_of_attendance[$assoc_id]) 
            ? trim(implode('-', array_filter([
                $years_of_attendance[$assoc_id]['start'] ?? '',
                $years_of_attendance[$assoc_id]['end'] ?? ''
            ]))) : null;
        $stmtAssoc->execute([
            ':user_id' => $user_id,
            ':association_id' => $assoc_id,
            ':years_of_attendance' => $years
        ]);
    }
    // Optionally update users.association_id to the first new one (if desired)
    // $sqlUpdate = "UPDATE users SET association_id = ? WHERE id = ? AND association_id IS NULL";
    // $pdo->prepare($sqlUpdate)->execute([$association_ids[0], $user_id]);
}

// Clear session data and redirect
unset($_SESSION['user_id']);
unset($_SESSION['association_ids']);
unset($_SESSION['years_of_attendance']); // Clear years_of_attendance from session
echo "<script>alert('Successfully added new association(s)!'); window.location.href = '/osnet/index.php';</script>";
exit();
?>