<?php
session_start();
require_once './db_connection.php';

if (!isset($_SESSION['user_email']) || $_SESSION['user_role'] !== 'client') {
    echo "You must be logged in as client to schedule viewing.";
    exit;
}

$userEmail = $_SESSION['user_email'];
$propertyId = $_POST['property_id'] ?? '';
$date = $_POST['viewing_date'] ?? '';
$time = $_POST['viewing_time'] ?? '';

if ($propertyId && $date && $time) {
    // Combine date and time into a DATETIME string
    $viewDateTime = date('Y-m-d H:i:s', strtotime("$date $time"));

    // Get clientNo from email
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
    }

    // Check if viewing already exists for this client and property
    $stmt = $conn->prepare("SELECT COUNT(*) FROM viewing WHERE clientNo = ? AND propertyNo = ?");
    $stmt->bind_param("ss", $clientNo, $propertyId);
    $stmt->execute();
    $stmt->bind_result($existingCount);
    $stmt->fetch();
    $stmt->close();

    if ($existingCount > 0) {
        // Update existing viewing
        $stmt = $conn->prepare("UPDATE viewing SET viewDate = ? WHERE clientNo = ? AND propertyNo = ?");
        $stmt->bind_param("sss", $viewDateTime, $clientNo, $propertyId);
        if ($stmt->execute()) {
            echo "Viewing rescheduled!";
        } else {
            echo "Failed to reschedule viewing.";
        }
        $stmt->close();
    } else {
        // Insert new viewing
        $stmt = $conn->prepare("INSERT INTO viewing (clientNo, propertyNo, viewDate) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $clientNo, $propertyId, $viewDateTime);
        if ($stmt->execute()) {
            echo "Viewing scheduled!";
        } else {
            echo "Failed to schedule viewing.";
        }
        $stmt->close();
    }
} else {
    echo "Date and time are required.";
}

$conn->close();
