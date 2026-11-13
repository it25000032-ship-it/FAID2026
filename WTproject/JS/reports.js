document.addEventListener("DOMContentLoaded", function () {

    const reportType = document.getElementById("reportType");
    const startDate = document.getElementById("startDate");
    const endDate = document.getElementById("endDate");
    const targetTable = document.getElementById("targetTable");

    const totalListings = document.getElementById("totalListings");
    const totalQuantity = document.getElementById("totalQuantity");
    const totalSavings = document.getElementById("totalSavings");
    const totalPurchases = document.getElementById("totalPurchases");

    const summaryReportType = document.getElementById("summaryReportType");
    const summaryStartDate = document.getElementById("summaryStartDate");
    const summaryEndDate = document.getElementById("summaryEndDate");

    const reportTableHead = document.getElementById("reportTableHead");
    const reportTableBody = document.getElementById("reportTableBody");

    window.generateReport = async function () {

        const type = reportType.value;
        const category = targetTable.value;

        if (type === "custom") {
            if (!startDate.value || !endDate.value) {
                alert("Please select start date and end date.");
                return;
            }

            if (startDate.value > endDate.value) {
                alert("Start date must be before end date.");
                return;
            }
        }

        const params = new URLSearchParams({
            report_type: type,
            target_table: category,
            start_date: startDate.value,
            end_date: endDate.value
        });

        try {
            const response = await fetch("FaidProject/report_data.php?" + params);
            const result = await response.json();

            if (!result.success) {
                alert(result.message || "Failed to generate report.");
                return;
            }

            // الأرقام جوّة summary مو بره
            const summary = result.summary || {};

            totalListings.textContent = summary.total_records ?? 0;
            totalQuantity.textContent = summary.total_quantity ?? 0;
            totalSavings.textContent = summary.total_savings ?? "Not Available";
            totalPurchases.textContent = summary.total_purchases ?? 0;

            summaryReportType.textContent = type;
            summaryStartDate.textContent = startDate.value || "---";
            summaryEndDate.textContent = endDate.value || "---";

            displayReportTable(result.data, category);

        } catch (error) {
            console.error("Report Error:", error);
            alert("Error connecting to reports.php. Check the file path and PHP errors.");
        }
    };

    function displayReportTable(data, category) {

        reportTableHead.innerHTML = "";
        reportTableBody.innerHTML = "";

        if (!data || data.length === 0) {
            reportTableBody.innerHTML =
                '<tr><td colspan="10">No data found.</td></tr>';
            return;
        }

        const columns = Object.keys(data[0]);

        const headerRow = document.createElement("tr");

        columns.forEach(function (column) {
            const th = document.createElement("th");
            th.textContent = column.replaceAll("_", " ");
            headerRow.appendChild(th);
        });

        reportTableHead.appendChild(headerRow);

        data.forEach(function (item) {
            const row = document.createElement("tr");

            columns.forEach(function (column) {
                const td = document.createElement("td");
                td.textContent = item[column] ?? "";
                row.appendChild(td);
            });

            reportTableBody.appendChild(row);
        });
    }

    reportType.addEventListener("change", function () {
        const isCustom = this.value === "custom";

        startDate.disabled = !isCustom;
        endDate.disabled = !isCustom;
    });

    startDate.disabled = true;
    endDate.disabled = true;
});