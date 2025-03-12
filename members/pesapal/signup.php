<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once './includes/dbh.inc.php';
include("./includes/signup.inc.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $association_id = $_POST['association_id'];
    $category_id = $_POST['category_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert into pesapal_users
    $stmt = mysqli_prepare($conn, "INSERT INTO pesapal_users (first_name, last_name, email, password, association_id, category_id) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssssii", $first_name, $last_name, $email, $hashed_password, $association_id, $category_id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        echo "User successfully registered!";
    } else {
        echo "Error during registration.";
    }
}

?>


<?php
include_once "dasher.php";
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAGEZI OSNET</title>
    <link rel="stylesheet" href="./fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/signup-style.css">

</head>

<body>
    <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">

                    <img src="assets/img/logo.png" class="img-responsive pb-5" alt="">

                    <div class="mb-3" style="">
                        <div class="container">
                            <form class="needs-validation" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="input-group">
                                            <input type="text" name="first_name" class="form-control" placeholder="First Name..." value="<?php echo isset($_SESSION['user_first_name']) ? $_SESSION['user_first_name'] : ""; ?>" required><br><br>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="input-group">
                                            <input type="text" name="last_name" class="form-control" placeholder="Last Name..." value="<?php echo isset($_SESSION['user_last_name']) ? $_SESSION['user_last_name'] : ""; ?>" required><br><br>
                                        </div>
                                    </div>

                                    <!-- Email Address -->
                                    <div class="col-12">

                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                                            <input type="email" name="email" class="form-control" id="email" placeholder="you@example.com" value="<?php echo isset($_SESSION['user_email']) ? $_SESSION['user_email'] : ""; ?>" required>
                                            <div class="invalid-feedback">
                                                Please enter a valid email address.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="input-group">
                                            <input type="number" name="contact" class="form-control" placeholder="eg: 256781234567" value="<?php echo isset($_SESSION['user_contact']) ? $_SESSION['user_contact'] : ""; ?>" required><br><br>
                                        </div>
                                    </div>

                                   

                                    <!-- Password -->
                                    <div class="col-12">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                            <input type="password" placeholder="password" name="password" value="<?php echo isset($_SESSION['user_password']) ? $_SESSION['user_password'] : ""; ?>" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}" title="Must contain atleast one number, one uppercase and one lowercase letter, one character and atleast 8 or more characters" class="form-control" id="Password" required>
                                            <div class="invalid-feedback">Please enter your password!</div>
                                        </div>
                                    </div>


                                    <!--Retype Password -->
                                    <div class="col-12">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                            <input type="password" placeholder="Retype Password" name="confirm_password" value="<?php echo isset($_SESSION['user_confirm_password']) ? $_SESSION['user_confirm_password'] : ""; ?>" class="form-control" id="Password" required>
                                            <div class="invalid-feedback">Please Retype your password!</div>
                                        </div>
                                    </div>
                                    <select class="form-select" name="association_id" required>
    <?php
    $result = mysqli_query($conn, "SELECT * FROM alumni_associations");
    while ($association = mysqli_fetch_assoc($result)) {
        echo "<option value='{$association['id']}'>{$association['school_name']}</option>";
    }
    ?>
</select>
<select name="category_id" id="category_id" required>
    <option value="">Select Category</option> <!-- Default empty option -->
    <?php
    // Fetch categories from the database and populate the dropdown
    $association_id = $_POST['association_id']; // Or any other way to get the association_id
    $query = "SELECT id, category_name FROM membership_categories WHERE association_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $association_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Check if categories are found and display them
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<option value='" . $row['id'] . "'>" . $row['category_name'] . "</option>";
        }
    } else {
        echo "<option value=''>No categories available</option>";
    }
    ?>
</select>



                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" name="terms" type="checkbox" value="1" id="acceptTerms" required>
                                            <label class="text-dark form-check-label " for="acceptTerms">I agree and accept the <a href="#" style="text-decoration:none; color:#2C4C93;">terms and conditions</a></label>
                                            <div class="invalid-feedback" style="text-decoration:none">You must agree before submitting.</div>

                                        </div>
                                    </div>
                                    <!-- Other fields -->
                                    <!-- Add your other form fields here -->

                                    <button class="w-100 btn text-light" style="background-color:#2C4C93;" type="submit" name="submit">Sign Up</button>

                                    <div class="col-12">
                                        <p class="text-dark small mb-2">Already have an account? <a href="login.php" style="text-decoration:none; color:#2C4C93;">Log in</a></p>
                                    </div>
                            </form>



                            <?php
                            if (isset($_GET["error"])) {
                                if ($_GET["error"] == "emptyinput") {
                                    echo "<p class='text-danger text-center'>Fill in all fields</p>";
                                }
                                // else if($_GET["error"] == "invaliduid") {
                                //     echo "<p class='text-danger text-center'>Choose a proper username</p>";
                                // }
                                else if ($_GET["error"] == "invalidemail") {
                                    echo "<p class='text-danger text-center'>Choose a proper email</p>";
                                } else if ($_GET["error"] == "passwordsdontmatch") {
                                    echo "<p class='text-danger text-center'>Password doesn't match!</p>";
                                } else if ($_GET["error"] == "stmtfailed") {
                                    echo "<p class='text-danger text-center'>Something went wrong, try again!</p>";
                                }
                                // else if($_GET["error"] == "usernametaken") {
                                //     echo "<p class='text-danger text-center'>Username already taken!</p>";
                                // }
                                else if ($_GET["error"] == "none") {
                                    echo "<p class='text-danger text-center'>You have signed up.</p>";
                                }
                            }
                            ?>
                        </div>
                    </div>


                </div>
    </section>

    </div>
    </div>
    </div>
    </div>
    </div>
    </section>

</body>

</html>