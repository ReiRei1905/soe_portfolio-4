<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Login Page</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <link href="style_login_page.css" rel="stylesheet"/>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen">
    <div class="text-overlay">
        <h1>SOE E-PORTFOLIO</h1>
        <form class="login-form" action="login_register.php" method="POST"> <!-- Set action to your PHP script -->
            <label for="email">Email:</label>
            <div class="input-container">
                <input type="email" id="email" name="email" placeholder="Enter your email address" required>
            </div>
            <label for="password">Password:</label>
            <div class="input-container">
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit">Log-in</button>
            <div class="links">
                <a href="data_privacy.html">Don’t have an account? Sign up</a>
                <a href="#">Forgot Password?</a>
            </div>
        </form>
    </div>
</body>
</html>