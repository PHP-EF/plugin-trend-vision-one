<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once(BASEPATH . '/inc/plugins/TrendVisionOne/plugin.php');

$plugin = new TrendVisionOnePlugin();
if (!$plugin->isAuthenticated()) {
    header('Location: ' . $plugin->getLoginUrl());
    exit;
}
?>

<div class="container-fluid mt-4">
    <h1>Trend Vision One Client Workloads</h1>
    
    <!-- Client Workloads Table -->
    <div class="card">
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

<!-- Details Modal -->
<?php include(__DIR__ . '/components/endpoint_details_modal.php'); ?>

<script>
$(document).ready(function() {
    initializeEndpointsTable('client');
    
    // Refresh data every 30 seconds
    setInterval(() => initializeEndpointsTable('client'), 30000);
});
</script>
