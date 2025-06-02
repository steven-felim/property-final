<?php
    session_start();

    if (!isset($_SESSION['user_email'])) {
        header("Location: index.php");
        exit();
    }

    $userEmail = $_SESSION['user_email'];
    $userRole = $_SESSION['user_role'];
    require_once './db_connection.php';

    if (isset($_GET['ajax'])) {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $type = $_GET['type'] ?? 'all';
        $sort = $_GET['sort'] ?? 'newest';
        $search = trim($_GET['search'] ?? '');
        $minPrice = (int)($_GET['min_price'] ?? 0);
        $maxPrice = (int)($_GET['max_price'] ?? 999999);

        $limit = 9;
        $offset = ($page - 1) * $limit;

        $myOwnerNoAjax = null;
        $isOwner = false;

        if ($userRole === 'property_owner') {
            $stmt = $conn->prepare("SELECT ownerNo FROM privateowner WHERE eMail = ?");
            $stmt->bind_param("s", $userEmail);
            $stmt->execute();
            $stmt->bind_result($myOwnerNoAjax);
            $stmt->fetch();
            $stmt->close();
            $isOwner = true;
        }

        $whereConditions = ["rent BETWEEN ? AND ?"];
        $params = [$minPrice, $maxPrice];
        $types = "ii";

        if ($type !== 'all') {
            $whereConditions[] = "pType = ?";
            $params[] = $type;
            $types .= "s";
        }

        if (!empty($search)) {
            $whereConditions[] = "(street LIKE ? OR city LIKE ? OR pType LIKE ?)";
            $searchParam = '%' . $search . '%';
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
            $types .= "sss";
        }

        if ($isOwner) {
            $whereConditions[] = "ownerNo = ?";
            $params[] = $myOwnerNoAjax;
            $types .= "s";
        } else {
            // Not an owner: show only properties not yet rented
            $whereConditions[] = "propertyNo NOT IN (SELECT propertyNo FROM rent)";
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Sorting logic
        $orderBy = "propertyNo DESC";
        switch ($sort) {
            case 'price_low': $orderBy = "rent ASC"; break;
            case 'price_high': $orderBy = "rent DESC"; break;
            case 'oldest': $orderBy = "propertyNo ASC"; break;
            default: $orderBy = "propertyNo DESC";
        }

        // Final SQL
        $sql = "SELECT propertyNo, pType, street, city, rooms, rent, ownerNo
                FROM propertyforrent
                WHERE $whereClause
                ORDER BY $orderBy
                LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $properties = [];
        while ($row = $result->fetch_assoc()) {
            $properties[] = $row;
        }

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM propertyforrent WHERE $whereClause";
        $countStmt = $conn->prepare($countSql);
        $countParams = array_slice($params, 0, -2); // exclude limit and offset
        $countTypes = substr($types, 0, -2);
        $countStmt->bind_param($countTypes, ...$countParams);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $totalCount = $countResult->fetch_assoc()['total'];

        header('Content-Type: application/json');
        echo json_encode([
            'properties' => $properties,
            'totalCount' => $totalCount,
            'currentPage' => $page,
            'totalPages' => ceil($totalCount / $limit),
            'hasMore' => ($page * $limit) < $totalCount,
            'myOwnerNo' => $myOwnerNoAjax
        ]);
        exit;
    }

    // If property_owner, get their ownerNo
    $myOwnerNo = null;
    if ($userRole === 'property_owner') {
        $stmt = $conn->prepare("SELECT ownerNo FROM privateowner WHERE eMail = ?");
        $stmt->bind_param("s", $userEmail);
        $stmt->execute();
        $stmt->bind_result($myOwnerNo);
        $stmt->fetch();
        $stmt->close();
    }

    // Fetch all properties with their images and ownerNo - Show ALL properties for everyone
    $properties = [];
    $sql = "SELECT p.propertyNo, p.street, p.city, p.rent, p.pType, p.ownerNo,
            (SELECT pi.image FROM propertyimage pi WHERE pi.propertyNo = p.propertyNo LIMIT 1) AS image
            FROM propertyforrent p
            ORDER BY p.propertyNo DESC";
    $result = $conn->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $properties[] = $row;
        }
    }

    // Set page variables for header
    $pageTitle = "Properties - HBProperty";
    $pageDescription = "Browse all available rental properties. Filter by price, type, location and more.";
    $showSearchForm = false;

    // Include header
    include 'header.php';
?>

<main class="properties-page">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>All Properties</h1>
            <p>Discover your perfect home from our extensive collection</p>
        </div>

        <!-- Advanced Filters -->
        <div class="filters-section">
            <div class="filters-header">
                <h3>Filter Properties</h3>
            </div>
            
            <div id="filters-panel" class="filters-panel">
                <div class="filter-group">
                    <label for="search-input">Search Location</label>
                    <input type="text" id="search-input" placeholder="Enter city or address...">
                </div>
                
                <div class="filter-group">
                    <label for="type-filter">Property Type</label>
                    <select id="type-filter">
                        <option value="all">All Types</option>
                        <option value="House">House</option>
                        <option value="Apartment">Apartment</option>
                        <option value="Condo">Condo</option>
                        <option value="Studio">Studio</option>
                        <option value="Villa">Villa</option>
                        <option value="Townhouse">Townhouse</option>
                        <option value="Kos">Kos</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Price Range</label>
                    <div class="price-range">
                        <input type="number" id="min-price" placeholder="Min" min="0">
                        <span>to</span>
                        <input type="number" id="max-price" placeholder="Max" min="0">
                    </div>
                </div>
                
                <div class="filter-group">
                    <label for="sort-select">Sort By</label>
                    <select id="sort-select">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="price_low">Price: Low to High</option>
                        <option value="price_high">Price: High to Low</option>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button id="apply-filters" class="btn-primary">Apply Filters</button>
                    <button id="clear-filters" class="btn-outline">Clear All</button>
                </div>
            </div>
        </div>

        <!-- Results Summary -->
        <div class="results-summary">
            <div class="results-count">
                <span id="results-text">Loading properties...</span>
            </div>
            <div class="view-options">
                <button class="view-btn active" data-view="grid" title="Grid View">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M4 6h6v6H4V6zm10 0h6v6h-6V6zM4 16h6v6H4v-6zm10 0h6v6h-6v-6z"/>
                    </svg>
                </button>
                <button class="view-btn" data-view="list" title="List View">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h16v2H4v-2z"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Add Property Button (for property owners) -->
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'property_owner'): ?>
            <div class="add-property-section">
                <a href="add-property.php" class="btn-add-property">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 8px;">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                    </svg>
                    Add New Property
                </a>
            </div>
        <?php endif; ?>

        <!-- Properties Grid -->
        <div id="properties-container" class="properties-container">
            <div class="loading-properties">
                <div class="loading-spinner"></div>
                <p>Loading amazing properties...</p>
            </div>
        </div>

        <!-- Pagination -->
        <div id="pagination" class="pagination" style="display: none;">
            <button id="prev-page" class="pagination-btn" disabled>Previous</button>
            <div id="page-numbers" class="page-numbers"></div>
            <button id="next-page" class="pagination-btn">Next</button>
        </div>
    </div>
</main>

<?php
// Set additional footer scripts for properties page
$additionalFooterScripts = '
<script>
class PropertiesManager {
    constructor() {
        this.currentPage = 1;
        this.totalPages = 1;
        this.currentView = "grid";
        this.filters = {
            type: "all",
            search: "",
            minPrice: "",
            maxPrice: "",
            sort: "newest"
        };
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadProperties();
    }

    setupEventListeners() {
        // Apply filters
        document.getElementById("apply-filters").addEventListener("click", () => {
            this.updateFilters();
            this.currentPage = 1;
            this.loadProperties();
        });

        // Clear filters
        document.getElementById("clear-filters").addEventListener("click", () => {
            this.clearFilters();
        });

        // View options
        document.querySelectorAll(".view-btn").forEach(btn => {
            btn.addEventListener("click", (e) => {
                document.querySelectorAll(".view-btn").forEach(b => b.classList.remove("active"));
                e.target.closest(".view-btn").classList.add("active");
                this.currentView = e.target.closest(".view-btn").dataset.view;
                this.updateViewMode();
            });
        });

        // Pagination
        document.getElementById("prev-page").addEventListener("click", () => {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.loadProperties();
            }
        });

        document.getElementById("next-page").addEventListener("click", () => {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.loadProperties();
            }
        });

        // Search on enter
        document.getElementById("search-input").addEventListener("keypress", (e) => {
            if (e.key === "Enter") {
                this.updateFilters();
                this.currentPage = 1;
                this.loadProperties();
            }
        });
    }

    updateFilters() {
        this.filters = {
            type: document.getElementById("type-filter").value,
            search: document.getElementById("search-input").value.trim(),
            minPrice: document.getElementById("min-price").value,
            maxPrice: document.getElementById("max-price").value,
            sort: document.getElementById("sort-select").value
        };
    }

    clearFilters() {
        document.getElementById("type-filter").value = "all";
        document.getElementById("search-input").value = "";
        document.getElementById("min-price").value = "";
        document.getElementById("max-price").value = "";
        document.getElementById("sort-select").value = "newest";
        
        this.filters = {
            type: "all",
            search: "",
            minPrice: "",
            maxPrice: "",
            sort: "newest"
        };
        
        this.currentPage = 1;
        this.loadProperties();
    }

    async loadProperties() {
        const container = document.getElementById("properties-container");
        container.innerHTML = "<div class=\\"loading-properties\\"><div class=\\"loading-spinner\\"></div><p>Loading properties...</p></div>";

        try {
            const params = new URLSearchParams({
                ajax: "1",
                page: this.currentPage,
                type: this.filters.type,
                sort: this.filters.sort,
                search: this.filters.search,
                min_price: this.filters.minPrice || "0",
                max_price: this.filters.maxPrice || "999999"
            });

            const response = await fetch(`properties.php?${params}`);
            const data = await response.json();

            // Store myOwnerNo for button control
            this.myOwnerNo = data.myOwnerNo;

            this.displayProperties(data.properties);
            this.updatePagination(data);
            this.updateResultsSummary(data);

        } catch (error) {
            console.error("Error loading properties:", error);
            container.innerHTML = "<div class=\\"error-message\\">Failed to load properties. Please try again.</div>";
        }
    }

    displayProperties(properties) {
        const container = document.getElementById("properties-container");
        
        if (properties.length === 0) {
            container.innerHTML = "<div class=\\"no-properties\\">No properties found matching your criteria. Try adjusting your filters.</div>";
            return;
        }

        container.innerHTML = "";
        container.className = `properties-container ${this.currentView}-view`;

        properties.forEach((property, index) => {
            const propertyCard = this.createPropertyCard(property, index);
            container.appendChild(propertyCard);
        });

        this.animateCards();
    }

    createPropertyCard(property, index) {
        const card = document.createElement("div");
        card.className = "property-card";
        card.style.animationDelay = `${index * 0.1}s`;
        
        // Only show edit/delete buttons for property owners and only for their own properties
        const userRole = "' . $userRole . '";
        const isOwner = userRole === "property_owner" && this.myOwnerNo && property.ownerNo === this.myOwnerNo;
        
        let actionButtons = `<a href="property.php?id=${property.propertyNo}" class="btn-view-details">View Details</a>`;
        
        if (isOwner) {
            actionButtons += `
                <a href="edit-property.php?propertyNo=${property.propertyNo}" class="btn-edit-property">Edit</a>
                <form method="post" action="delete-property.php" style="display: inline;">
                    <input type="hidden" name="propertyNo" value="${property.propertyNo}">
                    <button type="submit" class="btn-delete-property" onclick="return confirm(\'Are you sure you want to delete this property?\')">Delete</button>
                </form>
            `;
        }
        
        card.innerHTML = `
            <div class="property-image">
                <img src="../img/no-image-available.png" alt="${property.pType} in ${property.city}" loading="lazy" onerror="this.src=\'../img/no-image-available.png\'">
                <div class="property-badge">${property.pType}</div>
            </div>
            <div class="property-content">
                <h3>${property.pType} in ${property.city}</h3>
                <p class="property-location">📍 ${property.street}, ${property.city}</p>
                <p class="property-rooms">🏠 ${property.rooms} rooms</p>
                <p class="property-price">$${parseInt(property.rent).toLocaleString()}/month</p>
                <div class="property-actions">
                    ${actionButtons}
                </div>
            </div>
        `;

        // Try to load actual property image
        this.loadPropertyImage(card, property.propertyNo);

        return card;
    }

    async loadPropertyImage(card, propertyNo) {
        try {
            const response = await fetch(`../php/get_image.php?property_id=${propertyNo}`);
            const images = await response.json();
            
            if (images && images.length > 0) {
                const img = card.querySelector("img");
                img.src = `../img/${images[0]}`;
            }
        } catch (error) {
            // Keep default image
        }
    }

    updatePagination(data) {
        const pagination = document.getElementById("pagination");
        const prevBtn = document.getElementById("prev-page");
        const nextBtn = document.getElementById("next-page");
        const pageNumbers = document.getElementById("page-numbers");

        if (data.totalPages <= 1) {
            pagination.style.display = "none";
            return;
        }

        pagination.style.display = "flex";
        this.totalPages = data.totalPages;
        this.currentPage = data.currentPage;

        // Update buttons
        prevBtn.disabled = this.currentPage <= 1;
        nextBtn.disabled = this.currentPage >= this.totalPages;

        // Update page numbers
        pageNumbers.innerHTML = "";
        const maxVisible = 5;
        let startPage = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
        let endPage = Math.min(this.totalPages, startPage + maxVisible - 1);

        if (endPage - startPage + 1 < maxVisible) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }

        for (let i = startPage; i <= endPage; i++) {
            const pageBtn = document.createElement("button");
            pageBtn.textContent = i;
            pageBtn.className = `page-btn ${i === this.currentPage ? "active" : ""}`;
            pageBtn.addEventListener("click", () => {
                this.currentPage = i;
                this.loadProperties();
            });
            pageNumbers.appendChild(pageBtn);
        }
    }

    updateResultsSummary(data) {
        const resultsText = document.getElementById("results-text");
        const start = (data.currentPage - 1) * 9 + 1;
        const end = Math.min(data.currentPage * 9, data.totalCount);
        
        resultsText.textContent = `Showing ${start}-${end} of ${data.totalCount} properties`;
    }

    updateViewMode() {
        const container = document.getElementById("properties-container");
        container.className = `properties-container ${this.currentView}-view`;
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
}

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
    new PropertiesManager();
});
</script>

<style>
/* Properties Page Specific Styles */
.properties-page {
    padding-top: 100px;
    min-height: 100vh;
}

.page-header {
    text-align: center;
    margin-bottom: 40px;
}

.page-header h1 {
    font-size: 3rem;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.page-header p {
    font-size: 1.2rem;
    color: var(--text-secondary);
}

/* Filters Section */
.filters-section {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-light);
    margin-bottom: 30px;
    overflow: hidden;
}

.filters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 30px;
    background: var(--light-bg);
    border-bottom: 1px solid var(--border-color);
}

.filters-header h3 {
    margin: 0;
    color: var(--primary-color);
}

.filters-panel {
    display: grid;
    padding: 30px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--primary-color);
}

.filter-group input,
.filter-group select {
    padding: 12px 15px;
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: 1rem;
    transition: var(--transition);
}

.filter-group input:focus,
.filter-group select:focus {
    outline: none;
    border-color: var(--secondary-color);
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.price-range {
    display: flex;
    align-items: center;
    gap: 10px;
    grid-column: span 2;
}

.price-range input {
    flex: 1;
    min-width: 100px;
}

.price-range span {
    color: var(--text-secondary);
    font-weight: 500;
}

.filter-actions {
    display: flex;
    gap: 15px;
    grid-column: 1 / -1;
    justify-content: center;
    margin-top: 20px;
}

/* Results Summary */
.results-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 20px;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-light);
}

.results-count {
    font-weight: 600;
    color: var(--primary-color);
}

.view-options {
    display: flex;
    gap: 10px;
}

.view-btn {
    padding: 10px;
    border: 2px solid var(--border-color);
    background: white;
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: var(--transition);
}

.view-btn.active,
.view-btn:hover {
    border-color: var(--secondary-color);
    background: var(--secondary-color);
    color: white;
}

/* Add Property Section */
.add-property-section {
    text-align: center;
    margin-bottom: 30px;
}

/* Properties Container */
.properties-container.grid-view {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 30px;
}

.properties-container.list-view {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.properties-container.list-view .property-card {
    display: flex;
    align-items: center;
    padding: 20px;
}

.properties-container.list-view .property-image {
    width: 200px;
    height: 150px;
    flex-shrink: 0;
    margin-right: 20px;
}

.properties-container.list-view .property-content {
    flex: 1;
    padding: 0;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 40px;
}

.pagination-btn,
.page-btn {
    padding: 10px 15px;
    border: 2px solid var(--border-color);
    background: white;
    color: var(--primary-color);
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: var(--transition);
    font-weight: 500;
}

.pagination-btn:hover:not(:disabled),
.page-btn:hover {
    border-color: var(--secondary-color);
    background: var(--secondary-color);
    color: white;
}

.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.page-btn.active {
    background: var(--secondary-color);
    border-color: var(--secondary-color);
    color: white;
}

.page-numbers {
    display: flex;
    gap: 5px;
}
</style>
';
include 'footer.php';
$conn->close();
?>
