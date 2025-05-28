<?php
require_once './db_connection.php';

$xml = new SimpleXMLElement('<properties/>');

$result = $conn->query("SELECT propertyNo, street, city, rent, pType FROM propertyforrent");
while ($row = $result->fetch_assoc()) {
    $property = $xml->addChild('property');
    $property->addChild('propertyNo', htmlspecialchars($row['propertyNo']));
    $property->addChild('street', htmlspecialchars($row['street']));
    $property->addChild('city', htmlspecialchars($row['city']));
    $property->addChild('rent', $row['rent']);
    $property->addChild('type', htmlspecialchars($row['pType']));
}

// Format dengan DOMDocument agar rapi
$dom = new DOMDocument('1.0');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($xml->asXML());
file_put_contents('../XML/export-properties.xml', $dom->saveXML());

echo "XML exported to ../XML/export-properties.xml";
echo "<br><a href='xml-admin-report.php'>Back to Admin Report</a>";
$conn->close();
?>