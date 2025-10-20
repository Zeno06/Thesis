<?php
session_start();

// Database connection
$conn = new mysqli('localhost', 'root', '', 'carmax_carmona');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    
    // Verify reCAPTCHA
    if (empty($recaptchaResponse)) {
        $error = 'Please complete the reCAPTCHA verification.';
    } else {
        $email = $conn->real_escape_string($email);
        $sql = "SELECT * FROM users WHERE email = '$email' AND status = 'active'";
        $result = $conn->query($sql);
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['firstname'] . ' ' . $user['lastname'];
                
                // Update last login
                $userId = $user['id'];
                $conn->query("UPDATE users SET last_login = NOW() WHERE id = $userId");
                
                // ✅ FIXED: Redirect the entire window, not just inside the iframe
                $role = strtolower($user['role']);
                switch ($role) {
                    case 'acquisition':
                        echo "<script>
                            window.top.location.href = '../AcquisitionPage/acquiPage.php';
                        </script>";
                        break;
                    case 'operation':
                        echo "<script>
                            window.top.location.href = '../OperationPage/operationPage.php';
                        </script>";
                        break;
                    case 'superadmin':
                        echo "<script>
                            window.top.location.href = '../SuperadminPage/superadminPage.php';
                        </script>";
                        break;
                    default:
                        echo "<script>
                            alert('Unknown role. Please contact the administrator.');
                            window.top.location.href = '../LoginPage/loginPage.php';
                        </script>";
                        break;
                }
                exit();
            } else {
                $error = 'Invalid email or password';
            }
        } else {
            $error = 'Invalid email or password';
        }
    }
}

$conn->close();
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
                <div class="g-recaptcha" data-sitekey="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI"></div>
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
