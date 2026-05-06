document.addEventListener('DOMContentLoaded', function () {
    var body = document.body;
    var loadingOverlay = document.getElementById('pageLoading');
    var controlsForm = document.querySelector('.controls form');
    var applyFiltersButton = document.getElementById('applyFilters');
    var stationSelect = document.getElementById('station');
    var dateInput = document.getElementById('date');
    var initialDateValue = dateInput ? dateInput.value : '';
    var toggleButton = document.getElementById('toggleStats');
    var statsPanel = document.getElementById('statsPanel');
    var graphToggleButton = document.getElementById('toggleGraph');
    var graphPanel = document.getElementById('graphPanel');
    var tableToggleButton = document.getElementById('toggleTable');
    var tablePanel = document.getElementById('tablePanel');
    var panelStateInput = document.getElementById('panelState');
    var graphStateInput = document.getElementById('graphState');
    var graphControls = document.getElementById('graphControls');
    var graphDataElement = document.getElementById('graphData');
    var graphEmpty = document.getElementById('graphEmpty');
    var graphCanvas = document.getElementById('parameterChart');
    var chartInstance = null;
    var graphData = null;
    var pendingDateSubmit = false;
    var userAgent = window.navigator && window.navigator.userAgent ? window.navigator.userAgent : '';
    var isMobileUserAgent = /Android|iPhone|iPad|iPod|Mobile/i.test(userAgent);
    var hasCoarsePointer = !!(
        window.matchMedia &&
        window.matchMedia('(pointer: coarse)').matches
    );
    var isMobileDatePicker = isMobileUserAgent || hasCoarsePointer;

    function trackAnalyticsEvent(eventName, eventData) {
        if (typeof window.gtag !== 'function') {
            return;
        }

        window.gtag('event', eventName, eventData || {});
    }

    trackAnalyticsEvent('daily_view_page_load', {
        event_category: 'Daily View',
        event_label: 'Daily View Page Load',
        station_code: stationSelect ? stationSelect.value : '',
        station_name: stationSelect && stationSelect.selectedOptions.length ? stationSelect.selectedOptions[0].text : '',
        date: dateInput ? dateInput.value : '',
        interval: document.querySelector('input[name="interval"]:checked') ? document.querySelector('input[name="interval"]:checked').value : '',
        panel: panelStateInput ? panelStateInput.value : ''
    });

    // Sync form controls with URL parameters on initial load
    syncFormWithURL();

    function updateURL() {
        const url = new URL(window.location);
        if (graphControls) {
            const selected = Array.from(graphControls.querySelectorAll('input[type="checkbox"]:checked')).map(cb => cb.value);
            if (selected.length > 0) {
                url.searchParams.set('graph', selected.join(','));
                if (graphStateInput) graphStateInput.value = selected.join(',');
            } else {
                url.searchParams.delete('graph');
                if (graphStateInput) graphStateInput.value = '';
            }
        }
        if (panelStateInput) {
            if (panelStateInput.value) {
                url.searchParams.set('panel', panelStateInput.value);
            } else {
                url.searchParams.delete('panel');
            }
        }
        history.replaceState(null, '', url);
    }

    function showLoadingState(message) {
        if (!body || !loadingOverlay) {
            return;
        }

        var loadingText = loadingOverlay.querySelector('.loading-text');
        if (loadingText && message) {
            loadingText.textContent = message;
        }

        body.classList.add('is-loading');
        loadingOverlay.setAttribute('aria-hidden', 'false');
    }

    function hideLoadingState() {
        if (!body || !loadingOverlay) {
            return;
        }

        body.classList.remove('is-loading');
        loadingOverlay.setAttribute('aria-hidden', 'true');
    }

    function syncFormWithURL() {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Sync station
        const station = urlParams.get('station');
        if (station && stationSelect) {
            stationSelect.value = station;
        }
        
        // Sync date - check both 'date' and 'startDate' parameters
        const date = urlParams.get('date') || urlParams.get('startDate');
        if (date && dateInput) {
            dateInput.value = date;
            initialDateValue = date;
        }
        
        // Sync type radio buttons
        const type = urlParams.get('type');
        if (type && controlsForm) {
            const typeRadio = controlsForm.querySelector('input[name="type"][value="' + type + '"]');
            if (typeRadio) {
                typeRadio.checked = true;
            }
        }
        
        // Sync interval radio buttons
        const interval = urlParams.get('interval');
        if (interval && controlsForm) {
            const intervalRadio = controlsForm.querySelector('input[name="interval"][value="' + interval + '"]');
            if (intervalRadio) {
                intervalRadio.checked = true;
            }
        }
    }

    function submitFilters() {
        if (!controlsForm) {
            return;
        }

        if (applyFiltersButton && typeof controlsForm.requestSubmit === 'function') {
            controlsForm.requestSubmit(applyFiltersButton);
            return;
        }

        if (applyFiltersButton) {
            applyFiltersButton.click();
            return;
        }

        controlsForm.submit();
    }

    function submitDateIfComplete() {
        if (!controlsForm || !dateInput) {
            return;
        }

        if (!/^\d{4}-\d{2}-\d{2}$/.test(dateInput.value) || !dateInput.validity.valid) {
            return;
        }

        if (dateInput.value === initialDateValue) {
            return;
        }

        initialDateValue = dateInput.value;
        pendingDateSubmit = false;
        submitFilters();
    }

    function flushPendingDateSubmit() {
        if (!pendingDateSubmit) {
            return;
        }

        if (document.activeElement === dateInput) {
            return;
        }

        submitDateIfComplete();
    }

    function openDesktopDatePicker() {
        if (!dateInput || isMobileDatePicker) {
            return;
        }

        if (typeof dateInput.showPicker !== 'function') {
            return;
        }

        try {
            dateInput.showPicker();
        } catch (error) {
            // Ignore if the browser blocks opening picker without direct user activation.
        }
    }

    function setPanelState(button, panel, isVisible) {
        if (!button || !panel) {
            return;
        }

        panel.classList.toggle('is-visible', isVisible);
        button.setAttribute('aria-expanded', isVisible ? 'true' : 'false');
    }

    function syncPanelStateInput() {
        if (!panelStateInput) {
            return;
        }

        var activePanels = [];
        if (statsPanel && statsPanel.classList.contains('is-visible')) {
            activePanels.push('stats');
        }
        if (graphPanel && graphPanel.classList.contains('is-visible')) {
            activePanels.push('graph');
        }
        if (tablePanel && tablePanel.classList.contains('is-visible')) {
            activePanels.push('table');
        }
        panelStateInput.value = activePanels.join(',');
    }

    if (graphDataElement) {
        try {
            graphData = JSON.parse(graphDataElement.textContent);
        } catch (error) {
            graphData = null;
        }
    }

    // Parse URL parameters for graph and panel
    const urlParams = new URLSearchParams(window.location.search);
    const graphParam = urlParams.get('graph');
    const panelParam = urlParams.get('panel');
    if (graphParam && graphControls) {
        const selectedParams = graphParam.split(',');
        graphControls.querySelectorAll('input[type="checkbox"]').forEach(cb => {
            cb.checked = !cb.disabled && selectedParams.includes(cb.value);
        });
        if (graphStateInput) graphStateInput.value = graphParam;
    }
    if (panelParam && panelStateInput) {
        panelStateInput.value = panelParam;
    }

    function createScaleKey(unit) {
        return 'scale_' + (unit || 'value').replace(/[^a-zA-Z0-9]+/g, '_').toLowerCase();
    }

    function getSeriesBaseColor(seriesKey, index) {
        var fixedColors = {
            'Gage Precipitation (5)': [44, 160, 44],      // Rain - Green
            'Wind Chill': [128, 0, 128],                  // Wind Chill - Purple
            'Wind Chill Temperature': [128, 0, 128],      // Wind Chill - Purple
            'Heat Index': [139, 0, 0],                    // Heat Index - Dark Red
            'Dew Point Temperature': [44, 160, 44],       // Dew Pt - Green
            'Air Temperature': [214, 39, 40],             // Air Temp - Red
            'Wind Speed': [31, 119, 180],                 // Wind Speed - Blue
            'Wind Gust Speed (5)': [0, 55, 130],          // Wind Gust - Dark Blue
            'Wind Direction': [255, 127, 14]              // Wind Direction - Orange
        };

        if (fixedColors[seriesKey]) {
            return fixedColors[seriesKey];
        }

        var fallbackPalette = [
            [214, 39, 40],
            [31, 119, 180],
            [44, 160, 44],
            [255, 127, 14],
            [148, 103, 189],
            [140, 86, 75],
            [227, 119, 194],
            [127, 127, 127],
            [188, 189, 34],
            [23, 190, 207]
        ];

        return fallbackPalette[index % fallbackPalette.length];
    }

    function createSeriesColor(seriesKey, index, alpha) {
        var color = getSeriesBaseColor(seriesKey, index);
        return 'rgba(' + color[0] + ', ' + color[1] + ', ' + color[2] + ', ' + alpha + ')';
    }

    function formatGraphAxisLabel(label) {
        if (typeof label !== 'string' || label.trim() === '') {
            return label;
        }

        var parts = label.split(' ');
        if (parts.length <= 1) {
            return label;
        }

        return parts.slice(1).join(' ');
    }

    function renderGraph() {
        if (!graphPanel || !graphControls || !graphCanvas || !graphData || typeof Chart === 'undefined') {
            return;
        }

        var selectedKeys = Array.prototype.map.call(
            graphControls.querySelectorAll('input[type="checkbox"]:checked:not(:disabled)'),
            function (checkbox) {
                return checkbox.value;
            }
        );

        if (chartInstance) {
            chartInstance.destroy();
            chartInstance = null;
        }

        if (selectedKeys.length === 0) {
            // Try to select a default parameter
            let defaultKey = null;
            if (graphData.series['Air Temperature'] && graphData.series['Air Temperature'].graphable !== false) {
                defaultKey = 'Air Temperature';
            } else if (graphData.series['Water Temperature'] && graphData.series['Water Temperature'].graphable !== false) {
                defaultKey = 'Water Temperature';
            } else {
                // Select the first available
                const seriesKeys = Object.keys(graphData.series);
                if (seriesKeys.length > 0) {
                    defaultKey = seriesKeys.find(function (key) {
                        return graphData.series[key] && graphData.series[key].graphable !== false;
                    }) || null;
                }
            }
            if (defaultKey) {
                const checkbox = graphControls.querySelector(`input[value="${defaultKey}"]`);
                if (checkbox && !checkbox.disabled) {
                    checkbox.checked = true;
                    selectedKeys = [defaultKey];
                    updateURL();
                }
            }
        }

        if (selectedKeys.length === 0) {
            graphCanvas.style.display = 'none';
            if (graphEmpty) {
                graphEmpty.style.display = 'block';
            }
            return;
        }

        graphCanvas.style.display = 'block';
        if (graphEmpty) {
            graphEmpty.style.display = 'none';
        }

        var datasets = [];
        var scales = {
            x: {
                ticks: {
                    autoSkip: true,
                    maxTicksLimit: 14
                }
            }
        };
        var scaleOrder = [];

        selectedKeys.forEach(function (key, index) {
            var series = graphData.series[key];
            if (!series) {
                return;
            }

            var unit = series.unit || 'value';
            var scaleKey = createScaleKey(unit);

            if (!scales[scaleKey]) {
                scaleOrder.push(scaleKey);
                scales[scaleKey] = {
                    type: 'linear',
                    display: true,
                    position: scaleOrder.length === 1 ? 'left' : 'right',
                    offset: scaleOrder.length > 2,
                    beginAtZero: unit === 'mph' || unit === 'in',
                    min: unit === 'mph' || unit === 'in' ? 0 : undefined,
                    title: {
                        display: true,
                        text: unit === 'value' ? 'Value' : unit
                    },
                    grid: {
                        drawOnChartArea: scaleOrder.length === 1
                    }
                };
            }

            var isPointOnly = key === 'Heat Index' || key === 'Wind Chill' || key === 'Wind Chill Temperature';
            var datasetConfig = {
                label: series.unit ? series.label + ' (' + series.unit + ')' : series.label,
                data: series.values,
                type: series.graphType,
                yAxisID: scaleKey,
                borderColor: createSeriesColor(key, index, 1),
                backgroundColor: series.graphType === 'bar' ? createSeriesColor(key, index, 0.45) : createSeriesColor(key, index, 0.15),
                borderWidth: series.graphType === 'bar' ? 1 : 2,
                pointRadius: series.graphType === 'bar' ? 0 : (graphData.labels.length > 48 ? 0 : 2),
                pointHoverRadius: series.graphType === 'bar' ? 0 : 4,
                tension: series.graphType === 'bar' ? 0 : 0.2,
                spanGaps: true,
                order: series.graphType === 'bar' ? 2 : 1
            };

            if (isPointOnly) {
                datasetConfig.showLine = false;
                datasetConfig.pointRadius = graphData.labels.length > 48 ? 3 : 5;
                datasetConfig.pointHoverRadius = 7;
                datasetConfig.pointBorderWidth = 2;
                datasetConfig.pointStyle = 'circle';
            }

            datasets.push(datasetConfig);
        });

        chartInstance = new Chart(graphCanvas, {
            type: 'line',
            data: {
                labels: graphData.labels.map(formatGraphAxisLabel),
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                stacked: false,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            title: function (tooltipItems) {
                                if (!tooltipItems.length) {
                                    return '';
                                }

                                return graphData.labels[tooltipItems[0].dataIndex] || '';
                            }
                        }
                    }
                },
                scales: Object.assign({}, scales, {
                    x: Object.assign({}, scales.x, {
                        ticks: Object.assign({}, scales.x.ticks, {
                            maxRotation: 50,
                            minRotation: 50,
                            padding: 8,
                            font: {
                                size: 13
                            }
                        })
                    })
                })
            }
        });
    }

    if (toggleButton && statsPanel) {
        toggleButton.addEventListener('click', function () {
            trackAnalyticsEvent('daily_view_button_click', {
                event_category: 'Daily View',
                event_label: 'Button Clicked',
                button_name: 'toggleStats'
            });
            var isVisible = !statsPanel.classList.contains('is-visible');
            setPanelState(toggleButton, statsPanel, isVisible);
            syncPanelStateInput();
            trackAnalyticsEvent('daily_view_panel_toggle', {
                event_category: 'Daily View',
                event_label: 'Stats Panel Toggle',
                panel: 'stats',
                state: isVisible ? 'open' : 'closed'
            });
            updateURL();
        });
    }

    if (graphToggleButton && graphPanel) {
        graphToggleButton.addEventListener('click', function () {
            trackAnalyticsEvent('daily_view_button_click', {
                event_category: 'Daily View',
                event_label: 'Button Clicked',
                button_name: 'toggleGraph'
            });
            var isVisible = !graphPanel.classList.contains('is-visible');
            setPanelState(graphToggleButton, graphPanel, isVisible);
            syncPanelStateInput();
            trackAnalyticsEvent('daily_view_panel_toggle', {
                event_category: 'Daily View',
                event_label: 'Graph Panel Toggle',
                panel: 'graph',
                state: isVisible ? 'open' : 'closed'
            });
            if (isVisible) {
                renderGraph();
            }
            updateURL();
        });
    }

    if (tableToggleButton && tablePanel) {
        tableToggleButton.addEventListener('click', function () {
            trackAnalyticsEvent('daily_view_button_click', {
                event_category: 'Daily View',
                event_label: 'Button Clicked',
                button_name: 'toggleTable'
            });
            var isVisible = !tablePanel.classList.contains('is-visible');
            setPanelState(tableToggleButton, tablePanel, isVisible);
            syncPanelStateInput();
            trackAnalyticsEvent('daily_view_panel_toggle', {
                event_category: 'Daily View',
                event_label: 'Table Panel Toggle',
                panel: 'table',
                state: isVisible ? 'open' : 'closed'
            });
            updateURL();
        });
    }

    if (graphControls) {
        graphControls.addEventListener('change', function (event) {
            if (event.target && event.target.matches('input[type="checkbox"]')) {
                renderGraph();
                updateURL();
            }
        });
    }

    var downloadButton = document.getElementById('downloadGraph');
    if (downloadButton) {
        downloadButton.addEventListener('click', function() {
            if (!chartInstance || !graphCanvas || !graphData) return;
            html2canvas(graphCanvas).then(function(canvas) {
                var link = document.createElement('a');
                link.download = graphData.station + '_' + graphData.date + '_graph.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            }).catch(function(error) {
                console.error('Error generating graph image:', error);
            });
        });
    }

    if (stationSelect) {
        stationSelect.addEventListener('change', function () {
            trackAnalyticsEvent('daily_view_station_change', {
                event_category: 'Daily View',
                event_label: 'Station Changed',
                station_code: stationSelect.value,
                station_name: stationSelect.selectedOptions.length ? stationSelect.selectedOptions[0].text : '',
                date: dateInput ? dateInput.value : ''
            });
            submitFilters();
        });
    }

    if (controlsForm) {
        Array.prototype.forEach.call(controlsForm.querySelectorAll('input[name="type"]'), function (radio) {
            radio.addEventListener('change', function () {
                if (stationSelect) {
                    stationSelect.value = '';
                }
                controlsForm.submit();
            });
        });
    }

    if (controlsForm) {
        Array.prototype.forEach.call(controlsForm.querySelectorAll('input[name="interval"]'), function (radio) {
            radio.addEventListener('change', function () {
                submitFilters();
            });
        });
    }

    var prevDateBtn = document.getElementById('prevDate');
    var nextDateBtn = document.getElementById('nextDate');
    var dateInput = document.getElementById('date');

    function adjustDate(days) {
        if (!dateInput || !dateInput.value) return;
        var currentDate = new Date(dateInput.value);
        currentDate.setDate(currentDate.getDate() + days);
        var newDateStr = currentDate.toISOString().split('T')[0];
        dateInput.value = newDateStr;
        trackAnalyticsEvent('daily_view_date_change', {
            event_category: 'Daily View',
            event_label: 'Date Changed via Button',
            button_direction: days > 0 ? 'next' : 'previous',
            old_date: dateInput.getAttribute('data-previous-value') || '',
            new_date: newDateStr,
            station_code: stationSelect ? stationSelect.value : '',
            station_name: stationSelect && stationSelect.selectedOptions.length ? stationSelect.selectedOptions[0].text : ''
        });
        dateInput.setAttribute('data-previous-value', newDateStr);
        submitFilters();
    }

    if (prevDateBtn) {
        prevDateBtn.addEventListener('click', function() {
            trackAnalyticsEvent('daily_view_button_click', {
                event_category: 'Daily View',
                event_label: 'Button Clicked',
                button_name: 'prevDate'
            });
            adjustDate(-1);
        });
    }

    if (nextDateBtn) {
        nextDateBtn.addEventListener('click', function() {
            trackAnalyticsEvent('daily_view_button_click', {
                event_category: 'Daily View',
                event_label: 'Button Clicked',
                button_name: 'nextDate'
            });
            adjustDate(1);
        });
    }

    // Info Modal Handlers
    var infoButton = document.getElementById('infoButton');
    var infoModal = document.getElementById('infoModal');
    var closeInfoModal = document.getElementById('closeInfoModal');

    function openInfoModal() {
        if (!infoModal) return;
        infoModal.setAttribute('aria-hidden', 'false');
        infoButton.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }

    function closeInfoModalFunc() {
        if (!infoModal) return;
        infoModal.setAttribute('aria-hidden', 'true');
        infoButton.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }

    if (infoButton) {
        infoButton.addEventListener('click', openInfoModal);
    }

    if (closeInfoModal) {
        closeInfoModal.addEventListener('click', closeInfoModalFunc);
    }

    if (infoModal) {
        var modalOverlay = infoModal.querySelector('.modal-overlay');
        if (modalOverlay) {
            modalOverlay.addEventListener('click', closeInfoModalFunc);
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && infoModal.getAttribute('aria-hidden') === 'false') {
                closeInfoModalFunc();
            }
        });

        // Prevent modal scrolling from scrolling page
        var modalBody = infoModal.querySelector('.modal-body');
        if (modalBody) {
            modalBody.addEventListener('wheel', function (event) {
                event.stopPropagation();
            });
        }
    }

    if (panelStateInput) {
        var selectedPanels = panelStateInput.value ? panelStateInput.value.split(',') : [];
        var showStats = selectedPanels.includes('stats');
        var showGraph = selectedPanels.includes('graph');
        var showTable = selectedPanels.includes('table');

        if (toggleButton && statsPanel) {
            setPanelState(toggleButton, statsPanel, showStats);
        }

        if (graphToggleButton && graphPanel) {
            setPanelState(graphToggleButton, graphPanel, showGraph);
        }

        if (tableToggleButton && tablePanel) {
            setPanelState(tableToggleButton, tablePanel, showTable);
        }

        if (showGraph) {
            renderGraph();
        }
        syncPanelStateInput();
    }

    // Update URL with initial state
    updateURL();

    if (dateInput) {
        dateInput.addEventListener('pointerdown', function (event) {
            if (event.pointerType && event.pointerType !== 'mouse') {
                return;
            }

            if (isMobileDatePicker) {
                return;
            }

            event.preventDefault();
            dateInput.focus();
            openDesktopDatePicker();
        });
        dateInput.addEventListener('click', openDesktopDatePicker);
        dateInput.addEventListener('focus', openDesktopDatePicker);
        dateInput.addEventListener('change', function () {
            pendingDateSubmit = true;

            if (isMobileDatePicker) {
                window.setTimeout(function () {
                    if (!pendingDateSubmit) {
                        return;
                    }

                    submitDateIfComplete();
                }, 0);
                return;
            }

            submitDateIfComplete();
        });
        dateInput.addEventListener('blur', function () {
            if (isMobileDatePicker) {
                return;
            }

            if (!pendingDateSubmit && dateInput.value === initialDateValue) {
                return;
            }

            window.setTimeout(flushPendingDateSubmit, 0);
        });
        dateInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                submitDateIfComplete();
            }
        });
    }

    if (controlsForm) {
        controlsForm.addEventListener('submit', function (event) {
            var submitter = event.submitter || null;
            if (submitter && submitter.name === 'download') {
                trackAnalyticsEvent('daily_view_download', {
                    event_category: 'Daily View',
                    event_label: 'Daily View Download CSV',
                    station_code: stationSelect ? stationSelect.value : '',
                    station_name: stationSelect && stationSelect.selectedOptions.length ? stationSelect.selectedOptions[0].text : '',
                    date: dateInput ? dateInput.value : '',
                    interval: document.querySelector('input[name="interval"]:checked') ? document.querySelector('input[name="interval"]:checked').value : '',
                    panel: panelStateInput ? panelStateInput.value : ''
                });
                return;
            }

            trackAnalyticsEvent('daily_view_filter_submit', {
                event_category: 'Daily View',
                event_label: 'Filters Applied',
                station_code: stationSelect ? stationSelect.value : '',
                station_name: stationSelect && stationSelect.selectedOptions.length ? stationSelect.selectedOptions[0].text : '',
                date: dateInput ? dateInput.value : '',
                type: document.querySelector('input[name="type"]:checked') ? document.querySelector('input[name="type"]:checked').value : '',
                interval: document.querySelector('input[name="interval"]:checked') ? document.querySelector('input[name="interval"]:checked').value : ''
            });

            showLoadingState('Fetching the selected station and date. This can take a moment.');
        });
    }

    // Hide loading state when page is fully loaded
    hideLoadingState();

    // Handle browser back button and history navigation
    window.addEventListener('pageshow', function (event) {
        // Always hide loading state when page is shown (including from cache)
        hideLoadingState();
        // Sync form controls with URL when navigating back
        syncFormWithURL();
    });
});