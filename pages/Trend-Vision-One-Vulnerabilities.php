<?php
$TrendVisionOnePlugin = new TrendVisionOne();
$pluginConfig = $TrendVisionOnePlugin->config->get('Plugins', 'TrendVisionOne');
if ($TrendVisionOnePlugin->auth->checkAccess($pluginConfig['ACL-READ'] ?? "ACL-READ") == false) {
    $TrendVisionOnePlugin->api->setAPIResponse('Error', 'Unauthorized', 401);
    return false;
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

<script>
// Initialize the vulnerabilities table
$(function() {
    $("#vulnerabilitiesTable").bootstrapTable({
        url: "/api/plugin/TrendVisionOne/getvulnerabledevices",
        dataField: "items",
        sortable: true,
        pagination: true,
        search: true,
        showExport: true,
        showRefresh: true,
        exportTypes: ["json", "csv", "excel"],
        showColumns: true,
        filterControl: true,
        filterControlVisible: false,
        showFilterControlSwitch: true,
        pageSize: 25,
        columns: [{
            field: "state",
            checkbox: true
        }, {
            field: "endpointName",
            title: "Endpoint Name",
            sortable: true,
            filterControl: "input"
        }, {
            field: "riskLevel",
            title: "Risk Level",
            sortable: true,
            filterControl: "select",
            formatter: riskLevelFormatter
        }, {
            field: "cvssScore",
            title: "CVSS Score",
            sortable: true,
            filterControl: "input"
        }, {
            field: "vulnerabilityId",
            title: "Vulnerability ID",
            sortable: true,
            filterControl: "input"
        }, {
            field: "productName",
            title: "Product",
            sortable: true,
            filterControl: "input",
            formatter: productFormatter
        }, {
            field: "lastDetected",
            title: "Last Detected",
            sortable: true,
            filterControl: "input",
            formatter: dateFormatter
        }, {
            field: "actions",
            title: "Actions",
            formatter: actionFormatter,
            events: window.actionEvents
        }],
        onLoadSuccess: function(data) {
            if (data.stats) {
                $('#totalVulnerabilities').text(data.stats.total || 0);
                $('#highRiskCount').text(data.stats.high || 0);
                $('#mediumRiskCount').text(data.stats.medium || 0);
                $('#lowRiskCount').text(data.stats.low || 0);
            }
        }
    });

    // Refresh data every 5 minutes
    setInterval(function() {
        $("#vulnerabilitiesTable").bootstrapTable('refresh');
    }, 300000);
});

// Formatters
function riskLevelFormatter(value) {
    const riskClass = {
        'HIGH': 'risk-high',
        'MEDIUM': 'risk-medium',
        'LOW': 'risk-low'
    }[value] || '';
    return value ? `<span class="${riskClass}">${value}</span>` : '-';
}

function productFormatter(value, row) {
    return `${value || ''} ${row.productVersion || ''}`.trim() || '-';
}

function dateFormatter(value) {
    if (!value) return '-';
    return new Date(value).toLocaleString('en-GB');
}

function actionFormatter() {
    return '<button type="button" class="btn btn-sm btn-primary view-details">Details</button>';
}

// Event handlers
window.actionEvents = {
    'click .view-details': function (e, value, row) {
        // Get modal element
        const modalEl = document.getElementById('vulnerabilityDetailsModal');
        if (!modalEl) {
            console.error('Modal element not found');
            return;
        }

        // Initialize modal if needed
        let modal = bootstrap.Modal.getInstance(modalEl);
        if (!modal) {
            modal = new bootstrap.Modal(modalEl);
        }

        // Fetch endpoint details
        $.ajax({
            url: '/api/plugin/TrendVisionOne/getendpointdetails',
            method: 'GET',
            data: { endpointId: row.agentGuid },
            success: function(response) {
                if (response.result === 'Success' && response.data) {
                    const endpoint = response.data;
                    const vulnerability = endpoint.vulnerabilities.find(v => v.id === row.vulnerabilityId) || {};
                    
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

                    // Show modal
                    modal.show();
                }
            },
            error: function() {
                $('.modal-body .vulnerability-info').html(`
                    <div class="alert alert-danger">Failed to load vulnerability details.</div>
                `);
                modal.show();
            }
        });
    }
};
</script>
