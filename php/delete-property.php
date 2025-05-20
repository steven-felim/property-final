<?php
session_start();

if (!isset($_SESSION['user_email']) || $_SESSION['user_role'] !== 'property_owner') {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['propertyNo'])) {
    require_once './db_connection.php';
    $propertyNo = $_POST['propertyNo'];
    $email = $_SESSION['user_email'];
    // Get ownerNo for this user
    $stmt = $conn->prepare("SELECT ownerNo FROM PrivateOwner WHERE eMail = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($ownerNo);
    $stmt->fetch();
    $stmt->close();
    if (!$ownerNo) {
        die('Owner not found.');
    }
    // Only allow delete if this property belongs to this owner
    $stmt = $conn->prepare("DELETE FROM PropertyForRent WHERE propertyNo = ? AND ownerNo = ?");
    $stmt->bind_param("ss", $propertyNo, $ownerNo);
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: properties.php");
        exit();
    } else {
        $error = 'Error deleting property: ' . $stmt->error;
        $stmt->close();
        $conn->close();
        die($error);
    }
} else {
    header("Location: properties.php");
    exit();
}
?>
