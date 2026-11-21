document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("searchInput");
    const searchButton = document.getElementById("searchBtn");
    const searchModal = document.getElementById("searchModal");
    const searchResults = document.getElementById("searchResults");
    const closeButton = document.getElementById("closeSearchModal");
    const statusModal = document.getElementById("statusModal");
    const statusForm = document.getElementById("searchOrderStatusForm");
    const base = "FaidProject/";
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";

    const config = {
        companies: {
            title: "Companies",
            id: "company_id",
            cols: ["company_id", "company_name", "email", "phone", "address"]
        },
        materials: {
            title: "Offers",
            id: "material_id",
            cols: ["material_id", "material_name", "company_name", "category", "quantity", "price"]
        },
        orders: {
            title: "Orders",
            id: "order_id",
            cols: ["order_id", "buyer_name", "items", "order_date", "item_quantity", "order_total", "status"]
        }
    };

    function openModal() {
        searchModal.classList.add("is-visible");
    }

    function closeModal() {
        searchModal.classList.remove("is-visible");
    }

    closeButton.addEventListener("click", closeModal);
    searchModal.addEventListener("click", event => {
        if (event.target === searchModal) closeModal();
    });
    document.getElementById("closeStatusModal").addEventListener("click", function () {
        statusModal.classList.remove("is-visible");
    });
    statusModal.addEventListener("click", event => {
        if (event.target === statusModal) statusModal.classList.remove("is-visible");
    });

    async function doSearch() {
        const query = searchInput.value.trim();
        if (!query) {
            showToast("Please type a keyword.", "error");
            return;
        }

        const originalText = searchButton.textContent;
        searchButton.disabled = true;
        searchButton.classList.add("is-loading");
        searchButton.textContent = "Searching...";
        try {
            const response = await fetch(base + "search_admin.php?" +
                new URLSearchParams({ q: query }));
            const result = await response.json();
            if (!response.ok || !result.success) {
                showToast(result.message || "Search failed.", "error");
                return;
            }
            renderResults(result);
            openModal();
            if (Object.values(result).filter(Array.isArray).every(rows => rows.length === 0)) {
                showToast("No matching records found.", "error");
            }
        } catch (error) {
            console.error("Search error:", error);
            showToast("Could not search records. Please try again.", "error");
        } finally {
            searchButton.disabled = false;
            searchButton.classList.remove("is-loading");
            searchButton.textContent = originalText;
        }
    }

    function renderResults(result) {
        searchResults.replaceChildren();
        let total = 0;

        Object.keys(config).forEach(function (type) {
            const rows = result[type] || [];
            if (rows.length === 0) return;
            total += rows.length;

            const definition = config[type];
            const heading = document.createElement("h4");
            heading.textContent = definition.title + " (" + rows.length + ")";
            searchResults.appendChild(heading);

            const table = document.createElement("table");
            const header = document.createElement("thead");
            const headerRow = document.createElement("tr");
            const columnLabels = {
                material_id: "Offer ID",
                material_name: "Offer name",
                quantity: "Quantity",
                price: "Price",
                company_id: "Company ID",
                company_name: "Company",
                buyer_name: "Company",
                order_id: "Order ID",
                order_date: "Date",
                order_total: "Order total",
                item_quantity: "Quantity"
            };
            definition.cols.concat(["Action"]).forEach(column => {
                const cell = document.createElement("th");
                cell.textContent = columnLabels[column] || column.replaceAll("_", " ");
                headerRow.appendChild(cell);
            });
            header.appendChild(headerRow);
            table.appendChild(header);

            const body = document.createElement("tbody");
            rows.forEach(function (item) {
                const row = document.createElement("tr");
                definition.cols.forEach(column => {
                    const cell = document.createElement("td");
                    cell.textContent = column === "order_total"
                        ? Number(item[column] ?? 0).toFixed(2) + " IQD"
                        : (item[column] ?? "");
                    row.appendChild(cell);
                });

                const actions = document.createElement("td");
                if (type !== "orders") {
                    const editButton = document.createElement("button");
                    editButton.type = "button";
                    editButton.className = "btn";
                    editButton.textContent = "Edit";
                    editButton.addEventListener("click", () => editItem(type, item));
                    actions.appendChild(editButton);
                }

                const deleteButton = document.createElement("button");
                deleteButton.type = "button";
                deleteButton.className = "btn";
                deleteButton.textContent = "Delete";
                deleteButton.addEventListener("click", () => {
                    deleteItem(type, item[definition.id]);
                });
                actions.appendChild(deleteButton);
                if (type === "orders") {
                    const statusButton = document.createElement("button");
                    statusButton.type = "button";
                    statusButton.className = "btn";
                    statusButton.textContent = "Update status";
                    statusButton.addEventListener("click", () => openStatusModal(item));
                    actions.appendChild(statusButton);
                }
                row.appendChild(actions);
                body.appendChild(row);
            });
            table.appendChild(body);
            searchResults.appendChild(table);
        });

        if (total === 0) {
            const empty = document.createElement("p");
            empty.className = "empty-state";
            empty.textContent = "No results found. Try a different keyword.";
            searchResults.appendChild(empty);
        }
    }

    function showForm(formId) {
        const form = document.getElementById(formId);
        form.style.display = "block";
        form.scrollIntoView({ behavior: "smooth", block: "center" });
    }

    function editItem(type, item) {
        closeModal();
        if (type === "companies") {
            document.getElementById("updateCompanyForm").reset();
            document.getElementById("editCompanyId").value = item.company_id;
            document.getElementById("editCompanyName").value = item.company_name ?? "";
            document.getElementById("editCompanyEmail").value = item.email ?? "";
            document.getElementById("editCompanyPhone").value = item.phone ?? "";
            document.getElementById("editCompanyAddress").value = item.address ?? "";
            document.getElementById("editCompanyDescription").value = item.description ?? "";
            showForm("editCompanyForm");
        } else if (type === "materials") {
            document.getElementById("updateMaterialForm").reset();
            document.getElementById("editMaterialId").value = item.material_id;
            document.getElementById("editMaterialName").value = item.material_name ?? "";
            document.getElementById("editMaterialCategory").value = item.category ?? "";
            document.getElementById("editMaterialDescription").value = item.description ?? "";
            document.getElementById("editMaterialQuantity").value = item.quantity ?? "";
            document.getElementById("editMaterialPrice").value = item.price ?? "";
            showForm("editMaterialForm");
        }
    }

    function openStatusModal(order) {
        const statusSelect = document.getElementById("searchOrderStatus");
        const transitions = {
            Pending: ["Pending", "Approved", "Rejected", "Completed"],
            Approved: ["Approved", "Rejected", "Completed"],
            Rejected: ["Rejected"],
            Completed: ["Completed"]
        };
        statusSelect.replaceChildren();
        (transitions[order.status] || [order.status]).forEach(status => {
            const option = document.createElement("option");
            option.value = status;
            option.textContent = status;
            option.selected = status === order.status;
            statusSelect.appendChild(option);
        });
        document.getElementById("searchOrderId").value = order.order_id;
        statusModal.classList.add("is-visible");
        statusSelect.focus();
    }

    statusForm.addEventListener("submit", async function (event) {
        event.preventDefault();
        if (statusForm.dataset.submitting === "true") return;
        statusForm.dataset.submitting = "true";
        const submitButton = statusForm.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.classList.add("is-loading");
        submitButton.textContent = "Updating...";
        try {
            const response = await fetch(base + "update_order.php", {
                method: "POST",
                body: new FormData(statusForm),
                headers: { "X-CSRF-Token": csrfToken }
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                showToast(result.message || "Could not update the order.", "error");
                return;
            }
            showToast(result.message || "Order status updated.");
            statusModal.classList.remove("is-visible");
            await doSearch();
        } catch (error) {
            console.error("Order status update error:", error);
            showToast("Could not update the order. Please try again.", "error");
        } finally {
            statusForm.dataset.submitting = "false";
            submitButton.disabled = false;
            submitButton.classList.remove("is-loading");
            submitButton.textContent = originalText;
        }
    });

    async function deleteItem(type, id) {
        if (!confirm("Delete this record? This action cannot be undone.")) return;
        try {
            const response = await fetch(base + "delete_item.php", {
                method: "POST",
                body: new URLSearchParams({ type: type, id: id }),
                headers: { "X-CSRF-Token": csrfToken }
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                showToast(result.message || "Delete failed.", "error");
                return;
            }
            showToast(result.message || "Record deleted.");
            await doSearch();
        } catch (error) {
            console.error("Delete error:", error);
            showToast("Could not delete the record. Please try again.", "error");
        }
    }

    searchButton.addEventListener("click", doSearch);
    searchInput.addEventListener("keydown", event => {
        if (event.key === "Enter") doSearch();
    });
});
