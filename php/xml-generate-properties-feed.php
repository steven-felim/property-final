<?php
require_once './db_connection.php';

$xml = new SimpleXMLElement('<propertiesFeed/>');

$res = $conn->query("SELECT propertyNo, street, city, rent, pType FROM propertyforrent");
while ($row = $res->fetch_assoc()) {
    $item = $xml->addChild('property');
    $item->addChild('propertyNo', $row['propertyNo']);
    $item->addChild('street', htmlspecialchars($row['street']));
    $item->addChild('city', htmlspecialchars($row['city']));
    $item->addChild('rent', $row['rent']);
    $item->addChild('type', htmlspecialchars($row['pType']));
}

// Format dengan DOMDocument agar rapi
$dom = new DOMDocument('1.0');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($xml->asXML());
file_put_contents('../XML/properties.xml', $dom->saveXML());

echo "Feed generated as properties.xml";
$conn->close();
?>