<?php
require_once __DIR__.'/../../app/Auth.php';
require_once __DIR__.'/../../app/Project.php';

Auth::checkRole(['admin']);

$projects = Project::getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Projects - Construction PM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation Bar -->
    <?php include '../includes/admin_nav.php'; ?>

    <!-- Main Content -->
    <div class="container mt-4">
        <h2>Projects</h2>
        <a href="/admin/projects/create.php" class="btn btn-primary mb-3">Create Project</a>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Client</th>
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
                    <td><?= $project['client_id'] ?></td>
                    <td><?= $project['contractor_id'] ?></td>
                    <td>$<?= number_format($project['budget'], 2) ?></td>
                    <td><?= $project['status'] ?></td>
                    <td>
                        <a href="/admin/projects/edit.php?id=<?= $project['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="/admin/projects/delete.php?id=<?= $project['id'] ?>" class="btn btn-sm btn-danger">Delete</a>
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