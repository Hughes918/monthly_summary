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
    var panelStateInput = document.getElementById('panelState');
    var graphControls = document.getElementById('graphControls');
    var graphDataElement = document.getElementById('graphData');
    var graphEmpty = document.getElementById('graphEmpty');
    var graphCanvas = document.getElementById('parameterChart');
    var chartInstance = null;
    var graphData = null;
    var pendingDateSubmit = false;

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

    function setCurrentPanel(panelName) {
        if (!panelStateInput) {
            return;
        }

        panelStateInput.value = panelName;
    }

    function setPanelState(button, panel, isVisible) {
        if (!button || !panel) {
            return;
        }

        panel.classList.toggle('is-visible', isVisible);
        button.setAttribute('aria-expanded', isVisible ? 'true' : 'false');
    }

    function toggleExclusivePanel(activeButton, activePanel, inactiveButton, inactivePanel, activePanelName) {
        if (!activeButton || !activePanel) {
            return false;
        }

        var shouldShowActive = !activePanel.classList.contains('is-visible');

        setPanelState(activeButton, activePanel, shouldShowActive);

        if (inactiveButton && inactivePanel) {
            setPanelState(inactiveButton, inactivePanel, false);
        }

        setCurrentPanel(shouldShowActive ? activePanelName : '');

        return shouldShowActive;
    }

    if (graphDataElement) {
        try {
            graphData = JSON.parse(graphDataElement.textContent);
        } catch (error) {
            graphData = null;
        }
    }

    function createScaleKey(unit) {
        return 'scale_' + (unit || 'value').replace(/[^a-zA-Z0-9]+/g, '_').toLowerCase();
    }

    function createColor(index, alpha) {
        var palette = [
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
        var color = palette[index % palette.length];
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
            graphControls.querySelectorAll('input[type="checkbox"]:checked'),
            function (checkbox) {
                return checkbox.value;
            }
        );

        if (chartInstance) {
            chartInstance.destroy();
            chartInstance = null;
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

            datasets.push({
                label: series.unit ? series.label + ' (' + series.unit + ')' : series.label,
                data: series.values,
                type: series.graphType,
                yAxisID: scaleKey,
                borderColor: createColor(index, 1),
                backgroundColor: series.graphType === 'bar' ? createColor(index, 0.45) : createColor(index, 0.15),
                borderWidth: series.graphType === 'bar' ? 1 : 2,
                pointRadius: series.graphType === 'bar' ? 0 : (graphData.labels.length > 48 ? 0 : 2),
                pointHoverRadius: series.graphType === 'bar' ? 0 : 4,
                tension: series.graphType === 'bar' ? 0 : 0.2,
                spanGaps: true,
                order: series.graphType === 'bar' ? 2 : 1
            });
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
            var isVisible = toggleExclusivePanel(toggleButton, statsPanel, graphToggleButton, graphPanel, 'stats');
            trackAnalyticsEvent('daily_view_panel_toggle', {
                event_category: 'Daily View',
                event_label: 'Stats Panel Toggle',
                panel: 'stats',
                state: isVisible ? 'open' : 'closed'
            });
        });
    }

    if (graphToggleButton && graphPanel) {
        graphToggleButton.addEventListener('click', function () {
            var isVisible = toggleExclusivePanel(graphToggleButton, graphPanel, toggleButton, statsPanel, 'graph');
            trackAnalyticsEvent('daily_view_panel_toggle', {
                event_category: 'Daily View',
                event_label: 'Graph Panel Toggle',
                panel: 'graph',
                state: isVisible ? 'open' : 'closed'
            });
            if (isVisible) {
                renderGraph();
            }
        });
    }

    if (graphControls) {
        graphControls.addEventListener('change', function (event) {
            if (event.target && event.target.matches('input[type="checkbox"]')) {
                renderGraph();
            }
        });
    }

    if (stationSelect) {
        stationSelect.addEventListener('change', function () {
            submitFilters();
        });
    }

    if (controlsForm) {
        Array.prototype.forEach.call(controlsForm.querySelectorAll('input[name="interval"]'), function (radio) {
            radio.addEventListener('change', function () {
                submitFilters();
            });
        });
    }

    if (panelStateInput) {
        if (panelStateInput.value === 'stats' && toggleButton && statsPanel) {
            setPanelState(toggleButton, statsPanel, true);
            if (graphToggleButton && graphPanel) {
                setPanelState(graphToggleButton, graphPanel, false);
            }
        } else if (panelStateInput.value === 'graph' && graphToggleButton && graphPanel) {
            setPanelState(graphToggleButton, graphPanel, true);
            if (toggleButton && statsPanel) {
                setPanelState(toggleButton, statsPanel, false);
            }
            renderGraph();
        } else {
            setCurrentPanel('');
        }
    }

    if (dateInput) {
        dateInput.addEventListener('change', function () {
            pendingDateSubmit = true;
        });
        dateInput.addEventListener('blur', function () {
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

            showLoadingState('Fetching the selected station and date. This can take a moment.');
        });
    }
});