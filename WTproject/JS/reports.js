document.addEventListener("DOMContentLoaded", function () {
    const reportType = document.getElementById("reportType");
    const startDate = document.getElementById("startDate");
    const endDate = document.getElementById("endDate");
    const targetTable = document.getElementById("targetTable");
    const totalRecordsLabel = document.getElementById("totalRecordsLabel");
    const totalListings = document.getElementById("totalListings");
    const totalQuantityLabel = document.getElementById("totalQuantityLabel");
    const totalQuantity = document.getElementById("totalQuantity");
    const totalSavings = document.getElementById("totalSavings");
    const totalPurchasesLabel = document.getElementById("totalPurchasesLabel");
    const totalPurchases = document.getElementById("totalPurchases");
    const summaryReportType = document.getElementById("summaryReportType");
    const summaryPeriod = document.getElementById("summaryPeriod");
    const reportTableHead = document.getElementById("reportTableHead");
    const reportTableBody = document.getElementById("reportTableBody");
    const reportChart = document.getElementById("reportChart");
    const reportSearch = document.getElementById("reportTableSearch");
    const reportCount = document.getElementById("reportTableCount");
    const reportPagination = document.getElementById("reportPagination");
    const clearChartFilter = document.getElementById("clearReportChartFilter");
    const pageSize = 10;
    let reportRows = [];
    let currentPage = 1;
    let chartFilter = null;

    const periodLabels = {
        daily: "Today",
        weekly: "Last 7 days",
        monthly: "Past month",
        biannual: "Last 6 months",
        yearly: "Last 12 months",
        custom: "Custom date range"
    };
    const columnLabels = {
        material_id: "Offer ID",
        company_id: "Company ID",
        material_name: "Offer name",
        category: "Category",
        description: "Description",
        quantity: "Quantity",
        price: "Price",
        company_name: "Company",
        created_at: "Created",
        order_id: "Order ID",
        buyer_id: "Buyer ID",
        buyer_name: "Company",
        order_date: "Order date",
        status: "Status",
        item_quantity: "Order quantity",
        order_total: "Order total",
        email: "Email",
        phone: "Phone",
        address: "Address"
    };
    const categoryLabels = {
        wood: "Wooden Materials",
        metal: "Iron and Metals",
        packaging: "Packaging Materials and Cartons",
        plastic: "Plastic Materials",
        electrical: "Electrical Materials"
    };

    window.generateReport = async function () {
        const type = reportType.value;
        const category = targetTable.value;
        if (type === "custom" && (!startDate.value || !endDate.value)) {
            showToast("Please select a start date and end date.", "error");
            return;
        }
        if (type === "custom" && startDate.value > endDate.value) {
            showToast("Start date must be on or before the end date.", "error");
            return;
        }

        const button = document.querySelector(".generate-btn");
        const originalText = button.textContent;
        button.disabled = true;
        button.classList.add("is-loading");
        button.textContent = "Generating...";
        const params = new URLSearchParams({
            report_type: type,
            target_table: category,
            start_date: startDate.value,
            end_date: endDate.value
        });

        try {
            const response = await fetch("FaidProject/report_data.php?" + params);
            const result = await response.json();
            if (!response.ok || !result.success) {
                showToast(result.message || "Failed to generate report.", "error");
                return;
            }

            const summary = result.summary;
            summaryReportType.textContent = periodLabels[type] || type;
            const recordLabels = {
                materials: "Total Offers",
                companies: "Total Companies",
                orders: "Total Orders"
            };
            totalRecordsLabel.textContent = recordLabels[category] || "Total Records";
            totalListings.textContent = summary.total_records ?? 0;

            if (category === "companies") {
                totalQuantityLabel.textContent = "Quantity";
                totalQuantity.textContent = "Not applicable";
                totalPurchasesLabel.textContent = "Completed Orders";
                totalPurchases.textContent = "Not applicable";
            } else {
                totalQuantityLabel.textContent =
                    category === "orders" ? "Quantity in Orders" : "Offered Quantity";
                totalQuantity.textContent = summary.total_quantity ?? 0;
                totalPurchasesLabel.textContent = "Completed Orders";
                totalPurchases.textContent =
                    category === "orders" ? (summary.total_purchases ?? 0) : "Not applicable";
            }

            totalSavings.textContent = summary.total_savings ?? "Not available";
            summaryPeriod.textContent = type === "custom"
                ? startDate.value + " through " + endDate.value
                : (periodLabels[type] || type);
            reportRows = Array.isArray(result.data) ? result.data : [];
            currentPage = 1;
            chartFilter = null;
            reportSearch.value = "";
            clearChartFilter.hidden = true;
            renderChart(category, reportRows);
            renderReportTable();
            showToast("Report generated.");
        } catch (error) {
            console.error("Report error:", error);
            showToast("Could not load the report. Please try again.", "error");
        } finally {
            button.disabled = false;
            button.classList.remove("is-loading");
            button.textContent = originalText;
        }
    };

    function renderChart(category, rows) {
        reportChart.replaceChildren();
        if (rows.length === 0) {
            const empty = document.createElement("p");
            empty.className = "empty-state";
            empty.textContent = "No records in this reporting period.";
            reportChart.appendChild(empty);
            return;
        }

        let groupKey;
        let groups = new Map();
        if (category === "materials") {
            groupKey = "category";
            rows.forEach(row => {
                const key = String(row.category || "Uncategorized");
                groups.set(key, (groups.get(key) || 0) + 1);
            });
        } else if (category === "orders") {
            groupKey = "status";
            rows.forEach(row => {
                const key = String(row.status || "Unknown");
                groups.set(key, (groups.get(key) || 0) + 1);
            });
        } else {
            groupKey = "created_at";
            rows.forEach(row => {
                const key = String(row.created_at || "").slice(0, 10) || "Unknown date";
                groups.set(key, (groups.get(key) || 0) + 1);
            });
        }

        const items = Array.from(groups.entries())
            .sort((a, b) => b[1] - a[1] || a[0].localeCompare(b[0]))
            .slice(0, 8);
        const maximum = Math.max(...items.map(item => item[1]));
        items.forEach(([value, count]) => {
            const label = category === "materials"
                ? (categoryLabels[value] || value)
                : value;
            const button = document.createElement("button");
            button.type = "button";
            button.className = "category-chart-row";
            button.setAttribute("aria-pressed", "false");
            button.setAttribute("aria-label", label + ": " + count + " records. Filter report.");
            button.title = label + ": " + count + (count === 1 ? " record" : " records") + ". Click to filter the report.";
            const text = document.createElement("span");
            text.className = "category-chart-label";
            text.textContent = label;
            const track = document.createElement("span");
            track.className = "category-chart-track";
            const bar = document.createElement("span");
            bar.className = "category-chart-bar";
            bar.style.width = (count / maximum * 100) + "%";
            track.appendChild(bar);
            const number = document.createElement("span");
            number.className = "category-chart-value";
            number.textContent = String(count);
            button.append(text, track, number);
            button.addEventListener("click", function () {
                const isSameFilter = chartFilter &&
                    chartFilter.key === groupKey &&
                    chartFilter.value === value;
                chartFilter = isSameFilter ? null : { key: groupKey, value: value };
                currentPage = 1;
                clearChartFilter.hidden = !chartFilter;
                reportChart.querySelectorAll(".category-chart-row").forEach(rowButton => {
                    rowButton.setAttribute("aria-pressed", String(rowButton === button && Boolean(chartFilter)));
                });
                renderReportTable();
            });
            reportChart.appendChild(button);
        });
    }

    function getFilteredRows() {
        const term = reportSearch.value.trim().toLowerCase();
        return reportRows.filter(row => {
            const matchesChart = !chartFilter ||
                String(row[chartFilter.key] || "").slice(0, 10) === chartFilter.value;
            const matchesSearch = !term ||
                Object.values(row).some(value => String(value ?? "").toLowerCase().includes(term));
            return matchesChart && matchesSearch;
        });
    }

    function renderReportTable() {
        const filtered = getFilteredRows();
        const pages = Math.max(1, Math.ceil(filtered.length / pageSize));
        currentPage = Math.min(currentPage, pages);
        const visibleRows = filtered.slice((currentPage - 1) * pageSize, currentPage * pageSize);
        reportTableHead.replaceChildren();
        reportTableBody.replaceChildren();
        reportPagination.replaceChildren();
        reportCount.textContent = filtered.length + (filtered.length === 1 ? " record" : " records");

        if (visibleRows.length === 0) {
            const row = document.createElement("tr");
            const cell = document.createElement("td");
            cell.colSpan = Math.max(1, Object.keys(reportRows[0] || {}).length);
            cell.className = "empty-table-state";
            cell.textContent = reportRows.length ? "No report rows match these filters." : "No data found.";
            row.appendChild(cell);
            reportTableBody.appendChild(row);
            return;
        }

        const columns = Object.keys(visibleRows[0]);
        const headerRow = document.createElement("tr");
        columns.forEach(column => {
            const cell = document.createElement("th");
            cell.textContent = columnLabels[column] || column.replaceAll("_", " ");
            headerRow.appendChild(cell);
        });
        reportTableHead.appendChild(headerRow);

        visibleRows.forEach(item => {
            const row = document.createElement("tr");
            columns.forEach(column => {
                const cell = document.createElement("td");
                cell.textContent = item[column] ?? "";
                row.appendChild(cell);
            });
            reportTableBody.appendChild(row);
        });

        if (filtered.length > pageSize) {
            const summary = document.createElement("span");
            summary.textContent = "Page " + currentPage + " of " + pages;
            const previous = document.createElement("button");
            previous.type = "button";
            previous.textContent = "Previous";
            previous.disabled = currentPage <= 1;
            previous.addEventListener("click", function () {
                currentPage--;
                renderReportTable();
            });
            const next = document.createElement("button");
            next.type = "button";
            next.textContent = "Next";
            next.disabled = currentPage >= pages;
            next.addEventListener("click", function () {
                currentPage++;
                renderReportTable();
            });
            reportPagination.append(summary, previous, next);
        }
    }

    clearChartFilter.addEventListener("click", function () {
        chartFilter = null;
        clearChartFilter.hidden = true;
        reportChart.querySelectorAll(".category-chart-row")
            .forEach(button => button.setAttribute("aria-pressed", "false"));
        renderReportTable();
    });

    reportSearch.addEventListener("input", function () {
        currentPage = 1;
        renderReportTable();
    });

    reportType.addEventListener("change", function () {
        const custom = this.value === "custom";
        startDate.disabled = !custom;
        endDate.disabled = !custom;
    });

    startDate.disabled = true;
    endDate.disabled = true;
});
