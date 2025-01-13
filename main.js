// Function to format date and time
function formatDateTime(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
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
    if (!endpoints || !Array.isArray(endpoints)) {
        console.error('Invalid endpoints data:', endpoints);
        return;
    }

    const totalEndpoints = endpoints.length;
    const onlineEndpoints = endpoints.filter(endpoint => endpoint.agentUpdateStatus === 'onSchedule').length;
    const offlineEndpoints = totalEndpoints - onlineEndpoints;
    const outdatedComponents = endpoints.filter(endpoint => 
        endpoint.eppAgent?.componentVersion?.toLowerCase() === 'outdatedversion'
    ).length;

    $('#totalEndpoints').text(totalEndpoints);
    $('#onlineEndpoints').text(onlineEndpoints);
    $('#offlineEndpoints').text(offlineEndpoints);
    $('#outdatedComponents').text(outdatedComponents);
}

// Function to get appropriate badge class for endpoint status
function getEndpointStatusBadgeClass(isOnline) {
    return isOnline ? 'badge bg-success text-white' : 'badge bg-danger text-white';
}

// Function to initialize the endpoints table
function initializeEndpointsTable() {
    const tableBody = $('#trendEndpointsTable tbody');
    
    $.ajax({
        url: '/api/plugin/TrendVisionOne/getfulldesktops',
        method: 'GET',
        success: function(response) {
            if (response.result === 'Success' && Array.isArray(response.data)) {
                updateEndpointSummary(response.data);
                
                if (response.data.length === 0) {
                    tableBody.html(`
                        <tr>
                            <td colspan="7" class="text-center">
                                No endpoints available
                            </td>
                        </tr>
                    `);
                    return;
                }

                tableBody.empty();
                response.data.forEach((endpoint, index) => {
                    const row = $('<tr>');

                    row.append(`<td>${endpoint.displayName || '-'}</td>`);
                    row.append(`<td>${endpoint.os?.name || endpoint.osName || '-'}</td>`);
                    row.append(`<td>${endpoint.lastUsedIp || '-'}</td>`);
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
                tableBody.html(`
                    <tr>
                        <td colspan="7" class="text-center">
                            ${response.message || 'Error loading endpoints'}
                        </td>
                    </tr>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching endpoints:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            
            tableBody.html(`
                <tr>
                    <td colspan="7" class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error loading endpoints data
                    </td>
                </tr>
            `);
        }
    });
}

// Function to show endpoint details in modal
function showEndpointDetails(endpointId) {
    $.ajax({
        url: `/api/plugin/TrendVisionOne/getendpoint/${endpointId}`,
        method: 'GET',
        success: function(response) {
            if (response.result === 'Success' && response.data) {
                const endpoint = response.data;
                
                // Update modal fields
                $('#agentGuid').text(endpoint.agentGuid || '-');
                $('#displayName').text(endpoint.displayName || '-');
                $('#osName').text(endpoint.os?.name || endpoint.osName || '-');
                $('#osVersion').text(endpoint.os?.version || endpoint.osVersion || '-');
                $('#ipAddresses').text(endpoint.lastUsedIp || '-');
                $('#lastConnectedDateTime').text(formatDateTime(endpoint.eppAgent?.lastConnectedDateTime) || '-');
                
                const isOnline = endpoint.agentUpdateStatus === 'onSchedule';
                $('#endpointStatus')
                    .text(isOnline ? 'Online' : 'Offline')
                    .attr('class', getEndpointStatusBadgeClass(isOnline));
                
                $('#endpointGroup').text(endpoint.endpointGroup || '-');
                $('#protectionManager').text(endpoint.protectionManager || '-');
                
                // EPP Information
                $('#eppPolicyName').text(endpoint.eppAgent?.policyName || '-');
                $('#eppStatus').text(endpoint.eppAgent?.status || '-')
                    .attr('class', endpoint.eppAgent?.status === 'Active' ? 'badge bg-success' : 'badge bg-warning');
                $('#eppLastConnected').text(formatDateTime(endpoint.eppAgent?.lastConnectedDateTime) || '-');
                $('#eppVersion').text(endpoint.eppAgent?.version || '-');
                
                const componentVersion = endpoint.eppAgent?.componentVersion || '-';
                $('#eppComponentVersion')
                    .text(componentVersion)
                    .attr('class', componentVersion.toLowerCase() === 'outdatedversion' ? 'badge bg-danger' : 'badge bg-success');
                
                // EDR Information
                $('#edrConnectivity').text(endpoint.edrAgent?.connectivity || '-')
                    .attr('class', endpoint.edrAgent?.connectivity === 'Connected' ? 'badge bg-success' : 'badge bg-warning');
                $('#edrLastConnected').text(formatDateTime(endpoint.edrAgent?.lastConnectedDateTime) || '-');
                $('#edrVersion').text(endpoint.edrAgent?.version || '-');
                $('#edrStatus').text(endpoint.edrAgent?.status || '-')
                    .attr('class', endpoint.edrAgent?.status === 'Active' ? 'badge bg-success' : 'badge bg-warning');
                $('#edrRiskTelemetry').text(endpoint.edrAgent?.advancedRiskTelemetry ? 'Enabled' : 'Disabled')
                    .attr('class', endpoint.edrAgent?.advancedRiskTelemetry ? 'badge bg-success' : 'badge bg-warning');
                
                // Show the modal
                endpointModal.show();
            } else {
                console.error('Invalid endpoint details response:', response);
                alert('Error loading endpoint details');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching endpoint details:', error);
            alert('Error loading endpoint details');
        }
    });
}

// Initialize Bootstrap modal for endpoints
let endpointModal;
$(document).ready(function() {
    endpointModal = new bootstrap.Modal(document.getElementById('endpointDetailsModal'));
});

// Initial load of endpoints data
$(document).ready(function() {
    initializeEndpointsTable();
    
    // Refresh data every 30 seconds
    setInterval(() => initializeEndpointsTable(), 30000);
});