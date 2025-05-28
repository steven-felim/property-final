<?php
require_once './db_connection.php';

$xml = new SimpleXMLElement('<data/>');

// Export viewing
$viewing = $xml->addChild('viewings');
$res = $conn->query("SELECT clientNo, propertyNo, viewDate, vComment FROM viewing");
while ($row = $res->fetch_assoc()) {
    $v = $viewing->addChild('viewing');
    $v->addChild('clientNo', $row['clientNo']);
    $v->addChild('propertyNo', $row['propertyNo']);
    $v->addChild('viewDate', $row['viewDate']);
    $v->addChild('vComment', htmlspecialchars($row['vComment']));
}

// Export rent
$rent = $xml->addChild('rents');
$res = $conn->query("SELECT rentNo, clientNo, propertyNo, rentStart, rentEnd FROM rent");
while ($row = $res->fetch_assoc()) {
    $r = $rent->addChild('rent');
    $r->addChild('rentNo', $row['rentNo']);
    $r->addChild('clientNo', $row['clientNo']);
    $r->addChild('propertyNo', $row['propertyNo']);
    $r->addChild('rentStart', $row['rentStart']);
    $r->addChild('rentEnd', $row['rentEnd']);
}

$dom = new DOMDocument('1.0');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($xml->asXML());
file_put_contents('../XML/viewing-rent.xml', $dom->saveXML());

echo "XML exported to ../XML/viewing-rent.xml";
echo "<br><a href='xml-admin-report.php'>Back to Admin Report</a>";
$conn->close();
?>