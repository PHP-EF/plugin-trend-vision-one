<?php
$TrendVisionOnePlugin = new TrendVisionOne();
$pluginConfig = $TrendVisionOnePlugin->config->get('Plugins', 'TrendVisionOne');
if ($TrendVisionOnePlugin->auth->checkAccess($pluginConfig['ACL-READ'] ?? "ACL-READ") == false) {
    $TrendVisionOnePlugin->api->setAPIResponse('Error', 'Unauthorized', 401);
    return false;
}

// Check if we need to sync data
$lastSync = intval($TrendVisionOnePlugin->getLastSync());
$syncInterval = $TrendVisionOnePlugin->getSyncInterval();
$currentTime = time();

if (($currentTime - $lastSync) >= $syncInterval) {
    // Sync data from Vision One API
    $TrendVisionOnePlugin->getVulnerableDevices();
}
?>

<!-- Bootstrap Table Dependencies -->
<link href="https://unpkg.com/bootstrap-table@1.20.2/dist/bootstrap-table.min.css" rel="stylesheet">
<link href="https://unpkg.com/bootstrap-table@1.20.2/dist/extensions/filter-control/bootstrap-table-filter-control.min.css" rel="stylesheet">
<script src="https://unpkg.com/bootstrap-table@1.20.2/dist/bootstrap-table.min.js"></script>
<script src="https://unpkg.com/bootstrap-table@1.20.2/dist/extensions/filter-control/bootstrap-table-filter-control.min.js"></script>
<script src="https://unpkg.com/bootstrap-table@1.20.2/dist/extensions/export/bootstrap-table-export.min.js"></script>
<script src="https://unpkg.com/tableexport.jquery.plugin/tableExport.min.js"></script>

<style>
.risk-high { color: #dc3545; }
.risk-medium { color: #ffc107; }
.risk-low { color: #28a745; }

.stat-card {
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.1);
    padding: 20px;
}

.stat-card h3 {
    font-size: 1.2rem;
    margin: 0 0 10px 0;
    color: #fff;
}

.stat-card .value {
    font-size: 2.2rem;
    font-weight: bold;
    color: #fff;
}

.bg-primary { background-color: #007bff !important; }
.bg-danger { background-color: #dc3545 !important; }
.bg-warning { background-color: #ffc107 !important; }
.bg-success { background-color: #28a745 !important; }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <h1>Vulnerability Overview</h1>
                    
                    <!-- Statistics Cards -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card bg-primary">
                                <h3>Total Vulnerabilities</h3>
                                <div class="value" id="totalVulnerabilities">-</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-danger">
                                <h3>High Risk</h3>
                                <div class="value" id="highRiskCount">-</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-warning">
                                <h3>Medium Risk</h3>
                                <div class="value" id="mediumRiskCount">-</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-success">
                                <h3>Low Risk</h3>
                                <div class="value" id="lowRiskCount">-</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vulnerabilities Table -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="container">
                        <div class="row justify-content-center">
                            <table id="vulnerabilitiesTable" class="table table-striped"></table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="vulnerabilityDetailsModal" tabindex="-1" role="dialog" aria-labelledby="vulnerabilityDetailsModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vulnerability Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="vulnerability-info">
                    <h6>Endpoint Information</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> <span id="modalEndpointName"></span></p>
                            <p><strong>OS:</strong> <span id="modalOsName"></span> <span id="modalOsVersion"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>IP:</strong> <span id="modalIp"></span></p>
                            <p><strong>Last Connected:</strong> <span id="modalLastSeen"></span></p>
                        </div>
                    </div>
                    
                    <h6>Vulnerability Information</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>ID:</strong> <span id="modalVulnerabilityId"></span></p>
                            <p><strong>CVE ID:</strong> <span id="modalCveId"></span></p>
                            <p><strong>Risk Level:</strong> <span id="modalRiskLevel"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>CVSS Score:</strong> <span id="modalCvssScore"></span></p>
                            <p><strong>Product:</strong> <span id="modalProductName"></span> <span id="modalProductVersion"></span></p>
                            <p><strong>Last Detected:</strong> <span id="modalLastDetected"></span></p>
                        </div>
                    </div>
                    
                    <h6>Description</h6>
                    <div class="mb-3">
                        <p id="modalDescription"></p>
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
    // Initialize Bootstrap Table
    $('#vulnerabilitiesTable').bootstrapTable({
        url: '/api/plugin/TrendVisionOne/vulnerabilities',
        pagination: true,
        search: true,
        showRefresh: true,
        showExport: true,
        exportTypes: ['csv', 'excel', 'pdf'],
        pageSize: 10,
        columns: [{
            field: 'endpointName',
            title: 'Endpoint Name',
            sortable: true,
            formatter: function(value, row) {
                return value || row.hostname || row.displayName || 'N/A';
            }
        }, {
            field: 'vulnerabilityId',
            title: 'Vulnerability ID',
            sortable: true
        }, {
            field: 'cveId',
            title: 'CVE ID',
            sortable: true
        }, {
            field: 'description',
            title: 'Description'
        }, {
            field: 'riskLevel',
            title: 'Risk Level',
            sortable: true,
            formatter: function(value) {
                let badgeClass = 'badge-secondary';
                if (value === 'HIGH') badgeClass = 'badge-danger';
                else if (value === 'MEDIUM') badgeClass = 'badge-warning';
                else if (value === 'LOW') badgeClass = 'badge-success';
                return '<span class="badge ' + badgeClass + '">' + value + '</span>';
            }
        }, {
            field: 'cvssScore',
            title: 'CVSS Score',
            sortable: true
        }, {
            field: 'productName',
            title: 'Product',
            formatter: function(value, row) {
                if (row.productVersion) {
                    return value + ' ' + row.productVersion;
                }
                return value;
            }
        }, {
            field: 'lastDetected',
            title: 'Last Detected',
            sortable: true,
            formatter: function(value) {
                return moment(value).format('YYYY-MM-DD HH:mm:ss');
            }
        }, {
            field: 'operate',
            title: 'Actions',
            align: 'center',
            formatter: function(value, row) {
                return [
                    '<button class="btn btn-sm btn-info view-details" title="View Details">',
                    '<i class="fas fa-eye"></i>',
                    '</button>'
                ].join('');
            },
            events: {
                'click .view-details': function(e, value, row) {
                    showVulnerabilityDetails(row);
                }
            }
        }]
    });

    // Load statistics
    function loadStats() {
        $.get('/api/plugin/TrendVisionOne/vulnerabilities', function(response) {
            if (response.success && response.data.stats) {
                $('#totalVulnerabilities').text(response.data.stats.total);
                $('#highRiskCount').text(response.data.stats.high);
                $('#mediumRiskCount').text(response.data.stats.medium);
                $('#lowRiskCount').text(response.data.stats.low);
            }
        });
    }

    loadStats();
    
    // Refresh data every 5 minutes
    setInterval(function() {
        $('#vulnerabilitiesTable').bootstrapTable('refresh');
        loadStats();
    }, 300000);
});

function showVulnerabilityDetails(vulnerability) {
    // Populate modal with vulnerability details
    $('#modalEndpointName').text(vulnerability.endpointName || vulnerability.hostname || vulnerability.displayName || 'N/A');
    $('#modalVulnerabilityId').text(vulnerability.vulnerabilityId || 'N/A');
    $('#modalCveId').text(vulnerability.cveId || 'N/A');
    $('#modalDescription').text(vulnerability.description || 'No description available');
    $('#modalRiskLevel').html(`<span class="badge badge-${vulnerability.riskLevel.toLowerCase()}">${vulnerability.riskLevel}</span>`);
    $('#modalCvssScore').text(vulnerability.cvssScore || 'N/A');
    $('#modalProductName').text(vulnerability.productName || 'N/A');
    $('#modalProductVersion').text(vulnerability.productVersion || 'N/A');
    $('#modalLastDetected').text(moment(vulnerability.lastDetected).format('YYYY-MM-DD HH:mm:ss'));
    $('#modalOsName').text(vulnerability.osName || 'N/A');
    $('#modalOsVersion').text(vulnerability.osVersion || 'N/A');
    $('#modalIp').text(vulnerability.ip || 'N/A');
    $('#modalLastSeen').text(moment(vulnerability.lastSeen).format('YYYY-MM-DD HH:mm:ss'));

    // Show the modal
    $('#vulnerabilityDetailsModal').modal('show');
}
</script>
