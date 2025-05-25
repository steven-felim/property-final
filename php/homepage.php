<?php
session_start();
require_once './db_connection.php';
if (!isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit();
}

if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'staff') {
    header("Location: staff.php");
    exit();
}

// Tangani AJAX Search
if (isset($_GET['search_query'])) {
    $query = trim($_GET['search_query']);
    if ($query === '') {
        echo '';
        exit;
    }

    $sql = "SELECT propertyNo, street, city, pType, rent FROM propertyforrent 
            WHERE street LIKE ? OR city LIKE ? OR pType LIKE ?";
    $like = '%' . $query . '%';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo '<div class="search-result-list">';
        while ($row = $result->fetch_assoc()) {
            echo '<div class="search-result-item">
                    <a href="property_detail.php?id=' . htmlspecialchars($row['propertyNo']) . '">
                        <div class="result-title">' . htmlspecialchars($row['street']) . ', ' . htmlspecialchars($row['city']) . '</div>
                        <div class="result-type">' . htmlspecialchars($row['pType']) . '</div>
                        <div class="result-rent">$' . number_format($row['rent'], 0, ',', '.') . '</div>
                    </a>
                  </div>';
        }
        echo '</div>';
    } else {
        echo '<div class="search-result-empty">Tidak ada properti ditemukan.</div>';
    }

    $stmt->close();
    $conn->close();
    exit;
}

// Cek login
if (!isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit();
}

$userEmail = $_SESSION['user_email'];
$userName = $_SESSION['user_name'];
$userRole = $_SESSION['user_role'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

            <!-- Search Form -->
            <form class="search-form" onsubmit="return false;">
                <input type="text" id="searchInput" name="query" placeholder="Search property..." autocomplete="off" onkeyup="searchProperty()">
            </form>
            <div id="searchResults" class="search-results"></div>

            <!-- Navigation -->
            <nav>
                <ul>
                    <li><a href="homepage.php">Home</a></li>
                    <li><a href="properties.php">Properties</a></li>
                    <?php if (in_array($userRole, ['staff', 'property_owner'])): ?>
                        <li><a href="viewing.php">Viewing</a></li>
                    <?php endif; ?>
                    <li><a href="profile.php">Profile</a></li>
                </ul>
            </nav>

            <?php if (isset($_SERVER['HTTP_REFERER']) &&
                (strpos($_SERVER['HTTP_REFERER'], 'register.php') !== false || strpos($_SERVER['HTTP_REFERER'], 'index.php') !== false)) : ?>
                <script>
                    window.onload = function () {
                        alert("Welcome, <?php echo htmlspecialchars($userName); ?> (<?php echo htmlspecialchars($userRole); ?>)");
                    };
                </script>
            <?php endif; ?>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Find Your Perfect Home</h1>
            <p>Your dream property is just a click away.</p>
        </div>
    </section>

    <!-- Property Listings -->
    <section class="properties">
        <div class="container">
            <h2>Featured Properties</h2>
            <div id="property-list" class="property-list" style="margin-bottom: 100px;"></div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2025 HBProperty | All Rights Reserved</p>
        </div>
    </footer>

    <!-- JavaScript for property listing -->
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
                        <a href="#">View Details</a>
                    `;
                    propertyList.appendChild(propertyCard);
                });
            })
            .catch(error => console.log('Error fetching properties:', error));
    </script>

    <!-- AJAX Search Script -->
    <script>
        function searchProperty() {
            const keyword = document.getElementById('searchInput').value;
            if (keyword.trim() === '') {
                document.getElementById('searchResults').innerHTML = '';
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open("GET", "homepage.php?search_query=" + encodeURIComponent(keyword), true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    document.getElementById('searchResults').innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }
    </script>
</body>

</html>
