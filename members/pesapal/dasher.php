
<?php
// Start or resume session
// session_start();
if(isset($_POST['logout'])){
    // session_start();
    session_destroy();
    header("location: signup.php");

}

// Check if the user is logged in
if (isset($_SESSION['useruid'])) {
    // If logged in, display logout button
    echo '
    <form action="" method="post">
        <a href="" type="submit" name="logout">
            Logout
        </a>
    </form>';
}

?>


<!-- Switch buttons when logged in to be logged out -->
<?php if(isset($_SESSION['useruid'])): ?>
    <form action="" method="post">
        <button class="btn btn-danger" type="submit" name="logout">
            Logout
        </button>
    </form>

    <!-- <a href="hidden.php" class="btn btn-success">Login</a> -->
    <!-- <div class='btn btn-light ms-auto'>Admin</div>"; -->
<?php endif ?>

<!-- Include Bootstrap CSS -->
<link rel="stylesheet" href="assets/bootstrap/bootstrap-5.0.2-dist/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/style.css">

