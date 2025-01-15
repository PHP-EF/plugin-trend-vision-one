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
        dataType: 'json',
        success: function(response) {
            console.log('API Response:', response);
            
            if (response && response.result === 'Success' && response.data && response.data.items && Array.isArray(response.data.items.items)) {
                const endpoints = response.data.items.items;
                
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
            } else {
                console.error('Invalid response structure:', response);
                $('#trendEndpointsTable tbody').html(`
                    <tr>
                        <td colspan="8" class="text-center">
                            ${response.message || 'Error loading endpoints'}
                        </td>
                    </tr>
                `);
            }
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

    // Function to populate filter dropdown
    function populateEndpointGroupFilter(data) {
        if (!Array.isArray(data)) {
            console.error('Data is not an array:', data);
            return;
        }

        const select = $('#endpointGroupFilter');
        const currentValue = select.val();
        select.find('option:not(:first)').remove();

        // Get unique endpoint groups
        const groups = [...new Set(data.map(item => item.eppAgent?.endpointGroup).filter(Boolean))].sort();
        
        // Add options
        groups.forEach(group => {
            select.append($('<option>', {
                value: group,
                text: group
            }));
        });

        // Restore previous selection if it exists
        if (currentValue && groups.includes(currentValue)) {
            select.val(currentValue);
        }
    }

    // Function to update table content
    function updateTableContent(data) {
        const tableBody = $('#trendEndpointsTable tbody');
        tableBody.empty();

        if (data.length === 0) {
            tableBody.html('<tr><td colspan="8" class="text-center">No endpoints available</td></tr>');
            return;
        }

        // Get selected group filter
        const selectedGroup = $('#endpointGroupFilter').val();

        // Filter data if group is selected
        const filteredData = selectedGroup 
            ? data.filter(item => item.eppAgent?.endpointGroup === selectedGroup)
            : data;

        filteredData.forEach(endpoint => {
            const isOnline = endpoint.agentUpdateStatus === 'onSchedule';
            const row = $('<tr>');

            row.append(`<td>${endpoint.displayName || '-'}</td>`);
            row.append(`<td>${endpoint.os?.name || '-'}</td>`);
            row.append(`<td>${endpoint.lastUsedIp || '-'}</td>`);
            row.append(`<td>${endpoint.eppAgent?.endpointGroup || '-'}</td>`);
            row.append(`<td>${formatDateTime(endpoint.eppAgent?.lastConnectedDateTime)}</td>`);
            
            // Status column with badge
            const statusBadge = `<span class="badge ${isOnline ? 'bg-success' : 'bg-danger'}">${isOnline ? 'Online' : 'Offline'}</span>`;
            row.append(`<td>${statusBadge}</td>`);
            
            // Component Version column with badge
            const componentVersion = endpoint.eppAgent?.componentVersion || '-';
            const versionBadgeClass = componentVersion.toLowerCase() === 'outdatedversion' ? 'bg-danger' : 'bg-success';
            row.append(`<td><span class="badge ${versionBadgeClass}">${componentVersion}</span></td>`);
            
            // Actions column
            const detailsButton = `
                <button class="btn btn-sm btn-info" onclick="showEndpointDetails('${endpoint.agentGuid}')">
                    <i class="fas fa-info-circle"></i> Details
                </button>`;
            row.append(`<td>${detailsButton}</td>`);
            
            tableBody.append(row);
        });

        // Update summary boxes
        updateSummaryBoxes(data);
    }

    // Function to update summary boxes
    function updateSummaryBoxes(data) {
        const totalEndpoints = data.length;
        const clientWorkloads = data.filter(e => e.os?.name?.toLowerCase().includes('windows')).length;
        const serverWorkloads = data.filter(e => e.os?.name?.toLowerCase().includes('server')).length;
        const outdatedComponents = data.filter(e => e.eppAgent?.componentVersion?.toLowerCase() === 'outdatedversion').length;

        $('#totalEndpoints').text(totalEndpoints);
        $('#clientWorkloads').text(clientWorkloads);
        $('#serverWorkloads').text(serverWorkloads);
        $('#outdatedComponents').text(outdatedComponents);
    }

    // Function to format date time
    function formatDateTime(dateTimeStr) {
        if (!dateTimeStr) return '-';
        const date = new Date(dateTimeStr);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }

    // Update endpoints table
    function updateEndpointsTable() {
        $.ajax({
            url: '/api/plugin/TrendVisionOne/getfulldesktops?top=1000',
            method: 'GET',
            success: function(response) {
                if (!response || response.result !== 'Success' || !response.data || !response.data.items) {
                    console.error('Invalid response format:', response);
                    return;
                }

                const items = response.data.items;
                populateEndpointGroupFilter(items);
                updateTableContent(items);
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