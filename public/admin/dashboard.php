<?php
require_once __DIR__.'/../../vendor/autoload.php';
// Database class will be autoloaded

// Check if user is logged in and has admin role
\App\Auth::checkRole(['admin']);

// Fetch data for the dashboard
$pdo = \App\Database::connect();
$users = $pdo->query("SELECT * FROM users")->fetchAll();
$projects = $pdo->query("SELECT * FROM projects")->fetchAll();
$payments = $pdo->query("
    SELECT SUM(amount) as total_payments 
    FROM payments 
    WHERE status = 'confirmed'
")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Construction PM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Admin Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="/admin/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/projects.php">Projects</a>
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
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Users</h5>
                        <h2><?= count($users) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Projects</h5>
                        <h2><?= count($projects) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
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
                <h5 class="card-title">Recent Projects</h5>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Client</th>
                            <th>Contractor</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                        <tr>
                            <td><?= htmlspecialchars($project['name']) ?></td>
                            <td><?= $project['client_id'] ?></td>
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