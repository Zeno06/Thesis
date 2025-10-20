<?php
session_start();

// Check if logged in and has correct role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'operation') {
    header('Location: /loginPage/loginPage.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operation Dashboard - CarMax</title>
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
        <h1>⚙️ Operation Dashboard</h1>
        <p><strong>Welcome:</strong> <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
    </div>
    
    <a href="/logout.php" class="logout-btn">Logout</a>
    
    <h2>Operations Management</h2>
    <p>Operation dashboard content goes here...</p>
</body>
</html>