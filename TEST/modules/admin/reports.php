<?php
// Start session and load configuration
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../auth/User.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
    exit;
}

// Initialize variables
$message = '';
$error = '';
$reports = [];

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'generate_report') {
        $reportType = $_POST['report_type'] ?? '';
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        
        // Here you would typically generate the report based on the selected type
        $message = 'Report generated successfully';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .reports-container {
            padding: 20px;
        }
        .report-card {
            margin-bottom: 20px;
        }
        .chart-container {
            height: 300px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php require_once INCLUDES_PATH . '/header.php'; ?>
    
    <div class="container reports-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Reports</h1>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" class="mb-4">
            <input type="hidden" name="action" value="generate_report">
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="report_type" class="form-label">Report Type</label>
                        <select class="form-select" id="report_type" name="report_type" required>
                            <option value="">Select a report type</option>
                            <option value="properties">Properties Overview</option>
                            <option value="clients">Client Activity</option>
                            <option value="valuations">Property Valuations</option>
                            <option value="users">User Activity</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                    </div>
                </div>
            </div>
            
            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-chart-bar"></i> Generate Report
                </button>
            </div>
        </form>
        
        <!-- Report Results -->
        <div class="row">
            <div class="col-md-6">
                <div class="card report-card">
                    <div class="card-header">
                        <h5 class="mb-0">Properties Overview</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <!-- Placeholder for property chart -->
                            <p class="text-center text-muted">Select a report type and date range to generate charts</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card report-card">
                    <div class="card-header">
                        <h5 class="mb-0">Client Activity</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <!-- Placeholder for client activity chart -->
                            <p class="text-center text-muted">Select a report type and date range to generate charts</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Data Tables -->
        <div class="card report-card">
            <div class="card-header">
                <h5 class="mb-0">Report Data</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Placeholder for report data -->
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    Select a report type and date range to view data
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once INCLUDES_PATH . '/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html> 