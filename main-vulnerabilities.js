// Helper function to format date and time
function formatDateTime(dateTimeString) {
    if (!dateTimeString) return '-';
    
    const date = new Date(dateTimeString);
    if (isNaN(date.getTime())) return dateTimeString;
    
    return date.toLocaleString('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    }).replace(',', '');
}

// Function to update endpoint summary boxes
function updateEndpointSummary(endpoints) {
    const totalEndpoints = endpoints.length;
    const clientWorkloads = endpoints.filter(endpoint => {
        const osName = (endpoint.os?.name || endpoint.osName || '').toLowerCase();
        return osName.includes('windows') && !osName.includes('server');
    }).length;
    const serverWorkloads = endpoints.filter(endpoint => {
        const osName = (endpoint.os?.name || endpoint.osName || '').toLowerCase();
        return osName.includes('server');
    }).length;
    const outdatedComponents = endpoints.filter(endpoint => 
        endpoint.eppAgent?.componentVersion?.toLowerCase() === 'outdatedversion'
    ).length;

    $('#totalEndpoints').text(totalEndpoints);
    $('#clientWorkloads').text(clientWorkloads);
    $('#serverWorkloads').text(serverWorkloads);
    $('#outdatedComponents').text(outdatedComponents);
}

// Function to update the Trend Vision One endpoints table
function updateEndpointsTable() {
    $.ajax({
        url: '/api/plugin/TrendVisionOne/getfulldesktops?top=1000',
        method: 'GET',
        success: function(response) {
            if (!response || response.result !== 'Success' || !response.data) {
                console.error('Invalid response format:', response);
                return;
            }

            const data = response.data;
            if (!data.items || !Array.isArray(data.items)) {
                console.error('Data items is not an array:', data);
                return;
            }

            const endpoints = data.items;
            
            // Update summary boxes
            updateEndpointSummary(endpoints);
            
            // Update endpoint group filter
            updateEndpointGroupFilter(endpoints);
            
            // Filter endpoints based on selected group
            const selectedGroup = $('#endpointGroupFilter').val();
            const filteredEndpoints = selectedGroup ? 
                endpoints.filter(endpoint => (endpoint.eppAgent?.endpointGroup || '-') === selectedGroup) : 
                endpoints;
            
            const tableBody = $('#trendEndpointsTable tbody');
            tableBody.empty();

            if (filteredEndpoints.length === 0) {
                tableBody.html(`
                    <tr>
                        <td colspan="8" class="text-center">
                            No endpoints available
                        </td>
                    </tr>
                `);
                return;
            }

            filteredEndpoints.forEach((endpoint, index) => {
                const row = $('<tr>');
                console.log(`Processing endpoint ${index}:`, endpoint);

                row.append(`<td>${endpoint.displayName || '-'}</td>`);
                row.append(`<td>${endpoint.os?.name || endpoint.osName || '-'}</td>`);
                row.append(`<td>${endpoint.lastUsedIp || '-'}</td>`);
                row.append(`<td>${endpoint.eppAgent?.endpointGroup || '-'}</td>`);
                row.append(`<td>${formatDateTime(endpoint.eppAgent?.lastConnectedDateTime)}</td>`);
                
                // Status column with badge
                const isOnline = endpoint.agentUpdateStatus === 'onSchedule';
                const statusBadge = `<span class="${getEndpointStatusBadgeClass(isOnline)}">${isOnline ? 'Online' : 'Offline'}</span>`;
                row.append(`<td>${statusBadge}</td>`);
                
                // Component Version column with badge
                const componentVersion = endpoint.eppAgent?.componentVersion || '-';
                const componentVersionClass = componentVersion.toLowerCase() === 'outdatedversion' ? 'bg-danger' : 'bg-success';
                row.append(`<td><span class="badge ${componentVersionClass}">${componentVersion}</span></td>`);
                
                // Actions column with details button
                const detailsButton = `
                    <button class="btn btn-sm btn-info" 
                            onclick='showEndpointDetails("${endpoint.agentGuid}")'>
                        <i class="fas fa-info-circle"></i> Details
                    </button>`;
                row.append(`<td>${detailsButton}</td>`);
                
                tableBody.append(row);
            });
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            
            $('#trendEndpointsTable tbody').html(`
                <tr>
                    <td colspan="8" class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error loading endpoints data
                    </td>
                </tr>
            `);
        }
    });
}

// Function to update endpoint group filter dropdown
function updateEndpointGroupFilter(endpoints) {
    const groups = new Set(endpoints.map(endpoint => endpoint.eppAgent?.endpointGroup || '-'));
    const filterSelect = $('#endpointGroupFilter');
    
    // Save current selection
    const currentSelection = filterSelect.val();
    
    // Clear and rebuild options
    filterSelect.empty();
    filterSelect.append('<option value="">All Groups</option>');
    
    // Add sorted groups
    Array.from(groups)
        .sort()
        .forEach(group => {
            filterSelect.append(`<option value="${group}">${group}</option>`);
        });
    
    // Restore selection if it still exists in the new options
    if (currentSelection && groups.has(currentSelection)) {
        filterSelect.val(currentSelection);
    }
}

// Function to show endpoint details in modal
function showEndpointDetails(endpointId) {
    $.ajax({
        url: `/api/plugin/TrendVisionOne/getendpointdetails/${endpointId}`,
        method: 'GET',
        success: function(response) {
            if (response.result === 'Success' && response.data) {
                const data = response.data;
                // General Information
                $('#agentGuid').text(data.agentGuid || '-');
                $('#displayName').text(data.displayName || '-');
                $('#osName').text(data.os?.name || data.osName || '-');
                $('#osVersion').text(data.os?.version || '-');
                $('#ipAddresses').text(data.lastUsedIp || '-');
                $('#lastConnectedDateTime').text(formatDateTime(data.eppAgent?.lastConnectedDateTime) || '-');
                $('#endpointStatus')
                    .text(data.agentUpdateStatus === 'onSchedule' ? 'Online' : 'Offline')
                    .removeClass()
                    .addClass(getEndpointStatusBadgeClass(data.agentUpdateStatus === 'onSchedule'));
                $('#endpointGroup').text(data.eppAgent?.endpointGroup || '-');
                $('#protectionManager').text(data.eppAgent?.protectionManager || '-');

                // EDR Information
                const edrData = data.edrSensor || {};
                
                // Connectivity badge
                $('#edrConnectivity')
                    .text(edrData.connectivity || '-')
                    .removeClass('bg-success bg-danger')
                    .addClass(edrData.connectivity?.toLowerCase() === 'connected' ? 'bg-success' : 'bg-danger');

                $('#edrLastConnected').text(formatDateTime(edrData.lastConnectedDateTime) || '-');
                $('#edrVersion').text(edrData.version || '-');

                // Status badge
                $('#edrStatus')
                    .text(edrData.status || '-')
                    .removeClass('bg-success bg-danger')
                    .addClass(edrData.status?.toLowerCase() === 'enabled' ? 'bg-success' : 'bg-danger');

                // Advanced Risk Telemetry badge
                $('#edrAdvancedRiskTelemetry')
                    .text(edrData.advancedRiskTelemetryStatus || '-')
                    .removeClass('bg-success bg-danger')
                    .addClass(edrData.advancedRiskTelemetryStatus?.toLowerCase() === 'enabled' ? 'bg-success' : 'bg-danger');
                
                // EPP Information
                const eppData = data.eppAgent || {};
                $('#eppPolicyName').text(eppData.policyName || '-');
                
                // EPP Status badge
                $('#eppStatus')
                    .text(eppData.status || '-')
                    .removeClass('bg-success bg-danger')
                    .addClass(eppData.status?.toLowerCase() === 'on' ? 'bg-success' : 'bg-danger');
                
                $('#eppLastConnected').text(formatDateTime(eppData.lastConnectedDateTime) || '-');
                $('#eppVersion').text(eppData.version || '-');
                
                // Component Version badge
                $('#eppComponentVersion')
                    .text(eppData.componentVersion || '-')
                    .removeClass('bg-success bg-danger')
                    .addClass(eppData.componentVersion?.toLowerCase() === 'outdatedversion' ? 'bg-danger' : 'bg-success');
                
                endpointModal.show();
            } else {
                console.error('Error loading endpoint details:', response);
                toastr.error('Failed to load endpoint details: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading endpoint details:', error);
            toastr.error('Failed to load endpoint details');
        }
    });
}

// Function to get appropriate badge class for endpoint status
function getEndpointStatusBadgeClass(isOnline) {
    return isOnline ? 'badge bg-success text-white' : 'badge bg-danger text-white';
}

// Initialize Bootstrap modal for endpoints
let endpointModal;
$(document).ready(function() {
    endpointModal = new bootstrap.Modal(document.getElementById('endpointDetailsModal'));
});

// Initial setup
$(document).ready(function() {
    // Handle filter icon click
    $('#endpointGroupFilterIcon').click(function(e) {
        e.stopPropagation();
        const container = $('#endpointGroupFilterContainer');
        container.toggle();
        
        if (container.is(':visible')) {
            $('#endpointGroupFilter').focus();
        }
    });

    // Close filter dropdown when clicking outside
    $(document).click(function(e) {
        if (!$(e.target).closest('#endpointGroupFilterContainer').length && 
            !$(e.target).closest('#endpointGroupFilterIcon').length) {
            $('#endpointGroupFilterContainer').hide();
        }
    });

    // Add filter change handler
    $('#endpointGroupFilter').on('change', function() {
        const selectedValue = $(this).val();
        updateEndpointsTable();
        // Add active class to icon when filter is applied
        $('#endpointGroupFilterIcon').toggleClass('text-primary', selectedValue !== '');
        $('#endpointGroupFilterContainer').hide();
    });

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function formatDate(dateString) {
        if (!dateString) return '-';
        try {
            return new Date(dateString).toLocaleString();
        } catch (e) {
            return dateString;
        }
    }

    function filterData(data) {
        const groupFilter = $('#endpointGroupFilter').val();
        const searchTerm = $('#search-input').val().toLowerCase();

        return data.filter(endpoint => {
            const matchesGroup = !groupFilter || (endpoint.eppAgent?.endpointGroup || '') === groupFilter;
            const matchesSearch = !searchTerm || 
                (endpoint.displayName || '').toLowerCase().includes(searchTerm) ||
                (endpoint.endpointName || '').toLowerCase().includes(searchTerm) ||
                (endpoint.ip || '').toLowerCase().includes(searchTerm) ||
                (endpoint.mac || '').toLowerCase().includes(searchTerm);

            return matchesGroup && matchesSearch;
        });
    }

    function populateEndpointGroupFilter(endpoints) {
        if (!Array.isArray(endpoints)) {
            console.error('Data is not an array:', endpoints);
            return;
        }

        const groups = [...new Set(endpoints.map(endpoint => endpoint.eppAgent?.endpointGroup || 'No Group'))];
        const select = $('#endpointGroupFilter');
        select.empty();
        select.append('<option value="">All Groups</option>');
        groups.sort().forEach(group => {
            select.append(`<option value="${escapeHtml(group)}">${escapeHtml(group)}</option>`);
        });
    }

    function updateTableContent(data) {
        if (!Array.isArray(data)) {
            console.error('Data is not an array:', data);
            return;
        }

        const filteredData = filterData(data);
        const tbody = $('#trendEndpointsTable tbody');
        tbody.empty();

        filteredData.forEach(endpoint => {
            const row = $('<tr>');
            row.append(`
                <td>${escapeHtml(endpoint.displayName || endpoint.endpointName || '')}</td>
                <td>${escapeHtml(endpoint.os?.name || endpoint.osName || '')}</td>
                <td>${escapeHtml(endpoint.lastUsedIp || '')}</td>
                <td>${escapeHtml(endpoint.eppAgent?.endpointGroup || '')}</td>
                <td>${formatDate(endpoint.eppAgent?.lastConnectedDateTime)}</td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="showEndpointDetails('${escapeHtml(endpoint.agentGuid || '')}')">
                        Details
                    </button>
                </td>
            `);
            tbody.append(row);
        });
    }

    // Update endpoints table
    function updateEndpointsTable() {
        $.ajax({
            url: '/api/plugin/TrendVisionOne/getfulldesktops?top=1000',
            method: 'GET',
            success: function(response) {
                if (!response || response.result !== 'Success' || !response.data) {
                    console.error('Invalid response format:', response);
                    return;
                }

                const data = response.data;
                if (!data.items || !Array.isArray(data.items)) {
                    console.error('Data items is not an array:', data);
                    return;
                }

                populateEndpointGroupFilter(data.items);
                updateTableContent(data.items);
            },
            error: function(xhr, status, error) {
                console.error('Error fetching endpoints:', error);
            }
        });
    }

    // Initial load of endpoints data
    updateEndpointsTable();
    
    // Refresh data every 30 seconds
    setInterval(updateEndpointsTable, 30000);
});

// Trend Vision One Vulnerabilities Dashboard

document.addEventListener('DOMContentLoaded', function() {
    loadVulnerabilityData();
    setupEventListeners();
});

function setupEventListeners() {
    // Add search functionality
    $('#search-input').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterTable(searchTerm);
    });

    // Add sorting functionality
    $('#vulnerabilities-table th').on('click', function() {
        const column = $(this).index();
        sortTable(column);
    });
}

function loadVulnerabilityData() {
    $.ajax({
        url: '/api/plugin/TrendVisionOne/getvulnerabledevices',
        method: 'GET',
        success: function(response) {
            console.log('Raw API response:', response);
            if (response.result === 'Success' && response.data && response.data.items) {
                updateDashboard(response.data);
            } else {
                console.error('Failed to load vulnerability data:', response);
                resetCounters();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading vulnerability data:', error);
            resetCounters();
        }
    });
}

function updateDashboard(data) {
    if (!data || !data.items || !Array.isArray(data.items)) {
        console.error('Invalid data structure:', data);
        return;
    }

    const items = data.items;
    
    // Update total count from API response
    $('#total-vulnerabilities').text(data.totalCount || items.length);
    
    // Count vulnerabilities by risk level
    const riskCounts = {
        'HIGH': 0,
        'MEDIUM': 0,
        'LOW': 0
    };

    items.forEach(item => {
        if (item.riskLevel) {
            const risk = item.riskLevel.toUpperCase();
            if (riskCounts.hasOwnProperty(risk)) {
                riskCounts[risk]++;
            }
        }
    });

    // Update risk level counters
    $('#high-risk-count').text(riskCounts['HIGH']);
    $('#medium-risk-count').text(riskCounts['MEDIUM']);
    $('#low-risk-count').text(riskCounts['LOW']);

    // Update table
    updateTable(items);
}

function updateTable(items) {
    const tbody = $('#vulnerabilities-table tbody');
    tbody.empty();

    items.forEach(item => {
        const row = $('<tr>');
        const riskLevel = item.riskLevel ? item.riskLevel.toUpperCase() : 'UNKNOWN';
        
        row.html(`
            <td>${escapeHtml(item.endpointName || item.displayName || '')}</td>
            <td><span class="risk-${riskLevel.toLowerCase()}">${escapeHtml(riskLevel)}</span></td>
            <td>${item.cvssScore || '-'}</td>
            <td>${escapeHtml(item.vulnerabilityId || '')}</td>
            <td>${escapeHtml(item.installedProductName || '')} ${escapeHtml(item.installedProductVersion || '')}</td>
            <td>${formatDate(item.lastDetected || item.lastUsedIp)}</td>
            <td>
                <button class="btn btn-sm btn-info" onclick="showVulnerabilityDetails('${escapeHtml(item.agentGuid || '')}', '${escapeHtml(item.vulnerabilityId || '')}')">
                    Details
                </button>
            </td>
        `);
        tbody.append(row);
    });
}

function showVulnerabilityDetails(agentGuid, vulnId) {
    if (!agentGuid || !vulnId) {
        console.error('Missing required parameters for vulnerability details');
        return;
    }
    
    // Load endpoint details
    $.ajax({
        url: `/api/plugin/TrendVisionOne/getendpointdetails/${agentGuid}`,
        method: 'GET',
        success: function(response) {
            if (response.result === 'Success' && response.data) {
                showDetailsModal(response.data, vulnId);
            } else {
                console.error('Failed to load endpoint details:', response);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading endpoint details:', error);
        }
    });
}

function showDetailsModal(endpoint, vulnId) {
    const modal = $('#vulnerability-details-modal');
    const modalBody = modal.find('.modal-body');
    
    modalBody.html(`
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <h5>Endpoint Details</h5>
                    <dl class="row">
                        <dt class="col-sm-4">Name</dt>
                        <dd class="col-sm-8">${escapeHtml(endpoint.displayName || endpoint.endpointName || '')}</dd>
                        
                        <dt class="col-sm-4">IP Address</dt>
                        <dd class="col-sm-8">${escapeHtml(endpoint.ip || '')}</dd>
                        
                        <dt class="col-sm-4">OS</dt>
                        <dd class="col-sm-8">${escapeHtml(endpoint.osName || endpoint.os || '')}</dd>
                        
                        <dt class="col-sm-4">Last Seen</dt>
                        <dd class="col-sm-8">${formatDate(endpoint.lastSeen || endpoint.lastUsedIp)}</dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <h5>Vulnerability Details</h5>
                    <dl class="row">
                        <dt class="col-sm-4">ID</dt>
                        <dd class="col-sm-8">${escapeHtml(vulnId)}</dd>
                        
                        <dt class="col-sm-4">Risk Level</dt>
                        <dd class="col-sm-8">${escapeHtml(endpoint.riskLevel || '')}</dd>
                        
                        <dt class="col-sm-4">CVSS Score</dt>
                        <dd class="col-sm-8">${endpoint.cvssScore || '-'}</dd>
                    </dl>
                </div>
            </div>
        </div>
    `);
    
    modal.modal('show');
}

function filterTable(searchTerm) {
    const rows = $('#vulnerabilities-table tbody tr');
    
    rows.each(function() {
        const text = $(this).text().toLowerCase();
        $(this).toggle(text.includes(searchTerm));
    });
}

function sortTable(column) {
    const table = $('#vulnerabilities-table');
    const rows = table.find('tbody tr').toArray();
    const isAscending = table.data('sort-asc') !== true;
    
    rows.sort((a, b) => {
        const aText = $(a).find('td').eq(column).text();
        const bText = $(b).find('td').eq(column).text();
        return isAscending ? aText.localeCompare(bText) : bText.localeCompare(aText);
    });
    
    table.data('sort-asc', isAscending);
    table.find('tbody').empty().append(rows);
}

function resetCounters() {
    $('#total-vulnerabilities').text('0');
    $('#high-risk-count').text('0');
    $('#medium-risk-count').text('0');
    $('#low-risk-count').text('0');
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return '-';
    try {
        return new Date(dateString).toLocaleString();
    } catch (e) {
        return dateString;
    }
}

// Add custom styles
$('<style>')
    .text(`
        .progress {
            height: 20px;
            margin-bottom: 0;
        }
        .progress-bar {
            min-width: 2em;
        }
        .table td {
            vertical-align: middle;
        }
        .table th {
            padding: 0.75rem;
            vertical-align: middle;
            white-space: nowrap;
        }
        .filter-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            z-index: 1000;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 0.5rem;
            min-width: 200px;
            margin-top: 0.25rem;
        }
        .form-select {
            font-size: 0.875rem;
            padding: 0.25rem;
            width: 100%;
        }
        #endpointGroupFilterIcon {
            transition: color 0.2s;
        }
        #endpointGroupFilterIcon:hover {
            color: #0d6efd !important;
        }
        #endpointGroupFilterIcon.text-primary {
            color: #0d6efd !important;
        }
    `)
    .appendTo('head');