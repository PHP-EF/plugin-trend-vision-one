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
                
                // Initialize DataTable
                var table = $('#trendEndpointsTable').DataTable({
                    "processing": true,
                    "serverSide": false,
                    "columns": [
                        { "data": "displayName" },
                        { "data": "os.name" },
                        { "data": "lastUsedIp" },
                        { "data": "eppAgent.endpointGroup" },
                        { "data": "lastConnectedDateTime" },
                        { "data": "agentUpdateStatus" },
                        { "data": "eppAgent.componentVersion" },
                        { "data": null, "orderable": false }
                    ],
                    "columnDefs": [
                        {
                            "targets": 3,
                            "render": function(data, type, row) {
                                return row.eppAgent?.endpointGroup || '-';
                            }
                        },
                        {
                            "targets": 5,
                            "render": function(data, type, row) {
                                const isOnline = row.agentUpdateStatus === 'onSchedule';
                                return `<span class="badge ${isOnline ? 'bg-success' : 'bg-danger'} text-white">
                                    ${isOnline ? 'Online' : 'Offline'}</span>`;
                            }
                        },
                        {
                            "targets": 6,
                            "render": function(data, type, row) {
                                const version = row.eppAgent?.componentVersion || '-';
                                const isOutdated = version.toLowerCase() === 'outdatedversion';
                                return `<span class="badge ${isOutdated ? 'bg-danger' : 'bg-success'} text-white">
                                    ${version}</span>`;
                            }
                        },
                        {
                            "targets": -1,
                            "render": function(data, type, row) {
                                return `<button class="btn btn-info btn-sm" onclick="showEndpointDetails('${row.agentGuid}')">
                                    <i class="fas fa-info-circle"></i> Details</button>`;
                            }
                        }
                    ],
                    "order": [[0, 'asc']],
                    "pageLength": 25,
                    "dom": '<"top"lf>rt<"bottom"ip><"clear">',
                    "initComplete": function() {
                        // Add filter for Endpoint Group
                        this.api().columns(3).every(function() {
                            var column = this;
                            var select = $('<select class="form-select form-select-sm"><option value="">All Groups</option></select>')
                                .appendTo($(column.header()))
                                .on('change', function() {
                                    var val = $.fn.dataTable.util.escapeRegex($(this).val());
                                    column.search(val ? '^'+val+'$' : '', true, false).draw();
                                });

                            // Get unique endpoint groups
                            var groups = new Set();
                            column.data().unique().sort().each(function(d) {
                                if (d) groups.add(d);
                            });
                            
                            // Add options
                            groups.forEach(function(group) {
                                select.append('<option value="'+group+'">'+group+'</option>');
                            });
                        });
                    }
                });
                
                // Add data to DataTable
                table.clear();
                table.rows.add(endpoints);
                table.draw();
            } else {
                console.error('Invalid response structure:', response);
                $('#trendEndpointsTable tbody').html(`
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
            
            $('#trendEndpointsTable tbody').html(`
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
    `)
    .appendTo('head');

// Initial load of endpoints data
$(document).ready(function() {
    updateEndpointsTable();
    
    // Refresh data every 30 seconds
    setInterval(updateEndpointsTable, 30000);
});