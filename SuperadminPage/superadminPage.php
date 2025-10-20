<?php
session_start();

// Check if logged in and has correct role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'superadmin') {
    header('Location: /loginPage/loginPage.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - CarMax</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f3f4f6;
        }
        .header {
            background: #1e40af;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .logout-btn {
            background: #f59e0b;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>👑 Super Admin Dashboard</h1>
        <p><strong>Welcome:</strong> <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
    </div>
    
    <a href="/logout.php" class="logout-btn">Logout</a>
    
    <h2>System Management</h2>
    <p>Super admin dashboard content goes here...</p>
    <p>From here, you can create new users with their email and password.</p>
</body>
</html>