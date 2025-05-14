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
    <title>All Properties - Property Renting Website</title>
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
                    <li><a href="viewing.php">Viewing</li>
                    <li><a href="profile.php">Profile</a></li>
                </ul>
            </nav>
        </div>
    </header>


    <section class="properties">
        <div class="container" style="margin-top: 100px;">
            <h1>All Properties</h1>
            <div class="property-list" id="property-list">
                <div class="property-card">
                    <img src="../img/property1.jpg" alt="Cozy Apartment">
                    <h3>Cozy Apartment</h3>
                    <p>$1200/month</p>
                    <a href="property.php?id=1">View Details</a>
                </div>
                <div class="property-card">
                    <img src="../img/property2.jpg" alt="Modern Condo">
                    <h3>Modern Condo</h3>
                    <p>$1500/month</p>
                    <a href="property.php?id=2">View Details</a>
                </div>
                <div class="property-card">
                    <img src="../img/property3.jpg" alt="Spacious House">
                    <h3>Spacious House</h3>
                    <p>$2000/month</p>
                    <a href="property.php?id=3">View Details</a>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; 2025 Your Website | All Rights Reserved</p>
            <div class="social-links">
                <a href="#">Facebook</a>
                <a href="#">Instagram</a>
                <a href="#">Twitter</a>
            </div>
        </div>
    </footer>

    <script>
        fetch('../php/fetch-properties.php')
            .then(response => response.json())
            .then(data => {
                const propertyList = document.getElementById('property-list');
                data.forEach(property => {
                    const propertyCard = document.createElement('div');
                    propertyCard.classList.add('property-card');
                    propertyCard.innerHTML = `
                        <img src="${property.image_url}" alt="${property.title}">
                        <h3>${property.title}</h3>
                        <p>$${property.price}/month</p>
                        <a href="property.php?id=${property.id}">View Details</a>
                    `;
                    propertyList.appendChild(propertyCard);
                });
            })
            .catch(error => console.log('Error fetching properties:', error));
    </script>
</body>
</html>
