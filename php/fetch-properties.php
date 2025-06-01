<?php
// Simple test version to debug the issue
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Test if this file is being accessed
error_log("TEST - fetch-properties.php accessed at " . date('Y-m-d H:i:s'));

// Test database connection
require_once './db_connection.php';

if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
    exit;
}

// Query to get properties with their first image
$sql = "SELECT p.propertyNo, p.street, p.city, p.rent, p.pType,
        (SELECT pi.image FROM propertyimage pi WHERE pi.propertyNo = p.propertyNo LIMIT 1) AS image
        FROM propertyforrent p 
        ORDER BY p.propertyNo DESC
        LIMIT 12";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(["error" => "Query failed: " . $conn->error]);
    exit;
}

$properties = [];
while ($row = $result->fetch_assoc()) {
    // Determine image URL
    $imageUrl = '../img/no-image-available.png'; // Default fallback
    
    if ($row['image']) {
        // Check if image file exists
        $imagePath = '../img/' . $row['image'];
        if (file_exists($imagePath)) {
            $imageUrl = $imagePath;
        }
    }
    
    $properties[] = [
        'propertyNo' => $row['propertyNo'],
        'title' => $row['pType'] . ' - ' . $row['street'] . ', ' . $row['city'],
        'price' => number_format($row['rent'], 0, ',', '.'),
        'image_url' => $imageUrl,
        'pType' => $row['pType'],
        'street' => $row['street'],
        'city' => $row['city'],
        'rent' => $row['rent']
    ];
}

echo json_encode($properties);
$conn->close();
?>