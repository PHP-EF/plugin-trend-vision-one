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

function EndpointViewButtonFormatter(value, row, index) {
    return `<button class="btn btn-sm btn-info details" data-id="${row.agentGuid}">
              <i class="fas fa-eye"></i> View Details
            </button>`;
}

function StatusFormatter(value, row) {
    const isOnline = row.agentUpdateStatus === 'onSchedule';
    return `<span class="${getEndpointStatusBadgeClass(isOnline)}">${isOnline ? 'Online' : 'Offline'}</span>`;
}

function ComponentVersionFormatter(value, row) {
    const componentVersion = row.eppAgent?.componentVersion || '-';
    const componentVersionClass = componentVersion.toLowerCase() === 'outdatedversion' ? 'bg-danger' : 'bg-success';
    return `<span class="badge ${componentVersionClass}">${componentVersion}</span>`;
}

// Initialize the endpoints table using Bootstrap Table
function initEndpointsTable() {
    $('#trendEndpointsTable').bootstrapTable({
        url: '/api/plugin/TrendVisionOne/getfulldesktops?top=1000',
        method: 'GET',
        pagination: true,
        search: true,
        sortable: true,
        responseHandler: function(res) {
            if (res && res.result === 'Success' && res.data && res.data.items && Array.isArray(res.data.items.items)) {
                const endpoints = res.data.items.items;
                updateEndpointSummary(endpoints);
                return endpoints;
            }
            return [];
        },
        columns: [{
            field: 'displayName',
            title: 'Endpoint Name',
            sortable: true
        }, {
            field: 'os.name',
            title: 'Operating System',
            sortable: true,
            formatter: function(value, row) {
                return row.os?.name || row.osName || '-';
            }
        }, {
            field: 'lastUsedIp',
            title: 'IP Address',
            sortable: true,
            formatter: function(value) {
                return value || '-';
            }
        }, {
            field: 'eppAgent.endpointGroup',
            title: 'Endpoint Group',
            sortable: true,
            formatter: function(value) {
                return value || '-';
            }
        }, {
            field: 'eppAgent.lastConnectedDateTime',
            title: 'Last Connected',
            sortable: true,
            formatter: formatDateTime
        }, {
            field: 'agentUpdateStatus',
            title: 'Status',
            sortable: true,
            formatter: StatusFormatter
        }, {
            field: 'eppAgent.componentVersion',
            title: 'Component Version',
            sortable: true,
            formatter: ComponentVersionFormatter
        }, {
            field: 'actions',
            title: 'Actions',
            formatter: EndpointViewButtonFormatter,
            events: window.EndpointDetailsButtonEvents
        }]
    });
}

window.EndpointDetailsButtonEvents = {
    'click .details': function(e, value, row, index) {
        showEndpointDetails(row.agentGuid);
    }
};

function showEndpointDetails(endpointId) {
    queryAPI('GET', '/api/plugin/TrendVisionOne/getendpointdetails/' + endpointId)
        .then(function(response) {
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
                
                // Show the modal
                $('#endpointDetailsModal').modal('show');
            } else {
                console.error('Failed to fetch endpoint details:', response);
                alert('Failed to fetch endpoint details. Please try again.');
            }
        })
        .catch(function(error) {
            console.error('Error fetching endpoint details:', error);
            alert('Error fetching endpoint details. Please try again.');
        });
}

function getEndpointStatusBadgeClass(isOnline) {
    return `badge ${isOnline ? 'bg-success' : 'bg-danger'}`;
}

// Handle refresh button click
$('#refreshTable').click(function() {
    const $btn = $(this);
    const $icon = $btn.find('i');
    
    $btn.prop('disabled', true);
    $icon.addClass('fa-spin');
    
    $('#trendEndpointsTable').bootstrapTable('refresh')
        .then(function() {
            const now = new Date();
            $('#lastRefreshTime').text('Last refreshed: ' + now.toLocaleString());
        })
        .catch(function(error) {
            console.error('Error refreshing data:', error);
            alert('Error refreshing data. Please try again.');
        })
        .finally(function() {
            $btn.prop('disabled', false);
            $icon.removeClass('fa-spin');
        });
});

// Initialize on document ready
$(document).ready(function() {
    initEndpointsTable();
    const now = new Date();
    $('#lastRefreshTime').text('Last refreshed: ' + now.toLocaleString());
});

////// All new code for Vulns Dashboard DON'T EDIT PAST THIS POINT
// Function for action formatter for the table
function VulnerabilityViewButtonFormatter(value, row, index) {
    var actions = [
        '<button class="btn details btn-info btn-sm">View Details</button>'
    ];
    return actions.join("");
}

window.DeviceDetailsButtonEvents = {
    "click .details": function (e, value, row, index) {
        initCVETable(row);
        $('#cveDetailsModal').modal('show');
    }
}
window.CVEDetailsButtonEvents = {
    "click .details": function (e, value, row, index) {
        CVEDetailsPopulate(row);
    }
}

function MainTableHandler(data){
    $("#HighSeverity").text(data.data.severity_counts.high)
    $("#MediumSeverity").text(data.data.severity_counts.medium)
    $("#LowSeverity").text(data.data.severity_counts.low)
    return data.data.devices;
}

// Function to sync vulnerability data
function syncVulnerabilityData() {
    const $btn = $('#syncBtn');
    const $icon = $btn.find('i');
    
    // Disable button and show loading state
    $btn.prop('disabled', true);
    $icon.addClass('fa-spin');
    
    queryAPI('GET', '/api/plugin/TrendVisionOne/syncvulnerabilities')
        .then(function(response) {
            if (response.result === 'Success') {
                // Update last sync time
                $('#lastSyncTime').text('Last synced: ' + formatDateTime('2025-01-19T20:27:31Z'));
                // Refresh the vulnerability table if it exists
                if (typeof MainTableHandler === 'function') {
                    MainTableHandler();
                }
            } else {
                console.error('Sync failed:', response.message);
                alert('Failed to sync vulnerability data: ' + (response.message || 'Please try again.'));
            }
        })
        .catch(function(error) {
            console.error('Sync error:', error);
            alert('Error syncing vulnerability data. Please try again.');
        })
        .finally(function() {
            // Re-enable button and remove loading state
            $btn.prop('disabled', false);
            $icon.removeClass('fa-spin');
        });
}

// Add custom styles
// $('<style>')
//     .text(`
//         .progress {
//             height: 20px;
//             margin-bottom: 0;
//         }
//         .progress-bar {
//             min-width: 2em;
//         }
//         .table td {
//             vertical-align: middle;
//         }
//         .table th {
//             padding: 0.75rem;
//             vertical-align: middle;
//         }
//         .filter-dropdown {
//             position: absolute;
//             top: 100%;
//             left: 0;
//             z-index: 1000;
//             background: white;
//             border: 1px solid #dee2e6;
//             border-radius: 0.25rem;
//             box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
//             padding: 0.5rem;
//             min-width: 200px;
//             margin-top: 0.25rem;
//         }
//         #endpointGroupFilter {
//             font-size: 0.875rem;
//             padding: 0.25rem;
//             width: 100%;
//         }
//         #endpointGroupFilterIcon {
//             transition: color 0.2s;
//         }
//         #endpointGroupFilterIcon:hover {
//             color: #0d6efd !important;
//         }
//         #endpointGroupFilterIcon.text-primary {
//             color: #0d6efd !important;
//         }
//     `)
//     .appendTo('head');