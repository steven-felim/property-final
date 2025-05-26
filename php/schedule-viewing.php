<?php
// filepath: c:\xampp\htdocs\SBD\property\php\schedule-viewing.php
session_start();
require_once './db_connection.php';

if (!isset($_SESSION['user_email']) || $_SESSION['user_role'] !== 'client') {
    echo "You must be logged in as client to schedule viewing.";
    exit;
}

$userEmail = $_SESSION['user_email'];
$propertyId = $_POST['property_id'] ?? '';
$date = $_POST['viewing_date'] ?? '';

if ($propertyId && $date) {
    // Ambil clientNo dari email
    $stmt = $conn->prepare("SELECT clientNo FROM CClient WHERE eMail = ?");
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

    // Insert ke tabel viewing
    $stmt = $conn->prepare("INSERT INTO Viewing (clientNo, propertyNo, viewDate) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $clientNo, $propertyId, $date);
    if ($stmt->execute()) {
        echo "Viewing scheduled!";
    } else {
        echo "Failed to schedule viewing.";
    }
    $stmt->close();
} else {
    echo "Date is required.";
}
$conn->close();