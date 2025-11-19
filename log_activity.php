<?php

function logActivity($conn, $user_id, $action, $page) {
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, page) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $action, $page);
    $stmt->execute();
    $stmt->close();
}
?>