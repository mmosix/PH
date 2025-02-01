<?php
require_once __DIR__.'/../../app/Auth.php';
require_once __DIR__.'/../../app/Report.php';

Auth::checkRole(['admin', 'client', 'contractor']);

$userId = $_SESSION['user']['id'];
$role = $_SESSION['user']['role'];

$financialReport = Report::generateFinancialReport($userId, $role);
$projectReport = Report::generateProjectReport($userId, $role);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Construction PM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation Bar -->
    <?php include "../includes/{$role}_nav.php"; ?>

    <!-- Main Content -->
    <div class="container mt-4">
        <h2>Reports</h2>
        
        <!-- Financial Report -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Financial Summary</h5>
                <ul>
                    <li>Total Budget: $<?= number_format($financialReport['total_budget'], 2) ?></li>
                    <li>Total Payments: $<?= number_format($financialReport['total_payments'], 2) ?></li>
                </ul>
            </div>
        </div>

        <!-- Project Report -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Project Summary</h5>
                <ul>
                    <li>Total Projects: <?= $projectReport['total_projects'] ?></li>
                    <li>Completed Projects: <?= $projectReport['completed_projects'] ?></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>