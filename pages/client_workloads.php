<?php
// Authentication is handled by the plugin
global $plugin;

// Include required scripts
require_once(__DIR__ . '/components/header.php');
?>

<div class="container-fluid mt-4">
    <h1>Client Workloads</h1>
    
    <!-- Client Workloads Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Windows and macOS Endpoints</h5>
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

<!-- Include Modal -->
<?php require_once(__DIR__ . '/components/endpoint_details_modal.php'); ?>

<script>
$(document).ready(function() {
    initializeEndpointsTable('client');
    
    // Refresh data every 30 seconds
    setInterval(() => initializeEndpointsTable('client'), 30000);
});
</script>
