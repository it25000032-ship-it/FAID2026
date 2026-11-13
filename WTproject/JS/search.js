document.addEventListener("DOMContentLoaded", function () {

    const searchInput  = document.getElementById("searchInput");
    const searchBtn    = document.getElementById("searchBtn");
    const searchModal  = document.getElementById("searchModal");
    const searchResults = document.getElementById("searchResults");
    const closeBtn     = document.getElementById("closeSearchModal");

    const BASE = "FaidProject/";

    const config = {
        companies: {
            title: "Companies",
            id: "company_id",
            cols: ["company_id", "company_name", "email", "phone", "address"]
        },
        materials: {
            title: "Materials",
            id: "material_id",
            cols: ["material_id", "material_name", "company_name", "category", "quantity", "price"]
        },
        orders: {
            title: "Orders",
            id: "order_id",
            cols: ["order_id", "buyer_name", "order_date", "status"]
        }
    };

    function openModal()  { searchModal.style.display = "flex"; }
    function closeModal() { searchModal.style.display = "none"; }

    closeBtn.addEventListener("click", closeModal);
    searchModal.addEventListener("click", function (e) {
        if (e.target === searchModal) closeModal();
    });

    async function doSearch() {
        const q = searchInput.value.trim();
        if (!q) {
            alert("Please type a keyword.");
            return;
        }

        try {
            const res = await fetch(BASE + "search_admin.php?" + new URLSearchParams({ q: q }));
            const result = await res.json();

            if (!result.success) {
                alert(result.message || "Search failed.");
                return;
            }

            renderResults(result);
            openModal();
        } catch (err) {
            console.error("Search Error:", err);
            alert("Error connecting to search_admin.php.");
        }
    }

    function renderResults(result) {
        searchResults.innerHTML = "";
        let total = 0;

        Object.keys(config).forEach(function (type) {
            const rows = result[type] || [];
            if (rows.length === 0) return;
            total += rows.length;

            const cfg = config[type];

            const heading = document.createElement("h4");
            heading.textContent = cfg.title + " (" + rows.length + ")";
            searchResults.appendChild(heading);

            const table = document.createElement("table");

            const thead = document.createElement("thead");
            const headRow = document.createElement("tr");
            cfg.cols.concat(["action"]).forEach(function (c) {
                const th = document.createElement("th");
                th.textContent = c.replaceAll("_", " ");
                headRow.appendChild(th);
            });
            thead.appendChild(headRow);
            table.appendChild(thead);

            const tbody = document.createElement("tbody");

            rows.forEach(function (item) {
                const tr = document.createElement("tr");

                cfg.cols.forEach(function (c) {
                    const td = document.createElement("td");
                    td.textContent = item[c] ?? "";
                    tr.appendChild(td);
                });

                const actions = document.createElement("td");

                const editBtn = document.createElement("button");
                editBtn.type = "button";
                editBtn.className = "btn";
                editBtn.textContent = "Edit";
                editBtn.addEventListener("click", function () {
                    editItem(type, item);
                });

                const delBtn = document.createElement("button");
                delBtn.type = "button";
                delBtn.className = "btn";
                delBtn.textContent = "Delete";
                delBtn.addEventListener("click", function () {
                    deleteItem(type, item[cfg.id]);
                });

                actions.appendChild(editBtn);
                actions.appendChild(delBtn);
                tr.appendChild(actions);
                tbody.appendChild(tr);
            });

            table.appendChild(tbody);
            searchResults.appendChild(table);
        });

        if (total === 0) {
            searchResults.textContent = "No results found.";
        }
    }

    function showForm(formId) {
        const form = document.getElementById(formId);
        form.style.display = "block";
        form.scrollIntoView({ behavior: "smooth", block: "center" });
    }

    function editItem(type, item) {
        if (type === "companies") {
            closeModal();
            document.getElementById("editCompanyId").value      = item.company_id;
            document.getElementById("editCompanyName").value    = item.company_name ?? "";
            document.getElementById("editCompanyEmail").value   = item.email ?? "";
            document.getElementById("editCompanyPhone").value   = item.phone ?? "";
            document.getElementById("editCompanyAddress").value = item.address ?? "";
            showForm("editCompanyForm");

        } else if (type === "materials") {
            closeModal();
            document.getElementById("editMaterialId").value       = item.material_id;
            document.getElementById("editMaterialName").value     = item.material_name ?? "";
            document.getElementById("editMaterialCategory").value = item.category ?? "";
            document.getElementById("editMaterialQuantity").value = item.quantity ?? "";
            document.getElementById("editMaterialPrice").value    = item.price ?? "";
            showForm("editMaterialForm");

        } else if (type === "orders") {
            const status = prompt(
                "New status (Pending / Completed / Cancelled):",
                item.status
            );
            if (status === null) return;
            updateOrderStatus(item.order_id, status.trim());
        }
    }

    async function updateOrderStatus(id, status) {
        try {
            const res = await fetch(BASE + "update_order_status.php", {
                method: "POST",
                body: new URLSearchParams({ id: id, status: status })
            });
            const result = await res.json();
            if (!result.success) {
                alert(result.message || "Update failed.");
                return;
            }
            doSearch();
        } catch (err) {
            console.error(err);
            alert("Error updating order.");
        }
    }

    async function deleteItem(type, id) {
        if (!confirm("Are you sure you want to delete this record?")) return;

        try {
            const res = await fetch(BASE + "delete_item.php", {
                method: "POST",
                body: new URLSearchParams({ type: type, id: id })
            });
            const result = await res.json();
            if (!result.success) {
                alert(result.message || "Delete failed.");
                return;
            }
            doSearch();
        } catch (err) {
            console.error(err);
            alert("Error deleting record.");
        }
    }

    searchBtn.addEventListener("click", doSearch);
    searchInput.addEventListener("keydown", function (e) {
        if (e.key === "Enter") doSearch();
    });
});