<?php
  $plugin = new trendvisionone();
  $pluginConfig = $plugin->config->get('Plugins','TrendVisionOne');
  if ($plugin->auth->checkAccess($pluginConfig['ACL-READ'] ?? null) == false) {
    $plugin->api->setAPIResponse('Error','Unauthorized',401);
    return false;
  };

  $content = '
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h2>Vulnerability Dashboard</h2>
        </div>
        <div class="col-auto">
            <button id="syncBtn" class="btn btn-primary" onclick="syncVulnerabilityData()">
                <i class="fas fa-sync"></i> Sync Data
            </button>
            <span id="lastSyncTime" class="ms-2"></span>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <table id="vulnerabilitiesTable" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th data-field="device_name">Device Name</th>
                        <th data-field="criticality">Criticality</th>
                        <th data-field="ip_addresses">IP Addresses</th>
                        <th data-field="cve_count">CVE Count</th>
                        <th data-field="highest_cvss">Highest CVSS</th>
                        <th data-field="highest_risk">Risk Level</th>
                        <th data-field="actions">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Modal for CVE Details -->
<div class="modal fade" id="cveDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">CVE Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="cveDetailsContent"></div>
            </div>
        </div>
    </div>
</div>

<script>
let table;

$(document).ready(function() {
    initTable();
    updateLastSyncTime();
});

function initTable() {
    table = $("#vulnerabilitiesTable").bootstrapTable({
        url: "/api/plugin/TrendVisionOne/vulnerabilities",
        pagination: true,
        search: true,
        sortable: true,
        columns: [{
            field: "device_name",
            title: "Device Name",
            sortable: true
        }, {
            field: "criticality",
            title: "Criticality",
            sortable: true
        }, {
            field: "ip_addresses",
            title: "IP Addresses"
        }, {
            field: "cve_count",
            title: "CVE Count",
            sortable: true
        }, {
            field: "highest_cvss",
            title: "Highest CVSS",
            sortable: true
        }, {
            field: "highest_risk",
            title: "Risk Level",
            sortable: true
        }, {
            field: "actions",
            title: "Actions",
            formatter: function(value, row) {
                return \'<button class="btn btn-info btn-sm" onclick="showCVEDetails("\' + row.id + \'")">View Details</button>\';
            }
        }]
    });
}

function syncVulnerabilityData() {
    $("#syncBtn").prop("disabled", true);
    $("#syncBtn i").addClass("fa-spin");
    
    $.get("/api/plugin/TrendVisionOne/syncvulnerabilities")
        .done(function(response) {
            if (response.result === "Success") {
                table.bootstrapTable("refresh");
                updateLastSyncTime();
            } else {
                alert("Error syncing data: " + response.message);
            }
        })
        .fail(function(jqXHR) {
            alert("Error syncing data: " + jqXHR.responseText);
        })
        .always(function() {
            $("#syncBtn").prop("disabled", false);
            $("#syncBtn i").removeClass("fa-spin");
        });
}

function updateLastSyncTime() {
    $.get("/api/plugin/TrendVisionOne/lastsync")
        .done(function(response) {
            if (response.result === "Success" && response.data) {
                const date = new Date(response.data * 1000);
                $("#lastSyncTime").text("Last synced: " + date.toLocaleString());
            }
        });
}

function showCVEDetails(deviceId) {
    $.get("/api/plugin/TrendVisionOne/device/" + deviceId + "/vulnerabilities")
        .done(function(response) {
            if (response.result === "Success") {
                let content = \'<div class="table-responsive"><table class="table table-striped">\';
                content += \'<thead><tr><th>CVE ID</th><th>CVSS Score</th><th>Risk Level</th><th>Description</th></tr></thead><tbody>\';
                
                response.data.forEach(function(cve) {
                    content += \'<tr>\';
                    content += \'<td>\' + cve.cve_id + \'</td>\';
                    content += \'<td>\' + cve.cvss_score + \'</td>\';
                    content += \'<td>\' + cve.event_risk_level + \'</td>\';
                    content += \'<td>\' + cve.description + \'</td>\';
                    content += \'</tr>\';
                });
                
                content += \'</tbody></table></div>\';
                $("#cveDetailsContent").html(content);
                new bootstrap.Modal("#cveDetailsModal").show();
            }
        });
}
</script>';

return $content;