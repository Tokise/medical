<?php
session_start();
require_once 'config/db.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="src/styles/login-signup.css">
    <script src="https://accounts.google.com/gsi/client" async></script>
</head>
<body>
    <div class="container">
        <form action="auth/login_handler.php" method="POST">
            <h2>Login</h2>
            <?php if (isset($_GET['error']) && $_GET['error'] == 'invalid'): ?>
                <div class="error">Invalid email or password</div>
            <?php endif; ?>
            <div class="input-group">
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit">Log In</button>
            <div class="or-divider">OR</div>
            <div id="g_id_onload"
                data-client_id="851838852969-fetbeefl2lqobjvh0dlnq10vi17t1g4t.apps.googleusercontent.com"
                data-context="signin"
                data-callback="handleCredentialResponse">
            </div>
            <div class="g_id_signin"
                data-type="standard"
                data-size="large"
                data-theme="outline"
                data-text="sign_in_with"
                data-shape="rectangular"
                data-logo_alignment="left">
            </div>
            <p>Don't have an account? <a href="signup.php">Sign Up</a></p>
        </form>
    </div>
    <script>
        function handleCredentialResponse(response) {
            // Send the token to your server
            fetch('auth/google_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'credential=' + encodeURIComponent(response.credential)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.href = 'src/dashboard/index.php';
                } else {
                    console.error('Error:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>
