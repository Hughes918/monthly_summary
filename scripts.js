// DataTable configuration
const DATA_TABLE_CONFIG = {
    scrollY: "500px",
    scrollX: true,
    paging: false,
    searching: false,
    sorting: false,
    info: false,
    autoWidth: true
};

// Helper to set dropdown value
function setDropdownValue(dropdownId, value) {
    const dropdown = document.getElementById(dropdownId);
    if (dropdown) {
        const option = Array.from(dropdown.options).find(opt => opt.value.toUpperCase() === value.toUpperCase());
        if (option) option.selected = true;
    }
}

// Get query params
function getQueryParams() {
    const params = new URLSearchParams(window.location.search);
    return {
        station: params.get('station'),
        date: params.get('date')
    };
}

// Refresh page with new parameters
function refreshWithNewParams() {
    const station = document.getElementById("station").value;
    const month = document.getElementById("month").value;
    const year = document.getElementById("year").value;
    const date = month + '_' + year;

    if (!station || !date) {
        alert("Please provide both station and date values.");
        return;
    }

    const newUrl = `${window.location.origin}${window.location.pathname}?station=${station}&date=${date}`;
    window.location.href = newUrl;
}

document.addEventListener('DOMContentLoaded', () => {
    const { station, date } = getQueryParams();

    // Pre-fill station input
    if (station) {
        document.getElementById('station').value = station;
    }

    // Pre-fill month/year dropdowns
    if (date && date.includes('_')) {
        const [m, y] = date.split('_');
        setDropdownValue('month', m);
        setDropdownValue('year', y);
    }

    const table = $('#dataTable');

    // Initialize DataTable
    if ($.fn.DataTable.isDataTable(table)) {
        table.DataTable().destroy();
    }

    const metadata = JSON.parse(document.getElementById('metadata').textContent);
    const basicColumns = [true].concat(metadata.map(m => m.display_type === 'basic'));

    const dataTable = table.DataTable({
        ...DATA_TABLE_CONFIG,
        columnDefs: [
            { targets: 0, width: "150px" }, // Date column
            ...basicColumns.map((isBasic, index) => ({
                targets: index,
                visible: isBasic,
                width: "100px"
            }))
        ]
    });

    // Handle consecutive climate-flagged precip
    const precipColumnIndex = $('#dataTable thead tr:first-child th').toArray().findIndex(th =>
        $(th).text().toLowerCase().includes('precip')
    );

    if (precipColumnIndex !== -1) {
        const rows = $('#dataTable tbody tr');
        let consecutiveSum = 0;
        let consecutiveStartRow = null;

        rows.each(function () {
            const cell = $(this).find('td').eq(precipColumnIndex);
            const cellValue = parseFloat(cell.text());
            const isClimateFlagged = cell.hasClass('climate-flag');

            if (isClimateFlagged) {
                if (consecutiveStartRow === null) {
                    consecutiveStartRow = $(this);
                }
                if (!isNaN(cellValue)) {
                    consecutiveSum += cellValue;
                }
                cell.text('');
            } else if (consecutiveStartRow !== null) {
                // Place the total in the start row
                consecutiveStartRow
                    .find('td')
                    .eq(precipColumnIndex)
                    .text(`${consecutiveSum.toFixed(2)}*`);
                consecutiveStartRow = null;
                consecutiveSum = 0;
            }
        });
    }

    // Column toggle buttons
    $('.toggle-display').on('click', function () {
        const displayType = $(this).data('display');
        dataTable.columns().every(function (index) {
            if (index === 0) return; // Always show Date column
            const meta = metadata[index - 1];
            if (meta) {
                this.visible(meta.display_type === displayType);
            }
        });
        dataTable.columns.adjust().draw();
        hideNaNColumns(); // Call hideNaNColumns after toggling columns
    });

    // Highlight min/max values in certain columns
    highlightExtremes();

    // Hide columns with only "N/A" values
    hideNaNColumns();

    // Save to CSV functionality
    document.getElementById('saveCsvButton').addEventListener('click', function () {
        const csvData = [];
        const headers = [];
        const subHeaders = [];

        // Get headers and sub-headers
        $('#dataTable thead tr').each(function (index, row) {
            $(row).find('th').each(function (colIndex, col) {
                if (dataTable.column(colIndex).visible()) {
                    if (index === 0) {
                        headers.push($(col).text());
                    } else {
                        subHeaders.push($(col).text());
                    }
                }
            });
        });

        csvData.push(headers.join(','));
        csvData.push(subHeaders.join(','));

        // Get table data
        $('#dataTable tbody tr').each(function () {
            const row = [];
            $(this).find('td').each(function (colIndex, col) {
                if (dataTable.column(colIndex).visible()) {
                    row.push($(col).text());
                }
            });
            csvData.push(row.join(','));
        });

        const csvContent = csvData.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', 'data.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});

// Highlight extremes based on column type
function highlightExtremes() {
    const table = document.querySelector("#dataTable");
    if (!table) return;

    const rows = Array.from(table.querySelectorAll("tbody tr"));
    if (rows.length < 3) return;

    // Exclude summary rows (last two)
    const dataRows = rows.slice(0, -2);
    if (dataRows.length === 0) return;

    const columnsCount = dataRows[0].children.length;

    for (let colIndex = 0; colIndex < columnsCount; colIndex++) {
        const columnType = detectColumnType(colIndex, table);
        if (!columnType) continue;

        const values = [];
        dataRows.forEach((row) => {
            const cell = row.children[colIndex];
            const val = parseFloat(cell.textContent.trim());
            if (!isNaN(val)) values.push(val);
        });

        if (values.length === 0) continue;

        const maxValue = Math.max(...values);
        const minValue = Math.min(...values);

        dataRows.forEach((row) => {
            const cell = row.children[colIndex];
            const val = parseFloat(cell.textContent.trim());
            if (isNaN(val)) return;

            // Apply coloring rules
            if (["temperature", "dew_point"].includes(columnType)) {
                if (val === maxValue) {
                    cell.style.color = "red";
                    cell.style.fontWeight = "bold";
                } else if (val === minValue) {
                    cell.style.color = "blue";
                    cell.style.fontWeight = "bold";
                }
            } else if (["wind_gust", "wind"].includes(columnType) || columnType === "precip") {
                if (val === maxValue) {
                    cell.style.color = "green";
                    cell.style.fontWeight = "bold";
                }
            } else if (columnType === "heat_index") {
                if (val === maxValue) {
                    cell.style.color = "red";
                    cell.style.fontWeight = "bold";
                }
            } else if (columnType === "wind_chill") {
                if (val === minValue) {
                    cell.style.color = "blue";
                    cell.style.fontWeight = "bold";
                }
            }
        });
    }
}

// Detect column type by header
function detectColumnType(colIndex, table) {
    const header = table.querySelector(`thead tr th:nth-child(${colIndex + 1})`);
    if (!header) return null;

    const headerText = header.textContent.toLowerCase();

    if (headerText.includes("temp")) return "temperature";
    if (headerText.includes("dew point")) return "dew_point";
    if (headerText.includes("gust")) return "wind_gust";
    if (headerText.includes("wind") && !headerText.includes("gust")) return "wind";
    if (headerText.includes("precip")) return "precip";
    if (headerText.includes("hi")) return "heat_index";
    if (headerText.includes("wc")) return "wind_chill";

    return null;
}

// Hide columns with only "N/A" values
function hideNaNColumns() {
    const table = $('#dataTable').DataTable();
    table.columns().every(function (index) {
        if (index === 0) return; // Always show Date column

        const allNaN = this.data().toArray().slice(0, -2).every(cell => cell.trim() === 'N/A');
        if (allNaN) {
            this.visible(false);
        }
    });
}