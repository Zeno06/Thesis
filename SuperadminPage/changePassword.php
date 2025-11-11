<?php
session_start();
include '../db_connect.php';
if ($_SESSION['role'] !== 'superadmin') { header('Location: ../loginPage/loginPage.php'); exit; }

$id = $_GET['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $conn->query("UPDATE users SET password='$new' WHERE id=$id");
    header("Location: manageUsers.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Change Password</title>
<link rel="stylesheet" href="../css/superadmin.css">
</head>
<body>
<div class="header"><div class="header-left"><div class="header-title">Change Password</div></div></div>
<main class="main-content">
  <div class="sap-card">
    <div class="sap-card-body">
      <form method="POST">
        <label class="form-label">New Password</label>
        <input type="password" name="password" class="form-control" required>
        <button type="submit" class="btn-carmax-primary mt-3">Update Password</button>
      </form>
    </div>
  </div>
</main>
</body>
</html>
