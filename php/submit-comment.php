<?php
session_start();
require_once './db_connection.php';

if (!isset($_SESSION['user_email']) || $_SESSION['user_role'] !== 'client') {
    echo "You must be logged in as client to comment.";
    exit;
}

$userEmail = $_SESSION['user_email'];
$propertyId = $_POST['property_id'] ?? '';
$comment = trim($_POST['comment'] ?? '');

if ($propertyId && $comment) {    // Ambil clientNo dari email
    $stmt = $conn->prepare("SELECT clientNo FROM cclient WHERE eMail = ?");
    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $stmt->bind_result($clientNo);
    $stmt->fetch();
    $stmt->close();

    if (!$clientNo) {
        echo "Client not found.";
        $conn->close();
        exit;
    }    // Update vComment pada viewing terakhir untuk property ini
    $stmt = $conn->prepare("UPDATE viewing SET vComment = ? WHERE clientNo = ? AND propertyNo = ? ORDER BY viewDate DESC LIMIT 1");
    $stmt->bind_param("sss", $comment, $clientNo, $propertyId);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo "Comment submitted!";
    } else {
        echo "Failed to submit comment. Make sure you have scheduled a viewing.";
    }
    $stmt->close();
} else {
    echo "Comment cannot be empty.";
}
$conn->close();