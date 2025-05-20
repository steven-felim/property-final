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
                        <img id="highlighted-photo" src="" alt="Highlighted Property Photo">
                    </div>
                    <div class="photo-thumbnails">
                        <!-- Thumbnails will be populated by JavaScript -->
                    </div>
                </div>
                <div class="view-info" style="width: 33.33%; float: left; padding-left: 20px; margin-bottom: 100px;">
                    <div class="comments-section">
                        <h2>Comments</h2>
                        <!-- Tampilkan semua komen dari client, hanya bisa diakses owner & staff -->
                    </div>

                    <script>
                        const commentsList = document.getElementById('comments-list');
                        const commentText = document.getElementById('comment-text');
                        const submitComment = document.getElementById('submit-comment');

                        // Fetch and display existing comments
                        fetch(`../php/get-comments.php?id=${propertyId}`)
                            .then(response => response.json())
                            .then(comments => {
                                comments.forEach(comment => {
                                    const commentDiv = document.createElement('div');
                                    commentDiv.className = 'comment';
                                    commentDiv.innerHTML = `
                                        <p><strong>${comment.user}:</strong> ${comment.text}</p>
                                    `;
                                    commentsList.appendChild(commentDiv);
                                });
                            })
                            .catch(error => console.log('Error loading comments:', error));

                        // Add a new comment
                        submitComment.addEventListener('click', () => {
                            const newComment = commentText.value.trim();
                            if (newComment) {
                                fetch(`../php/add-comment.php`, {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ propertyId, text: newComment })
                                })
                                    .then(response => response.json())
                                    .then(result => {
                                        if (result.success) {
                                            const commentDiv = document.createElement('div');
                                            commentDiv.className = 'comment';
                                            commentDiv.innerHTML = `
                                                <p><strong>You:</strong> ${newComment}</p>
                                            `;
                                            commentsList.appendChild(commentDiv);
                                            commentText.value = '';
                                        } else {
                                            console.log('Error adding comment:', result.message);
                                        }
                                    })
                                    .catch(error => console.log('Error adding comment:', error));
                            }
                        });
                    </script>
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
        const urlParams = new URLSearchParams(window.location.search);
        const propertyId = urlParams.get('id');

        fetch(`../php/get-property.php?id=${propertyId}`)
            .then(response => response.json())
            .then(property => {
                const container = document.getElementById('property-details');
                container.innerHTML = `
                    <h1>${property.title}</h1>
                    <img src="${property.image_url}" alt="${property.title}">
                    <p><strong>Price:</strong> $${property.price}/month</p>
                    <p><strong>Description:</strong> ${property.description}</p>
                    <p><strong>Location:</strong> ${property.location}</p>
                `;
            })
            .catch(error => console.log('Error loading property:', error));
    </script>
</body>
</html>
