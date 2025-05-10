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
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Property Renting Website</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>

<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="logo">
                <img src="../img/logo.png" alt="Logo" class="logo-img">
            </div>
            <nav>
                <ul>
                    <li><a href="homepage.php">Home</a></li>
                    <li><a href="properties.php">Properties</a></li>
                    <li><a href="viewing.php">Viewing</a></li>
                    <li><a href="profile.php">Profile</a></li>
                </ul>
            </nav>
            <div class="user-info">
                <p>Welcome, <?php echo htmlspecialchars($userEmail); ?> (<?php echo htmlspecialchars($userRole); ?>)</p>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content" style="margin-top: 100px;">
            <h1>Find Your Perfect Home</h1>
            <p>Your dream property is just a click away.</p>
        </div>
    </section>

    <!-- Property Listings -->
    <section class="properties">
        <div class="container">
            <h2>Featured Properties</h2>
            <div id="property-list" class="property-list" style="margin-bottom: 100px;">
                <!-- Dynamic property cards will be loaded here -->
            </div>
        </div>
    </section>

    <!-- Footer -->
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
        // Fetch the properties data from the PHP script
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
                        <a href="#">View Details</a>
                    `;
                    propertyList.appendChild(propertyCard);
                });
            })
            .catch(error => console.log('Error fetching properties:', error));
    </script>

</body>
</html>
