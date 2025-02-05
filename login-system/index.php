<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Magezi Osnet</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="login-container">
    <form action="login.php" method="POST">
        <h2>Login to Your Account</h2>
        <div class="input-group">
            <input type="text" name="username" placeholder="Username or Email" required>
        </div>
        <div class="input-group">
            <input type="password" name="password" id="password" placeholder="Password" required>
            <input type="checkbox" id="show-password"> Show Password
        </div>
        <button type="submit">Login</button>
        <p class="register-link">Don't have an account? <a href="#">Sign up</a></p>
    </form>
</div>

<script>
    // Get the password input and checkbox
    const passwordInput = document.getElementById('password');
    const showPasswordCheckbox = document.getElementById('show-password');

    // Toggle the password visibility
    showPasswordCheckbox.addEventListener('change', function () {
        if (this.checked) {
            passwordInput.type = 'text';  // Show password
        } else {
            passwordInput.type = 'password';  // Hide password
        }
    });
</script>

</body>
</html>
