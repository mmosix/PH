<?php
require_once __DIR__.'/../../app/Auth.php';
require_once __DIR__.'/../../app/Project.php';

Auth::checkRole(['client']);

$userId = $_SESSION['user']['id'];
$projects = Project::getByClient($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Projects - Construction PM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation Bar -->
    <?php include '../includes/client_nav.php'; ?>

    <!-- Main Content -->
    <div class="container mt-4">
        <h2>My Projects</h2>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Contractor</th>
                    <th>Budget</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $project): ?>
                <tr>
                    <td><?= htmlspecialchars($project['name']) ?></td>
                    <td><?= $project['contractor_id'] ?></td>
                    <td>$<?= number_format($project['budget'], 2) ?></td>
                    <td><?= $project['status'] ?></td>
                    <td>
                        <a href="/client/projects/view.php?id=<?= $project['id'] ?>" class="btn btn-sm btn-primary">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>