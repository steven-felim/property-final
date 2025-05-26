<?php
// Create rent table if it doesn't exist
require_once './db_connection.php';

$createTableSQL = "
CREATE TABLE IF NOT EXISTS `rent` (
  `rentNo` int(11) NOT NULL AUTO_INCREMENT,
  `clientNo` char(4) DEFAULT NULL,
  `propertyNo` char(4) DEFAULT NULL,
  `rentStart` date DEFAULT NULL,
  `rentEnd` date DEFAULT NULL,
  PRIMARY KEY (`rentNo`),
  KEY `clientNo` (`clientNo`),
  KEY `propertyNo` (`propertyNo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if ($conn->query($createTableSQL) === TRUE) {
    echo "Rent table created successfully or already exists.<br>";
} else {
    echo "Error creating rent table: " . $conn->error . "<br>";
}

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'rent'");
if ($result->num_rows > 0) {
    echo "Rent table exists in database.<br>";
    
    // Show table structure
    $result = $conn->query("DESCRIBE rent");
    echo "<h3>Rent table structure:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Rent table does not exist in database.<br>";
}

$conn->close();
?>
