<?php
require_once __DIR__.'/../../app/Auth.php';
require_once __DIR__.'/../../app/FileUpload.php';

Auth::checkRole(['admin', 'client', 'contractor']);

$projectId = $_GET['project_id'] ?? null;
$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (FileUpload::upload($projectId, $_FILES['file'], $_POST['category'])) {
            $success = true;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$documents = FileUpload::getByProject($projectId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents - Construction PM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation Bar -->
    <?php include "../includes/{$_SESSION['user']['role']}_nav.php"; ?>

    <!-- Main Content -->
    <div class="container mt-4">
        <h2>Project Documents</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success">File uploaded successfully!</div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Upload Form -->
        <form method="POST" enctype="multipart/form-data" class="mb-4">
            <div class="mb-3">
                <label for="file" class="form-label">Choose File</label>
                <input type="file" class="form-control" id="file" name="file" required>
            </div>
            <div class="mb-3">
                <label for="category" class="form-label">Category</label>
                <select class="form-select" id="category" name="category" required>
                    <option value="contract">Contract</option>
                    <option value="blueprint">Blueprint</option>
                    <option value="permit">Permit</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Upload</button>
        </form>

        <!-- Document List -->
        <table class="table">
            <thead>
                <tr>
                    <th>Filename</th>
                    <th>Category</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $document): ?>
                <tr>
                    <td><?= htmlspecialchars($document['filename']) ?></td>
                    <td><?= $document['category'] ?></td>
                    <td>
                        <a href="<?= $document['filepath'] ?>" class="btn btn-sm btn-primary" download>Download</a>
                        <a href="/documents/delete.php?id=<?= $document['id'] ?>" class="btn btn-sm btn-danger">Delete</a>
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