<?php
$trendvisiononeplugin = new trendvisionone();
$trendvisiononepluginConfig = $trendvisiononeplugin->config->get('Plugins', 'TrendVisionOne');
if ($trendvisiononeplugin->auth->checkAccess($trendvisiononepluginConfig['ACL-READ'] ?? null) == false) {
    $trendvisiononeplugin->api->setAPIResponse('Error', 'Unauthorized', 401);
    return;
}

?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1>Vulnerability Dashboard</h1>
        </div>
        <div class="col-auto">
            <span id="lastSyncTime">Last synced: Never</span>
            <button id="syncBtn" class="btn btn-primary ms-2" onclick="syncVulnerabilityData()">
                <i class="fas fa-sync-alt"></i> Sync Data
            </button>
        </div>
    </div>

    <!-- Severity Summary Dashboard -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">High Severity</h5>
                    <h2 class="card-text" id="HighSeverity"></h2>
                    <p class="card-text">CVEs with CVSS ≥ 7.0</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Medium Severity</h5>
                    <h2 class="card-text" id="MediumSeverity"></h2>
                    <p class="card-text">CVEs with 4.0 ≤ CVSS < 7.0</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Low Severity</h5>
                    <h2 class="card-text" id="LowSeverity"></h2>
                    <p class="card-text">CVEs with CVSS < 4.0</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <table id="vulnerabilitiesTable"></table>
        </div>
    </div>
</div>

<!-- CVE Details Modal -->
<div class="modal fade" id="cveDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">CVE Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="cveDetailsContent">
                <table id="CVEDetailsTable"></table>
            </div>
        </div>
    </div>
</div>

<script>
var table;

function initTable() {
    table = $("#vulnerabilitiesTable").bootstrapTable({
        url: "/api/plugin/TrendVisionOne/vulnerabilities",
        responseHandler: "MainTableHandler",
        pagination: true,
        search: true,
        sortable: true,
        columns: [{
            field: "device_name",
            title: "Device Name",
            sortable: true
        }, {
            field: "ip_addresses",
            title: "IP Addresses",
            formatter: function(value) {
                if (!Array.isArray(value)) return "";
                var ipv4Only = value.filter(function(ip) { return !ip.includes(":"); });
                return ipv4Only.join(", ");
            }
        }, {
            field: "cve_count",
            title: "CVE Count",
            sortable: true
        }, {
            field: "highest_cvss",
            title: "Highest CVSS",
            sortable: true,
            formatter: function(value) {
                return value ? parseFloat(value).toFixed(1) : "0.0";
            }
        }, {
            field: "highest_risk",
            title: "Risk Level",
            sortable: true
        }, {
            field: "actions",
            title: "Actions",
            formatter: VulnerabilityViewButtonFormatter,
            events: "DeviceDetailsButtonEvents"
        }]
    });
}

function initCVETable(row) {
    console.log(row);
    $("#CVEDetailsTable").bootstrapTable("destroy");
    $("#CVEDetailsTable").bootstrapTable({
        data: [row],
        pagination: true,
        search: true,
        sortable: true,
        columns: [{
            field: "cve.id",
            title: "CVE ID",
            sortable: true
        }, {
            field: "cve.cvss_score",
            title: "CVSS Score",
            sortable: true,
            formatter: function(value) {
                return value ? value.charAt(0).toUpperCase() + value.slice(1) : "";
            }
        }, {
            field: "cve.event_risk_level",
            title: "CVE Risk Level",
            formatter: function(value) {
                if (!Array.isArray(value)) return "";
                var ipv4Only = value.filter(function(ip) { return !ip.includes(":"); });
                return ipv4Only.join(", ");
            }
        }, {
            field: "cve.affectedComponents",
            title: "CVE Affected Components",
            sortable: true
        }, {
            field: "actions",
            title: "Actions",
            formatter: VulnerabilityViewButtonFormatter            
        }]
    });
}

function syncVulnerabilityData() {
    $("#syncBtn").prop("disabled", true);
    $("#syncBtn i").addClass("fa-spin");
    
    queryAPI("/plugin/TrendVisionOne/syncvulnerabilities", "GET")
        .then(function(response) {
            if (response.result === "Success") {
                // Reload the page to get fresh data
                location.reload();
            } else {
                alert("Error syncing data: " + response.message);
            }
        })
        .catch(function(error) {
            alert("Error syncing data: " + error);
        })
        .finally(function() {
            $("#syncBtn").prop("disabled", false);
            $("#syncBtn i").removeClass("fa-spin");
        });
}

function updateLastSyncTime() {
    queryAPI("/plugin/TrendVisionOne/lastsync", "GET")
        .then(function(response) {
            if (response.result === "Success" && response.data) {
                var timestamp = parseInt(response.data);
                if (!isNaN(timestamp)) {
                    var date = new Date(timestamp * 1000); // Convert Unix timestamp to milliseconds
                    $("#lastSyncTime").text("Last synced: " + date.toLocaleString());
                } else {
                    $("#lastSyncTime").text("Last synced: Invalid timestamp");
                }
            } else {
                $("#lastSyncTime").text("Last synced: Never");
            }
        })
        .catch(function() {
            $("#lastSyncTime").text("Last synced: Error fetching time");
        });
}

$(document).ready(function() {
    initTable();
    updateLastSyncTime();
});
</script>