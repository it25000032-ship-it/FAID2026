
<?php
    require_once __DIR__ . "/FaidProject/admin_check.php";
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>FAID | Admin Dashboard</title>
        <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="CSS/admin.css?v=20261116-cachefix-1025">
        <link rel="stylesheet" href="CSS/reports.css?v=20261116-cachefix-1025">
    </head>
    <body>
         <header class="admin-header">
                <div class="admin-brand">
                <div class="logo">
                    <a href="home.html" aria-label="FAID homepage"><img src="Img/FaidLogo.png" alt="FAID Logo"></a>
                 </div>
                 <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-controls="adminSidebar" aria-expanded="true" aria-label="Collapse navigation">
                    <span aria-hidden="true">☰</span>
                 </button>
                </div>
                <div class="admin-info">
                    <div class="notification-menu">
                        <button class="notification-toggle" id="notificationButton" type="button" aria-expanded="false" aria-controls="notificationPanel" aria-label="Notifications">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9m-8 12h4"></path></svg>
                            <span class="notification-count" id="notificationCount" hidden>0</span>
                        </button>
                        <div class="notification-panel" id="notificationPanel" hidden>
                            <div class="dropdown-heading"><div><strong>Order activity</strong><span id="notificationSummary">Loading updates...</span></div><span class="dropdown-live-dot" aria-hidden="true"></span></div>
                            <div class="notification-list" id="notificationList"><p class="empty-state">Loading recent activity...</p></div>
                            <a class="dropdown-footer-link" href="#orders">View all orders <span aria-hidden="true">→</span></a>
                        </div>
                    </div>
                    <details class="profile-menu" id="profileMenu">
                        <summary aria-label="Open administrator menu">
                            <span class="admin-avatar" aria-hidden="true"><?php echo htmlspecialchars(substr($_SESSION["admin_name"], 0, 1)); ?></span>
                            <span class="profile-menu-copy"><span>Signed in as</span><strong><?php echo htmlspecialchars($_SESSION["admin_name"]); ?></strong></span>
                            <span class="profile-chevron" aria-hidden="true">⌄</span>
                        </summary>
                        <div class="profile-dropdown">
                            <p>Administrator</p>
                            <button class="profile-action" type="button" id="changePasswordButton">Change password</button>
                            <form action="FaidProject/logout.php" method="POST" class="logout-form">
                                <?php echo csrf_field(); ?>
                                <button class="profile-action profile-logout" type="submit">Sign out</button>
                            </form>
                        </div>
                    </details>
                </div>
          </header>
          <div class="admin-layout">
         <aside class="sidebar" id="adminSidebar">
            <h3>ADMIN PANEL</h3>
            <nav>
                <a href="#dashboard" class="active" aria-label="Dashboard" title="Dashboard">
                    <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="8" height="8" rx="2"></rect><rect x="13" y="3" width="8" height="5" rx="2"></rect><rect x="13" y="10" width="8" height="11" rx="2"></rect><rect x="3" y="13" width="8" height="8" rx="2"></rect></svg></span><span class="nav-label">Dashboard</span>
                </a>
                <a href="#companies" aria-label="Companies" title="Companies">
                    <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 10h1m4 0h1m-6 4h1m4 0h1m-6 7v-4h4v4"></path></svg></span><span class="nav-label">Companies</span>
                </a>
                <a href="#materials" aria-label="Offers" title="Offers">
                    <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5Zm-9 9 9 5 9-5M3 16l9 5 9-5"></path></svg></span><span class="nav-label">Offers</span>
                </a>
                <a href="#orders" aria-label="Orders" title="Orders">
                    <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 5h16v16H4zM8 3v4m8-4v4M4 10h16m-11 4h2m3 0h2m-7 4h2"></path></svg></span><span class="nav-label">Orders</span>
                </a>
                <a href="#search" aria-label="Search" title="Search">
                    <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="10.5" cy="10.5" r="6.5"></circle><path d="m16 16 5 5"></path></svg></span><span class="nav-label">Search</span>
                </a>
                <a href="#reports" aria-label="Reports" title="Reports">
                    <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 20V10m8 10V4m8 16v-7"></path></svg></span><span class="nav-label">Reports</span>
                </a>
            </nav>
         </aside>
    <main class="main-content">
        <form id="adminPasswordForm" class="admin-form password-form"
            action="FaidProject/change_admin_password.php" method="POST">
            <?php echo csrf_field(); ?>
            <h2>Change administrator password</h2>
            <label for="currentPassword">Current password</label>
            <div class="password-field"><input id="currentPassword" type="password" name="current_password" autocomplete="current-password" required><button class="password-toggle" type="button" data-password-toggle aria-label="Show password" aria-pressed="false">Show</button></div>
            <span class="field-error" aria-live="polite"></span>
            <label for="newPassword">New password (at least 8 characters)</label>
            <div class="password-field"><input id="newPassword" type="password" name="new_password" minlength="8" maxlength="1024" autocomplete="new-password" aria-describedby="adminPasswordStrength" required><button class="password-toggle" type="button" data-password-toggle aria-label="Show password" aria-pressed="false">Show</button></div>
            <span class="field-error" aria-live="polite"></span>
            <div class="password-strength" id="adminPasswordStrength" data-password-strength-for="newPassword" aria-live="polite"><span>Password strength</span><div class="strength-meter" aria-hidden="true"><i></i><i></i><i></i><i></i></div><strong>Use at least 8 characters</strong></div>
            <label for="confirmPassword">Confirm new password</label>
            <div class="password-field"><input id="confirmPassword" type="password" name="confirm_password" minlength="8" maxlength="1024" autocomplete="new-password" required><button class="password-toggle" type="button" data-password-toggle aria-label="Show password" aria-pressed="false">Show</button></div>
            <span class="field-error" aria-live="polite"></span>
            <button class="btn" type="submit">Update password</button>
            <button class="btn" type="button" id="cancelPasswordChange">Cancel</button>
        </form>
        <section id="dashboard">
            <div class="page-heading">
                <div><p class="dashboard-eyebrow">FAID MARKETPLACE</p><h1>Platform overview</h1><p class="dashboard-subtitle" id="dashboardDate">A clear view of marketplace activity.</p></div>
                <label class="analytics-range-label" for="analyticsRange">Activity window
                    <select id="analyticsRange">
                        <option value="7">Last 7 days</option>
                        <option value="30" selected>Last 30 days</option>
                        <option value="90">Last 90 days</option>
                        <option value="365">Last 12 months</option>
                        <option value="all">All available data</option>
                    </select>
                </label>
            </div>
            <div class="stats-container">
                <div class="stat-card stat-companies">
                    <span class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 10h1m4 0h1m-6 4h1m4 0h1"></path></svg></span>
                    <h3>Total companies</h3><p id="totalCompanies" data-counter>—</p><span class="stat-footnote">Registered on FAID</span>
                </div>
                <div class="stat-card stat-offers">
                    <span class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5Zm-9 9 9 5 9-5M3 16l9 5 9-5"></path></svg></span>
                    <h3>Published offers</h3><p id="totalMaterials" data-counter>—</p><span class="stat-footnote">Available across categories</span>
                </div>
                <div class="stat-card stat-orders">
                    <span class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 5h16v16H4zM8 3v4m8-4v4M4 10h16"></path></svg></span>
                    <h3>Total orders</h3><p id="totalOrders" data-counter>—</p><span class="stat-footnote">Requests recorded</span>
                </div>
                <div class="stat-card stat-pending">
                    <span class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg></span>
                    <h3>Pending orders</h3><p id="pendingOrders" data-counter>—</p><span class="stat-footnote">Awaiting a status update</span>
                </div>
            </div>
            <div class="overview-panel">
                <div>
                    <h2>Offer categories</h2>
                    <p>Live breakdown based on published offers.</p>
                </div>
                <div id="dashboardOfferChart" class="category-chart" aria-live="polite">
                    <p class="empty-state">Loading offer data...</p>
                </div>
            </div>
            <div class="analytics-grid">
                <article class="analytics-card activity-analytics">
                    <div class="analytics-card-heading"><div><p class="dashboard-eyebrow">ACTIVITY</p><h2>Marketplace momentum</h2><p>Real records grouped by their creation dates.</p></div><span class="chart-date-label" id="activityDateLabel">Last 30 days</span></div>
                    <div class="chart-legend" aria-label="Chart series"><span><i class="legend-company"></i>Companies</span><span><i class="legend-offer"></i>Offers</span><span><i class="legend-order"></i>Orders</span></div>
                    <div class="activity-chart" id="activityChart" role="img" aria-label="Company, offer, and order activity chart"><p class="empty-state">Loading activity...</p></div>
                </article>
                <article class="analytics-card order-analytics">
                    <div class="analytics-card-heading"><div><p class="dashboard-eyebrow">ORDER HEALTH</p><h2>Orders by status</h2><p>Current distribution across all orders.</p></div></div>
                    <div class="order-status-chart">
                        <div class="order-donut" id="orderStatusChart" role="img" aria-label="Order status distribution"><p class="empty-state">Loading orders...</p></div>
                        <div class="order-status-legend" id="orderStatusLegend"></div>
                    </div>
                </article>
            </div>
            <div class="dashboard-bottom-grid">
                <section class="dashboard-panel activity-panel" aria-labelledby="activityTimelineTitle">
                    <div class="analytics-card-heading"><div><p class="dashboard-eyebrow">LATEST EVENTS</p><h2 id="activityTimelineTitle">Recent activity</h2></div></div>
                    <div class="activity-timeline" id="activityTimeline"><p class="empty-state">Loading recent activity...</p></div>
                </section>
                <section class="dashboard-panel recent-offers-panel" aria-labelledby="recentOffersTitle">
                    <div class="analytics-card-heading"><div><p class="dashboard-eyebrow">LATEST LISTINGS</p><h2 id="recentOffersTitle">Recent offers</h2></div><a href="#materials">Manage offers <span aria-hidden="true">→</span></a></div>
                    <div class="recent-offers-list" id="recentOffersList"><p class="empty-state">Loading recent offers...</p></div>
                </section>
            </div>
            <section class="dashboard-panel recent-orders-panel" aria-labelledby="recentOrdersTitle">
                <div class="analytics-card-heading"><div><p class="dashboard-eyebrow">LATEST REQUESTS</p><h2 id="recentOrdersTitle">Recent orders</h2></div><a href="#orders">Manage orders <span aria-hidden="true">→</span></a></div>
                <div class="table-container">
                    <table class="compact-table">
                        <thead><tr><th>Order</th><th>Company</th><th>Offer</th><th>Requested</th><th>Status</th><th></th></tr></thead>
                        <tbody id="recentOrdersTable"><tr><td class="empty-table-state" colspan="6">Loading recent orders...</td></tr></tbody>
                    </table>
                </div>
            </section>
        </section>
                   <section id="search" class="dashboard-section">
            <div class="section-heading">
                <h2>Search & Manage</h2>
            </div>
            <div class="filters">
                <div class="form-group">
                    <label for="searchInput">Keyword</label>
                    <input type="search" id="searchInput"
                        placeholder="Company, offer, order ID...">
                </div>
                <button type="button" class="btn" id="searchBtn">Search</button>
            </div>
        </section>

        <!-- نافذة النتائج (popup) -->
        <div id="searchModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="searchModalTitle">
            <div class="modal-box">
                <div class="modal-header">
                    <h3 id="searchModalTitle">Search Results</h3>
                    <button type="button" class="btn btn-secondary" id="closeSearchModal">Close</button>
                </div>
                <div id="searchResults" class="modal-body"></div>
            </div>
        </div>
        <div id="recordModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="recordModalTitle">
            <div class="modal-box record-modal-box">
                <div class="modal-header">
                    <h3 id="recordModalTitle">Record Details</h3>
                    <button type="button" class="btn btn-secondary" id="closeRecordModal">Close</button>
                </div>
                <div id="recordModalBody" class="modal-body"></div>
            </div>
        </div>
        <div id="statusModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="statusModalTitle">
            <div class="modal-box status-modal-box">
                <div class="modal-header">
                    <h3 id="statusModalTitle">Update order status</h3>
                    <button type="button" class="btn btn-secondary" id="closeStatusModal">Close</button>
                </div>
                <form id="searchOrderStatusForm" class="status-modal-form">
                    <input type="hidden" name="order_id" id="searchOrderId">
                    <label for="searchOrderStatus">Order status</label>
                    <select id="searchOrderStatus" name="status" required>
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                        <option value="Completed">Completed</option>
                    </select>
                    <button type="submit" class="btn">Update status</button>
                </form>
            </div>
        </div>
        <section id="companies" class="dashboard-section">
            <div class="section-heading">
                <h2>Companies Management</h2>
                <button class="btn" type="button"
                    onclick="openCompanyForm()">
                    + Add Company
                </button>
            </div>
            <div id="companyForm" class="admin-form"
                style="display:none;">
                <h3>Add New Company</h3>
                <form id="addCompanyForm" action="FaidProject/addCompany.php" method="POST">
                    <?php echo csrf_field(); ?>
                    <label for="newCompanyName">Company name</label>
                    <input id="newCompanyName" type="text" name="company_name" autocomplete="organization" maxlength="150" required>
                    <label for="newCompanyEmail">Email address</label>
                    <input id="newCompanyEmail" type="email" name="email" autocomplete="email" maxlength="150" required>
                    <label for="newCompanyPhone">Phone number</label>
                    <input id="newCompanyPhone" type="tel" name="phone" autocomplete="tel" maxlength="30">
                    <label for="newCompanyAddress">Address</label>
                    <input id="newCompanyAddress" type="text" name="address" autocomplete="street-address" maxlength="255">
                    <label for="newCompanyPassword">Password</label>
                    <div class="password-field">
                        <input id="newCompanyPassword" type="password" name="password" autocomplete="new-password" minlength="8" maxlength="1024" aria-describedby="companyPasswordStrength" required>
                        <button class="password-toggle" type="button" data-password-toggle aria-label="Show password" aria-pressed="false">Show</button>
                    </div>
                    <div class="password-strength" id="companyPasswordStrength" data-password-strength-for="newCompanyPassword" aria-live="polite"><span>Password strength</span><div class="strength-meter" aria-hidden="true"><i></i><i></i><i></i><i></i></div><strong>Use at least 8 characters</strong></div>
                    <button class="btn" type="submit">
                        Save Company
                    </button>
                    <button class="btn" type="button"
                        onclick="closeCompanyForm()">
                        Cancel
                    </button>
                </form>
            </div>
            <div id="editCompanyForm" class="admin-form"
                style="display:none;">
                <h3>Edit Company</h3>
                <form id="updateCompanyForm" action="FaidProject/edit_company.php" method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="company_id"
                        id="editCompanyId">
                    <label for="editCompanyName">Company name</label>
                    <input type="text" name="company_name"
                        id="editCompanyName"
                        placeholder="Company Name" required>
                    <label for="editCompanyEmail">Email address</label>
                    <input type="email" name="email"
                        id="editCompanyEmail"
                        placeholder="Email" required>
                    <label for="editCompanyPhone">Phone number</label>
                    <input type="text" name="phone"
                        id="editCompanyPhone"
                        placeholder="Phone">
                    <label for="editCompanyAddress">Address</label>
                    <input type="text" name="address"
                        id="editCompanyAddress"
                        placeholder="Address">
                    <label for="editCompanyDescription">Company description</label>
                    <textarea name="description" id="editCompanyDescription"
                        maxlength="500" rows="3"></textarea>
                    <button class="btn" type="submit">
                        Update Company
                    </button>
                    <button class="btn" type="button"
                        onclick="closeEditCompanyForm()">
                        Cancel
                    </button>
                </form>
            </div>
            <div class="table-tools">
                <label>Filter companies<input class="table-filter" type="search" data-table="companies" placeholder="Name, email, phone..."></label>
                <div class="pagination" data-pagination="companies" aria-label="Company table pages"></div>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Company Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="companiesTable"><tr><td class="empty-table-state" colspan="4">Loading companies...</td></tr></tbody>
                </table>
            </div>
        </section>
        <section id="materials" class="dashboard-section">
            <div class="section-heading">
                <h2>Offers Management</h2>
                <button type="button" class="btn"
                    onclick="openMaterialForm()">
                    + Publish Offer
                </button>
            </div>
            <div id="materialForm" class="admin-form"
                style="display:none;">
                <h3>Publish New Offer</h3>
                <form id="addAdminMaterialForm" action="FaidProject/addmaterial.php" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <label for="adminOfferName">Offer name</label>
                    <input id="adminOfferName" type="text" name="material_name" maxlength="150" required>
                    <label for="adminOfferCategory">Category</label>
                    <select id="adminOfferCategory" name="category" required>
                        <option value="">Select category</option>
                        <option value="wood">Wood</option>
                        <option value="metal">Metal</option>
                        <option value="packaging">Packaging</option>
                        <option value="plastic">Plastic</option>
                        <option value="electrical">Electrical</option>
                    </select>
                    <label for="adminOfferDescription">Description</label>
                    <textarea id="adminOfferDescription" name="description" maxlength="2000"></textarea>
                    <label for="adminOfferQuantity">Quantity</label>
                    <input id="adminOfferQuantity" type="number" name="quantity" min="1" step="1" required>
                    <label for="adminOfferPrice">Price</label>
                    <input id="adminOfferPrice" type="number" name="price" min="0" step="0.01" required>
                    <label for="adminMaterialCompany">Company</label>
                    <select name="company_id" id="adminMaterialCompany" required>
                        <option value="">Select a company</option>
                    </select>
                    <label class="file-label" for="adminOfferImage">Offer image (optional)
                        <input id="adminOfferImage" type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                    </label>
                    <button class="btn" type="submit">
                        Publish Offer
                    </button>
                    <button class="btn" type="button"
                        onclick="closeMaterialForm()">
                        Cancel
                    </button>
                </form>
            </div>
            <div id="editMaterialForm" class="admin-form"
                style="display:none;">
                <h3>Edit Offer</h3>
                <form id="updateMaterialForm" action="FaidProject/edit_material.php" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="material_id"
                        id="editMaterialId">
                    <label for="editMaterialName">Offer name</label>
                    <input type="text" name="material_name"
                        id="editMaterialName"
                        placeholder="Offer name" required>
                    <label for="editMaterialCategory">Category</label>
                    <select name="category" id="editMaterialCategory" required>
                        <option value="">Select category</option>
                        <option value="wood">Wood</option>
                        <option value="metal">Metal</option>
                        <option value="packaging">Packaging</option>
                        <option value="plastic">Plastic</option>
                        <option value="electrical">Electrical</option>
                    </select>
                    <label for="editMaterialDescription">Description</label>
                    <textarea name="description" id="editMaterialDescription"></textarea>
                    <label for="editMaterialQuantity">Quantity</label>
                    <input type="number" name="quantity"
                        id="editMaterialQuantity"
                        placeholder="Quantity" min="1" required>
                    <label for="editMaterialPrice">Price</label>
                    <input type="number" name="price"
                        id="editMaterialPrice"
                        placeholder="Price" min="0" step="0.01" required>
                    <label class="file-label" for="editOfferImage">Replace offer image (optional)
                        <input id="editOfferImage" type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="remove_image" value="1">
                        Remove current image
                    </label>
                    <button class="btn" type="submit">
                        Save Offer
                    </button>
                    <button class="btn" type="button"
                        onclick="closeEditMaterialForm()">
                        Cancel
                    </button>
                </form>
            </div>
            <div class="table-tools">
                <label>Filter offers<input class="table-filter" type="search" data-table="materials" placeholder="Offer, company, category..."></label>
                <label>Category<select id="materialCategoryFilter" aria-label="Filter offers by category"><option value="">All categories</option><option value="wood">Wood</option><option value="metal">Iron & metals</option><option value="packaging">Packaging</option><option value="plastic">Plastic</option><option value="electrical">Electrical</option></select></label>
                <label>Availability<select id="materialAvailabilityFilter" aria-label="Filter offers by availability"><option value="">All availability</option><option value="available">Available</option><option value="unavailable">Unavailable</option></select></label>
                <label>Sort by<select id="materialSort" aria-label="Sort offers"><option value="recent">Recently published</option><option value="name">Name A–Z</option><option value="price-low">Price: low to high</option><option value="price-high">Price: high to low</option><option value="quantity-high">Quantity: high to low</option></select></label>
                <div class="pagination" data-pagination="materials" aria-label="Offer table pages"></div>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Offer Name</th>
                            <th>Company</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="materialsTable"><tr><td class="empty-table-state" colspan="5">Loading offers...</td></tr></tbody>
                </table>
            </div>
        </section>
        <section id="orders" class="dashboard-section">
            <div class="section-heading">
                <h2>Orders Management</h2>
            </div>
            <div class="table-tools">
                <label>Filter orders<input class="table-filter" type="search" data-table="orders" placeholder="Company, status, order ID..."></label>
                <label>Status<select id="orderStatusFilter" aria-label="Filter orders by status"><option value="">All statuses</option><option value="Pending">Pending</option><option value="Approved">Approved</option><option value="Rejected">Rejected</option><option value="Completed">Completed</option></select></label>
                <div class="pagination" data-pagination="orders" aria-label="Order table pages"></div>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Company</th>
                            <th>Offers</th>
                            <th>Order Date</th>
                            <th>Quantity</th>
                            <th>Order Total</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTable"><tr><td class="empty-table-state" colspan="8">Loading orders...</td></tr></tbody>
                </table>
            </div>
        </section>


        <!-- زهراء هنا يبدن التقارير -->

        <section id="reports" class="dashboard-section">
            <div class="section-heading">
                <h2>Reports</h2>
            </div>
            <!-- هنا نسوي انشاء للتقارير -->
            <div class="filter-section">
    <h2>Generate Report</h2>
    <div class="filters">
        <div class="form-group">
            <label for="targetTable">Report Category</label>
            <select id="targetTable">
                <option value="materials">Offers</option>
                <option value="companies">Companies</option>
                <option value="orders">Orders</option>
            </select>
        </div>
        <div class="form-group">
            <label for="reportType">Report Type</label>
            <select id="reportType">
                <option value="daily">Daily</option>
                <option value="weekly">Last 7 days</option>
                <option value="monthly">Past month</option>
                <option value="biannual">Last 6 months</option>
                <option value="yearly">Last 12 months</option>
                <option value="custom">Custom date range</option>
            </select>
        </div>
        <div class="form-group">
            <label for="startDate">Start Date</label>
            <input type="date" id="startDate">
        </div>
        <div class="form-group">
            <label for="endDate">End Date</label>
            <input type="date" id="endDate">
        </div>
        <button type="button" class="generate-btn" onclick="generateReport()">
            Generate Report
        </button>
    </div>
</div>
            <!-- ملخص التقرير -->
            <div class="report-section">
                <div class="report-heading">
                    <div>
                        <h2>Report Summary</h2>
                        <p>Platform performance overview</p>
                    </div>
                    <button type="button" class="print-btn"
                        onclick="window.print()">
                        Print Report
                    </button>
                </div>
                <div class="report-info">
                    <p>
                        Report Type:
                        <strong id="summaryReportType">Daily</strong>
                    </p>
                    <p>
                        Reporting Window:
                        <strong id="summaryPeriod">Today</strong>
                    </p>
                </div>
                <div class="statistics">
                    <div class="stat-card">
                        <p id="totalRecordsLabel">Total Offers</p>
                        <h2 id="totalListings">0</h2>
                    </div>
                    <div class="stat-card">
                        <p id="totalQuantityLabel">Total Quantity</p>
                        <h2 id="totalQuantity">0</h2>
                    </div>
                    <div class="stat-card">
                        <p>Total Savings</p>
                        <h2 id="totalSavings">Not Available</h2>
                    </div>
                    <div class="stat-card">
                        <p id="totalPurchasesLabel">Completed Orders</p>
                        <h2 id="totalPurchases">0</h2>
                    </div>
                </div>
                <div class="report-visual">
                    <div class="report-chart-heading">
                        <div>
                            <h3>Report visualization</h3>
                            <p>Click a bar to filter the report details below.</p>
                        </div>
                        <button type="button" class="btn btn-secondary" id="clearReportChartFilter" hidden>Clear chart filter</button>
                    </div>
                    <div id="reportChart" class="report-chart" aria-live="polite">
                        <p class="empty-state">Generate a report to view its data.</p>
                    </div>
                </div>
                <div class="table-tools report-table-tools">
                    <label>Filter report rows<input id="reportTableSearch" type="search" placeholder="Search report details..."></label>
                    <p id="reportTableCount" aria-live="polite"></p>
                    <div id="reportPagination" class="pagination" aria-label="Report table pages"></div>
                </div>
                <div class="table-container">
                    <table>
                        <thead id="reportTableHead"></thead>
                        <tbody id="reportTableBody"></tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</div>
<div id="toastRegion" class="toast-region" aria-live="polite" aria-atomic="true"></div>
<script src="JS/admin.js?v=20261117-company-profile"></script>
<script src="JS/reports.js?v=20261116-cachefix-1025"></script>
<script src="JS/search.js?v=20261117-company-profile"></script>
<script src="JS/ui.js?v=20261116-cachefix-1025"></script>
</body>
</html>
