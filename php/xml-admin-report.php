<?php
session_start();
if (!isset($_SESSION['user_email']) || ($_SESSION['sPosition'] ?? '') !== 'admin') {
    header("Location: index.php");
    exit();
}

$reportMsg = '';
$reportType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['export_properties'])) {
        include 'xml-export-properties.php';
        $reportMsg = "Properties XML exported! <a href='../XML/export-properties.xml' download>Download</a>";
        $reportType = 'properties';
        exit;
    }
    if (isset($_POST['export_viewing_rent'])) {
        include 'xml-export-viewing-rent.php';
        $reportMsg = "Viewing & Rent XML exported! <a href='../XML/viewing-rent.xml' download>Download</a>";
        $reportType = 'viewing_rent';
        exit;
    }
    if (isset($_POST['generate_feed'])) {
        include 'xml-generate-properties-feed.php';
        $reportMsg = "Properties Feed XML generated! <a href='../XML/properties.xml' download>Download</a>";
        $reportType = 'feed';
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin XML Report</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container" style="max-width:600px;margin-top:40px;">
        <h2>Admin: Generate XML Reports</h2>
        <?php if (!empty($reportMsg)): ?>
            <div class="success-message"><?= $reportMsg ?></div>
        <?php endif; ?>
        <form method="post" style="margin-bottom:20px;">
            <button type="submit" name="export_properties">Export Properties XML</button>
        </form>
        <form method="post" style="margin-bottom:20px;">
            <button type="submit" name="export_viewing_rent">Export Viewing & Rent XML</button>
        </form>
        <form method="post" style="margin-bottom:20px;">
            <button type="submit" name="generate_feed">Generate Properties Feed XML</button>
        </form>
        <hr>
        <h4>Download Last Generated Files:</h4>
        <ul>
            <li><a href="../XML/export-properties.xml" download>Properties XML</a></li>
            <li><a href="../XML/viewing-rent.xml" download>Viewing & Rent XML</a></li>
            <li><a href="../XML/properties.xml" download>Properties Feed XML</a></li>
        </ul>
    </div>
</body>
</html>