<?php
$pageData = [
    'title' => 'Trend Vision One - Vulnerabilities',
    'description' => 'View and manage vulnerable devices detected by Trend Vision One',
];
?>

<!-- Custom CSS for the vulnerabilities page -->
<style>
.vulnerability-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--surface-card);
    padding: 1rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-card h3 {
    margin: 0;
    color: var(--text-color);
    font-size: 1rem;
}

.stat-card .value {
    font-size: 2rem;
    font-weight: bold;
    color: var(--primary-color);
}

.risk-high {
    color: #dc3545;
}

.risk-medium {
    color: #ffc107;
}

.risk-low {
    color: #28a745;
}
</style>

<!-- Main content -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2>Vulnerability Overview</h2>
    </div>
    <div class="card-body">
        <!-- Statistics Cards -->
        <div class="vulnerability-stats">
            <div class="stat-card">
                <h3>Total Vulnerabilities</h3>
                <div class="value" id="totalVulnerabilities">-</div>
            </div>
            <div class="stat-card">
                <h3>High Risk</h3>
                <div class="value risk-high" id="highRiskCount">-</div>
            </div>
            <div class="stat-card">
                <h3>Medium Risk</h3>
                <div class="value risk-medium" id="mediumRiskCount">-</div>
            </div>
            <div class="stat-card">
                <h3>Low Risk</h3>
                <div class="value risk-low" id="lowRiskCount">-</div>
            </div>
        </div>

        <!-- Vulnerabilities Table -->
        <div class="table-responsive">
            <table class="table table-striped" id="vulnerabilitiesTable">
                <thead>
                    <tr>
                        <th>Endpoint Name</th>
                        <th>Risk Level</th>
                        <th>CVSS Score</th>
                        <th>Vulnerability ID</th>
                        <th>Product</th>
                        <th>Last Detected</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be populated by DataTables -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="vulnerabilityDetailsModal" tabindex="-1" aria-labelledby="vulnerabilityDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vulnerabilityDetailsModalLabel">Vulnerability Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Content will be populated by JavaScript -->
                <div class="vulnerability-info">
                    <h6>Endpoint Information</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> <span id="modal-endpoint-name"></span></p>
                            <p><strong>OS:</strong> <span id="modal-endpoint-os"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>IP:</strong> <span id="modal-endpoint-ip"></span></p>
                            <p><strong>Last Connected:</strong> <span id="modal-endpoint-last-connected"></span></p>
                        </div>
                    </div>
                    
                    <h6>Vulnerability Information</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>ID:</strong> <span id="modal-vuln-id"></span></p>
                            <p><strong>Risk Level:</strong> <span id="modal-risk-level"></span></p>
                            <p><strong>CVSS Score:</strong> <span id="modal-cvss-score"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Product:</strong> <span id="modal-product"></span></p>
                            <p><strong>Version:</strong> <span id="modal-version"></span></p>
                            <p><strong>Last Detected:</strong> <span id="modal-last-detected"></span></p>
                        </div>
                    </div>
                    
                    <h6>Description</h6>
                    <div class="mb-3">
                        <p id="modal-description"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Include DataTables -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<!-- Initialize DataTable -->
<script>
$(document).ready(function() {
    var table = $('#vulnerabilitiesTable').DataTable({
        "processing": true,
        "serverSide": false,
        "ajax": {
            "url": "/api/plugin/TrendVisionOne/getvulnerabledevices",
            "type": "GET",
            "dataSrc": function(json) {
                // Update statistics
                if (json.stats) {
                    $('#totalVulnerabilities').text(json.stats.total || 0);
                    $('#highRiskCount').text(json.stats.high || 0);
                    $('#mediumRiskCount').text(json.stats.medium || 0);
                    $('#lowRiskCount').text(json.stats.low || 0);
                }
                return json.items || [];
            }
        },
        "columns": [
            { "data": "endpointName" },
            { 
                "data": "riskLevel",
                "render": function(data, type, row) {
                    const riskClass = {
                        'HIGH': 'risk-high',
                        'MEDIUM': 'risk-medium',
                        'LOW': 'risk-low'
                    }[data] || '';
                    return `<span class="${riskClass}">${data}</span>`;
                }
            },
            { "data": "cvssScore" },
            { "data": "vulnerabilityId" },
            { 
                "data": null,
                "render": function(data, type, row) {
                    return `${data.productName || ''} ${data.productVersion || ''}`.trim() || '-';
                }
            },
            { 
                "data": "lastDetected",
                "render": function(data, type, row) {
                    if (!data) return '-';
                    return new Date(data).toLocaleString('en-GB');
                }
            },
            {
                "data": null,
                "orderable": false,
                "render": function(data, type, row) {
                    return `<button class="btn btn-sm btn-primary view-details" data-id="${data.agentGuid}" data-vuln-id="${data.vulnerabilityId}">Details</button>`;
                }
            }
        ],
        "order": [[1, "desc"]], // Sort by risk level by default
        "pageLength": 25,
        "responsive": true
    });

    // Handle vulnerability details button click
    $('#vulnerabilitiesTable').on('click', '.view-details', function() {
        var agentGuid = $(this).data('id');
        var vulnId = $(this).data('vuln-id');
        
        // Show modal
        var modal = new bootstrap.Modal(document.getElementById('vulnerabilityDetailsModal'));
        modal.show();

        // Fetch endpoint details
        $.ajax({
            url: '/api/plugin/TrendVisionOne/getendpointdetails',
            method: 'GET',
            data: { endpointId: agentGuid },
            success: function(response) {
                if (response.result === 'Success' && response.data) {
                    const endpoint = response.data;
                    const vulnerability = endpoint.vulnerabilities.find(v => v.id === vulnId) || {};
                    
                    // Update modal content
                    $('#modal-endpoint-name').text(endpoint.endpointName || '-');
                    $('#modal-endpoint-os').text(endpoint.osName || '-');
                    $('#modal-endpoint-ip').text(endpoint.ip || '-');
                    $('#modal-endpoint-last-connected').text(endpoint.lastSeen ? new Date(endpoint.lastSeen).toLocaleString('en-GB') : '-');
                    
                    $('#modal-vuln-id').text(vulnerability.id || '-');
                    $('#modal-risk-level').html(`<span class="risk-${vulnerability.riskLevel?.toLowerCase()}">${vulnerability.riskLevel || '-'}</span>`);
                    $('#modal-cvss-score').text(vulnerability.cvssScore || '-');
                    $('#modal-product').text(vulnerability.productName || '-');
                    $('#modal-version').text(vulnerability.productVersion || '-');
                    $('#modal-last-detected').text(vulnerability.lastDetected ? new Date(vulnerability.lastDetected).toLocaleString('en-GB') : '-');
                    $('#modal-description').text(vulnerability.description || 'No description available');
                }
            },
            error: function() {
                $('.modal-body .vulnerability-info').html(`
                    <div class="alert alert-danger">Failed to load vulnerability details.</div>
                `);
            }
        });
    });

    // Refresh data every 5 minutes
    setInterval(function() {
        table.ajax.reload(null, false);
    }, 300000);
});
</script>
