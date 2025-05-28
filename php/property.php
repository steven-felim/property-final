<?php
session_start();

if (!isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit();
}

$userEmail = $_SESSION['user_email'];
$userRole = $_SESSION['user_role'];
require_once './db_connection.php';

$rentMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rent_property']) && $userRole === 'client') {
    $propertyId = $_POST['property_id'];
    
    $stmt = $conn->prepare("SELECT clientNo FROM cclient WHERE eMail = ?");
    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $stmt->bind_result($clientNo);
    $stmt->fetch();
    $stmt->close();
    
    // Check if THE property is already rented by THE client
    $stmt = $conn->prepare("SELECT rentNo FROM rent WHERE clientNo = ? AND propertyNo = ?");
    $stmt->bind_param("ss", $clientNo, $propertyId);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $rentMsg = "You have already rented this property.";
        $stmt->close();
    } else {
        $stmt->close();
        
        // Get branchNo from property
        $stmt = $conn->prepare("SELECT branchNo FROM propertyforrent WHERE propertyNo = ?");
        $stmt->bind_param("s", $propertyId);
        $stmt->execute();
        $stmt->bind_result($branchNo);
        $stmt->fetch();
        $stmt->close();
        
        // Get staffNo (first staff in branch)
        $stmt = $conn->prepare("SELECT staffNo FROM staff WHERE branchNo = ? LIMIT 1");
        $stmt->bind_param("s", $branchNo);
        $stmt->execute();
        $stmt->bind_result($staffNo);
        $stmt->fetch();
        $stmt->close();
        
        // Check if client is registered, if not register them
        $stmt = $conn->prepare("SELECT clientNo FROM registration WHERE clientNo = ?");
        $stmt->bind_param("s", $clientNo);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows == 0) {
            $stmt->close();
            $dateJoined = date('Y-m-d');
            $stmt = $conn->prepare("INSERT INTO registration (clientNo, branchNo, staffNo, dateJoined) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $clientNo, $branchNo, $staffNo, $dateJoined);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt->close();
        }
          // Generate unique rentNo (integer)
        $rentNo = time() + rand(1000, 9999);
        
        // Set rental period (1 year from today)
        $rentStart = date('Y-m-d');
        $rentEnd = date('Y-m-d', strtotime('+1 year'));
        
        // Insert into rent table
        $stmt = $conn->prepare("INSERT INTO rent (rentNo, clientNo, propertyNo, rentStart, rentEnd) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $rentNo, $clientNo, $propertyId, $rentStart, $rentEnd);
        
        if ($stmt->execute()) {
            $rentMsg = "Successfully rented! Property rental period: $rentStart to $rentEnd";
        } else {
            $rentMsg = "Failed to process rental. Please try again.";
        }
        $stmt->close();
    }
}

// Ambil detail property dari DB
$propertyDetail = null;
$propertyId = $_GET['id'] ?? '';
if ($propertyId) {
    $stmt = $conn->prepare("SELECT pType, street, city, rooms, rent FROM propertyforrent WHERE propertyNo = ?");
    $stmt->bind_param("s", $propertyId);
    $stmt->execute();
    $stmt->bind_result($pType, $street, $city, $rooms, $rent);
    if ($stmt->fetch()) {
        $propertyDetail = [
            'pType' => $pType,
            'street' => $street,
            'city' => $city,
            'rooms' => $rooms,
            'rent' => $rent
        ];
    }
    $stmt->close();
}

// Handler untuk fetch seluruh komentar dari semua property
if (isset($_GET['all_comments'])) {
    $comments = [];    $stmt = $conn->prepare(
        "SELECT c.fName, c.lName, v.vComment, v.viewDate, v.propertyNo
         FROM viewing v
         JOIN cclient c ON v.clientNo = c.clientNo
         WHERE v.vComment IS NOT NULL AND v.vComment != ''
         ORDER BY v.viewDate DESC"
    );
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $comments[] = [
            'user' => $row['fName'] . ' ' . $row['lName'],
            'comment' => $row['vComment'],
            'date' => $row['viewDate'],
            'propertyNo' => $row['propertyNo']
        ];
    }
    $stmt->close();
    header('Content-Type: application/json');
    echo json_encode($comments);
    $conn->close();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Details</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .comment-box, .viewing-form {
            margin-top: 30px;
            border-top: 1px solid #ccc;
            padding-top: 20px;
        }
    </style>
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
    <div class="container" style="margin-top: 100px;">
        <h1>Property Details</h1>
        <div class="property-header">
            <!-- LEFT: Image Carousel -->
            <div class="property-gallery" style="flex: 2; padding-right: 20px;">
                <div class="carousel">
                    <div style="display: flex; justify-content: center; align-items: center; width: 700px; height: 400px;">
                        <img id="carousel-image" src="" alt="Property Image" style="max-width:100%; max-height: 100%; height: auto; border: 1px solid #ccc;">
                    </div>
                    <div style="text-align: center; margin-top: 10px;">
                        <button onclick="prevImage()">&#10094; Prev</button>
                        <button onclick="nextImage()">Next &#10095;</button>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Property Details -->
            <div class="property-info" style="flex: 1; background-color: #f9f9f9; padding: 20px; border: 1px solid #ccc;">
                <h2 id="property-type"><?php echo htmlspecialchars($propertyDetail['pType'] ?? '-'); ?></h2>
                <p><strong>Address:</strong> <span id="property-address"><?php echo htmlspecialchars(($propertyDetail['street'] ?? '') . ', ' . ($propertyDetail['city'] ?? '')); ?></span></p>
                <p><strong>Rooms:</strong> <span id="property-rooms"><?php echo htmlspecialchars($propertyDetail['rooms'] ?? '-'); ?></span></p>
                <p><strong>Rent:</strong> $<span id="property-rent"><?php echo htmlspecialchars($propertyDetail['rent'] ?? '-'); ?></span>/month</p>
                <?php if ($userRole === 'client'): ?>
                    <form method="post" style="margin-top: 20px;">
                        <input type="hidden" name="rent_property" value="1">
                        <input type="hidden" name="property_id" value="<?php echo htmlspecialchars($_GET['id'] ?? ''); ?>">
                        <button type="submit">Rent This Property</button>
                    </form>
                    <?php if (isset($rentMsg)) echo "<p style='color:green;'>$rentMsg</p>"; ?>
                <?php endif; ?>
            </div>
        </div>

        <hr style="margin: 40px 0;">

        <!-- Schedule Viewing -->
        <div class="viewing-form">
            <br><h3>Schedule a Viewing</h3><br>
            <div id="viewing-message"></div>
            <form id="viewing-form">
                <label for="viewing-date">Choose a date:</label>
                <input type="date" id="viewing-date" name="viewing_date" required>
                <input type="hidden" id="property-id" name="property_id" value="<?php echo htmlspecialchars($_GET['id'] ?? ''); ?>">
                <button type="submit">Submit Viewing Request</button>
            </form>
            <br>
        </div>

        <hr style="margin: 40px 0;">

        <!-- Comments Section -->
        <div class="comments-section">
            <br><h3>Comments</h3><br>
            <div id="comments-list">Loading client's comments...</div>
            <br>
            <h4>Leave a Comment</h4>
            <form id="comment-form">
                <textarea id="comment-text" name="comment" placeholder="Write your comment here..." rows="4" style="width: 100%;" required></textarea>
                <input type="hidden" name="property_id" value="<?php echo htmlspecialchars($_GET['id'] ?? ''); ?>">
                <button id="submit-comment" style="margin-top: 10px;">Submit</button>
            </form>
            <div id="comment-message"></div>
        </div>
    </div>
</section>

<footer>
    <div class="container">
        <p>&copy; 2025 HBProperty | All Rights Reserved</p>
    </div>
</footer>

<script>
    let images = [];
    let currentImageIndex = 0;

    const propertyId = new URLSearchParams(window.location.search).get('id');

    // Proper fetch with fallback handling
    fetch(`../php/get_image.php?property_id=${propertyId}`)
        .then(res => res.json())
        .then(data => {
            images = data;
            updateCarousel();
        })
        .catch(error => {
            console.error("Image fetch failed:", error);
            document.getElementById('carousel-image').src = "../img/no-image-available.png";
            document.getElementById('carousel-image').alt = "No Image Available";
        });

    function updateCarousel() {
        if (images.length === 0) {
            document.getElementById('carousel-image').src = "../img/no-image-available.png";
            document.getElementById('carousel-image').alt = "No Image Available";
            return;
        }
        const img = document.getElementById('carousel-image');
        img.src = `../img/${images[currentImageIndex]}`;
        img.alt = "Property Image";
    }

    function nextImage() {
        if (images.length === 0) return;
        currentImageIndex = (currentImageIndex + 1) % images.length;
        updateCarousel();
    }

    function prevImage() {
        if (images.length === 0) return;
        currentImageIndex = (currentImageIndex - 1 + images.length) % images.length;
        updateCarousel();
    }

    // Schedule Viewing
    document.getElementById('viewing-form').addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('../php/schedule-viewing.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.text())
        .then(data => {
            document.getElementById('viewing-message').textContent = data;
        })
        .catch(() => {
            document.getElementById('viewing-message').textContent = "Failed to schedule viewing.";
        });
    });    // Load comments
    function loadComments() {
        fetch(`../php/get-comment.php?property_id=${propertyId}`)
            .then(res => res.json())
            .then(comments => {
                const list = comments.map(c => `<p><strong>${c.user}:</strong> ${c.comment}</p>`).join('');
                document.getElementById('comments-list').innerHTML = list || "No comments yet.";
            })
            .catch(() => {
                document.getElementById('comments-list').innerHTML = "No comments yet.";
            });
    }
    loadComments();

    // Handle comment form
    document.getElementById('comment-form').addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('../php/submit-comment.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.text())
        .then(data => {
            document.getElementById('comment-message').textContent = data;
            loadComments(); 
            document.getElementById('comment-text').value = ''; 
        })
        .catch(() => {
            document.getElementById('comment-message').textContent = "Failed to submit comment.";
        });
    });

    const propertyDetail = <?php echo json_encode($propertyDetail); ?>;
    document.getElementById('property-type').textContent = propertyDetail.pType || '-';
    document.getElementById('property-address').textContent = (propertyDetail.street || '') + ', ' + (propertyDetail.city || '');
    document.getElementById('property-rooms').textContent = propertyDetail.rooms || '-';
    document.getElementById('property-rent').textContent = propertyDetail.rent || '-';
</script>
</body>
</html>
