<?php
// Authentication is handled by the plugin
global $plugin;

// Include required scripts
require_once(__DIR__ . '/components/header.php');
?>

<div class="container-fluid mt-4">
    <h1>Trend Vision One Overview</h1>
    
    <!-- Summary Boxes -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Endpoints</h5>
                    <h2 id="totalEndpoints">-</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Client Workloads</h5>
                    <h2 id="clientWorkloads">-</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Server Workloads</h5>
                    <h2 id="serverWorkloads">-</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Outdated Components</h5>
                    <h2 id="outdatedComponents">-</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Endpoints Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">All Endpoints</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="trendEndpointsTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Endpoint Name</th>
                            <th>OS Name</th>
                            <th>IP Address</th>
                            <th>Last Connected</th>
                            <th>Status</th>
                            <th>Component Version</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="7" class="text-center">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Include Modal -->
<?php require_once(__DIR__ . '/components/endpoint_details_modal.php'); ?>

<script>
$(document).ready(function() {
    initializeEndpointsTable('all');
    
    // Refresh data every 30 seconds
    setInterval(() => initializeEndpointsTable('all'), 30000);
});

function initializeEndpointsTable(workloadType) {
    // Initialize DataTable
    var table = $('#trendEndpointsTable').DataTable({
        "processing": true,
        "serverSide": false,
        "ajax": {
            "url": "/api/plugin/TrendVisionOne/getfulldesktops",
            "dataSrc": function(json) {
                return json.data.items.map(function(item) {
                    return [
                        item.displayName,
                        item.osName,
                        item.ipAddresses ? item.ipAddresses.join(', ') : '',
                        item.lastConnectedDateTime,
                        '<span class="badge ' + (item.status === 'on' ? 'bg-success' : 'bg-danger') + '">' + 
                            (item.status === 'on' ? 'Online' : 'Offline') + '</span>',
                        item.componentVersion,
                        '<button class="btn btn-sm btn-info view-details" data-id="' + item.agentGuid + 
                            '"><i class="fas fa-info-circle"></i> Details</button>'
                    ];
                });
            }
        },
        "columns": [
            { "title": "Endpoint Name" },
            { "title": "OS Name" },
            { "title": "IP Address" },
            { "title": "Last Connected" },
            { "title": "Status" },
            { "title": "Component Version" },
            { "title": "Actions" }
        ]
    });

    // Handle click on details button
    $('#trendEndpointsTable').on('click', '.view-details', function() {
        var id = $(this).data('id');
        $.ajax({
            url: '/api/plugin/TrendVisionOne/getendpointdetails/' + id,
            method: 'GET',
            success: function(response) {
                if (response.result === 'Success') {
                    var data = response.data;
                    $('#agentGuid').text(data.agentGuid || '');
                    $('#displayName').text(data.displayName || '');
                    $('#osName').text(data.osName || '');
                    $('#osVersion').text(data.osVersion || '');
                    $('#ipAddresses').text(data.ipAddresses ? data.ipAddresses.join(', ') : '');
                    $('#lastConnectedDateTime').text(data.lastConnectedDateTime || '');
                    $('#endpointStatus')
                        .text(data.status === 'on' ? 'Online' : 'Offline')
                        .removeClass('bg-success bg-danger')
                        .addClass(data.status === 'on' ? 'bg-success' : 'bg-danger');
                    $('#endpointGroup').text(data.endpointGroup || '');
                    $('#protectionManager').text(data.protectionManager || '');
                    $('#edrConnectivity')
                        .text(data.edrConnectivity || '')
                        .removeClass('bg-success bg-danger bg-warning')
                        .addClass(data.edrConnectivity === 'Connected' ? 'bg-success' : data.edrConnectivity === 'Disconnected' ? 'bg-danger' : 'bg-warning');
                    $('#edrLastConnected').text(data.edrLastConnected || '');
                    $('#edrVersion').text(data.edrVersion || '');
                    $('#edrStatus')
                        .text(data.edrStatus || '')
                        .removeClass('bg-success bg-danger bg-warning')
                        .addClass(data.edrStatus === 'Healthy' ? 'bg-success' : data.edrStatus === 'Unhealthy' ? 'bg-danger' : 'bg-warning');
                    $('#edrAdvancedRiskTelemetry')
                        .text(data.edrAdvancedRiskTelemetry || '')
                        .removeClass('bg-success bg-danger bg-warning')
                        .addClass(data.edrAdvancedRiskTelemetry === 'Enabled' ? 'bg-success' : data.edrAdvancedRiskTelemetry === 'Disabled' ? 'bg-danger' : 'bg-warning');
                    $('#eppPolicyName').text(data.eppPolicyName || '');
                    $('#eppStatus')
                        .text(data.eppStatus || '')
                        .removeClass('bg-success bg-danger bg-warning')
                        .addClass(data.eppStatus === 'Healthy' ? 'bg-success' : data.eppStatus === 'Unhealthy' ? 'bg-danger' : 'bg-warning');
                    $('#eppLastConnected').text(data.eppLastConnected || '');
                    $('#eppVersion').text(data.eppVersion || '');
                    $('#eppComponentVersion')
                        .text(data.eppComponentVersion || '')
                        .removeClass('bg-success bg-danger bg-warning')
                        .addClass(data.eppComponentVersion === 'Up-to-date' ? 'bg-success' : data.eppComponentVersion === 'Outdated' ? 'bg-danger' : 'bg-warning');
                    $('#endpointDetailsModal').modal('show');
                } else {
                    toastr.error('Failed to load endpoint details: ' + response.message);
                }
            },
            error: function() {
                toastr.error('Failed to load endpoint details');
            }
        });
    });
}
</script>