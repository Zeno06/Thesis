<?php

if (session_status() === PHP_SESSION_NONE) {
    session_name('CARMAX_MAIN_SESSION');
    session_start();
}

include '../db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $email = $conn->real_escape_string($email);
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Check if account is inactive
    if ($user['status'] !== 'active') {
        $error = 'This account is no longer active.';
    }
    // Check password
    elseif (!password_verify($password, $user['password'])) {
        $error = 'Incorrect password.';
    }
    // LOGIN SUCCESS
    else {
        session_unset();
        session_destroy();

        $sessionName = 'CARMAX_' . strtoupper($user['role']) . '_SESSION';
        session_name($sessionName);
        session_start();

        $_SESSION['id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_name'] = $user['firstname'] . ' ' . $user['lastname'];

        $conn->query("UPDATE users SET last_login = NOW() WHERE id = {$user['id']}");

        switch ($user['role']) {
            case 'acquisition': $redirect = "../AcquisitionPage/acquiPage.php"; break;
            case 'operation': $redirect = "../OperationPage/operationPage.php"; break;
            case 'superadmin': $redirect = "../SuperadminPage/superadminPage.php"; break;
            default: $redirect = "../LandingPage/LandingPage.php";
        }

        echo "<script>window.top.location.href = '$redirect';</script>";
        exit;
    }

} else {
    $error = 'Email not found.';
}
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarMax</title>
    <link rel="stylesheet" href="../css/loginPage.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <img src="../Pictures/Carmax_logo.jpg" alt="CarMax Logo" class="logo">
            <h2>Welcome Back!</h2>
        </div>
        
        <form class="login-form" method="POST" action="" id="loginForm">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrapper">
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="Enter your email address"
                        required
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    >
                </div>
                <div class="error-message" id="emailError"></div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password"
                        required
                    >
                </div>
                <div class="error-message" id="passwordError"></div>
            </div>

            <div class="captcha-wrapper">
                <div class="g-recaptcha" 
                     data-sitekey="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI"
                     data-callback="onCaptchaSuccess"></div>
            </div>
            <div class="error-message" id="captchaError"></div>

            <button type="submit" class="login-button" id="loginButton">
                Sign In
            </button>
        </form>
    </div>

    <script>
        if (window.self !== window.top) {
            document.body.classList.add('embedded');
        }
    </script>           
    
    <script src="../js/loginPage.js"></script>
</body>
</html>