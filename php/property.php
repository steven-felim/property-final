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
    $comments = [];
    $stmt = $conn->prepare(
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
include_once './header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Details - HBProperty</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta name="description" content="View detailed information about this property including photos, amenities, and pricing.">
</head>
<body>

<section class="property-details">
    <div class="container">
        <!-- Breadcrumb Navigation -->
        <nav class="breadcrumb">
            <a href="homepage.php">Home</a>
            <span>/</span>
            <a href="properties.php">Properties</a>
            <span>/</span>
            <span>Property Details</span>
        </nav>

        <h1 class="property-title">Property Details</h1>
        
        <div class="property-layout">
            <!-- LEFT: Enhanced Image Gallery -->
            <div class="property-gallery">
                <div class="main-image-container">
                    <img id="main-property-image" src="../img/no-image-available.png" alt="Property Image" class="main-image">
                    <div class="image-overlay">
                        <button class="carousel-btn prev-btn" onclick="prevImage()" aria-label="Previous image">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
                            </svg>
                        </button>
                        <button class="carousel-btn next-btn" onclick="nextImage()" aria-label="Next image">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
                            </svg>
                        </button>
                    </div>
                    <div class="image-counter">
                        <span id="current-image">1</span> / <span id="total-images">1</span>
                    </div>
                </div>
                
                <div class="image-thumbnails" id="image-thumbnails">
                    <!-- Thumbnails will be populated by JavaScript -->
                </div>
            </div>

            <!-- RIGHT: Enhanced Property Information -->
            <div class="property-info-panel">
                <div class="property-header">
                    <h2 id="property-type"><?php echo htmlspecialchars($propertyDetail['pType'] ?? 'Property'); ?></h2>
                    <div class="property-status">
                        <span class="status-badge available">Available</span>
                    </div>
                </div>

                <div class="property-price-section">
                    <div class="price-main">
                        $<span id="property-rent"><?php echo htmlspecialchars($propertyDetail['rent'] ?? '0'); ?></span>
                        <span class="price-period">/month</span>
                    </div>
                </div>

                <div class="property-details-grid">
                    <div class="detail-item">
                        <div class="detail-icon">📍</div>
                        <div class="detail-content">
                            <span class="detail-label">Address</span>
                            <span class="detail-value" id="property-address">
                                <?php echo htmlspecialchars(($propertyDetail['street'] ?? '') . ', ' . ($propertyDetail['city'] ?? '')); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">🏠</div>
                        <div class="detail-content">
                            <span class="detail-label">Rooms</span>
                            <span class="detail-value" id="property-rooms"><?php echo htmlspecialchars($propertyDetail['rooms'] ?? '-'); ?></span>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">🏢</div>
                        <div class="detail-content">
                            <span class="detail-label">Property Type</span>
                            <span class="detail-value"><?php echo htmlspecialchars($propertyDetail['pType'] ?? '-'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Action Buttons -->
                <div class="property-actions">
                    <?php if ($userRole === 'client'): ?>
                        <button class="btn-primary rent-btn" onclick="openRentModal()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 8px;">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            Rent This Property
                        </button>
                        <button class="btn-secondary schedule-btn" onclick="openScheduleModal()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 8px;">
                                <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.11 0 2-.9 2-2V5c0-1.1-.89-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                            </svg>
                            Schedule Viewing
                        </button>
                    <?php endif; ?>
                    
                    <button class="btn-outline share-btn" onclick="shareProperty()">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 8px;">
                            <path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.50-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92S19.61 16.08 18 16.08z"/>
                        </svg>
                        Share
                    </button>
                </div>

                <?php if (isset($rentMsg) && $rentMsg): ?>
                    <div class="rent-message <?php echo strpos($rentMsg, 'Successfully') !== false ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($rentMsg); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Comments Section -->
        <div class="comments-section">
            <h3>Client Reviews & Comments</h3>
            <div id="comments-list" class="comments-list">
                <div class="loading-comments">Loading reviews...</div>
            </div>
            
            <?php if ($userRole === 'client'): ?>
                <div class="comment-form">
                    <h4>Leave a Review</h4>
                    <form id="comment-form">
                        <div class="rating-section">
                            <label>Rate this property:</label>
                            <div class="star-rating">
                                <span class="star" data-rating="1">★</span>
                                <span class="star" data-rating="2">★</span>
                                <span class="star" data-rating="3">★</span>
                                <span class="star" data-rating="4">★</span>
                                <span class="star" data-rating="5">★</span>
                            </div>
                        </div>
                        <textarea id="comment-text" name="comment" placeholder="Share your experience with this property..." rows="4" required></textarea>
                        <input type="hidden" name="property_id" value="<?php echo htmlspecialchars($_GET['id'] ?? ''); ?>">
                        <button type="submit" class="btn-primary">Submit Review</button>
                    </form>
                    <div id="comment-message" class="form-message"></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Rent Property Modal -->
<div id="rent-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Rent This Property</h3>
            <button class="modal-close" onclick="closeModal('rent-modal')">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to rent this property?</p>
            <div class="rental-terms">
                <h4>Rental Terms:</h4>
                <ul>
                    <li>Rental period: 1 year</li>
                    <li>Monthly rent: $<span id="modal-rent"><?php echo htmlspecialchars($propertyDetail['rent'] ?? '0'); ?></span></li>
                    <li>Security deposit required</li>
                    <li>Property inspection included</li>
                </ul>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeModal('rent-modal')">Cancel</button>
            <form method="post" style="display: inline;">
                <input type="hidden" name="rent_property" value="1">
                <input type="hidden" name="property_id" value="<?php echo htmlspecialchars($_GET['id'] ?? ''); ?>">
                <button type="submit" class="btn-primary">Confirm Rental</button>
            </form>
        </div>
    </div>
</div>

<!-- Schedule Viewing Modal -->
<div id="schedule-modal" class="modal">
    <div class="modal-content schedule-modal-content">
        <div class="modal-header">
            <h3>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 10px;">
                    <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.11 0 2-.9 2-2V5c0-1.1-.89-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                </svg>
                Schedule a Property Viewing
            </h3>
            <button class="modal-close" onclick="closeModal('schedule-modal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="viewing-intro">
                <p>Book a personal viewing of this property at your convenience. Our staff will be available to show you around and answer any questions.</p>
            </div>

            <form id="viewing-form">
                <div class="form-step active" id="step-1">
                    <h4 class="step-title">
                        <span class="step-number">1</span>
                        Select Your Preferred Date
                    </h4>
                    <div class="date-selection">
                        <div class="form-group">
                            <label for="viewing-date">Choose Date:</label>
                            <input type="date" id="viewing-date" name="viewing_date" required min="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                        </div>
                        <div class="date-info">
                            <div class="info-item">
                                <span class="info-icon">📅</span>
                                <span>Available dates: Today to 30 days ahead</span>
                            </div>
                            <div class="info-item">
                                <span class="info-icon">⏰</span>
                                <span>Viewings available Monday to Saturday</span>
                            </div>
                        </div>
                    </div>
                    <div class="step-actions">
                        <button type="button" class="btn-next" onclick="nextStep()">Next Step</button>
                    </div>
                </div>

                <div class="form-step" id="step-2">
                    <h4 class="step-title">
                        <span class="step-number">2</span>
                        Choose Your Preferred Time
                    </h4>
                    <div class="time-selection">
                        <div class="time-slots-grid" id="time-slots">
                            <!-- Morning Slots -->
                            <div class="time-period">
                                <h5>Morning</h5>
                                <div class="slots-row">
                                    <div class="time-slot" data-time="09:00">
                                        <span class="time">9:00 AM</span>
                                        <span class="availability">Available</span>
                                    </div>
                                    <div class="time-slot" data-time="10:00">
                                        <span class="time">10:00 AM</span>
                                        <span class="availability">Available</span>
                                    </div>
                                    <div class="time-slot" data-time="11:00">
                                        <span class="time">11:00 AM</span>
                                        <span class="availability">Available</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Afternoon Slots -->
                            <div class="time-period">
                                <h5>Afternoon</h5>
                                <div class="slots-row">
                                    <div class="time-slot" data-time="14:00">
                                        <span class="time">2:00 PM</span>
                                        <span class="availability">Available</span>
                                    </div>
                                    <div class="time-slot" data-time="15:00">
                                        <span class="time">3:00 PM</span>
                                        <span class="availability">Available</span>
                                    </div>
                                    <div class="time-slot" data-time="16:00">
                                        <span class="time">4:00 PM</span>
                                        <span class="availability">Available</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="selected-time" name="viewing_time" required>
                    </div>
                    <div class="step-actions">
                        <button type="button" class="btn-prev" onclick="prevStep()">Previous</button>
                        <button type="button" class="btn-next" onclick="nextStep()">Next Step</button>
                    </div>
                </div>

                <div class="form-step" id="step-3">
                    <h4 class="step-title">
                        <span class="step-number">3</span>
                        Additional Information
                    </h4>
                    <div class="additional-info">
                        <div class="form-group">
                            <label for="viewing-contact">Contact Number:</label>
                            <input type="tel" id="viewing-contact" name="contact_number" placeholder="Your phone number for confirmation" required>
                        </div>
                        <div class="form-group">
                            <label for="viewing-notes">Special Requests (Optional):</label>
                            <textarea id="viewing-notes" name="notes" rows="4" placeholder="Any specific areas you'd like to focus on, accessibility needs, or questions you have..."></textarea>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="bring_documents" value="1">
                                <span class="checkmark"></span>
                                I will bring necessary documents (ID, proof of income if interested)
                            </label>
                        </div>
                    </div>
                    <div class="step-actions">
                        <button type="button" class="btn-prev" onclick="prevStep()">Previous</button>
                        <button type="button" class="btn-next" onclick="reviewBooking()">Review Booking</button>
                    </div>
                </div>

                <div class="form-step" id="step-4">
                    <h4 class="step-title">
                        <span class="step-number">4</span>
                        Confirm Your Booking
                    </h4>
                    <div class="booking-summary">
                        <div class="summary-card">
                            <h5>Booking Summary</h5>
                            <div class="summary-details">
                                <div class="summary-item">
                                    <span class="label">Property:</span>
                                    <span class="value" id="summary-property"><?php echo htmlspecialchars($propertyDetail['pType'] ?? 'Property'); ?></span>
                                </div>
                                <div class="summary-item">
                                    <span class="label">Address:</span>
                                    <span class="value" id="summary-address"><?php echo htmlspecialchars(($propertyDetail['street'] ?? '') . ', ' . ($propertyDetail['city'] ?? '')); ?></span>
                                </div>
                                <div class="summary-item">
                                    <span class="label">Date:</span>
                                    <span class="value" id="summary-date">-</span>
                                </div>
                                <div class="summary-item">
                                    <span class="label">Time:</span>
                                    <span class="value" id="summary-time">-</span>
                                </div>
                                <div class="summary-item">
                                    <span class="label">Contact:</span>
                                    <span class="value" id="summary-contact">-</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="important-notes">
                            <h6>Important Notes:</h6>
                            <ul>
                                <li>Please arrive 5-10 minutes early</li>
                                <li>Bring a valid ID for verification</li>
                                <li>Our staff will contact you 24 hours before to confirm</li>
                                <li>You can reschedule up to 2 hours before the appointment</li>
                            </ul>
                        </div>
                    </div>
                    <div class="step-actions">
                        <button type="button" class="btn-prev" onclick="prevStep()">Previous</button>
                        <button type="submit" class="btn-confirm">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 8px;">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                            </svg>
                            Confirm Booking
                        </button>
                    </div>
                </div>

                <input type="hidden" name="property_id" value="<?php echo htmlspecialchars($_GET['id'] ?? ''); ?>">
            </form>

            <!-- Progress Indicator -->
            <div class="progress-indicator">
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
                <div class="progress-steps">
                    <div class="progress-step active" data-step="1">
                        <span class="step-dot"></span>
                        <span class="step-label">Date</span>
                    </div>
                    <div class="progress-step" data-step="2">
                        <span class="step-dot"></span>
                        <span class="step-label">Time</span>
                    </div>
                    <div class="progress-step" data-step="3">
                        <span class="step-dot"></span>
                        <span class="step-label">Details</span>
                    </div>
                    <div class="progress-step" data-step="4">
                        <span class="step-dot"></span>
                        <span class="step-label">Confirm</span>
                    </div>
                </div>
            </div>
        </div>
        <div id="viewing-message" class="form-message"></div>
    </div>
</div>

<?php include_once './footer.php'; ?>

<script>
    // Enhanced Property Details Manager
    class PropertyDetailsManager {
        constructor() {
            this.images = [];
            this.currentImageIndex = 0;
            this.propertyId = new URLSearchParams(window.location.search).get('id');
            this.rating = 0;
            this.init();
        }

        async init() {
            await this.loadImages();
            await this.loadComments();
            this.setupEventListeners();
            this.setupStarRating();
        }

        async loadImages() {
            try {
                const response = await fetch(`../php/get_image.php?property_id=${this.propertyId}`);
                this.images = await response.json();
                this.updateImageGallery();
            } catch (error) {
                console.error('Error loading images:', error);
                this.images = [];
                this.updateImageGallery();
            }
        }

        updateImageGallery() {
            const mainImage = document.getElementById('main-property-image');
            const thumbnailsContainer = document.getElementById('image-thumbnails');
            const currentImageSpan = document.getElementById('current-image');
            const totalImagesSpan = document.getElementById('total-images');

            if (this.images.length === 0) {
                mainImage.src = "../img/no-image-available.png";
                mainImage.alt = "No Image Available";
                thumbnailsContainer.innerHTML = '';
                currentImageSpan.textContent = '0';
                totalImagesSpan.textContent = '0';
                return;
            }

            // Update main image
            mainImage.src = `../img/${this.images[this.currentImageIndex]}`;
            mainImage.alt = "Property Image";

            // Update counters
            currentImageSpan.textContent = this.currentImageIndex + 1;
            totalImagesSpan.textContent = this.images.length;

            // Update thumbnails
            thumbnailsContainer.innerHTML = '';
            this.images.forEach((image, index) => {
                const thumbnail = document.createElement('img');
                thumbnail.src = `../img/${image}`;
                thumbnail.alt = `Property Image ${index + 1}`;
                thumbnail.className = `thumbnail ${index === this.currentImageIndex ? 'active' : ''}`;
                thumbnail.onclick = () => this.setCurrentImage(index);
                thumbnailsContainer.appendChild(thumbnail);
            });
        }

        setCurrentImage(index) {
            this.currentImageIndex = index;
            this.updateImageGallery();
        }

        nextImage() {
            if (this.images.length === 0) return;
            this.currentImageIndex = (this.currentImageIndex + 1) % this.images.length;
            this.updateImageGallery();
        }

        prevImage() {
            if (this.images.length === 0) return;
            this.currentImageIndex = (this.currentImageIndex - 1 + this.images.length) % this.images.length;
            this.updateImageGallery();
        }

        async loadComments() {
            try {
                const response = await fetch(`../php/get-comment.php?property_id=${this.propertyId}`);
                const comments = await response.json();
                this.displayComments(comments);
            } catch (error) {
                console.error('Error loading comments:', error);
                document.getElementById('comments-list').innerHTML = '<div class="error-message">Failed to load reviews.</div>';
            }
        }

        displayComments(comments) {
            const commentsList = document.getElementById('comments-list');
            
            if (comments.length === 0) {
                commentsList.innerHTML = '<div class="no-comments">No reviews yet. Be the first to review this property!</div>';
                return;
            }

            commentsList.innerHTML = comments.map(comment => `
                <div class="comment-item">
                    <div class="comment-header">
                        <div class="comment-author">${comment.user}</div>
                        <div class="comment-date">${new Date(comment.date).toLocaleDateString()}</div>
                    </div>
                    <div class="comment-content">${comment.comment}</div>
                </div>
            `).join('');
        }

        setupEventListeners() {
            // Viewing form
            document.getElementById('viewing-form').addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitViewingRequest(e);
            });

            // Comment form
            document.getElementById('comment-form')?.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitComment(e);
            });

            // Keyboard navigation for images
            document.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft') this.prevImage();
                if (e.key === 'ArrowRight') this.nextImage();
            });
        }

        setupStarRating() {
            const stars = document.querySelectorAll('.star');
            stars.forEach((star, index) => {
                star.addEventListener('click', () => {
                    this.rating = index + 1;
                    this.updateStarDisplay();
                });

                star.addEventListener('mouseover', () => {
                    this.highlightStars(index + 1);
                });
            });

            document.querySelector('.star-rating')?.addEventListener('mouseleave', () => {
                this.updateStarDisplay();
            });
        }

        highlightStars(count) {
            const stars = document.querySelectorAll('.star');
            stars.forEach((star, index) => {
                star.classList.toggle('highlighted', index < count);
            });
        }

        updateStarDisplay() {
            const stars = document.querySelectorAll('.star');
            stars.forEach((star, index) => {
                star.classList.toggle('selected', index < this.rating);
                star.classList.remove('highlighted');
            });
        }

        async submitViewingRequest(event) {
            const formData = new FormData(event.target);
            const messageDiv = document.getElementById('viewing-message');
            
            try {
                const response = await fetch('../php/schedule-viewing.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.text();
                messageDiv.innerHTML = `<div class="success-message">${result}</div>`;
                event.target.reset();
                
                setTimeout(() => {
                    closeModal('schedule-modal');
                }, 2000);
            } catch (error) {
                messageDiv.innerHTML = '<div class="error-message">Failed to schedule viewing. Please try again.</div>';
            }
        }

        async submitComment(event) {
            const formData = new FormData(event.target);
            formData.append('rating', this.rating);
            const messageDiv = document.getElementById('comment-message');
            
            try {
                const response = await fetch('../php/submit-comment.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.text();
                messageDiv.innerHTML = `<div class="success-message">${result}</div>`;
                event.target.reset();
                this.rating = 0;
                this.updateStarDisplay();
                
                // Reload comments
                await this.loadComments();
            } catch (error) {
                messageDiv.innerHTML = '<div class="error-message">Failed to submit review. Please try again.</div>';
            }
        }
    }

    // Global functions for backward compatibility
    let propertyManager;

    function nextImage() {
        propertyManager?.nextImage();
    }

    function prevImage() {
        propertyManager?.prevImage();
    }

    function openRentModal() {
        document.getElementById('rent-modal').style.display = 'flex';
    }

    function openScheduleModal() {
        document.getElementById('schedule-modal').style.display = 'flex';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    function shareProperty() {
        if (navigator.share) {
            navigator.share({
                title: 'Check out this property',
                text: 'I found this amazing property on HBProperty',
                url: window.location.href
            });
        } else {
            // Fallback: copy to clipboard
            navigator.clipboard.writeText(window.location.href).then(() => {
                showNotification('Property link copied to clipboard!', 'success');
            });
        }
    }

    function confirmLogout() {
        return confirm('Are you sure you want to logout?');
    }

    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed; top: 100px; right: 20px; z-index: 10000;
            padding: 15px 20px; border-radius: 8px; color: white;
            background: ${type === 'success' ? '#27ae60' : '#3498db'};
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            animation: slideIn 0.5s ease-out;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 4000);
    }

    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', () => {
        propertyManager = new PropertyDetailsManager();
        
        // Close modals when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        });
    });

    // Schedule Viewing Modal Script
    let currentStep = 1;
    const totalSteps = 4;

    function nextStep() {
        if (!validateCurrentStep()) {
            return;
        }

        const activeStep = document.querySelector('.form-step.active');
        const nextStep = activeStep.nextElementSibling;
        if (!nextStep || !nextStep.classList.contains('form-step')) return;

        activeStep.classList.remove('active');
        nextStep.classList.add('active');
        currentStep++;

        updateProgressIndicator();
        scrollToTop();
    }

    function prevStep() {
        const activeStep = document.querySelector('.form-step.active');
        const prevStep = activeStep.previousElementSibling;
        if (!prevStep || !prevStep.classList.contains('form-step')) return;

        activeStep.classList.remove('active');
        prevStep.classList.add('active');
        currentStep--;

        updateProgressIndicator();
        scrollToTop();
    }

    function validateCurrentStep() {
        if (currentStep === 1) {
            const dateInput = document.getElementById('viewing-date');
            if (!dateInput.value) {
                showValidationError('Please select a viewing date.');
                return false;
            }
            
            // Check if selected date is not Sunday
            const selectedDate = new Date(dateInput.value);
            if (selectedDate.getDay() === 0) {
                showValidationError('Sorry, viewings are not available on Sundays. Please select Monday to Saturday.');
                return false;
            }
            
            return true;
        }
        
        if (currentStep === 2) {
            const selectedTime = document.getElementById('selected-time');
            if (!selectedTime.value) {
                showValidationError('Please select a preferred time.');
                return false;
            }
            return true;
        }
        
        if (currentStep === 3) {
            const contactInput = document.getElementById('viewing-contact');
            if (!contactInput.value.trim()) {
                showValidationError('Please enter your contact number.');
                return false;
            }
            
            // Basic phone number validation
            const phoneRegex = /^[\d\s\-\+\(\)]+$/;
            if (!phoneRegex.test(contactInput.value.trim())) {
                showValidationError('Please enter a valid phone number.');
                return false;
            }
            
            return true;
        }
        
        return true;
    }

    function showValidationError(message) {
        const messageDiv = document.getElementById('viewing-message');
        messageDiv.innerHTML = `<div class="error-message">${message}</div>`;
        
        setTimeout(() => {
            messageDiv.innerHTML = '';
        }, 5000);
    }

    function updateProgressIndicator() {
        const steps = document.querySelectorAll('.progress-step');
        const progressFill = document.getElementById('progress-fill');

        steps.forEach((step, index) => {
            step.classList.toggle('active', index + 1 === currentStep);
        });

        const fillWidth = (currentStep / totalSteps) * 100;
        progressFill.style.width = `${fillWidth}%`;
    }

    function scrollToTop() {
        const modalBody = document.querySelector('.schedule-modal-content .modal-body');
        if (modalBody) {
            modalBody.scrollTop = 0;
        }
    }

    function reviewBooking() {
        if (!validateCurrentStep()) {
            return;
        }

        const date = document.getElementById('viewing-date').value;
        const time = document.getElementById('selected-time').value;
        const contact = document.getElementById('viewing-contact').value;

        // Update summary display
        const dateObj = new Date(date);
        const timeObj = new Date(`1970-01-01T${time}:00`);
        
        document.getElementById('summary-date').textContent = dateObj.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        
        document.getElementById('summary-time').textContent = timeObj.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
        
        document.getElementById('summary-contact').textContent = contact;

        nextStep();
    }

    // Time slot selection
    function setupTimeSlots() {
        const timeSlots = document.querySelectorAll('.time-slot');
        const selectedTimeInput = document.getElementById('selected-time');

        timeSlots.forEach(slot => {
            slot.addEventListener('click', function() {
                if (this.classList.contains('unavailable')) {
                    return;
                }

                // Remove previous selection
                timeSlots.forEach(s => s.classList.remove('selected'));
                
                // Add selection to clicked slot
                this.classList.add('selected');
                
                // Update hidden input
                selectedTimeInput.value = this.dataset.time;
                
                // Add haptic feedback (if supported)
                if (navigator.vibrate) {
                    navigator.vibrate(50);
                }
            });
        });
    }

    // Date input handling
    function setupDateHandling() {
        const dateInput = document.getElementById('viewing-date');
        
        dateInput.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const dayOfWeek = selectedDate.getDay();
            
            // Update time slots availability based on day
            updateTimeSlotAvailability(dayOfWeek);
            
            // Clear previously selected time if date changes
            document.getElementById('selected-time').value = '';
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.classList.remove('selected');
            });
        });
    }

    function updateTimeSlotAvailability(dayOfWeek) {
        const timeSlots = document.querySelectorAll('.time-slot');
        
        timeSlots.forEach(slot => {
            const time = slot.dataset.time;
            const hour = parseInt(time.split(':')[0]);
            
            // Make Sunday unavailable
            if (dayOfWeek === 0) {
                slot.classList.add('unavailable');
                slot.querySelector('.availability').textContent = 'Unavailable';
                return;
            }
            
            // Saturday has limited hours (9 AM - 2 PM only)
            if (dayOfWeek === 6 && hour >= 14) {
                slot.classList.add('unavailable');
                slot.querySelector('.availability').textContent = 'Unavailable';
            } else {
                slot.classList.remove('unavailable');
                slot.querySelector('.availability').textContent = 'Available';
            }
        });
    }

    // Enhanced form submission
    function setupViewingFormSubmission() {
        const viewingForm = document.getElementById('viewing-form');
        
        viewingForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('.btn-confirm');
            const originalText = submitBtn.innerHTML;
            const messageDiv = document.getElementById('viewing-message');
            
            try {
                // Show loading state
                submitBtn.innerHTML = `
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 8px; animation: spin 1s linear infinite;">
                        <path d="M12 4V2A10 10 0 0 0 2 12h2a8 8 0 0 1 8-8z"/>
                    </svg>
                    Processing...
                `;
                submitBtn.disabled = true;
                
                const formData = new FormData(this);
                
                const response = await fetch('../php/schedule-viewing.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.text();
                
                if (response.ok) {
                    messageDiv.innerHTML = `
                        <div class="success-message">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 8px;">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                            </svg>
                            ${result}
                        </div>
                    `;
                    
                    // Reset form after success
                    this.reset();
                    currentStep = 1;
                    updateProgressIndicator();
                    
                    // Close modal after delay
                    setTimeout(() => {
                        closeModal('schedule-modal');
                        messageDiv.innerHTML = '';
                    }, 3000);
                    
                } else {
                    throw new Error(result);
                }
                
            } catch (error) {
                console.error('Error:', error);
                messageDiv.innerHTML = `
                    <div class="error-message">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 8px;">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                        Failed to schedule viewing. Please try again.
                    </div>
                `;
            } finally {
                // Restore button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
    }

    // Reset modal when opened
    function resetScheduleModal() {
        currentStep = 1;
        document.querySelectorAll('.form-step').forEach(step => step.classList.remove('active'));
        document.getElementById('step-1').classList.add('active');
        
        // Clear form
        document.getElementById('viewing-form').reset();
        document.getElementById('selected-time').value = '';
        document.querySelectorAll('.time-slot').forEach(slot => {
            slot.classList.remove('selected');
        });
        
        // Reset progress
        updateProgressIndicator();
        
        // Clear messages
        document.getElementById('viewing-message').innerHTML = '';
        
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('viewing-date').min = today;
    }

    // Override the global openScheduleModal function
    function openScheduleModal() {
        resetScheduleModal();
        document.getElementById('schedule-modal').style.display = 'flex';
    }

    // Initialize schedule viewing functionality when DOM is loaded
    document.addEventListener('DOMContentLoaded', () => {
        setupTimeSlots();
        setupDateHandling();
        setupViewingFormSubmission();
        
        // Add CSS for spin animation
        if (!document.querySelector('#spin-animation-style')) {
            const style = document.createElement('style');
            style.id = 'spin-animation-style';
            style.textContent = `
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }
    });
</script>
</body>
</html>
