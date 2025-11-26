<?php

function logActivity($conn, $user_id, $action, $page, $remarks = null) {
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, page, remarks) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $action, $page, $remarks);
    $stmt->execute();
    $stmt->close();
}
?>