
<?php
    require_once __DIR__ . "/FaidProject/admin_check.php";
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>FAID | Admin Dashboard</title>
        <link rel="stylesheet" href="CSS/admin.css">
        <link rel="stylesheet" href="CSS/reports.css">
    </head>
    <body>
         <header class="admin-header">
                <div class="logo">
                    <img src="Img/FaidLogo.png" alt="FAID Logo">
                 </div>
                <div class="admin-info">
                     <span><?php echo htmlspecialchars($_SESSION["admin_name"]); ?></span>
                     <div class="admin-avatar">
    <?php echo htmlspecialchars(substr($_SESSION["admin_name"], 0, 1)); ?>
</div>
                 </div>
          </header>
          <div class="admin-layout">
         <aside class="sidebar">
            <h3>ADMIN PANEL</h3>
            <nav>
                <a href="#dashboard" class="active">Dashboard</a>
                <a href="#companies">Companies</a>
                <a href="#materials">Materials</a>
                <a href="#orders">Orders</a>
                <a href="#search">Search</a>
                <a href="#reports">Reports</a>
            </nav>
         </aside>
    <main class="main-content">
        <section id="dashboard">
            <div class="page-heading">
                <h1>Dashboard</h1>
            </div>
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Total Companies</h3>
                    <p id="totalCompanies">0</p>
                </div>
                <div class="stat-card">
                    <h3>Total Materials</h3>
                    <p id="totalMaterials">0</p>
                </div>
                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <p id="totalOrders">0</p>
                </div>
                <div class="stat-card">
                    <h3>Pending Companies</h3>
                    <p id="pendingCompanies">0</p>
                </div>
            </div>
        </section>
                   <section id="search" class="dashboard-section">
            <div class="section-heading">
                <h2>Search & Manage</h2>
            </div>
            <div class="filters">
                <div class="form-group">
                    <label for="searchInput">Keyword</label>
                    <input type="search" id="searchInput"
                        placeholder="Company, material, order ID...">
                </div>
                <button type="button" class="btn" id="searchBtn">Search</button>
            </div>
        </section>

        <!-- نافذة النتائج (popup) -->
        <div id="searchModal" class="modal-overlay"
            style="display:none; position:fixed; top:0; left:0; right:0; bottom:0;
                   background:rgba(0,0,0,0.5); align-items:center;
                   justify-content:center; z-index:1000;">
            <div class="modal-box"
                style="background:#fff; width:90%; max-width:900px; max-height:80vh;
                       border-radius:10px; display:flex; flex-direction:column;
                       overflow:hidden;">
                <div class="modal-header"
                    style="display:flex; justify-content:space-between;
                           align-items:center; padding:14px 18px;
                           border-bottom:1px solid #ddd;">
                    <h3>Search Results</h3>
                    <button type="button" class="btn" id="closeSearchModal">Close</button>
                </div>
                <div id="searchResults" class="modal-body"
                    style="padding:16px 18px; overflow-y:auto;"></div>
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
                <form action="FaidProject/addCompany.php" method="POST">
                    <input type="text" name="company_name"
                        placeholder="Company Name" required>
                    <input type="email" name="email"
                        placeholder="Email" required>
                    <input type="text" name="phone"
                        placeholder="Phone">
                    <input type="text" name="address"
                        placeholder="Address">
                    <input type="password" name="password"
                        placeholder="Password" required>
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
                <form action="FaidProject/edit_company.php" method="POST">
                    <input type="hidden" name="company_id"
                        id="editCompanyId">
                    <input type="text" name="company_name"
                        id="editCompanyName"
                        placeholder="Company Name" required>
                    <input type="email" name="email"
                        id="editCompanyEmail"
                        placeholder="Email" required>
                    <input type="text" name="phone"
                        id="editCompanyPhone"
                        placeholder="Phone">
                    <input type="text" name="address"
                        id="editCompanyAddress"
                        placeholder="Address">
                    <button class="btn" type="submit">
                        Update Company
                    </button>
                    <button class="btn" type="button"
                        onclick="closeEditCompanyForm()">
                        Cancel
                    </button>
                </form>
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
                    <tbody id="companiesTable"></tbody>
                </table>
            </div>
        </section>
        <section id="materials" class="dashboard-section">
            <div class="section-heading">
                <h2>Materials Management</h2>
                <button type="button" class="btn"
                    onclick="openMaterialForm()">
                    + Add Material
                </button>
            </div>
            <div id="materialForm" class="admin-form"
                style="display:none;">
                <h3>Add New Material</h3>
                <form action="FaidProject/addmaterial.php" method="POST">
                    <input type="text" name="material_name"
                        placeholder="Material Name" required>
                    <input type="text" name="category"
                        placeholder="Category" required>
                    <textarea name="description"
                        placeholder="Description"></textarea>
                    <input type="number" name="quantity"
                        placeholder="Quantity" min="1" required>
                    <input type="number" name="price"
                        placeholder="Price" min="0"
                        step="0.01" required>
                    <input type="number" name="company_id"
                        placeholder="Company ID" min="1" required>
                    <button class="btn" type="submit">
                        Save Material
                    </button>
                    <button class="btn" type="button"
                        onclick="closeMaterialForm()">
                        Cancel
                    </button>
                </form>
            </div>
            <div id="editMaterialForm" class="admin-form"
                style="display:none;">
                <h3>Edit Material</h3>
                <form action="FaidProject/edit_material.php" method="POST">
                    <input type="hidden" name="material_id"
                        id="editMaterialId">
                    <input type="text" name="material_name"
                        id="editMaterialName"
                        placeholder="Material Name" required>
                    <input type="text" name="category"
                        id="editMaterialCategory"
                        placeholder="Category" required>
                    <input type="number" name="quantity"
                        id="editMaterialQuantity"
                        placeholder="Quantity" required>
                    <input type="number" name="price"
                        id="editMaterialPrice"
                        placeholder="Price" required>
                    <button class="btn" type="submit">
                        Update Material
                    </button>
                    <button class="btn" type="button"
                        onclick="closeEditMaterialForm()">
                        Cancel
                    </button>
                </form>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Material Name</th>
                            <th>Company</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="materialsTable"></tbody>
                </table>
            </div>
        </section>
        <section id="orders" class="dashboard-section">
            <div class="section-heading">
                <h2>Orders Management</h2>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Company</th>
                            <th>Order Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTable"></tbody>
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
                <option value="materials">Materials</option>
                <option value="companies">Companies</option>
                <option value="orders">Orders</option>
            </select>
        </div>
        <div class="form-group">
            <label for="reportType">Report Type</label>
            <select id="reportType">
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
                <option value="biannual">Every 6 Months</option>
                <option value="yearly">Yearly</option>
                <option value="custom">Custom Date Range</option>
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
                        Start Date:
                        <strong id="summaryStartDate">---</strong>
                    </p>
                    <p>
                        End Date:
                        <strong id="summaryEndDate">---</strong>
                    </p>
                </div>
                <div class="statistics">
                    <div class="stat-card">
                        <p>Total Listings</p>
                        <h2 id="totalListings">0</h2>
                    </div>
                    <div class="stat-card">
                        <p>Total Quantity</p>
                        <h2 id="totalQuantity">0</h2>
                    </div>
                    <div class="stat-card">
                        <p>Total Savings</p>
                        <h2 id="totalSavings">Not Available</h2>
                    </div>
                    <div class="stat-card">
                        <p>Total Purchases</p>
                        <h2 id="totalPurchases">0</h2>
                    </div>
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
<script src="JS/admin.js"></script>
<script src="JS/reports.js"></script>
<script src="JS/search.js"></script>
</body>
</html>
