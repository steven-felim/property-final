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
                <h2 id="property-type">Property Type</h2>
                <p><strong>Address:</strong> <span id="property-address"></span></p>
                <p><strong>Rooms:</strong> <span id="property-rooms"></span></p>
                <p><strong>Rent:</strong> $<span id="property-rent"></span>/month</p>
                <button style="margin-top: 20px;" onclick="rentProperty()">Rent This Property</button>
            </div>
        </div>

        <hr style="margin: 40px 0;">

        <!-- Schedule Viewing -->
        <div class="viewing-form">
            <br><h3>Schedule a Viewing</h3><br>
            <div id="viewing-message">Last visited property: DD/MM/YYYY</div>
            <form id="viewing-form">
                <label for="viewing-date">Choose a date:</label>
                <input type="date" id="viewing-date" name="viewing_date" required>
                <input type="hidden" id="property-id" name="property_id">
                <button type="submit">Submit Viewing Request</button>
            </form>
            <br>
        </div>

        <hr style="margin: 40px 0;">

        <!-- Comments Section -->
        <div class="comments-section">
            <br><h3>Comments</h3><br>
            <div id="comments-list">DD/MM/YYYY - Loading client's comments...</div>
            <br>
            <h4>Leave a Comment</h4>
            <form id="comment-form">
                <div class="comments-list" id="comments-list">
                    <!-- Comments will be populated by JavaScript -->
                </div>
                <div class="add-comment">
                    <textarea id="comment-text" placeholder="Write your comment here..." rows="4" style="width: 100%;"></textarea>
                    <button id="submit-comment" style="margin-top: 10px;">Submit</button>
                </div>
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
            updateCarousel(); // this will automatically show fallback if empty
        })
        .catch(error => {
            console.error("Image fetch failed:", error);
            document.getElementById('carousel-image').src = "../img/no-image-available.png";
            document.getElementById('carousel-image').alt = "No Image Available";
        });

    // Update image path to use ../img/ instead of ../uploads/
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
        currentImageIndex = (currentImageIndex + 1) % images.length;
        updateCarousel();
    }

    function prevImage() {
        currentImageIndex = (currentImageIndex - 1 + images.length) % images.length;
        updateCarousel();
    }

    // Handle viewing form
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
        });
    });

    // Load comments
    function loadComments() {
        fetch(`../php/get-comments.php?property_id=${propertyId}`)
            .then(res => res.json())
            .then(comments => {
                const list = comments.map(c => `<p><strong>${c.user}:</strong> ${c.comment}</p>`).join('');
                document.getElementById('comments-list').innerHTML = list || "No comments yet.";
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
            loadComments(); // Reload comments
            document.getElementById('comment').value = ''; // Clear field
        });
    });
</script>
</body>
</html>
