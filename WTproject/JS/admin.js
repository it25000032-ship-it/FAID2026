
const tableState = {
    companies: { query: "", page: 1, pageSize: 8 },
    materials: { query: "", category: "", availability: "", sort: "recent", page: 1, pageSize: 8 },
    orders: { query: "", status: "", focusedId: "", page: 1, pageSize: 8 }
};

const chartColors = {
    navy: "#0b2559",
    blue: "#2563eb",
    yellow: "#facc15",
    sky: "#dbe8ff"
};
const chartSeries = [
    { key: "companies", label: "Company registrations", color: chartColors.navy, dateKey: "created_at" },
    { key: "materials", label: "Offers published", color: chartColors.blue, dateKey: "created_at" },
    { key: "orders", label: "Orders placed", color: chartColors.yellow, dateKey: "order_date" }
];

function animateAdminCounter(element, value) {
    if (!element) return;
    const target = Math.max(0, Number(value) || 0);
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
        element.textContent = target.toLocaleString();
        return;
    }
    const start = performance.now();
    const duration = 720;
    function tick(now) {
        const progress = Math.min(1, (now - start) / duration);
        element.textContent = Math.round(target * (1 - Math.pow(1 - progress, 3))).toLocaleString();
        if (progress < 1) window.requestAnimationFrame(tick);
    }
    window.requestAnimationFrame(tick);
}

function materialImageUrl(imagePath) {
    const savedPath = String(imagePath || "").replace(/^FaidProject\//i, "");
    if (!/^uploads\/[A-Za-z0-9_.-]+\.(jpg|jpeg|png|gif|webp)$/i.test(savedPath)) return "";
    return "FaidProject/" + savedPath;
}

function showRecordDetails(title, values) {
    const modal = document.getElementById("recordModal");
    const titleElement = document.getElementById("recordModalTitle");
    const body = document.getElementById("recordModalBody");
    titleElement.textContent = title;
    body.replaceChildren();
    const details = document.createElement("dl");
    details.className = "record-details";
    Object.entries(values).forEach(([label, value]) => {
        const term = document.createElement("dt");
        term.textContent = label;
        const description = document.createElement("dd");
        description.textContent = value === null || value === undefined || value === ""
            ? "Not provided"
            : String(value);
        details.append(term, description);
    });
    body.appendChild(details);
    modal.classList.add("is-visible");
    document.getElementById("closeRecordModal").focus();
}

function renderCurrentTable(type) {
    if (type === "companies") renderCompanies(window.companiesData || []);
    if (type === "materials") renderMaterials(window.materialsData || []);
    if (type === "orders") renderOrders(window.ordersData || []);
}

function renderPaginatedTable(type, records, searchFields, renderRow, columnCount) {
    const table = document.getElementById(type + "Table");
    const state = tableState[type];
    const query = state.query;
    let filtered = records.filter(record => type === "orders" && state.focusedId
        ? String(record.order_id) === state.focusedId
        : !query || searchFields.some(field =>
            String(record[field] ?? "").toLowerCase().includes(query)));
    if (type === "materials") {
        if (state.category) filtered = filtered.filter(record => record.category === state.category);
        if (state.availability === "available") {
            filtered = filtered.filter(record => Number(record.available_quantity ?? record.quantity) > 0);
        }
        if (state.availability === "unavailable") {
            filtered = filtered.filter(record => Number(record.available_quantity ?? record.quantity) <= 0);
        }
        const sorters = {
            recent: (a, b) => String(b.created_at || "").localeCompare(String(a.created_at || "")),
            name: (a, b) => String(a.material_name || "").localeCompare(String(b.material_name || "")),
            "price-low": (a, b) => Number(a.price) - Number(b.price),
            "price-high": (a, b) => Number(b.price) - Number(a.price),
            "quantity-high": (a, b) => Number(b.quantity) - Number(a.quantity)
        };
        filtered = filtered.slice().sort(sorters[state.sort] || sorters.recent);
    }
    if (type === "orders") {
        if (state.status) filtered = filtered.filter(record => record.status === state.status);
        filtered = filtered.slice().sort((a, b) =>
            String(b.order_date || "").localeCompare(String(a.order_date || "")));
    }
    if (type === "companies") {
        filtered = filtered.slice().sort((a, b) =>
            String(b.created_at || "").localeCompare(String(a.created_at || "")));
    }
    const pages = Math.max(1, Math.ceil(filtered.length / state.pageSize));
    state.page = Math.min(state.page, pages);
    const start = (state.page - 1) * state.pageSize;
    const currentPage = filtered.slice(start, start + state.pageSize);
    table.replaceChildren();

    if (currentPage.length === 0) {
        const row = document.createElement("tr");
        const cell = document.createElement("td");
        cell.colSpan = columnCount;
        cell.className = "empty-table-state";
        const hasFilter = query || state.category || state.availability || state.status;
        cell.textContent = hasFilter ? "No records match the current filters." : "No records to display.";
        row.appendChild(cell);
        table.appendChild(row);
    } else {
        currentPage.forEach(record => table.appendChild(renderRow(record)));
    }

    renderPagination(type, filtered.length, pages);
}

function renderPagination(type, total, pages) {
    const container = document.querySelector('[data-pagination="' + type + '"]');
    const state = tableState[type];
    container.replaceChildren();
    if (!total) return;

    const summary = document.createElement("span");
    const first = (state.page - 1) * state.pageSize + 1;
    const last = Math.min(state.page * state.pageSize, total);
    summary.textContent = first + "-" + last + " of " + total;

    const previous = document.createElement("button");
    previous.type = "button";
    previous.textContent = "Previous";
    previous.disabled = state.page <= 1;
    previous.setAttribute("aria-label", "Previous page");
    previous.addEventListener("click", function () {
        state.page--;
        renderCurrentTable(type);
    });

    const next = document.createElement("button");
    next.type = "button";
    next.textContent = "Next";
    next.disabled = state.page >= pages;
    next.setAttribute("aria-label", "Next page");
    next.addEventListener("click", function () {
        state.page++;
        renderCurrentTable(type);
    });

    container.append(summary, previous, next);
}

document.addEventListener("DOMContentLoaded", function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";
    loadAdminData();
    const analyticsRange = document.getElementById("analyticsRange");
    const dashboardDate = document.getElementById("dashboardDate");
    dashboardDate.textContent = new Intl.DateTimeFormat(undefined, {
        weekday: "long",
        month: "long",
        day: "numeric",
        year: "numeric"
    }).format(new Date());
    analyticsRange.addEventListener("change", function () {
        renderActivityChart(window.companiesData || [], window.materialsData || [], window.ordersData || []);
    });

    const notificationButton = document.getElementById("notificationButton");
    const notificationPanel = document.getElementById("notificationPanel");
    notificationButton.addEventListener("click", function () {
        const opening = notificationPanel.hidden;
        notificationPanel.hidden = !opening;
        notificationButton.setAttribute("aria-expanded", String(opening));
    });
    document.addEventListener("click", function (event) {
        if (!event.target.closest(".notification-menu")) {
            notificationPanel.hidden = true;
            notificationButton.setAttribute("aria-expanded", "false");
        }
        const profileMenu = document.getElementById("profileMenu");
        if (!profileMenu.contains(event.target)) profileMenu.open = false;
    });

    const navLinks = Array.from(document.querySelectorAll(".sidebar nav a"));
    const adminLayout = document.querySelector(".admin-layout");
    const sidebarToggle = document.getElementById("sidebarToggle");
    const mobileNavigation = window.matchMedia("(max-width: 760px)");
    function syncSidebarButton() {
        const expanded = mobileNavigation.matches
            ? adminLayout.classList.contains("sidebar-open")
            : !adminLayout.classList.contains("sidebar-collapsed");
        sidebarToggle.setAttribute("aria-expanded", String(expanded));
        sidebarToggle.setAttribute("aria-label", expanded ? "Collapse navigation" : "Expand navigation");
    }
    if (mobileNavigation.matches) {
        adminLayout.classList.remove("sidebar-open");
    }
    syncSidebarButton();
    sidebarToggle.addEventListener("click", function () {
        if (mobileNavigation.matches) {
            adminLayout.classList.toggle("sidebar-open");
        } else {
            adminLayout.classList.toggle("sidebar-collapsed");
        }
        syncSidebarButton();
    });
    mobileNavigation.addEventListener("change", function (event) {
        adminLayout.classList.remove("sidebar-open");
        adminLayout.classList.remove("sidebar-collapsed");
        if (event.matches) adminLayout.classList.remove("sidebar-open");
        syncSidebarButton();
    });

    const sections = navLinks
        .map(link => document.getElementById(link.getAttribute("href").substring(1)))
        .filter(Boolean)
        .sort((a, b) => {
            return a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1;
        });

    function setActive(id) {
        navLinks.forEach(link => {
            link.classList.toggle("active", link.getAttribute("href") === "#" + id);
        });
    }

    navLinks.forEach(link => {
        link.addEventListener("click", function (event) {
            event.preventDefault();

            const id = this.getAttribute("href").substring(1);
            const target = document.getElementById(id);

            if (target) {
                target.scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });

                history.replaceState(null, "", "#" + id);
                setActive(id);
                if (mobileNavigation.matches) {
                    adminLayout.classList.remove("sidebar-open");
                    syncSidebarButton();
                }
            }
        });
    });

    function updateActiveOnScroll() {
        if (!sections.length) return;

        let currentId = sections[0].id;

        sections.forEach(section => {
            if (section.getBoundingClientRect().top <= 120) {
                currentId = section.id;
            }
        });

        const atBottom =
            window.innerHeight + window.scrollY >=
            document.documentElement.scrollHeight - 5;

        if (atBottom) {
            currentId = sections[sections.length - 1].id;
        }

        setActive(currentId);
    }

    window.addEventListener("scroll", updateActiveOnScroll);

    const initialSection = location.hash.substring(1);

    if (initialSection && document.getElementById(initialSection)) {
        document.getElementById(initialSection).scrollIntoView();
        setActive(initialSection);
    } else {
        updateActiveOnScroll();
    }

    const companiesTable = document.getElementById("companiesTable");

    if (companiesTable) {
        companiesTable.addEventListener("click", function (event) {
            const button = event.target.closest("button");
            if (!button) return;

            const id = button.dataset.id;

            const company = window.companiesData?.find(
                c => Number(c.company_id) === Number(id)
            );

            if (button.classList.contains("view-btn") && company) {
                showRecordDetails("Company details", {
                    "Company name": company.company_name,
                    "Email": company.email,
                    "Phone": company.phone,
                    "Address": company.address,
                    "Description": company.description,
                    "Registered": company.created_at
                });
            }

            if (button.classList.contains("edit-btn") && company) {
                document.getElementById("updateCompanyForm").reset();
                document.getElementById("editCompanyId").value = company.company_id;
                document.getElementById("editCompanyName").value = company.company_name;
                document.getElementById("editCompanyEmail").value = company.email;
                document.getElementById("editCompanyPhone").value = company.phone || "";
                document.getElementById("editCompanyAddress").value = company.address || "";
                document.getElementById("editCompanyDescription").value = company.description || "";

                document.getElementById("editCompanyForm").style.display = "block";
                document.getElementById("companyForm").style.display = "none";
            }

            if (
                button.classList.contains("delete-btn") &&
                confirm("Delete " + (company?.company_name || "this company") + "? This action cannot be undone.")
            ) {
                submitDelete("companies", id);
            }
        });
    }

    const materialsTable = document.getElementById("materialsTable");

    if (materialsTable) {
        materialsTable.addEventListener("click", function (event) {
            const button = event.target.closest("button");
            if (!button) return;

            const id = button.dataset.id;

            const material = window.materialsData?.find(
                m => Number(m.material_id) === Number(id)
            );

            if (button.classList.contains("view-btn") && material) {
                showRecordDetails("Offer details", {
                    "Offer name": material.material_name,
                    "Company": material.company_name,
                    "Category": material.category,
                    "Description": material.description,
                    "Quantity": material.quantity,
                    "Price": Number(material.price).toFixed(2) + " IQD"
                });
            }

            if (button.classList.contains("edit-btn") && material) {
                document.getElementById("updateMaterialForm").reset();
                document.getElementById("editMaterialId").value = material.material_id;
                document.getElementById("editMaterialName").value = material.material_name;
                document.getElementById("editMaterialCategory").value = material.category;
                document.getElementById("editMaterialDescription").value = material.description || "";
                document.getElementById("editMaterialQuantity").value = material.quantity;
                document.getElementById("editMaterialPrice").value = material.price;

                document.getElementById("editMaterialForm").style.display = "block";
                document.getElementById("materialForm").style.display = "none";
            }

            if (
                button.classList.contains("delete-btn") &&
                confirm("Delete this offer? This action cannot be undone.")
            ) {
                submitDelete("materials", id);
            }
        });
    }

    const ordersTable = document.getElementById("ordersTable");

    if (ordersTable) {
        ordersTable.addEventListener("click", function (event) {
            const button = event.target.closest("button");
            if (!button) return;

            const id = button.dataset.id;

            if (button.classList.contains("view-btn")) {
                const order = window.ordersData?.find(
                    o => Number(o.order_id) === Number(id)
                );

                if (order) {
                    showRecordDetails("Order details", {
                        "Order ID": order.order_id,
                        "Company": order.buyer_name,
                        "Offers": order.items || "No offer details",
                        "Date": order.order_date,
                        "Quantity": order.item_quantity,
                        "Total": Number(order.order_total).toFixed(2) + " IQD",
                        "Status": order.status
                    });
                }
            }

            if (
                button.classList.contains("delete-btn") &&
                confirm("Delete this order and its order items? This action cannot be undone.")
            ) {
                submitDelete("orders", id);
            }
        });

        ordersTable.addEventListener("submit", async function (event) {
            const form = event.target.closest(".order-status-form");
            if (!form) return;
            event.preventDefault();

            try {
                const response = await fetch(form.action, {
                    method: "POST",
                    body: new FormData(form),
                    headers: { "X-CSRF-Token": csrfToken }
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    showToast(result.message || "Could not update the order.", "error");
                    return;
                }
                showToast(result.message || "Order status updated.");
                await loadAdminData();
            } catch (error) {
                console.error("Order update error:", error);
                showToast("Could not update the order. Please try again.", "error");
            }
        });
    }

    [
        ["addCompanyForm", "companyForm"],
        ["updateCompanyForm", "editCompanyForm"],
        ["addAdminMaterialForm", "materialForm"],
        ["updateMaterialForm", "editMaterialForm"],
        ["adminPasswordForm", "adminPasswordForm"]
    ].forEach(function ([formId, panelId]) {
        const form = document.getElementById(formId);
        if (!form) return;
        form.addEventListener("submit", async function (event) {
            event.preventDefault();
            if (form.dataset.submitting === "true" || !form.reportValidity()) return;
            form.dataset.submitting = "true";
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton ? submitButton.textContent : "";
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add("is-loading");
                submitButton.textContent = "Saving...";
            }
            try {
                const response = await fetch(form.action, {
                    method: "POST",
                    body: new FormData(form),
                    headers: { "X-CSRF-Token": csrfToken }
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    showToast(result.message || "The change could not be saved.", "error");
                    return;
                }
                showToast(result.message || "Changes saved.");
                form.reset();
                if (formId === "adminPasswordForm") {
                    form.classList.remove("is-open");
                    return;
                }
                document.getElementById(panelId).style.display = "none";
                await loadAdminData();
            } catch (error) {
                console.error("Admin form error:", error);
                showToast("The change could not be saved. Please try again.", "error");
            } finally {
                form.dataset.submitting = "false";
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.classList.remove("is-loading");
                    submitButton.textContent = originalText;
                }
            }
        });
    });

    const passwordForm = document.getElementById("adminPasswordForm");
    document.getElementById("changePasswordButton").addEventListener("click", function () {
        document.getElementById("profileMenu").open = false;
        passwordForm.reset();
        passwordForm.classList.add("is-open");
        passwordForm.scrollIntoView({ behavior: "smooth", block: "center" });
    });
    document.getElementById("cancelPasswordChange").addEventListener("click", function () {
        passwordForm.reset();
        passwordForm.classList.remove("is-open");
    });

    document.getElementById("closeSearchModal").addEventListener("click", function () {
        document.getElementById("searchModal").classList.remove("is-visible");
    });
    document.getElementById("searchModal").addEventListener("click", function (event) {
        if (event.target === this) this.classList.remove("is-visible");
    });
    document.getElementById("closeRecordModal").addEventListener("click", function () {
        document.getElementById("recordModal").classList.remove("is-visible");
    });
    document.getElementById("recordModal").addEventListener("click", function (event) {
        if (event.target === this) this.classList.remove("is-visible");
    });
    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            document.querySelectorAll(".modal-overlay.is-visible")
                .forEach(modal => modal.classList.remove("is-visible"));
            notificationPanel.hidden = true;
            notificationButton.setAttribute("aria-expanded", "false");
            document.getElementById("profileMenu").open = false;
        }
    });

    document.querySelectorAll(".table-filter").forEach(function (filter) {
        filter.addEventListener("input", function () {
            if (filter.dataset.table === "orders") {
                tableState.orders.focusedId = "";
            }
            tableState[filter.dataset.table].query = filter.value.trim().toLowerCase();
            tableState[filter.dataset.table].page = 1;
            renderCurrentTable(filter.dataset.table);
        });
    });

    document.getElementById("materialCategoryFilter").addEventListener("change", function () {
        tableState.materials.category = this.value;
        tableState.materials.page = 1;
        renderMaterials(window.materialsData || []);
    });
    document.getElementById("materialAvailabilityFilter").addEventListener("change", function () {
        tableState.materials.availability = this.value;
        tableState.materials.page = 1;
        renderMaterials(window.materialsData || []);
    });
    document.getElementById("materialSort").addEventListener("change", function () {
        tableState.materials.sort = this.value;
        tableState.materials.page = 1;
        renderMaterials(window.materialsData || []);
    });
    document.getElementById("orderStatusFilter").addEventListener("change", function () {
        tableState.orders.status = this.value;
        tableState.orders.focusedId = "";
        tableState.orders.page = 1;
        renderOrders(window.ordersData || []);
    });

    document.getElementById("recentOrdersTable").addEventListener("click", function (event) {
        const link = event.target.closest("[data-open-order]");
        if (link) focusOrder(link.dataset.openOrder);
    });
    document.getElementById("recentOffersList").addEventListener("click", function (event) {
        const link = event.target.closest("[data-open-offer]");
        if (link) focusOffer(link.dataset.openOffer);
    });
    document.getElementById("notificationList").addEventListener("click", function (event) {
        const link = event.target.closest("[data-open-order]");
        if (link) focusOrder(link.dataset.openOrder);
    });

});

async function loadAdminData() {
    try {
        const response = await fetch("FaidProject/admin_data.php");
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || "Failed to load dashboard data.");
        }

        animateAdminCounter(document.getElementById("totalCompanies"), data.totalCompanies);
        animateAdminCounter(document.getElementById("totalMaterials"), data.totalMaterials);
        animateAdminCounter(document.getElementById("totalOrders"), data.totalOrders);
        animateAdminCounter(document.getElementById("pendingOrders"), data.pendingOrders);

        window.companiesData = data.companies;
        window.materialsData = data.materials;
        window.ordersData = data.orders;

        renderDashboardOfferChart(data.materials);
        renderActivityChart(data.companies, data.materials, data.orders);
        renderOrderStatusChart(data.orders);
        renderRecentActivity(data.companies, data.materials, data.orders);
        renderRecentOrders(data.orders);
        renderRecentOffers(data.materials);
        renderNotifications(data.orders);
        renderCompanies(data.companies);
        renderMaterials(data.materials);
        renderOrders(data.orders);
    } catch (error) {
        console.error("Admin data error:", error);
        showToast(error.message || "Could not load dashboard data.", "error");
    }
}

function parseAdminDate(value) {
    if (!value) return null;
    const parsed = new Date(String(value).replace(" ", "T"));
    return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function svgElement(name, attributes) {
    const element = document.createElementNS("http://www.w3.org/2000/svg", name);
    Object.entries(attributes || {}).forEach(([key, value]) => element.setAttribute(key, value));
    return element;
}

function renderActivityChart(companies, materials, orders) {
    const chart = document.getElementById("activityChart");
    const range = document.getElementById("analyticsRange").value;
    const dateLabel = document.getElementById("activityDateLabel");
    const labels = { "7": "Last 7 days", "30": "Last 30 days", "90": "Last 90 days", "365": "Last 12 months", all: "All available data" };
    dateLabel.textContent = labels[range] || labels["30"];
    chart.setAttribute("aria-label", "Company registrations, published offers, and orders placed: " + dateLabel.textContent);

    const groups = [
        { descriptor: chartSeries[0], records: companies },
        { descriptor: chartSeries[1], records: materials },
        { descriptor: chartSeries[2], records: orders }
    ];
    const datedRecords = groups.flatMap(group => group.records.map(record => ({
        date: parseAdminDate(record[group.descriptor.dateKey]),
        series: group.descriptor.key
    }))).filter(record => record.date);
    chart.replaceChildren();
    if (!datedRecords.length) {
        const empty = document.createElement("p");
        empty.className = "empty-state";
        empty.textContent = "No dated platform activity is available yet.";
        chart.appendChild(empty);
        return;
    }

    const end = new Date();
    end.setHours(23, 59, 59, 999);
    const earliest = new Date(Math.min(...datedRecords.map(record => record.date.getTime())));
    const start = range === "all" ? earliest : new Date(end.getTime() - Number(range) * 86400000);
    const visible = datedRecords.filter(record => record.date >= start && record.date <= end);
    if (!visible.length) {
        const empty = document.createElement("p");
        empty.className = "empty-state";
        empty.textContent = "No activity was recorded in this time window.";
        chart.appendChild(empty);
        return;
    }

    const bucketCount = range === "7" ? 7 : (range === "30" ? 10 : 12);
    const span = Math.max(1, end.getTime() - start.getTime());
    const values = {};
    chartSeries.forEach(series => { values[series.key] = Array(bucketCount).fill(0); });
    visible.forEach(record => {
        const index = Math.min(bucketCount - 1, Math.floor(((record.date.getTime() - start.getTime()) / span) * bucketCount));
        values[record.series][Math.max(0, index)]++;
    });

    const width = 820;
    const height = 270;
    const padding = { top: 18, right: 18, bottom: 42, left: 40 };
    const plotWidth = width - padding.left - padding.right;
    const plotHeight = height - padding.top - padding.bottom;
    const maximum = Math.max(1, ...Object.values(values).flat());
    const svg = svgElement("svg", {
        viewBox: "0 0 " + width + " " + height,
        preserveAspectRatio: "none",
        role: "img",
        "aria-label": chart.getAttribute("aria-label")
    });
    const title = svgElement("title");
    title.textContent = "Activity counts for companies, offers, and orders during " + dateLabel.textContent.toLowerCase();
    svg.appendChild(title);

    for (let tick = 0; tick <= 3; tick++) {
        const y = padding.top + (plotHeight * tick / 3);
        const grid = svgElement("line", {
            x1: padding.left, y1: y, x2: width - padding.right, y2: y,
            class: "activity-grid-line"
        });
        const axisLabel = svgElement("text", {
            x: padding.left - 10, y: y + 4, "text-anchor": "end",
            class: "activity-axis-label"
        });
        axisLabel.textContent = String(Math.round(maximum * (1 - tick / 3)));
        svg.append(grid, axisLabel);
    }

    chartSeries.forEach(series => {
        const points = values[series.key].map((count, index) => {
            const x = padding.left + (bucketCount === 1 ? 0 : (index / (bucketCount - 1)) * plotWidth);
            const y = padding.top + plotHeight - (count / maximum) * plotHeight;
            return { x, y, count, index };
        });
        const path = svgElement("path", {
            d: points.map((point, index) => (index ? "L" : "M") + point.x.toFixed(2) + " " + point.y.toFixed(2)).join(" "),
            class: "activity-line",
            stroke: series.color
        });
        path.setAttribute("aria-label", series.label);
        svg.appendChild(path);
        points.forEach(point => {
            const marker = svgElement("circle", {
                cx: point.x, cy: point.y, r: "3.5",
                class: "activity-point",
                fill: series.color
            });
            const pointTitle = svgElement("title");
            const bucketDate = new Date(start.getTime() + ((point.index + 0.5) / bucketCount) * span);
            pointTitle.textContent = series.label + ": " + point.count + " · " +
                new Intl.DateTimeFormat(undefined, { month: "short", day: "numeric" }).format(bucketDate);
            marker.appendChild(pointTitle);
            svg.appendChild(marker);
        });
    });

    const labelCount = Math.min(5, bucketCount);
    for (let labelIndex = 0; labelIndex < labelCount; labelIndex++) {
        const bucket = Math.round(labelIndex * (bucketCount - 1) / Math.max(1, labelCount - 1));
        const x = padding.left + (bucketCount === 1 ? 0 : (bucket / (bucketCount - 1)) * plotWidth);
        const labelDate = new Date(start.getTime() + ((bucket + 0.5) / bucketCount) * span);
        const axisLabel = svgElement("text", {
            x, y: height - 12, "text-anchor": "middle",
            class: "activity-axis-label"
        });
        axisLabel.textContent = new Intl.DateTimeFormat(undefined, { month: "short", day: "numeric" }).format(labelDate);
        svg.appendChild(axisLabel);
    }
    chart.appendChild(svg);
}

function renderOrderStatusChart(orders) {
    const chart = document.getElementById("orderStatusChart");
    const legend = document.getElementById("orderStatusLegend");
    const counts = new Map();
    orders.forEach(order => {
        const status = String(order.status || "Unknown");
        counts.set(status, (counts.get(status) || 0) + 1);
    });
    const statusColors = {
        Pending: chartColors.yellow,
        Approved: chartColors.blue,
        Rejected: chartColors.sky,
        Completed: chartColors.navy
    };
    const entries = Array.from(counts.entries());
    chart.replaceChildren();
    legend.replaceChildren();
    if (!entries.length) {
        const empty = document.createElement("p");
        empty.className = "empty-state";
        empty.textContent = "No orders have been placed yet.";
        chart.appendChild(empty);
        return;
    }

    const total = orders.length;
    const radius = 48;
    const circumference = 2 * Math.PI * radius;
    const svg = svgElement("svg", {
        viewBox: "0 0 140 140",
        role: "img",
        "aria-label": entries.map(([status, count]) => status + ": " + count).join(", ")
    });
    const background = svgElement("circle", {
        cx: 70, cy: 70, r: radius, fill: "none",
        stroke: chartColors.sky, "stroke-width": 15
    });
    svg.appendChild(background);
    let offset = 0;
    entries.forEach(([status, count]) => {
        const length = (count / total) * circumference;
        const segment = svgElement("circle", {
            cx: 70, cy: 70, r: radius, fill: "none",
            stroke: statusColors[status] || chartColors.sky,
            "stroke-width": 15,
            "stroke-dasharray": length + " " + (circumference - length),
            "stroke-dashoffset": -offset,
            class: "donut-segment"
        });
        const title = svgElement("title");
        title.textContent = status + ": " + count + " orders";
        segment.appendChild(title);
        svg.appendChild(segment);
        offset += length;

        const item = document.createElement("button");
        item.type = "button";
        item.className = "order-legend-item";
        item.dataset.status = status;
        const swatch = document.createElement("i");
        swatch.setAttribute("aria-hidden", "true");
        swatch.style.backgroundColor = statusColors[status] || chartColors.sky;
        const statusLabel = document.createElement("span");
        statusLabel.textContent = status;
        const statusCount = document.createElement("strong");
        statusCount.textContent = count.toLocaleString();
        item.append(swatch, statusLabel, statusCount);
        item.addEventListener("click", function () {
            const select = document.getElementById("orderStatusFilter");
            if ([...select.options].some(option => option.value === status)) {
                select.value = status;
                tableState.orders.status = status;
                tableState.orders.page = 1;
                renderOrders(window.ordersData || []);
                document.getElementById("orders").scrollIntoView({ behavior: "smooth" });
            }
        });
        legend.appendChild(item);
    });
    const center = svgElement("text", { x: 70, y: 66, "text-anchor": "middle", class: "donut-total" });
    center.textContent = total.toLocaleString();
    const centerLabel = svgElement("text", { x: 70, y: 82, "text-anchor": "middle", class: "donut-caption" });
    centerLabel.textContent = "orders";
    svg.append(center, centerLabel);
    chart.appendChild(svg);
}

function renderRecentActivity(companies, materials, orders) {
    const timeline = document.getElementById("activityTimeline");
    const events = [
        ...companies.map(record => ({ type: "Company registered", title: record.company_name, date: record.created_at })),
        ...materials.map(record => ({ type: "Offer published", title: record.material_name, date: record.created_at })),
        ...orders.map(record => ({ type: "Order received", title: record.buyer_name + " · " + (record.items || "Material request"), date: record.order_date }))
    ].map(event => ({ ...event, parsedDate: parseAdminDate(event.date) }))
        .filter(event => event.parsedDate)
        .sort((a, b) => b.parsedDate - a.parsedDate)
        .slice(0, 7);
    timeline.replaceChildren();
    if (!events.length) {
        const empty = document.createElement("p");
        empty.className = "empty-state";
        empty.textContent = "Activity will appear here as records are created.";
        timeline.appendChild(empty);
        return;
    }
    events.forEach(event => {
        const item = document.createElement("article");
        item.className = "timeline-item";
        const mark = document.createElement("span");
        mark.className = "timeline-mark";
        mark.setAttribute("aria-hidden", "true");
        const content = document.createElement("div");
        const title = document.createElement("strong");
        title.textContent = event.title || event.type;
        const detail = document.createElement("span");
        detail.textContent = event.type;
        const time = document.createElement("time");
        time.dateTime = event.parsedDate.toISOString();
        time.textContent = event.parsedDate.toLocaleString(undefined, { month: "short", day: "numeric", hour: "numeric", minute: "2-digit" });
        content.append(title, detail);
        item.append(mark, content, time);
        timeline.appendChild(item);
    });
}

function renderRecentOrders(orders) {
    const table = document.getElementById("recentOrdersTable");
    table.replaceChildren();
    const recent = orders.slice().sort((a, b) =>
        String(b.order_date || "").localeCompare(String(a.order_date || ""))).slice(0, 5);
    if (!recent.length) {
        const row = document.createElement("tr");
        const cell = document.createElement("td");
        cell.colSpan = 6;
        cell.className = "empty-table-state";
        cell.textContent = "No orders have been received yet.";
        row.appendChild(cell);
        table.appendChild(row);
        return;
    }
    recent.forEach(order => {
        const row = document.createElement("tr");
        const cells = [order.order_id, order.buyer_name, order.items || "Material request", order.item_quantity];
        cells.forEach(value => {
            const cell = document.createElement("td");
            cell.textContent = value ?? "";
            row.appendChild(cell);
        });
        const status = document.createElement("td");
        const badge = document.createElement("span");
        badge.className = "status-pill status-" + String(order.status || "").toLowerCase();
        badge.textContent = order.status || "Unknown";
        status.appendChild(badge);
        row.appendChild(status);
        const action = document.createElement("td");
        const link = document.createElement("a");
        link.href = "#orders";
        link.className = "table-link";
        link.dataset.openOrder = order.order_id;
        link.textContent = "Review";
        action.appendChild(link);
        row.appendChild(action);
        table.appendChild(row);
    });
}

function renderRecentOffers(materials) {
    const list = document.getElementById("recentOffersList");
    list.replaceChildren();
    const recent = materials.slice().sort((a, b) =>
        String(b.created_at || "").localeCompare(String(a.created_at || ""))).slice(0, 4);
    if (!recent.length) {
        const empty = document.createElement("p");
        empty.className = "empty-state";
        empty.textContent = "Published offers will appear here.";
        list.appendChild(empty);
        return;
    }
    recent.forEach(material => {
        const link = document.createElement("a");
        link.href = "#materials";
        link.className = "recent-offer-item";
        link.dataset.openOffer = material.material_name || "";
        const imagePath = materialImageUrl(material.image_path);
        if (imagePath) {
            const image = document.createElement("img");
            image.src = imagePath;
            image.alt = "";
            image.loading = "lazy";
            image.onerror = () => image.replaceWith(makeAdminImagePlaceholder());
            link.appendChild(image);
        } else {
            link.appendChild(makeAdminImagePlaceholder());
        }
        const copy = document.createElement("span");
        copy.className = "recent-offer-copy";
        const name = document.createElement("strong");
        name.textContent = material.material_name || "Untitled offer";
        const details = document.createElement("small");
        details.textContent = String(material.category || "").replace(/^./, letter => letter.toUpperCase()) +
            " · " + Number(material.available_quantity ?? material.quantity ?? 0).toLocaleString() + " available";
        copy.append(name, details);
        const arrow = document.createElement("span");
        arrow.className = "recent-offer-arrow";
        arrow.setAttribute("aria-hidden", "true");
        arrow.textContent = "→";
        link.append(copy, arrow);
        list.appendChild(link);
    });
}

function makeAdminImagePlaceholder() {
    const placeholder = document.createElement("span");
    placeholder.className = "admin-image-placeholder";
    placeholder.setAttribute("aria-hidden", "true");
    placeholder.textContent = "FAID";
    return placeholder;
}

function renderNotifications(orders) {
    const pending = orders.filter(order => order.status === "Pending")
        .sort((a, b) => String(b.order_date || "").localeCompare(String(a.order_date || "")));
    const count = document.getElementById("notificationCount");
    const button = document.getElementById("notificationButton");
    const summary = document.getElementById("notificationSummary");
    const list = document.getElementById("notificationList");
    count.textContent = pending.length > 99 ? "99+" : String(pending.length);
    count.hidden = pending.length === 0;
    button.setAttribute("aria-label", pending.length
        ? "Notifications, " + pending.length + " pending orders"
        : "Notifications, no pending orders");
    summary.textContent = pending.length
        ? pending.length + (pending.length === 1 ? " pending order" : " pending orders")
        : "No pending orders";
    list.replaceChildren();
    if (!pending.length) {
        const empty = document.createElement("p");
        empty.className = "empty-state";
        empty.textContent = "You are all caught up.";
        list.appendChild(empty);
        return;
    }
    pending.slice(0, 5).forEach(order => {
        const link = document.createElement("a");
        link.href = "#orders";
        link.className = "notification-item";
        link.dataset.openOrder = order.order_id;
        const dot = document.createElement("i");
        dot.setAttribute("aria-hidden", "true");
        const copy = document.createElement("span");
        const company = document.createElement("strong");
        company.textContent = order.buyer_name || "Company";
        const detail = document.createElement("small");
        detail.textContent = "Order #" + order.order_id + " · " + (order.items || "Material request");
        copy.append(company, detail);
        link.append(dot, copy);
        list.appendChild(link);
    });
}

function focusOrder(orderId) {
    const notificationPanel = document.getElementById("notificationPanel");
    notificationPanel.hidden = true;
    document.getElementById("notificationButton").setAttribute("aria-expanded", "false");
    const filter = document.querySelector('[data-table="orders"]');
    const status = document.getElementById("orderStatusFilter");
    filter.value = String(orderId);
    status.value = "";
    tableState.orders.query = String(orderId).toLowerCase();
    tableState.orders.status = "";
    tableState.orders.focusedId = String(orderId);
    tableState.orders.page = 1;
    renderOrders(window.ordersData || []);
    document.getElementById("orders").scrollIntoView({ behavior: "smooth" });
}

function focusOffer(offerName) {
    document.getElementById("profileMenu").open = false;
    const filter = document.querySelector('[data-table="materials"]');
    filter.value = offerName;
    document.getElementById("materialCategoryFilter").value = "";
    document.getElementById("materialAvailabilityFilter").value = "";
    tableState.materials.query = String(offerName || "").toLowerCase();
    tableState.materials.category = "";
    tableState.materials.availability = "";
    tableState.materials.page = 1;
    renderMaterials(window.materialsData || []);
    document.getElementById("materials").scrollIntoView({ behavior: "smooth" });
}

function renderDashboardOfferChart(materials) {
    const chart = document.getElementById("dashboardOfferChart");
    const labels = {
        wood: "Wooden Materials",
        metal: "Iron and Metals",
        packaging: "Packaging Materials and Cartons",
        plastic: "Plastic Materials",
        electrical: "Electrical Materials"
    };
    const counts = {};
    materials.forEach(material => {
        counts[material.category] = (counts[material.category] || 0) + 1;
    });
    const categories = Object.keys(labels)
        .filter(category => counts[category])
        .map(category => ({ category, label: labels[category], count: counts[category] }));
    chart.replaceChildren();

    if (!categories.length) {
        const empty = document.createElement("p");
        empty.className = "empty-state";
        empty.textContent = "No offers have been published yet.";
        chart.appendChild(empty);
        return;
    }

    const maxCount = Math.max(...categories.map(item => item.count));
    categories.forEach(item => {
        const row = document.createElement("button");
        row.type = "button";
        row.className = "category-chart-row";
        row.setAttribute("aria-label", item.label + ": " + item.count + " offers. Filter offers table.");
        const label = document.createElement("span");
        label.className = "category-chart-label";
        label.textContent = item.label;
        const track = document.createElement("span");
        track.className = "category-chart-track";
        const bar = document.createElement("span");
        bar.className = "category-chart-bar";
        bar.style.width = (item.count / maxCount * 100) + "%";
        track.appendChild(bar);
        const value = document.createElement("span");
        value.className = "category-chart-value";
        value.textContent = String(item.count);
        row.append(label, track, value);
        row.addEventListener("click", function () {
            document.querySelector('[data-table="materials"]').value = "";
            document.getElementById("materialCategoryFilter").value = item.category;
            tableState.materials.query = "";
            tableState.materials.category = item.category;
            tableState.materials.page = 1;
            renderMaterials(window.materialsData || []);
            document.getElementById("materials").scrollIntoView({ behavior: "smooth" });
        });
        chart.appendChild(row);
    });
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
    const companySelect = document.getElementById("adminMaterialCompany");
    if (companySelect) {
        const selectedCompany = companySelect.value;
        companySelect.replaceChildren();
        const placeholder = document.createElement("option");
        placeholder.value = "";
        placeholder.textContent = companies.length ? "Select a company" : "No companies available";
        companySelect.appendChild(placeholder);

        companies.forEach(company => {
            const option = document.createElement("option");
            option.value = company.company_id;
            option.textContent = company.company_name;
            option.selected = String(company.company_id) === selectedCompany;
            companySelect.appendChild(option);
        });
    }

    renderPaginatedTable("companies", companies, [
        "company_name", "email", "phone", "address", "description"
    ], function (company) {
        const row = document.createElement("tr");

        [company.company_name, company.email].forEach(value => {
            const cell = document.createElement("td");

            cell.textContent = value || "";
            row.appendChild(cell);
        });

        const statusCell = document.createElement("td");
        const status = document.createElement("span");
        status.className = "status-pill status-registered";
        status.textContent = "Registered";
        statusCell.appendChild(status);
        row.appendChild(statusCell);

        const action = document.createElement("td");

        action.append(
            makeButton("View", "view-btn", company.company_id),
            makeButton("Edit", "edit-btn", company.company_id),
            makeButton("Delete", "delete-btn", company.company_id)
        );

        row.appendChild(action);
        return row;
    }, 4);
}

function renderMaterials(materials) {
    renderPaginatedTable("materials", materials, [
        "material_name", "company_name", "category", "description", "quantity", "price"
    ], function (material) {
        const row = document.createElement("tr");

        const imageCell = document.createElement("td");
        const imageUrl = materialImageUrl(material.image_path);
        if (imageUrl) {
            const image = document.createElement("img");
            image.className = "table-thumb";
            image.src = imageUrl;
            image.alt = material.material_name ? "Image of " + material.material_name : "Offer image";
            image.loading = "lazy";
            image.onerror = () => image.replaceWith(makeAdminImagePlaceholder());
            imageCell.appendChild(image);
        } else {
            imageCell.appendChild(makeAdminImagePlaceholder());
        }
        row.appendChild(imageCell);

        [
            material.material_name,
            material.company_name,
            String(material.category || "").replace(/^./, char => char.toUpperCase()),
            Number(material.price).toFixed(2) + " IQD"
        ].forEach(value => {
            const cell = document.createElement("td");

            cell.textContent = value ?? "";
            row.appendChild(cell);
        });

        const action = document.createElement("td");

        action.append(
            makeButton("Offer Details", "view-btn", material.material_id),
            makeButton("Edit", "edit-btn", material.material_id),
            makeButton("Delete", "delete-btn", material.material_id)
        );

        row.appendChild(action);
        return row;
    }, 6);
}

function renderOrders(orders) {
    renderPaginatedTable("orders", orders, [
        "order_id", "buyer_name", "items", "order_date", "status"
    ], function (order) {
        const row = document.createElement("tr");
        row.dataset.orderId = String(order.order_id);

        [
            order.order_id,
            order.buyer_name,
            order.items || "No offer details",
            order.order_date,
            order.item_quantity,
            Number(order.order_total).toFixed(2) + " IQD"
        ].forEach(value => {
            const cell = document.createElement("td");

            cell.textContent = value ?? "";
            row.appendChild(cell);
        });

        const statusCell = document.createElement("td");
        const statusBadge = document.createElement("span");
        statusBadge.className = "status-pill status-" + String(order.status).toLowerCase();
        statusBadge.textContent = order.status;
        statusCell.appendChild(statusBadge);
        row.appendChild(statusCell);

        const action = document.createElement("td");

        action.append(makeButton("View", "view-btn", order.order_id));

        const form = document.createElement("form");

        form.method = "POST";
        form.action = "FaidProject/update_order.php";
        form.className = "order-status-form";
        form.appendChild(makeHiddenInput("_csrf_token",
            document.querySelector('meta[name="csrf-token"]')?.content || ""));

        const hidden = document.createElement("input");

        hidden.type = "hidden";
        hidden.name = "order_id";
        hidden.value = order.order_id;

        const select = document.createElement("select");

        select.name = "status";

        const transitions = {
            Pending: ["Pending", "Approved", "Rejected", "Completed"],
            Approved: ["Approved", "Rejected", "Completed"],
            Rejected: ["Rejected"],
            Completed: ["Completed"]
        };
        (transitions[order.status] || [order.status]).forEach(status => {
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
        return row;
    }, 8);
}

function makeHiddenInput(name, value) {
    const input = document.createElement("input");
    input.type = "hidden";
    input.name = name;
    input.value = value;
    return input;
}

async function submitDelete(type, id) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";
    try {
        const response = await fetch("FaidProject/delete_item.php", {
            method: "POST",
            body: new URLSearchParams({ type: type, id: id }),
            headers: { "X-CSRF-Token": csrfToken }
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
            showToast(result.message || "Could not delete the record.", "error");
            return;
        }
        showToast(result.message || "Record deleted.");
        await loadAdminData();
    } catch (error) {
        console.error("Delete error:", error);
        showToast("Could not delete the record. Please try again.", "error");
    }
}

function openCompanyForm() {
    document.getElementById("addCompanyForm").reset();
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
    document.getElementById("addAdminMaterialForm").reset();
    document.getElementById("materialForm").style.display = "block";
    document.getElementById("editMaterialForm").style.display = "none";
}

function closeMaterialForm() {
    document.getElementById("materialForm").style.display = "none";
}

function closeEditMaterialForm() {
    document.getElementById("editMaterialForm").style.display = "none";
}
