<?php
// Add required CSS and JS for DataTables
?>
<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

<!-- DataTables JavaScript -->
<script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<div class="container-fluid">
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
            <h5 class="card-title mb-0">Trend Vision One Endpoints</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="trendEndpointsTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th class="align-bottom">Endpoint Name</th>
                            <th class="align-bottom">OS Name</th>
                            <th class="align-bottom">IP Address</th>
                            <th class="position-relative" style="min-width: 150px;">
                                <div class="d-flex align-items-center">
                                    <span>Endpoint Group</span>
                                    <i class="fas fa-filter ms-2 text-muted" style="cursor: pointer;" id="endpointGroupFilterIcon"></i>
                                </div>
                                <div id="endpointGroupFilterContainer" class="filter-dropdown" style="display: none;">
                                    <select id="endpointGroupFilter" class="form-select form-select-sm">
                                        <option value="">All Groups</option>
                                    </select>
                                </div>
                            </th>
                            <th class="align-bottom">Last Connected</th>
                            <th class="align-bottom">Status</th>
                            <th class="align-bottom">Component Version</th>
                            <th class="align-bottom">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal for showing endpoint details -->
<div class="modal fade" id="endpointDetailsModal" tabindex="-1" aria-labelledby="endpointDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="endpointDetailsModalLabel">Endpoint Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="mb-3">General Information</h6>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Agent GUID:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext" id="agentGuid">-</p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Display Name:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext" id="displayName">-</p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">OS Name:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext" id="osName">-</p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">OS Version:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext" id="osVersion">-</p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">IP Addresses:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext" id="ipAddresses">-</p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Last Connected:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext" id="lastConnectedDateTime">-</p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Status:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext"><span id="endpointStatus" class="badge">-</span></p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Endpoint Group:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext" id="endpointGroup">-</p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Protection Manager:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext" id="protectionManager">-</p>
                    </div>
                </div>

                <h6 class="mt-4 mb-3">EDR Information</h6>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Connectivity:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext"><span id="edrConnectivity" class="badge">-</span></p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Last Connected:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext" id="edrLastConnected">-</p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Version:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext" id="edrVersion">-</p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Status:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext"><span id="edrStatus" class="badge">-</span></p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Advanced Risk Telemetry:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext"><span id="edrAdvancedRiskTelemetry" class="badge">-</span></p>
                    </div>
                </div>

                <h6 class="mt-4 mb-3">EPP Information</h6>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Policy Name:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext" id="eppPolicyName">-</p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Status:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext"><span id="eppStatus" class="badge">-</span></p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Last Connected:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext" id="eppLastConnected">-</p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Version:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext" id="eppVersion">-</p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Component Version:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext"><span id="eppComponentVersion" class="badge">-</span></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add custom styles -->
<style>
    /* DataTables header filter styling */
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 1rem;
    }
    
    /* Make the group filter dropdown look nice */
    .form-select-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        border-radius: 0.2rem;
        width: auto;
        display: inline-block;
    }
    
    /* Ensure table headers align properly with filters */
    #trendEndpointsTable thead th {
        padding-bottom: 15px;
    }
</style>

<script>
$(document).ready(function() {
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
                        item.endpointGroup,
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
            { "title": "Endpoint Group" },
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
});
</script>