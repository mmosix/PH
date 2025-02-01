<?php
require_once __DIR__.'/../../app/Auth.php';
require_once __DIR__.'/../../app/BlockchainService.php';

Auth::checkRole(['admin', 'client', 'contractor']);

$userId = $_SESSION['user']['id'];
$role = $_SESSION['user']['role'];

$pdo = Database::connect();
$payments = $pdo->prepare("
    SELECT * FROM payments 
    WHERE project_id IN (
        SELECT id FROM projects 
        WHERE client_id = ? OR contractor_id = ?
    )
");
$payments->execute([$userId, $userId]);
$payments = $payments->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Construction PM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation Bar -->
    <?php include "../includes/{$role}_nav.php"; ?>

    <!-- Main Content -->
    <div class="container mt-4">
        <h2>Payments</h2>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Transaction Hash</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                <tr>
                    <td><?= $payment['project_id'] ?></td>
                    <td>$<?= number_format($payment['amount'], 2) ?></td>
                    <td><?= $payment['status'] ?></td>
                    <td><?= $payment['tx_hash'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>