<?php
require_once __DIR__.'/../../app/Auth.php';
require_once __DIR__.'/../../app/Calendar.php';

Auth::checkRole(['admin', 'client', 'contractor']);

$userId = $_SESSION['user']['id'];
$role = $_SESSION['user']['role'];

$milestones = Calendar::getMilestones($userId, $role);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar - Construction PM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation Bar -->
    <?php include "../includes/{$role}_nav.php"; ?>

    <!-- Main Content -->
    <div class="container mt-4">
        <h2>Project Calendar</h2>
        
        <div id="calendar"></div>
    </div>

    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: [
                    <?php foreach ($milestones as $milestone): ?>
                    {
                        title: '<?= addslashes($milestone['description']) ?>',
                        start: '<?= $milestone['created_at'] ?>',
                        url: '/projects/view.php?id=<?= $milestone['project_id'] ?>'
                    },
                    <?php endforeach; ?>
                ]
            });
            calendar.render();
        });
    </script>
</body>
</html>