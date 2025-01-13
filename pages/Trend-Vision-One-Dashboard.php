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
                <div class="row mb-3">
                    <div class="col-4"><strong>Agent GUID:</strong></div>
                    <div class="col-8" id="agentGuid"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Display Name:</strong></div>
                    <div class="col-8" id="displayName"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>OS Name:</strong></div>
                    <div class="col-8" id="osName"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>OS Version:</strong></div>
                    <div class="col-8" id="osVersion"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>IP Addresses:</strong></div>
                    <div class="col-8" id="ipAddresses"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Last Connected:</strong></div>
                    <div class="col-8" id="lastConnectedDateTime"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Status:</strong></div>
                    <div class="col-8"><span id="endpointStatus" class="badge"></span></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Endpoint Group:</strong></div>
                    <div class="col-8" id="endpointGroup"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Protection Manager:</strong></div>
                    <div class="col-8" id="protectionManager"></div>
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