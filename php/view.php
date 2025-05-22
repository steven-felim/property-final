<?php
    session_start();

    if (!isset($_SESSION['user_email'])) {
        header("Location: index.php");
        exit();
    }

    $userEmail = $_SESSION['user_email'];
    $userRole = $_SESSION['user_role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Details - Property Renting Website</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <img src="../img/logo.png" alt="Logo" class="logo-img">
            </div>
            <nav>
                <ul>
                    <li><a href="homepage.php">Home</a></li>
                    <li><a href="properties.php">Properties</a></li>
                    <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['staff', 'property_owner'])): ?>
                        <li><a href="viewing.php">Viewing</a></li>
                    <?php endif; ?>
                    <li><a href="profile.php">Profile</a></li>
                </ul>
            </nav>
        </div>
    </header>


    <section class="property-details">
        <div class="container" id="property-details">
            <div class="property-layout" style=" margin-top: 100px;">
                <div class="property-gallery" style="width: 66.66%; float: left;">
                    <div class="large-photo">
                        <img id="highlighted-photo" src="../img/no-image-available.png" alt="Property Image" style="max-width: 100%; max-height: 400px; border-radius: 8px; border: 1px solid #ccc;">
                    </div>
                </div>
                <div class="view-info" style="width: 33.33%; float: left; padding-left: 20px; margin-bottom: 100px;">
                    <div class="comments-section">
                        <h2>Client Comments</h2>
                        <div id="comments-list">Loading comments...</div>
                    </div>
                </div>
                <div style="clear: both;"></div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; 2025 HBProperty | All Rights Reserved</p>
        </div>
    </footer>
    <script>
        // Support both ?propertyNo=... and ?id=...
        const urlParams = new URLSearchParams(window.location.search);
        let propertyNo = urlParams.get('propertyNo');
        if (!propertyNo) propertyNo = urlParams.get('id');
        // Fetch images for this property
        fetch(`../php/get_image.php?property_id=${propertyNo}`)
            .then(res => res.json())
            .then(images => {
                if (images.length > 0) {
                    document.getElementById('highlighted-photo').src = `../img/${images[0]}`;
                }
            })
            .catch(() => {
                document.getElementById('highlighted-photo').src = '../img/no-image-available.png';
            });
        // Fetch and display comments for this property
        fetch(`../php/get-comments.php?property_id=${propertyNo}`)
            .then(res => res.json())
            .then(comments => {
                const list = comments.map(c => `<p><strong>${c.user}:</strong> ${c.comment}</p>`).join('');
                document.getElementById('comments-list').innerHTML = list || "No comments yet.";
            })
            .catch(() => {
                document.getElementById('comments-list').innerHTML = "No comments yet.";
            });
    </script>
</body>
</html>
