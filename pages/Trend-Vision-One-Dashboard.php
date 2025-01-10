<!-- <div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Veeam Backup Sessions</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="veeamSessionsTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Session Type</th>
                                    <th>Platform</th>
                                    <th>Name</th>
                                    <th>Created</th>
                                    <th>Ended</th>
                                    <th>Progress</th>
                                    <th>Result</th>
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

<!-- Modal for showing session details -->
<div class="modal fade" id="sessionDetailsModal" tabindex="-1" aria-labelledby="sessionDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sessionDetailsModalLabel">Session Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-4"><strong>Status:</strong></div>
                    <div class="col-8"><span id="resultStatus" class="badge"></span></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Message:</strong></div>
                    <div class="col-8" id="resultMessage"></div>
                </div>
                <div class="row">
                    <div class="col-4"><strong>Canceled:</strong></div>
                    <div class="col-8" id="resultCanceled"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div> -->