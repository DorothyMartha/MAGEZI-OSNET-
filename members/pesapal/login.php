<?php
error_reporting(E_ALL); // Report all PHP errors
ini_set('display_errors', 1); // Display errors in the browser
session_start();


include __DIR__ . "/includes/subscription.inc.php";
include_once __DIR__ . "/dasher.php";
include __DIR__ . "/includes/login.inc.php";


?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAGEZI OSNET</title>
    <link rel="stylesheet" href="./fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">

</head>

<body>
    <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">

                    <img src="assets/img/logo.png" class="img-responsive pt-0" alt="">

                    <div class="container mb-3" style="">
                        <div class="container">

                            <form class="needs-validation" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <div class="row g-3">

                                    <!-- <div class="card-body" style="font-size:14px;"> -->

                                    <div class="pt-4 pb-2">
                                        <h6 class="card-title text-dark text-center pb-0 fs-4">
                                        </h6>

                                    </div>

                                    <!-- Email Address -->
                                    <div class="col-12">
                                        <!-- <label for="email" class="text-dark form-label">Email Address<span
                                                class="text-dark">(Optional)</span></label> -->
                                        <div class="input-group">
                                            <!-- <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span> -->
                                            <input type="text" class="form-control" id="email" placeholder="Enter email." name="email">
                                            <div class="invalid-feedback">
                                                Please enter a valid email address.
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Password -->
                                    <div class="col-12">
                                        <div class="input-group">
                                            <!-- <span class="input-group-text"><i class="bi bi-lock-fill"></i></span> -->
                                            <input type="password" placeholder="password" name="password" class="form-control" id="Password" required>

                                            <div class="invalid-feedback">Please enter your password!</div>
                                        </div>
                                    </div>

                                    <div class="col-12">

                                    </div>

                                    <button class="btn text-light" type="submit" name="submit" style="background-color:#2C4C93;">
                                        <i class="	fas fa-door-closed"></i> Sign
                                        In</button>

                            </form>
                        </div>
                    </div>
                </div>
    </section>



    <?php
    if (isset($_GET["errors"])) {
        if ($_GET["errors"] == "emptyinputs") {
            echo "<p style='color:white;'>Fill in all fields</p>";
        } else if ($_GET["errors"] == "wronglogins") {
            echo "<p style='color:white;'>Incorrect login information</p>";
        }
    }
    ?>
</body>

</html>