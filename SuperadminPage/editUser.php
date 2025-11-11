<?php
session_start();
include '../db_connect.php';
if ($_SESSION['role'] !== 'superadmin') { header('Location: ../loginPage/loginPage.php'); exit; }

$id = $_GET['id'];
$user = $conn->query("SELECT * FROM users WHERE id=$id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = $_POST['firstname'];
    $lname = $_POST['lastname'];
    $role  = $_POST['role'];
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE users SET firstname=?, lastname=?, role=?, status=? WHERE id=?");
    $stmt->bind_param("ssssi", $fname, $lname, $role, $status, $id);
    $stmt->execute();
    header("Location: manageUsers.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Account</title>
<link rel="stylesheet" href="../css/superadmin.css">
</head>

<body>
<div class="header"><div class="header-left"><div class="header-title">Edit Account</div></div></div>
<main class="main-content">
  <div class="sap-card">
    <div class="sap-card-body">
      <form method="POST">
        <label class="form-label">First Name</label>
        <input type="text" name="firstname" value="<?= htmlspecialchars($user['firstname']); ?>" class="form-control">
        <label class="form-label">Last Name</label>
        <input type="text" name="lastname" value="<?= htmlspecialchars($user['lastname']); ?>" class="form-control">
        <label class="form-label">Role</label>
        <select name="role" class="form-select">
          <option value="acquisition" <?= $user['role']=='acquisition'?'selected':''; ?>>Acquisition</option>
          <option value="operation" <?= $user['role']=='operation'?'selected':''; ?>>Operation</option>
          <option value="superadmin" <?= $user['role']=='superadmin'?'selected':''; ?>>Superadmin</option>
        </select>
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="active" <?= $user['status']=='active'?'selected':''; ?>>Active</option>
          <option value="inactive" <?= $user['status']=='inactive'?'selected':''; ?>>Inactive</option>
        </select>
        <button type="submit" class="btn-carmax-primary mt-3">Save</button>
      </form>
    </div>
  </div>
</main>
</body>
</html>
