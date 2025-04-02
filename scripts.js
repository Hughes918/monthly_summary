// DataTable configuration
const DATA_TABLE_CONFIG = {
    scrollY: false,
    scrollX: true,
    scrollCollapse: true,
    paging: false,
    searching: false,
    sorting: false,
    info: false,
    autoWidth: true
};

// Set custom dimensions for user properties (Idea 10: Device type)
(function() {
    var deviceType = /Mobi|Android/i.test(navigator.userAgent) ? 'mobile' : 'desktop';
    gtag('set', {'custom_dimension_device_type': deviceType});
})();

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
    sessionStorage.setItem("scrollPos", window.scrollY);
    const station = document.getElementById("station-select").value;
    const month = document.getElementById("month").value;
    const year = document.getElementById("year").value;
    const date = month + '_' + year;

    if (!station || !date) {
        alert("Please provide both station and date values.");
        return;
    }
    
    // GA tracking for selectors (existing)
    gtag('event', 'filter_change', {
        'event_category': 'Selector Change',
        'event_label': `Station: ${station}, Month: ${month}, Year: ${year}`
    });

    window.location.href = `${window.location.origin}${window.location.pathname}?station=${station}&date=${date}`;
}

// GLOBAL GA COUNTERS
let infoButtonCount = 0;
let downloadButtonCount = 0;
let viewChangeCount = 0;
const pageLoadTime = Date.now(); // for time spent on site tracking

// --- DOMContentLoaded setup --- 
document.addEventListener('DOMContentLoaded', () => {
    // Immediately track page load with selected station, month, and year.
    const stationVal = document.getElementById('station').value || 'Unknown Station';
    const monthVal = document.getElementById('month').value || 'Unknown Month';
    const yearVal = document.getElementById('year').value || 'Unknown Year';
    gtag('event', 'page_load', {
        event_category: 'Page Load',
        event_label: `Page loaded with Station: ${stationVal}, Month: ${monthVal}, Year: ${yearVal}`
    });
    
    // Restore scroll position if available
    const scrollPos = sessionStorage.getItem("scrollPos");
    if (scrollPos) {
        window.scrollTo(0, parseInt(scrollPos));
        sessionStorage.removeItem("scrollPos");
    }

    const { station, date } = getQueryParams();
    if (station) document.getElementById('station').value = station;
    if (date && date.includes('_')) {
        const [m, y] = date.split('_');
        setDropdownValue('month', m);
        setDropdownValue('year', y);
    }

    // Initialize DataTable
    const table = $('#dataTable');
    if ($.fn.DataTable.isDataTable(table)) {
        table.DataTable().destroy();
    }
    const metadata = JSON.parse(document.getElementById('metadata').textContent);
    
    // Add data_units property to each metadata item
    const UNIT_MAPPING = {
        "temperature": "°F",
        "dew_point": "°F",
        "wind": "mph",
        "wind_gust": "mph",
        "precip": "in",
        "heat_index": "°F",
        "wind_chill": "°F"
    };
    metadata.forEach(item => {
        item.data_units = UNIT_MAPPING[item.display_type] || "";
    });
    
    const basicColumns = [true].concat(metadata.map(m => m.display_type === 'basic'));
    const dataTable = table.DataTable({
        ...DATA_TABLE_CONFIG,
        columnDefs: [
            { targets: 0, width: "150px" },
            ...basicColumns.map((isBasic, index) => ({
                targets: index,
                visible: isBasic,
                width: "100px"
            }))
        ]
    });
    setTimeout(() => dataTable.columns.adjust(), 100);
    
    // Handle consecutive climate-flagged precip column
    const precipColumnIndex = $('#dataTable thead tr:first-child th').toArray()
        .findIndex(th => $(th).text().toLowerCase().includes('precip'));
    if (precipColumnIndex !== -1) {
        const rows = $('#dataTable tbody tr');
        let consecutiveSum = 0, consecutiveStartRow = null, dayCount = 0;
        rows.each(function () {
            const cell = $(this).find('td').eq(precipColumnIndex);
            const cellValue = parseFloat(cell.text());
            const isClimateFlagged = cell.hasClass('climate-flag');
            if (isClimateFlagged) {
                if (consecutiveStartRow === null) {
                    consecutiveStartRow = $(this);
                    dayCount = 1;
                } else {
                    dayCount++;
                    cell.html(dayCount === 2 ? "<i>Multiday Total</i>" : '**');
                }
                if (!isNaN(cellValue)) consecutiveSum += cellValue;
            } else if (consecutiveStartRow !== null) {
                if (dayCount > 1) {
                    consecutiveStartRow.find('td').eq(precipColumnIndex)
                        .text(`${consecutiveSum.toFixed(2)}*`);
                } else {
                    consecutiveStartRow.find('td').eq(precipColumnIndex)
                        .removeClass('climate-flag');
                }
                consecutiveStartRow = null;
                consecutiveSum = 0;
                dayCount = 0;
            }
        });
        if (consecutiveStartRow !== null) {
            if (dayCount > 1) {
                consecutiveStartRow.find('td').eq(precipColumnIndex)
                    .text(`${consecutiveSum.toFixed(2)}*`);
            } else {
                consecutiveStartRow.find('td').eq(precipColumnIndex)
                    .removeClass('climate-flag');
            }
        }
    
        // Column toggle buttons for display type
        $('.toggle-display').on('click', function () {
            const displayType = $(this).data('display');
            dataTable.columns().every(function (index) {
                if (index === 0) return;
                const meta = metadata[index - 1];
                this.visible(meta && meta.display_type === displayType);
            });
            dataTable.columns.adjust().draw();
            hideNaNColumns();
        });
    
        // Highlight extremes & hide NaN columns
        highlightExtremes();
        hideNaNColumns();
    }
    
    // Save to CSV functionality
    document.getElementById('saveCsvButton').addEventListener('click', function () {
        downloadButtonCount++;
        const dataTable = $('#dataTable').DataTable();
        const allIndexes = dataTable.columns().indexes().toArray();
        let csvData = [], headers = [], subHeaders = [];
        $('#dataTable thead tr').eq(0).find('th').each(function (index, col) {
            if (allIndexes.includes(index)) {
                headers.push($(col).text().normalize('NFKC')
                    .replace(/\u00C2/g, '')
                    .replace(/Â°/g, '°')
                    .replace(/[^\x00-\x7F]/g, ''));
            }
        });
        $('#dataTable thead tr').eq(1).find('th').each(function (index, col) {
            if (allIndexes.includes(index)) {
                subHeaders.push($(col).text().normalize('NFKC')
                    .replace(/\u00C2/g, '')
                    .replace(/Â°/g, '°')
                    .replace(/[^\x00-\x7F]/g, ''));
            }
        });
        csvData.push(headers.join(','));
        csvData.push(subHeaders.join(','));
        dataTable.rows({ search: 'applied' }).every(function (rowIdx) {
            const rowData = $('#dataTable tbody tr').eq(rowIdx).find('td').toArray()
                .map(td => $(td).text().trim());
            csvData.push(rowData.join(','));
        });
        const csvContent = csvData.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.setAttribute('href', URL.createObjectURL(blob));
        const fileName = document.getElementById('station').value.trim() + '_' +
                         document.getElementById('month').value.trim() + '_' +
                         document.getElementById('year').value.trim() + '.csv';
        link.setAttribute('download', fileName);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Added GA tracking for Download button
        gtag('event', 'download_click', {
            event_category: 'Download Button',
            event_label: `Download CSV pressed. Click count: ${downloadButtonCount}`
        });
    });
    
    window.addEventListener('resize', () => dataTable.columns.adjust());
    
    // Time on Page tracking (enhanced description)
    window.addEventListener('beforeunload', () => {
        const timeOnPage = Math.round((Date.now() - pageLoadTime) / 1000); // seconds
        gtag('event', 'time_on_page', {
            event_category: 'Engagement',
            event_label: `User spent ${timeOnPage} seconds on this page`
        });
    });
    
    // Info popup events with counter update
    const infoButton = document.getElementById('infoButton');
    const infoPopup = document.getElementById('infoPopup');
    const closePopup = document.getElementById('closeInfoPopup');
    infoButton.addEventListener('click', () => { 
        infoButtonCount++;
        infoPopup.style.display = 'block'; 
        gtag('event', 'info_click', {
            event_category: 'Info Button',
            event_label: `Info Button Pressed. Click count: ${infoButtonCount}`
        });
    });
    closePopup.addEventListener('click', () => { infoPopup.style.display = 'none'; });
    window.addEventListener('click', event => { if (event.target == infoPopup) infoPopup.style.display = 'none'; });
});

// Highlight extremes based on column type
function highlightExtremes() {
    const table = document.querySelector("#dataTable");
    if (!table) return;
    const rows = Array.from(table.querySelectorAll("tbody tr"));
    if (rows.length < 3) return;
    const dataRows = rows; // ...existing code to skip summary rows if needed...
    if (dataRows.length === 0) return;
    const columnsCount = dataRows[0].children.length;
    for (let colIndex = 0; colIndex < columnsCount; colIndex++) {
        const columnType = detectColumnType(colIndex, table);
        if (!columnType) continue;
        let values = [];
        dataRows.forEach(row => {
            const val = parseFloat(row.children[colIndex].textContent.trim());
            if (!isNaN(val)) values.push(val);
        });
        if (values.length === 0) continue;
        const maxValue = Math.max(...values);
        const minValue = Math.min(...values);
        dataRows.forEach(row => {
            const cell = row.children[colIndex];
            const val = parseFloat(cell.textContent.trim());
            if (isNaN(val)) return;
            if (["temperature", "dew_point"].includes(columnType)) {
                if (val === maxValue) {
                    cell.style.color = "red";
                    cell.style.fontWeight = "bold";
                    cell.style.fontStyle = "italic";
                } else if (val === minValue) {
                    cell.style.color = "blue";
                    cell.style.fontWeight = "bold";
                    cell.style.fontStyle = "italic";
                }
            } else if ((["wind_gust", "wind"].includes(columnType) || columnType === "precip")) {
                if (val === maxValue && !cell.classList.contains('climate-flag')) {
                    cell.style.color = "green";
                    cell.style.fontWeight = "bold";
                    cell.style.fontStyle = "italic";
                }
            } else if (columnType === "heat_index" && val === maxValue) {
                cell.style.color = "red";
                cell.style.fontWeight = "bold";
                cell.style.fontStyle = "italic";
            } else if (columnType === "wind_chill" && val === minValue) {
                cell.style.color = "blue";
                cell.style.fontWeight = "bold";
                cell.style.fontStyle = "italic";
            }
        });
    }
}

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

function hideNaNColumns() {
    const table = $('#dataTable').DataTable();
    table.columns().every(function (index) {
        if (index === 0) return;
        const allNaN = this.data().toArray().slice(0, -2).every(cell => cell.trim() === 'N/A');
        if (allNaN) this.visible(false);
    });
}

// Toggle button active class
document.addEventListener("DOMContentLoaded", () => {
    const buttons = document.querySelectorAll(".toggle-display");
    buttons.forEach(button => {
        button.addEventListener("click", () => {
            buttons.forEach(btn => btn.classList.remove("active"));
            button.classList.add("active");
        });
    });
});

// Consolidated populateStationSelect (only one copy)
async function populateStationSelect() {
    const selectElement = document.getElementById('station-select');
    const metadataUrl = 'metadata_new2.json';
    //const metadataUrl = 'station_metadata_deos.json';                 
    if (!selectElement) return console.error('Select element "station-select" not found.');
    try {
        const response = await fetch(metadataUrl);
        if (!response.ok) throw new Error(`Network response not ok: ${response.status}`);
        let metadata = await response.json();
        let stations = [];
        if (Array.isArray(metadata)) {
            stations = metadata;
            // Build lookup object for stations if needed
        } else {
            for (const key in metadata) {
                if (metadata.hasOwnProperty(key)) {
                    let stationObj = metadata[key];
                    if (!stationObj.name && !stationObj.Name) stationObj.Name = key;
                    if (!stationObj.description && !stationObj.Description) stationObj.Description = stationObj.Name;
                    stations.push(stationObj);
                }
            }
        }
        // Get selected year and month
        const selectedYear = parseInt(document.getElementById('year').value);
        const selectedMonthCode = document.getElementById('month').value.toUpperCase();
        const monthMapping = { "JAN":0, "FEB":1, "MAR":2, "APR":3, "MAY":4, "JUN":5, "JUL":6, "AUG":7, "SEP":8, "OCT":9, "NOV":10, "DEC":11 };
        const selectedMonth = monthMapping[selectedMonthCode] ?? 0;
    
        let pondStations = [], otherStations = [];
        stations.forEach(station => {
            let name = station.name || station.Name;
            let desc = station.description || station.Description || name;
            let hasData = false;
            if (station.first_observation) {
                let dt = new Date(station.first_observation);
                if (!isNaN(dt.getFullYear())) {
                    hasData = (dt.getFullYear() < selectedYear ||
                              (dt.getFullYear() === selectedYear && dt.getMonth() <= selectedMonth));
                }
            }
            if (!hasData) desc += " 🚫";
            // New grouping based on the station metadata: if Weather_Station is "N"
            if (String(station.weather_station).toUpperCase() === "N") {
                pondStations.push({ Name: name, Description: desc });
            } else {
                otherStations.push({ Name: name, Description: desc });
            }
        });
        otherStations.sort((a, b) => a.Description.localeCompare(b.Description, undefined, { sensitivity: 'base' }));
        pondStations.sort((a, b) => a.Description.localeCompare(b.Description, undefined, { sensitivity: 'base' }));
    
        const urlParams = new URLSearchParams(window.location.search);
        const defaultStation = urlParams.get('station') || '';
        let defaultStationExists = false;
        
        // Create optgroup for Meteorological group
        const meteorologicalGroup = document.createElement('optgroup');
        meteorologicalGroup.label = "--------Meteorological--------";
        selectElement.appendChild(meteorologicalGroup);
    
        otherStations.forEach(station => {
            const opt = document.createElement('option');
            opt.value = station.Name;
            opt.textContent = station.Description;
            if (station.Name === defaultStation) { 
                opt.selected = true; 
                defaultStationExists = true; 
            }
            meteorologicalGroup.appendChild(opt);
        });
    
        // Create optgroup for Hydrological/Pond group
        const hydrologicalGroup = document.createElement('optgroup');
        hydrologicalGroup.label = "--------Hydrological/Pond--------";
        selectElement.appendChild(hydrologicalGroup);
    
        pondStations.forEach(station => {
            const opt = document.createElement('option');
            opt.value = station.Name;
            opt.textContent = station.Description;
            if (station.Name === defaultStation) { 
                opt.selected = true; 
                defaultStationExists = true; 
            }
            hydrologicalGroup.appendChild(opt);
        });
    
        if (defaultStation && !defaultStationExists) {
            console.warn(`Default station "${defaultStation}" not found.`);
        }
        if (otherStations.length + pondStations.length === 0) {
            const noOption = document.createElement('option');
            noOption.value = "";
            noOption.textContent = "No DEOS stations available";
            selectElement.appendChild(noOption);
        }
    
        // Set default view based on selected station group
        setDefaultViewBasedOnMetadata();
    } catch (error) {
        console.error('Error fetching metadata:', error);
        const errorOption = document.createElement('option');
        errorOption.value = "";
        errorOption.textContent = "Error loading stations";
        selectElement.appendChild(errorOption);
    }
}
window.addEventListener('DOMContentLoaded', populateStationSelect);

// Helper: Set default view based on station select grouping
function setDefaultViewBasedOnMetadata() {
    const viewSelect = document.getElementById('viewSelect');
    const stationSelect = document.getElementById('station-select');
    if (viewSelect && stationSelect) {
        const selectedOption = stationSelect.options[stationSelect.selectedIndex];
        if (selectedOption && selectedOption.parentElement 
            && selectedOption.parentElement.label 
            && selectedOption.parentElement.label.includes("Hydrological/Pond")) {
            viewSelect.value = "water";
        } else {
            viewSelect.value = "basic";
        }
        viewSelect.dispatchEvent(new Event('change'));
    }
}

// View toggle event listener with GA tracking (Idea 8)
document.addEventListener('DOMContentLoaded', () => {
    const viewSelect = document.getElementById('viewSelect');
    viewSelect.addEventListener('change', function(){
        viewChangeCount++;
        const selectedType = this.value;
        const dataTable = $('#dataTable').DataTable();
        const metadata = JSON.parse(document.getElementById('metadata').textContent);
        dataTable.columns().every(function(index) {
            if (index === 0) return;
            const meta = metadata[index - 1];
            this.visible(meta && meta.display_type === selectedType);
        });
        dataTable.columns.adjust().draw();
        // GA tracking for view toggle (Idea 8)
        gtag('event', 'view_change', {
            event_category: 'View Toggle',
            event_label: `View changed to: ${selectedType}. Total changes: ${viewChangeCount}`
        });
    });
});

// Additional GA tracking for station select and date filter changes (Ideas 1, 2, 9, and 10)
document.addEventListener('DOMContentLoaded', () => {
    // Station select change tracking updated to include descriptive text instead of just numbers
    const stationSelect = document.getElementById('station-select');
    if (stationSelect) {
        stationSelect.addEventListener('change', function() {
            const selectedOption = stationSelect.options[stationSelect.selectedIndex];
            // Use the option's parent label instead of a numeric value.
            const stationGroupText = selectedOption.parentElement ? selectedOption.parentElement.label : 'Unknown Group';
            const station = stationSelect.value;
            gtag('event', 'station_filter_selection', {
                event_category: 'Station Filter',
                event_label: `Station: ${station} (${stationGroupText})`
            });
        });
    }
    
    // Date filter change tracking (Idea 2)
    const monthSelect = document.getElementById('month');
    const yearSelect = document.getElementById('year');
    if (monthSelect) {
        monthSelect.addEventListener('change', function() {
            gtag('event', 'date_filter_change', {
                event_category: 'Date Filter',
                event_label: `Month: ${this.value}`
            });
        });
    }
    if (yearSelect) {
        yearSelect.addEventListener('change', function() {
            gtag('event', 'date_filter_change', {
                event_category: 'Date Filter',
                event_label: `Year: ${this.value}`
            });
        });
    }
    
    // Example of setting a custom dimension for station group on page load (Idea 10)
    const stationSelectElement = document.getElementById('station-select');
    if (stationSelectElement && stationSelectElement.value) {
        const selectedOption = stationSelectElement.options[stationSelectElement.selectedIndex];
        const stationGroup = selectedOption.parentElement ? selectedOption.parentElement.label : 'Unknown Group';
        gtag('set', {'custom_dimension_station_group': stationGroup});
    }
});

// Info popup events
document.addEventListener('DOMContentLoaded', () => {
    const infoButton = document.getElementById('infoButton');
    const infoPopup = document.getElementById('infoPopup');
    const closePopup = document.getElementById('closeInfoPopup');
    infoButton.addEventListener('click', () => { 
        infoButtonCount++;
        infoPopup.style.display = 'block'; 
        // Added GA tracking for Info button
        gtag('event', 'info_click', {
            event_category: 'Info Button',
            event_label: `Info Button Pressed. Click count: ${infoButtonCount}`
        });
    });
    closePopup.addEventListener('click', () => { infoPopup.style.display = 'none'; });
    window.addEventListener('click', event => { if (event.target == infoPopup) infoPopup.style.display = 'none'; });
});
