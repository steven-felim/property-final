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
                    <a href="property.php?id=' . htmlspecialchars($row['propertyNo']) . '">
                        <div class="result-title">' . htmlspecialchars($row['street']) . ', ' . htmlspecialchars($row['city']) . '</div>
                        <div class="result-type">' . htmlspecialchars($row['pType']) . '</div>
                        <div class="result-rent">$' . number_format($row['rent'], 0, ',', '.') . '/month</div>
                    </a>
                  </div>';
        }
        echo '</div>';
    } else {
        echo '<div class="search-result-empty">No properties found matching your search.</div>';
    }

    $stmt->close();
    $conn->close();
    exit;
}

// Set page variables for header
$pageTitle = "HBProperty - Find Your Perfect Home";
$pageDescription = "Find your perfect rental property with HBProperty. Browse thousands of listings and connect with property owners.";
$showSearchForm = true;

// Include header
include 'header.php';
?>

<main class="homepage">
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Find Your Perfect Home</h1>
            <p>Discover amazing rental properties in prime locations. Your dream home is just a search away.</p>
            <div class="hero-stats">
                <div class="stat-item">
                    <span class="stat-number" id="property-count">0</span>
                    <span class="stat-label">Properties</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">50+</span>
                    <span class="stat-label">Locations</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">1000+</span>
                    <span class="stat-label">Happy Clients</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Properties -->
    <section class="properties">
        <div class="container">
            <h2>Featured Properties</h2>
            <div class="filter-controls">
                <button class="filter-btn active" data-filter="all">All Properties</button>
                <button class="filter-btn" data-filter="House">Houses</button>
                <button class="filter-btn" data-filter="Apartment">Apartments</button>
                <button class="filter-btn" data-filter="Condo">Condos</button>
                <button class="filter-btn" data-filter="Kos">Kos</button>
            </div>
            <div id="property-list" class="property-list">
                <div class="loading-properties">
                    <div class="loading-spinner"></div>
                    <p>Loading amazing properties...</p>
                </div>
            </div>
            <div class="load-more-container">
                <button id="load-more-btn" class="btn-load-more" style="display: none;">Load More Properties</button>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <h2>Why Choose HBProperty?</h2>
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">🏠</div>
                    <h3>Quality Properties</h3>
                    <p>Carefully vetted properties from trusted owners and agents.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">⚡</div>
                    <h3>Fast & Easy</h3>
                    <p>Quick search and instant booking with our streamlined process.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🔒</div>
                    <h3>Secure & Safe</h3>
                    <p>All transactions are protected with bank-level security.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">💬</div>
                    <h3>24/7 Support</h3>
                    <p>Our team is always ready to help you find your perfect home.</p>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
// Set additional footer scripts
$additionalFooterScripts = '';

// Include footer
include 'footer.php';
?>

<script>
// Modern property management
class PropertyManager {
    constructor() {
        this.properties = [];
        this.displayedProperties = [];
        this.currentFilter = "all";
        this.propertiesPerPage = 6;
        this.currentPage = 1;
        this.init();
    }

    async init() {
        await this.loadProperties();
        this.setupEventListeners();
        this.updatePropertyCount();
    }

    async loadProperties() {
        try {
            console.log("Starting to fetch properties...");
            const response = await fetch("fetch-properties.php");
            console.log("Fetch response status:", response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const responseText = await response.text();
            console.log("Raw response:", responseText);
            
            // Try to parse JSON
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error("JSON parse error:", parseError);
                console.error("Response text:", responseText);
                throw new Error("Invalid JSON response");
            }
            
            this.properties = data;
            console.log("Loaded properties:", this.properties);
            
            if (Array.isArray(this.properties) && this.properties.length > 0) {
                this.displayProperties();
            } else {
                console.log("No properties found or empty array");
                this.showError("No properties available at the moment.");
            }
        } catch (error) {
            console.error("Error loading properties:", error);
            this.showError(`Failed to load properties: ${error.message}`);
        }
    }

    displayProperties() {
        const propertyList = document.getElementById("property-list");
        const filteredProperties = this.getFilteredProperties();
        const startIndex = (this.currentPage - 1) * this.propertiesPerPage;
        const endIndex = startIndex + this.propertiesPerPage;
        const propertiesToShow = filteredProperties.slice(0, endIndex);

        propertyList.innerHTML = "";

        if (propertiesToShow.length === 0) {
            propertyList.innerHTML = '<div class="no-properties">No properties found matching your criteria.</div>';
            return;
        }

        propertiesToShow.forEach((property, index) => {
            const propertyCard = this.createPropertyCard(property, index);
            propertyList.appendChild(propertyCard);
        });

        // Show/hide load more button
        const loadMoreBtn = document.getElementById("load-more-btn");
        if (filteredProperties.length > endIndex) {
            loadMoreBtn.style.display = "block";
        } else {
            loadMoreBtn.style.display = "none";
        }

        // Animate cards
        this.animateCards();
    }

    createPropertyCard(property, index) {
        const card = document.createElement("div");
        card.classList.add("property-card");
        card.style.animationDelay = `${index * 0.1}s`;
        
        card.innerHTML = `
            <div class="property-image">
                <img src="${property.image_url}" alt="${property.title}" loading="lazy">
                <div class="property-badge">Featured</div>
            </div>
            <div class="property-content">
                <h3>${property.title}</h3>
                <p class="property-price">$${property.price}/month</p>
                <a href="property.php?id=${property.propertyNo}" class="btn-view-details">
                    View Details
                </a>
            </div>
        `;

        return card;
    }

    getFilteredProperties() {
        if (this.currentFilter === "all") {
            return this.properties;
        }
        return this.properties.filter(property => 
            property.pType && property.pType.toLowerCase().includes(this.currentFilter.toLowerCase())
        );
    }

    setupEventListeners() {
        // Filter buttons
        document.querySelectorAll(".filter-btn").forEach(btn => {
            btn.addEventListener("click", (e) => {
                document.querySelectorAll(".filter-btn").forEach(b => b.classList.remove("active"));
                e.target.classList.add("active");
                this.currentFilter = e.target.dataset.filter;
                this.currentPage = 1;
                this.displayProperties();
            });
        });

        // Load more button
        document.getElementById("load-more-btn").addEventListener("click", () => {
            this.currentPage++;
            this.displayProperties();
        });
    }

    animateCards() {
        const cards = document.querySelectorAll(".property-card");
        cards.forEach((card, index) => {
            card.style.opacity = "0";
            card.style.transform = "translateY(20px)";
            
            setTimeout(() => {
                card.style.transition = "opacity 0.5s ease, transform 0.5s ease";
                card.style.opacity = "1";
                card.style.transform = "translateY(0)";
            }, index * 100);
        });
    }

    updatePropertyCount() {
        const countElement = document.getElementById("property-count");
        if (countElement) {
            this.animateNumber(countElement, this.properties.length);
        }
    }

    animateNumber(element, target) {
        let current = 0;
        const increment = target / 50;
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current);
        }, 30);
    }

    showError(message) {
        const propertyList = document.getElementById("property-list");
        propertyList.innerHTML = `<div class="error-message">${message}</div>`;
    }
}

// Enhanced search functionality
let searchTimeout;
function searchProperty() {
    clearTimeout(searchTimeout);
    const keyword = document.getElementById("searchInput").value;
    
    if (keyword.trim() === "") {
        document.getElementById("searchResults").innerHTML = "";
        return;
    }

    // Add loading state
    document.getElementById("searchResults").innerHTML = '<div class="search-loading">Searching...</div>';

    searchTimeout = setTimeout(() => {
        fetch(`homepage.php?search_query=${encodeURIComponent(keyword)}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById("searchResults").innerHTML = data;
            })
            .catch(error => {
                console.error("Search error:", error);
                document.getElementById("searchResults").innerHTML = '<div class="search-error">Search failed. Please try again.</div>';
            });
    }, 300);
}

// Newsletter subscription
function subscribeNewsletter(event) {
    event.preventDefault();
    const email = event.target.querySelector('input[type="email"]').value;
    showNotification("Thank you for subscribing! We will keep you updated.", "success");
    event.target.reset();
}

// Show notification function
function showNotification(message, type) {
    if (!type) type = 'info';
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
document.addEventListener("DOMContentLoaded", () => {
    new PropertyManager();
    
    // Close search results when clicking outside
    document.addEventListener("click", (e) => {
        if (!e.target.closest(".search-form")) {
            document.getElementById("searchResults").innerHTML = "";
        }
    });
});

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener("click", function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute("href"));
        if (target) {
            target.scrollIntoView({
                behavior: "smooth",
                block: "start"
            });
        }
    });
});
</script>
