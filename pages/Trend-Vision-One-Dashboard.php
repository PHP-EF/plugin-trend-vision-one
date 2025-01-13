<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Trend Vision One Endpoints</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="trendEndpointsTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Endpoint Name</th>
                                    <th>OS Name</th>
                                    <th>IP Address</th>
                                    <th>Last Connected</th>
                                    <th>Status</th>
                                    <th>Actions</th>
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
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

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
                        item.lastConnectedDateTime,
                        '<span class="badge ' + (item.status === 'on' ? 'bg-success' : 'bg-danger') + '">' + 
                            (item.status === 'on' ? 'Online' : 'Offline') + '</span>',
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