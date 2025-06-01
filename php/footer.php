<footer>
    <div class="container">
        <?php if ($userRole === 'staff'): ?>
            <!-- Simple footer for staff -->
            <div class="footer-bottom">
                <p>&copy; 2025 HBProperty | All Rights Reserved</p>
            </div>
        <?php else: ?>
            <!-- Full footer for other users -->
            <div class="footer-content">
                <div class="footer-section">
                    <h3>HBProperty</h3>
                    <p>Your trusted partner in finding the perfect rental property.</p>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook">Facebook</a>
                        <a href="#" aria-label="Instagram">Instagram</a>
                        <a href="#" aria-label="Twitter">Twitter</a>
                    </div>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="contact.php">Contact</a></li>
                        <li><a href="properties.php">All Properties</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 HBProperty | All Rights Reserved</p>
            </div>
        <?php endif; ?>
    </div>
</footer>

<!-- Notification Container -->
<div id="notification-container"></div>

<?php if (isset($additionalFooterScripts)) echo $additionalFooterScripts; ?>

<script>
// Global logout confirmation function
function confirmLogout() {
    return confirm('Are you sure you want to logout?');
}

// Global notification system
function showNotification(message, type = 'info') {
    const container = document.getElementById('notification-container');
    if (!container) return;
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    container.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => {
            if (container.contains(notification)) {
                container.removeChild(notification);
            }
        }, 300);
    }, 4000);
}
</script>
</body>
</html>