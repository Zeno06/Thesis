<?php
session_start();
include '../db_connect.php';
if ($_SESSION['role'] !== 'superadmin') { header('Location: ../loginPage/loginPage.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $fname = $_POST['firstname'];
    $lname = $_POST['lastname'];
    $role  = $_POST['role'];
    $pass  = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (email, password, role, firstname, lastname, status) VALUES (?, ?, ?, ?, ?, 'active')");
    $stmt->bind_param("sssss", $email, $pass, $role, $fname, $lname);
    $stmt->execute();
    header("Location: manageUsers.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Account</title>
<link rel="stylesheet" href="../css/superadmin.css">
</head>
<body>
<div class="header"><div class="header-left"><div class="header-title">Add New Account</div></div></div>
<main class="main-content">
  <div class="sap-card">
    <div class="sap-card-body">
      <form method="POST">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required>
        <label class="form-label">First Name</label>
        <input type="text" name="firstname" class="form-control" required>
        <label class="form-label">Last Name</label>
        <input type="text" name="lastname" class="form-control" required>
        <label class="form-label">Role</label>
        <select name="role" class="form-select" required>
          <option value="acquisition">Acquisition</option>
          <option value="operation">Operation</option>
        </select>
        <label class="form-label">Password</label>
        <input type="text" name="password" class="form-control" required>
        <button type="submit" class="btn-carmax-primary mt-3">Create</button>
      </form>
    </div>
  </div>
</main>
</body>
</html>
