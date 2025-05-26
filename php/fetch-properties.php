<?php
require_once './db_connection.php';

// Ambil properti terbaru dari database
$sql = "SELECT p.propertyNo, p.street, p.city, p.rent, p.pType, 
               pi.image
        FROM propertyforrent p
        LEFT JOIN propertyimage pi ON p.propertyNo = pi.propertyNo
        ORDER BY p.propertyNo DESC";
$result = $conn->query($sql);

$properties = [];
while ($row = $result->fetch_assoc()) {
    // Jika image kosong, pakai default
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

header('Content-Type: homepage.php');
echo json_encode($properties);
$conn->close();
?>