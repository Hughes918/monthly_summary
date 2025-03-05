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
    const station = document.getElementById("station-select").value;
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
                cell.text('**');
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
        const dataTable = $('#dataTable').DataTable();
        // Get all column indexes as an array
        const allIndexes = dataTable.columns().indexes().toArray();
        
        const csvData = [];
        const headers = [];
        const subHeaders = [];

        // Build headers (first header row) using all columns
        $('#dataTable thead tr').eq(0).find('th').each(function (index, col) {
            if (allIndexes.indexOf(index) !== -1) {
                let textVal = $(col).text().normalize('NFKC')
                    .replace(/\u00C2/g, '')
                    .replace(/Â°/g, '°')
                    .replace(/[^\x00-\x7F]/g, '');
                headers.push(textVal);
            }
        });

        // Build sub-headers (second header row) using all columns
        $('#dataTable thead tr').eq(1).find('th').each(function (index, col) {
            if (allIndexes.indexOf(index) !== -1) {
                let textVal = $(col).text().normalize('NFKC')
                    .replace(/\u00C2/g, '')
                    .replace(/Â°/g, '°')
                    .replace(/[^\x00-\x7F]/g, '');
                subHeaders.push(textVal);
            }
        });

        csvData.push(headers.join(','));
        csvData.push(subHeaders.join(','));

        // Build rows data using all columns
        dataTable.rows({ search: 'applied' }).every(function (rowIdx) {
            const rowCells = $('#dataTable tbody tr').eq(rowIdx).find('td').toArray();
            const rowData = rowCells.map(td => $(td).text().trim());
            csvData.push(rowData.join(','));
        });

        const csvContent = csvData.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        // Build the file name from station, month and year
        const stationName = document.getElementById('station').value.trim();
        const month = document.getElementById('month').value.trim();
        const year = document.getElementById('year').value.trim();
        const fileName = stationName + '_' + month + '_' + year + '.csv';
        
        link.setAttribute('href', url);
        link.setAttribute('download', fileName);
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
    const dataRows = rows; //rows.slice(0, -2);
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
                const isPartOfMultiDayClog = cell.classList.contains('climate-flag');  // Check if cell has the clog flag
            
                if (val === maxValue && !isPartOfMultiDayClog) {
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

document.addEventListener("DOMContentLoaded", function () {
    const buttons = document.querySelectorAll(".toggle-display");

    buttons.forEach(button => {
        button.addEventListener("click", function () {
            // Remove active class from all buttons
            buttons.forEach(btn => btn.classList.remove("active"));

            // Add active class to the clicked button
            this.classList.add("active");
        });
    });
});

// Function to populate the selection menu
async function populateStationSelect() {
    const selectElement = document.getElementById('station-select');
    const metadataUrl = 'metadata.json'; // Ensure this path is correct relative to your HTML file

    // Verify that the select element exists
    if (!selectElement) {
        console.error('Select element with ID "station-select" not found in the DOM.');
        return;
    }

    try {
        console.log(`Fetching metadata from: ${metadataUrl}`);

        // Fetch the JSON data
        const response = await fetch(metadataUrl);

        // Log the response status
        console.log(`Fetch response status: ${response.status} ${response.statusText}`);

        // Check if the response is OK (status code 200-299)
        if (!response.ok) {
            throw new Error(`Network response was not ok: ${response.status} ${response.statusText}`);
        }

        // Parse the JSON data
        const metadata = await response.json();
        console.log('Metadata successfully parsed:', metadata);

        // Extract DEOS stations
        const deosStations = [];

        for (const stationKey in metadata) {
            if (metadata.hasOwnProperty(stationKey)) {
                const station = metadata[stationKey];
                console.log(`Processing station: ${stationKey}, Network_name: ${station.Network_name}`);

                // Check if Network_name is "DEOS"
                if (station.Network_name === "DEOS") {
                    console.log(`Adding DEOS station: ${station.Description} (Name: ${station.Name})`);
                    deosStations.push({
                        Name: station.Name,
                        Description: station.Description
                    });
                }
            }
        }

        console.log(`Total DEOS stations found: ${deosStations.length}`);

        // Sort the DEOS stations alphabetically by Description
        deosStations.sort((a, b) => {
            const descA = a.Description.toUpperCase(); // Ignore case
            const descB = b.Description.toUpperCase(); // Ignore case
            if (descA < descB) {
                return -1;
            }
            if (descA > descB) {
                return 1;
            }
            return 0;
        });

        // Log sorted stations for verification
        console.log('Sorted DEOS stations:', deosStations);

        // Read the 'station' parameter from the URL
        const urlParams = new URLSearchParams(window.location.search);
        const defaultStation = urlParams.get('station') || ''; // Default to empty string if not present
        console.log(`Default station from URL parameter: ${defaultStation}`);

        // Flag to check if the default station exists
        let defaultStationExists = false;

        // Iterate over the sorted DEOS stations and append them to the select element
        deosStations.forEach(station => {
            const option = document.createElement('option');
            option.value = station.Name; // Set the value to 'Name'
            option.textContent = station.Description; // Display 'Description'

            // Check if this station should be the default selected option
            if (station.Name === defaultStation) {
                option.selected = true;
                defaultStationExists = true;
                console.log(`Setting default selected station: ${station.Description} (Name: ${station.Name})`);
            }

            // Append the option to the select element
            selectElement.appendChild(option);
        });

        // If the default station parameter doesn't match any DEOS station, you can handle it accordingly
        if (defaultStation && !defaultStationExists) {
            console.warn(`Default station "${defaultStation}" not found among DEOS stations.`);
            // Optionally, you can set a fallback default or inform the user
            // For example, selecting the first DEOS station:
            /*
            if (deosStations.length > 0) {
                selectElement.selectedIndex = 1; // Assuming the first option is the default placeholder
                console.log(`Falling back to the first DEOS station: ${deosStations[0].Description}`);
            }
            */
        }

        // Handle case where no DEOS stations match the criteria
        if (deosStations.length === 0) { // Only the default option exists
            console.warn('No DEOS stations available to display.');
            const noOption = document.createElement('option');
            noOption.value = "";
            noOption.textContent = "No DEOS stations available";
            selectElement.appendChild(noOption);
        }

    } catch (error) {
        console.error('Error fetching or processing metadata:', error);

        // Inform the user of the error
        const errorOption = document.createElement('option');
        errorOption.value = "";
        errorOption.textContent = "Error loading stations";
        selectElement.appendChild(errorOption);
    }
}

// Call the function when the page loads
window.addEventListener('DOMContentLoaded', populateStationSelect);

// Add a global variable to store metadata for station info
let stationMetadata = null;

// Function to populate station select element
async function populateStationSelect() {
    const selectElement = document.getElementById('station-select');
    const metadataUrl = 'metadata.json'; // Ensure this path is correct relative to your HTML file

    if (!selectElement) {
        console.error('Select element with ID "station-select" not found in the DOM.');
        return;
    }

    try {
        console.log(`Fetching metadata from: ${metadataUrl}`);
        const response = await fetch(metadataUrl);
        console.log(`Fetch response status: ${response.status} ${response.statusText}`);
        if (!response.ok) {
            throw new Error(`Network response was not ok: ${response.status} ${response.statusText}`);
        }
        // Parse the JSON data and store globally
        const metadata = await response.json();
        stationMetadata = metadata;
        console.log('Metadata successfully parsed:', metadata);

        // Extract only DEOS stations for the select menu
        const deosStations = [];
        for (const stationKey in metadata) {
            if (metadata.hasOwnProperty(stationKey)) {
                const station = metadata[stationKey];
                console.log(`Processing station: ${stationKey}, Network_name: ${station.Network_name}`);
                if (station.Network_name === "DEOS") {
                    console.log(`Adding DEOS station: ${station.Description} (Name: ${station.Name})`);
                    deosStations.push({
                        Name: station.Name,
                        Description: station.Description
                    });
                }
            }
        }
        console.log(`Total DEOS stations found: ${deosStations.length}`);
        deosStations.sort((a, b) => a.Description.toUpperCase().localeCompare(b.Description.toUpperCase()));
        console.log('Sorted DEOS stations:', deosStations);

        const urlParams = new URLSearchParams(window.location.search);
        const defaultStation = urlParams.get('station') || '';
        console.log(`Default station from URL parameter: ${defaultStation}`);
        let defaultStationExists = false;
        deosStations.forEach(station => {
            const option = document.createElement('option');
            option.value = station.Name;
            option.textContent = station.Description;
            if (station.Name === defaultStation) {
                option.selected = true;
                defaultStationExists = true;
                console.log(`Setting default selected station: ${station.Description} (Name: ${station.Name})`);
            }
            selectElement.appendChild(option);
        });

        if (defaultStation && !defaultStationExists) {
            console.warn(`Default station "${defaultStation}" not found among DEOS stations.`);
        }

        if (deosStations.length === 0) {
            console.warn('No DEOS stations available to display.');
            const noOption = document.createElement('option');
            noOption.value = "";
            noOption.textContent = "No DEOS stations available";
            selectElement.appendChild(noOption);
        }

        // Set default view based on the fetched station metadata
        setDefaultViewBasedOnMetadata();
    } catch (error) {
        console.error('Error fetching or processing metadata:', error);
        const errorOption = document.createElement('option');
        errorOption.value = "";
        errorOption.textContent = "Error loading stations";
        selectElement.appendChild(errorOption);
    }
}

// New helper to set default view based on station metadata
function setDefaultViewBasedOnMetadata() {
    const viewSelect = document.getElementById('viewSelect');
    const stationSelect = document.getElementById('station-select');
    if (viewSelect && stationSelect && stationMetadata) {
        const currentStation = stationMetadata[stationSelect.value];
        if (currentStation && currentStation.Weather_Station === "N") {
            viewSelect.value = "water";
            viewSelect.dispatchEvent(new Event('change'));
            console.log('Default view set to Water due to Weather_Station == "N"');
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    var tableCells = document.querySelectorAll('#dataTable td');
    var showMessage = Array.from(tableCells).some(function(cell) {
        return cell.textContent.includes("**");
    });
    if (showMessage) {
        var bottomMessage = document.createElement('div');
        bottomMessage.className = 'bottom-message';
        bottomMessage.textContent = '* Blue shading indicates a multiday total.';
        bottomMessage.style.textAlign = 'center';
        bottomMessage.style.color = 'red';
        bottomMessage.style.fontWeight = 'bold';
        bottomMessage.style.fontStyle = 'italic';
        bottomMessage.style.fontSize = '1.2em';
        bottomMessage.style.marginTop = '20px';
        
        var footer = document.getElementById('deos-footer');
        if (footer) {
            footer.parentNode.insertBefore(bottomMessage, footer);
        } else {
            document.body.appendChild(bottomMessage);
        }
    }
});

document.addEventListener('DOMContentLoaded', function(){
    const viewSelect = document.getElementById('viewSelect');
    viewSelect.addEventListener('change', function(){
        const selectedType = this.value;
        const dataTable = $('#dataTable').DataTable();
        const metadata = JSON.parse(document.getElementById('metadata').textContent);
        dataTable.columns().every(function(index) {
            if (index === 0) return; // Always show Date column
            // Use metadata based on column index
            const meta = metadata[index - 1];
            this.visible(meta && meta.display_type === selectedType);
        });
        dataTable.columns.adjust().draw();
    });
});

document.addEventListener('DOMContentLoaded', function(){
    const infoButton = document.getElementById('infoButton');
    const infoPopup = document.getElementById('infoPopup');
    const closePopup = document.getElementById('closeInfoPopup');

    infoButton.addEventListener('click', function(){
        infoPopup.style.display = 'block';
    });
    closePopup.addEventListener('click', function(){
        infoPopup.style.display = 'none';
    });
    
    // Optional: hide popup if click outside the box
    window.addEventListener('click', function(event) {
        if (event.target == infoPopup) {
            infoPopup.style.display = 'none';
        }
    });
});