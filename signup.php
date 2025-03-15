<?php
session_start();
require_once 'config/db.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sign Up</title>
    <link rel="stylesheet" href="src/styles/login-signup.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container">
        <form id="signupForm" onsubmit="handleSignup(event)">
            <h2>Create Account</h2>
            <?php if (isset($_GET['error']) && $_GET['error'] == 'exists'): ?>
                <div class="error">Email already exists</div>
            <?php endif; ?>
            <div class="input-group">
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit">Sign Up</button>
            <p>Already have an account? <a href="login.php">Login</a></p>
        </form>
    </div>

    <script>
        async function handleSignup(e) {
            e.preventDefault();
            
            const formData = new FormData(document.getElementById('signupForm'));
            
            try {
                const response = await fetch('auth/signup_handler.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Welcome!',
                        text: 'Your account has been created successfully',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    window.location.href = data.redirect;
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: data.message || 'Something went wrong!'
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Something went wrong!'
                });
            }
        }
    </script>
</body>
</html>
