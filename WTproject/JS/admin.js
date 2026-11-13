
document.addEventListener("DOMContentLoaded", function () {
    loadAdminData();
    document.getElementById("companiesTable").addEventListener("click", function (event) {
        const button = event.target.closest("button");
        if (!button) return;
        const id = button.dataset.id;
        const company = window.companiesData?.find(c => Number(c.company_id) === Number(id));
        if (button.classList.contains("view-btn") && company) {
            alert(
                "Company: " + company.company_name +
                "\nEmail: " + company.email +
                "\nPhone: " + (company.phone || "") +
                "\nAddress: " + (company.address || "")
            );
        }
        if (button.classList.contains("edit-btn") && company) {
            document.getElementById("editCompanyId").value = company.company_id;
            document.getElementById("editCompanyName").value = company.company_name;
            document.getElementById("editCompanyEmail").value = company.email;
            document.getElementById("editCompanyPhone").value = company.phone || "";
            document.getElementById("editCompanyAddress").value = company.address || "";
            document.getElementById("editCompanyForm").style.display = "block";
            document.getElementById("companyForm").style.display = "none";
        }
        if (button.classList.contains("delete-btn")) {
            if (confirm("Are you sure you want to delete this company?")) {
                submitDelete("FaidProject/delete_company.php", "company_id", id);
            }
        }
    });
    document.getElementById("materialsTable").addEventListener("click", function (event) {
        const button = event.target.closest("button");
        if (!button) return;
        const id = button.dataset.id;
        const material = window.materialsData?.find(m => Number(m.material_id) === Number(id));
        if (button.classList.contains("view-btn") && material) {
            alert(
                "Material: " + material.material_name +
                "\nCategory: " + material.category +
                "\nQuantity: " + material.quantity +
                "\nPrice: " + material.price
            );
        }
        if (button.classList.contains("edit-btn") && material) {
            document.getElementById("editMaterialId").value = material.material_id;
            document.getElementById("editMaterialName").value = material.material_name;
            document.getElementById("editMaterialCategory").value = material.category;
            document.getElementById("editMaterialQuantity").value = material.quantity;
            document.getElementById("editMaterialPrice").value = material.price;
            document.getElementById("editMaterialForm").style.display = "block";
            document.getElementById("materialForm").style.display = "none";
        }
        if (button.classList.contains("delete-btn")) {
            if (confirm("Are you sure you want to delete this material?")) {
                submitDelete("FaidProject/delete_material.php", "material_id", id);
            }
        }
    });
    document.getElementById("ordersTable").addEventListener("click", function (event) {
        const button = event.target.closest("button");
        if (!button) return;
        const id = button.dataset.id;
        if (button.classList.contains("view-btn")) {
            const order = window.ordersData?.find(o => Number(o.order_id) === Number(id));
            if (order) {
                alert(
                    "Order ID: " + order.order_id +
                    "\nCompany: " + order.buyer_name +
                    "\nDate: " + order.order_date +
                    "\nStatus: " + order.status
                );
            }
        }
        if (button.classList.contains("delete-btn")) {
            if (confirm("Are you sure you want to delete this order?")) {
                submitDelete("FaidProject/delete_order.php", "order_id", id);
            }
        }
    });
});
function loadAdminData() {
    fetch("FaidProject/admin_data.php")
        .then(response => {
            if (!response.ok) throw new Error("Failed to load admin data");
            return response.json();
        })
        .then(data => {
            document.getElementById("totalCompanies").textContent = data.totalCompanies;
            document.getElementById("totalMaterials").textContent = data.totalMaterials;
            document.getElementById("totalOrders").textContent = data.totalOrders;
            document.getElementById("pendingCompanies").textContent = data.pendingCompanies;
            window.companiesData = data.companies;
            window.materialsData = data.materials;
            window.ordersData = data.orders;
            renderCompanies(data.companies);
            renderMaterials(data.materials);
            renderOrders(data.orders);
        })
        .catch(error => console.error("Admin data error:", error));
}
function makeButton(label, className, id) {
    const button = document.createElement("button");
    button.type = "button";
    button.textContent = label;
    button.className = className;
    button.dataset.id = id;
    return button;
}
function renderCompanies(companies) {
    const table = document.getElementById("companiesTable");
    table.innerHTML = "";
    companies.forEach(company => {
        const row = document.createElement("tr");
        [company.company_name, company.email, "Registered"].forEach(value => {
            const cell = document.createElement("td");
            cell.textContent = value || "";
            row.appendChild(cell);
        });
        const action = document.createElement("td");
        action.append(
            makeButton("View", "view-btn", company.company_id),
            makeButton("Edit", "edit-btn", company.company_id),
            makeButton("Delete", "delete-btn", company.company_id)
        );

        row.appendChild(action);
        table.appendChild(row);
    });
}
function renderMaterials(materials) {
    const table = document.getElementById("materialsTable");
    table.innerHTML = "";
    materials.forEach(material => {
        const row = document.createElement("tr");
        [
            material.material_name,
            material.company_name,
            material.category,
            material.price + " IQD"
        ].forEach(value => {
            const cell = document.createElement("td");
            cell.textContent = value ?? "";
            row.appendChild(cell);
        });
        const action = document.createElement("td");
        action.append(
            makeButton("View", "view-btn", material.material_id),
            makeButton("Edit", "edit-btn", material.material_id),
            makeButton("Delete", "delete-btn", material.material_id)
        );
        row.appendChild(action);
        table.appendChild(row);
    });
}
function renderOrders(orders) {
    const table = document.getElementById("ordersTable");
    table.innerHTML = "";
    orders.forEach(order => {
        const row = document.createElement("tr");
        [order.order_id, order.buyer_name, order.order_date, order.status].forEach(value => {
            const cell = document.createElement("td");
            cell.textContent = value ?? "";
            row.appendChild(cell);
        });
        const action = document.createElement("td");
        action.append(
            makeButton("View", "view-btn", order.order_id)
        );
        const form = document.createElement("form");
        form.method = "POST";
        form.action = "FaidProject/update_order.php";
        const hidden = document.createElement("input");
        hidden.type = "hidden";
        hidden.name = "order_id";
        hidden.value = order.order_id;
        const select = document.createElement("select");
        select.name = "status";
        ["Pending", "Approved", "Rejected", "Completed"].forEach(status => {
            const option = document.createElement("option");
            option.value = status;
            option.textContent = status;
            option.selected = status === order.status;
            select.appendChild(option);
        });
        const update = document.createElement("button");
        update.type = "submit";
        update.textContent = "Update";
        form.append(hidden, select, update);
        action.appendChild(form);
        action.appendChild(makeButton("Delete", "delete-btn", order.order_id));
        row.appendChild(action);
        table.appendChild(row);
    });
}
function submitDelete(url, fieldName, id) {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = url;
    const input = document.createElement("input");
    input.type = "hidden";
    input.name = fieldName;
    input.value = id;
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}
function openCompanyForm() {
    document.getElementById("companyForm").style.display = "block";
    document.getElementById("editCompanyForm").style.display = "none";
}
function closeCompanyForm() {
    document.getElementById("companyForm").style.display = "none";
}
function closeEditCompanyForm() {
    document.getElementById("editCompanyForm").style.display = "none";
}
function openMaterialForm() {
    document.getElementById("materialForm").style.display = "block";
    document.getElementById("editMaterialForm").style.display = "none";
}
function closeMaterialForm() {
    document.getElementById("materialForm").style.display = "none";
}
function closeEditMaterialForm() {
    document.getElementById("editMaterialForm").style.display = "none";
}
