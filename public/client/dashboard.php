<?php
require_once __DIR__.'/../../vendor/autoload.php';
// Database class will be autoloaded

// Check if user is logged in and has client role
\App\Auth::checkRole(['client']);

$userId = $_SESSION['user']['id'];
$pdo = \App\Database::connect();

// Fetch client's projects
$projects = $pdo->prepare("
    SELECT * FROM projects 
    WHERE client_id = ?
");
$projects->execute([$userId]);
$projects = $projects->fetchAll();

// Fetch total payments
$payments = $pdo->prepare("
    SELECT SUM(amount) as total_payments 
    FROM payments 
    WHERE project_id IN (
        SELECT id FROM projects WHERE client_id = ?
    ) AND status = 'confirmed'
");
$payments->execute([$userId]);
$payments = $payments->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - Construction PM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .dashboard-container {
            padding: 20px;
        }
        .card {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Client Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="/client/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/client/projects.php">Projects</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/client/payments.php">Payments</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="dashboard-container">
        <div class="row">
            <!-- Quick Stats -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Projects</h5>
                        <h2><?= count($projects) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Payments</h5>
                        <h2>$<?= number_format($payments['total_payments'], 2) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Projects -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">My Projects</h5>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contractor</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                        <tr>
                            <td><?= htmlspecialchars($project['name']) ?></td>
                            <td><?= $project['contractor_id'] ?></td>
                            <td><?= $project['status'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>