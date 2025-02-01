<?php
require_once __DIR__.'/../../app/Auth.php';
require_once __DIR__.'/../../app/Notification.php';

Auth::checkRole(['admin', 'client', 'contractor']);

$userId = $_SESSION['user']['id'];
$notifications = Notification::getUnread($userId);

// Mark notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($notifications as $notification) {
        Notification::markAsRead($notification['id']);
    }
    header("Location: /notifications.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Construction PM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation Bar -->
    <?php include "../includes/{$_SESSION['user']['role']}_nav.php"; ?>

    <!-- Main Content -->
    <div class="container mt-4">
        <h2>Notifications</h2>
        
        <?php if (empty($notifications)): ?>
            <div class="alert alert-info">No new notifications.</div>
        <?php else: ?>
            <form method="POST">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Message</th>
                            <th>Type</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notifications as $notification): ?>
                        <tr>
                            <td><?= htmlspecialchars($notification['message']) ?></td>
                            <td><?= $notification['type'] ?></td>
                            <td><?= $notification['created_at'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" class="btn btn-primary">Mark All as Read</button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>