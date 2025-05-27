<?php
session_start();
require_once './db_connection.php';

$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : null;
$userRole = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;

$properties = [];

if ($userRole === 'property_owner' && $userEmail) {
    // Ambil ownerNo berdasarkan email login
    $ownerEmail = $conn->real_escape_string($userEmail);
    $ownerNo = '';
    $ownerResult = $conn->query("SELECT ownerNo FROM privateowner WHERE eMail = '$ownerEmail' LIMIT 1");
    if ($ownerResult && $ownerResult->num_rows > 0) {
        $ownerRow = $ownerResult->fetch_assoc();
        $ownerNo = $ownerRow['ownerNo'];
    }
    if ($ownerNo) {
        $sql = "SELECT p.propertyNo, p.street, p.city, p.rent, p.pType, pi.image
                FROM propertyforrent p
                LEFT JOIN propertyimage pi ON p.propertyNo = pi.propertyNo
                WHERE p.ownerNo = '$ownerNo'
                ORDER BY p.propertyNo DESC";
    } else {
        $sql = null;
    }
} else {
    // Staff atau guest: tampilkan semua properti
    $sql = "SELECT p.propertyNo, p.street, p.city, p.rent, p.pType, pi.image
            FROM propertyforrent p
            LEFT JOIN propertyimage pi ON p.propertyNo = pi.propertyNo
            ORDER BY p.propertyNo DESC";
}

if ($sql) {
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $image_url = $row['image']
            ? '../img/' . $row['image']
            : '../img/no-image-available.png';

        $properties[] = [
            'propertyNo' => $row['propertyNo'],
            'title' => $row['pType'] . ' - ' . $row['street'] . ', ' . $row['city'],
            'price' => number_format($row['rent'], 0, ',', '.'),
            'image_url' => $image_url
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($properties);
$conn->close();
?>