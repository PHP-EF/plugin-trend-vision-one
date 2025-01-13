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
        const filterSelect = $('#endpointGroupFilter');
        filterSelect.toggle();
        
        if (filterSelect.is(':visible')) {
            filterSelect.focus();
        }
    });

    // Close filter dropdown when clicking outside
    $(document).click(function(e) {
        if (!$(e.target).closest('#endpointGroupFilter').length) {
            $('#endpointGroupFilter').hide();
        }
    });

    // Add filter change handler
    $('#endpointGroupFilter').on('change', function() {
        updateEndpointsTable();
        // Add active class to icon when filter is applied
        $('#endpointGroupFilterIcon').toggleClass('text-primary', $(this).val() !== '');
        $(this).hide();
    });

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
            padding-top: 1rem;
            padding-bottom: 1rem;
            vertical-align: bottom;
        }
        #endpointGroupFilter {
            font-size: 0.875rem;
            padding: 0.25rem;
            position: absolute;
            width: calc(100% - 2rem);
            z-index: 1000;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        #endpointGroupFilterIcon:hover {
            color: #0d6efd !important;
        }
    `)
    .appendTo('head');