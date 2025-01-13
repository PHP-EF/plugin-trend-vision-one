<?php
// Authentication is handled by the plugin
global $plugin;

// Bootstrap and jQuery are included by the framework
?>

<div class="container-fluid mt-4">
    <h1>Trend Vision One Dashboard</h1>
    
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
                    <h5 class="card-title">Online Endpoints</h5>
                    <h2 id="onlineEndpoints">-</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Offline Endpoints</h5>
                    <h2 id="offlineEndpoints">-</h2>
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

<!-- Endpoint Details Modal -->
<div class="modal fade" id="endpointDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Endpoint Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- General Information -->
                <h6 class="border-bottom pb-2">General Information</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Agent GUID:</strong> <span id="agentGuid">-</span></p>
                        <p><strong>Display Name:</strong> <span id="displayName">-</span></p>
                        <p><strong>OS Name:</strong> <span id="osName">-</span></p>
                        <p><strong>OS Version:</strong> <span id="osVersion">-</span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>IP Address:</strong> <span id="ipAddresses">-</span></p>
                        <p><strong>Last Connected:</strong> <span id="lastConnectedDateTime">-</span></p>
                        <p><strong>Status:</strong> <span id="endpointStatus" class="badge">-</span></p>
                        <p><strong>Endpoint Group:</strong> <span id="endpointGroup">-</span></p>
                        <p><strong>Protection Manager:</strong> <span id="protectionManager">-</span></p>
                    </div>
                </div>

                <!-- EPP Information -->
                <h6 class="border-bottom pb-2">EPP Information</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Policy Name:</strong> <span id="eppPolicyName">-</span></p>
                        <p><strong>Status:</strong> <span id="eppStatus" class="badge">-</span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Last Connected:</strong> <span id="eppLastConnected">-</span></p>
                        <p><strong>Version:</strong> <span id="eppVersion">-</span></p>
                        <p><strong>Component Version:</strong> <span id="eppComponentVersion" class="badge">-</span></p>
                    </div>
                </div>

                <!-- EDR Information -->
                <h6 class="border-bottom pb-2">EDR Information</h6>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Connectivity:</strong> <span id="edrConnectivity" class="badge">-</span></p>
                        <p><strong>Last Connected:</strong> <span id="edrLastConnected">-</span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Version:</strong> <span id="edrVersion">-</span></p>
                        <p><strong>Status:</strong> <span id="edrStatus" class="badge">-</span></p>
                        <p><strong>Risk Telemetry:</strong> <span id="edrRiskTelemetry" class="badge">-</span></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initial load
    initializeEndpointsTable();
    
    // Refresh data every 30 seconds
    setInterval(() => initializeEndpointsTable(), 30000);
});

function initializeEndpointsTable() {
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