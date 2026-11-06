
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
        <section id="companies" class="dashboard-section">
            <div class="section-heading">
                <h2>Companies Management</h2>
                <button class="btn" type="button" onclick="openCompanyForm()">+ Add Company</button>
            </div>
            <div id="companyForm" class="admin-form" style="display:none;">
                <h3>Add New Company</h3>
                <form action="FaidProject/add_company.php" method="POST">
                    <input type="text" name="company_name"placeholder="Company Name" required>
                    <input type="email" name="email"placeholder="Email" required>
                    <input type="text" name="phone"placeholder="Phone">
                    <input type="text" name="address"placeholder="Address">
                    <input type="password" name="password"placeholder="Password" required>
                    <button class="btn" type="submit">Save Company</button>
                    <button  class="btn" type="button"onclick="closeCompanyForm()">Cancel</button>
                </form>
            </div>
            <div id="editCompanyForm" class="admin-form" style="display:none;">
                <h3>Edit Company</h3>
                <form action="FaidProject/edit_company.php" method="POST">
                    <input type="hidden" name="company_id" id="editCompanyId">
                    <input type="text" name="company_name"id="editCompanyName" placeholder="Company Name" required>
                    <input type="email" name="email"id="editCompanyEmail" placeholder="Email" required>
                    <input type="text" name="phone"id="editCompanyPhone" placeholder="Phone">
                    <input type="text" name="address"id="editCompanyAddress" placeholder="Address">
                    <button class="btn"type="submit">Update Company</button>
                    <button  class="btn" type="button"onclick="closeEditCompanyForm()">Cancel
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
                <button type="button" class="btn" onclick="openMaterialForm()"> + Add Material</button>
            </div>
            <div id="materialForm" class="admin-form" style="display:none;">
                <h3>Add New Material</h3>
                <form action="FaidProject/add_material.php" method="POST">
                    <input type="text" name="material_name"placeholder="Material Name" required>
                    <input type="text" name="category"placeholder="Category" required>
                    <textarea name="description"placeholder="Description"></textarea>
                    <input type="number" name="quantity"placeholder="Quantity" min="1" required>
                    <input type="number" name="price"placeholder="Price" min="0" step="0.01" required>
                    <input type="number" name="company_id"placeholder="Company ID" min="1" required>
                    <button class="btn"type="submit">Save Material</button>
                    <button class="btn"type="button"onclick="closeMaterialForm()">Cancel</button>
                </form>
            </div>
            <div id="editMaterialForm" class="admin-form" style="display:none;">
                <h3>Edit Material</h3>
                <form action="FaidProject/edit_material.php" method="POST">
                    <input type="hidden" name="material_id" id="editMaterialId">
                    <input type="text" name="material_name"id="editMaterialName" placeholder="Material Name" required>
                    <input type="text" name="category"id="editMaterialCategory" placeholder="Category" required>
                    <input type="number" name="quantity" id="editMaterialQuantity" placeholder="Quantity" required>
                    <input type="number" name="price"id="editMaterialPrice" placeholder="Price" required>
                    <button class="btn"type="submit">Update Material</button>
                    <button class="btn"type="button"onclick="closeEditMaterialForm()">Cancel</button>
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

    </main>
</div>
<script src="JS/admin.js"></script>
</body>
</html>
