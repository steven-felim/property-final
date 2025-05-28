<?php
require_once './db_connection.php';

$xml = new SimpleXMLElement('<rentedProperties/>');

// Ambil properti yang sudah dirental beserta owner & client
$sql = "SELECT 
            p.propertyNo, p.street, p.city, p.rent, p.pType,
            o.ownerNo, o.fName AS ownerFName, o.lName AS ownerLName, o.eMail AS ownerEmail,
            c.clientNo, c.fName AS clientFName, c.lName AS clientLName, c.eMail AS clientEmail,
            r.rentStart, r.rentEnd
        FROM rent r
        JOIN propertyforrent p ON r.propertyNo = p.propertyNo
        JOIN privateowner o ON p.ownerNo = o.ownerNo
        JOIN cclient c ON r.clientNo = c.clientNo";

$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $property = $xml->addChild('property');
    $property->addChild('propertyNo', htmlspecialchars($row['propertyNo']));
    $property->addChild('street', htmlspecialchars($row['street']));
    $property->addChild('city', htmlspecialchars($row['city']));
    $property->addChild('rent', $row['rent']);
    $property->addChild('type', htmlspecialchars($row['pType']));

    $owner = $property->addChild('owner');
    $owner->addChild('ownerNo', $row['ownerNo']);
    $owner->addChild('fName', htmlspecialchars($row['ownerFName']));
    $owner->addChild('lName', htmlspecialchars($row['ownerLName']));
    $owner->addChild('email', htmlspecialchars($row['ownerEmail']));

    $client = $property->addChild('client');
    $client->addChild('clientNo', $row['clientNo']);
    $client->addChild('fName', htmlspecialchars($row['clientFName']));
    $client->addChild('lName', htmlspecialchars($row['clientLName']));
    $client->addChild('email', htmlspecialchars($row['clientEmail']));

    $property->addChild('rentStart', $row['rentStart']);
    $property->addChild('rentEnd', $row['rentEnd']);
}

// Format dengan DOMDocument agar rapi
$dom = new DOMDocument('1.0');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($xml->asXML());
file_put_contents('../XML/rented-properties.xml', $dom->saveXML());

echo "XML exported to ../XML/rented-properties.xml";
echo "<br><a href='xml-admin-report.php'>Back to Admin Report</a>";
$conn->close();
?>