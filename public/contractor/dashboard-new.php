<?php
require_once __DIR__.'/../../app/Auth.php';
require_once __DIR__.'/../../app/Models/Project.php';
require_once __DIR__.'/../../app/Calendar.php';
require_once __DIR__.'/../../app/FileUpload.php';
require_once __DIR__.'/../../app/Notification.php';

// Ensure user is logged in and has contractor role
Auth::checkRole(['contractor']);
$userId = $_SESSION['user']['id'];

// Get all contractor's projects
$projects = Project::getByContractor($userId);

// Get active project if one is selected
$activeProject = null;
if (isset($_GET['project_id'])) {
    $activeProject = Project::getById($_GET['project_id']);
    $projectPhases = Project::getPhases($_GET['project_id']);
    $teamMembers = Project::getTeamMembers($_GET['project_id']);
    $workLogs = Project::getWorkLogs($_GET['project_id']);
}

// Get notifications
$notifications = Notification::getUnread($userId);

// Get calendar events for next 30 days
$calendar = new Calendar();
$upcomingEvents = $calendar->getUpcomingEvents($userId, 30);

// Handle work log submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_work_log'])) {
    try {
        $logData = [
            'project_id' => $_POST['project_id'],
            'user_id' => $userId,
            'log_date' => $_POST['log_date'],
            'description' => $_POST['description'],
            'hours_worked' => $_POST['hours_worked']
        ];
        
        Project::addWorkLog($logData);
        $success = "Work log submitted successfully";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contractor Dashboard</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Notifications Bar -->
        <div class="notifications-bar">
            <h3>Notifications</h3>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification <?php echo $notification['type']; ?>">
                    <?php echo htmlspecialchars($notification['message']); ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Project Overview -->
        <?php if ($activeProject): ?>
            <div class="project-overview">
                <h2><?php echo htmlspecialchars($activeProject['title']); ?></h2>
                <div class="project-details">
                    <div class="detail">
                        <label>Status:</label>
                        <span class="status-badge <?php echo strtolower($activeProject['status']); ?>">
                            <?php echo $activeProject['status']; ?>
                        </span>
                    </div>
                    <div class="detail">
                        <label>Client:</label>
                        <span><?php echo htmlspecialchars($activeProject['client_name']); ?></span>
                    </div>
                    <div class="detail">
                        <label>Location:</label>
                        <span><?php echo htmlspecialchars($activeProject['property_address']); ?></span>
                    </div>
                    <div class="detail">
                        <label>Completion:</label>
                        <div class="progress-bar">
                            <div class="progress" style="width: <?php echo $activeProject['completion_percentage']; ?>%">
                                <?php echo number_format($activeProject['completion_percentage'], 1); ?>%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Map View -->
                <div id="projectMap" class="map-container" 
                     data-lat="<?php echo $activeProject['location_coordinates']['lat']; ?>"
                     data-lng="<?php echo $activeProject['location_coordinates']['lng']; ?>">
                </div>
            </div>

            <!-- Project Phases -->
            <div class="project-phases">
                <h3>Construction Phases</h3>
                <?php foreach ($projectPhases as $phase): ?>
                    <div class="phase-card">
                        <div class="phase-header">
                            <h4><?php echo htmlspecialchars($phase['name']); ?></h4>
                            <div class="phase-actions">
                                <button class="update-status" data-phase-id="<?php echo $phase['id']; ?>">
                                    Update Status
                                </button>
                                <span class="status-badge <?php echo strtolower($phase['status']); ?>">
                                    <?php echo $phase['status']; ?>
                                </span>
                            </div>
                        </div>
                        <div class="phase-details">
                            <div class="progress-bar">
                                <div class="progress" style="width: <?php echo $phase['completion_percentage']; ?>%">
                                    <?php echo number_format($phase['completion_percentage'], 1); ?>%
                                </div>
                            </div>
                            <div class="phase-dates">
                                <span><?php echo date('M d', strtotime($phase['start_date'])); ?></span>
                                <span>to</span>
                                <span><?php echo date('M d, Y', strtotime($phase['end_date'])); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Work Log Submission -->
            <div class="work-log-form">
                <h3>Submit Work Log</h3>
                <?php if (isset($success)): ?>
                    <div class="alert success"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert error"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <input type="hidden" name="project_id" value="<?php echo $activeProject['id']; ?>">
                    <div class="form-group">
                        <label for="log_date">Date:</label>
                        <input type="date" id="log_date" name="log_date" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="description">Work Description:</label>
                        <textarea id="description" name="description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="hours_worked">Hours Worked:</label>
                        <input type="number" id="hours_worked" name="hours_worked" step="0.5" required>
                    </div>
                    <button type="submit" name="submit_work_log">Submit Work Log</button>
                </form>
            </div>

            <!-- Team Members -->
            <div class="team-members">
                <h3>Project Team</h3>
                <div class="team-grid">
                    <?php foreach ($teamMembers as $member): ?>
                        <div class="team-card">
                            <div class="team-info">
                                <h4><?php echo htmlspecialchars($member['name']); ?></h4>
                                <span class="role"><?php echo $member['role']; ?></span>
                            </div>
                            <div class="contact-info">
                                <a href="mailto:<?php echo $member['email']; ?>" class="contact-button">
                                    Email
                                </a>
                                <a href="tel:<?php echo $member['phone']; ?>" class="contact-button">
                                    Call
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recent Work Logs -->
            <div class="work-logs">
                <h3>Recent Updates</h3>
                <?php foreach ($workLogs as $log): ?>
                    <div class="log-entry">
                        <div class="log-header">
                            <span class="log-date"><?php echo date('M d, Y', strtotime($log['log_date'])); ?></span>
                            <span class="log-author"><?php echo htmlspecialchars($log['user_name']); ?></span>
                        </div>
                        <div class="log-content">
                            <?php echo nl2br(htmlspecialchars($log['description'])); ?>
                        </div>
                        <div class="log-hours">
                            Hours: <?php echo number_format($log['hours_worked'], 1); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- Project Selection -->
            <div class="project-selection">
                <h2>Select a Project</h2>
                <div class="project-grid">
                    <?php foreach ($projects as $project): ?>
                        <a href="?project_id=<?php echo $project['id']; ?>" class="project-card">
                            <h3><?php echo htmlspecialchars($project['title']); ?></h3>
                            <div class="project-card-details">
                                <span class="status-badge <?php echo strtolower($project['status']); ?>">
                                    <?php echo $project['status']; ?>
                                </span>
                                <div class="progress-bar">
                                    <div class="progress" style="width: <?php echo $project['completion_percentage']; ?>%">
                                        <?php echo number_format($project['completion_percentage'], 1); ?>%
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Calendar -->
        <div class="calendar-widget">
            <h3>Upcoming Events</h3>
            <div class="event-list">
                <?php foreach ($upcomingEvents as $event): ?>
                    <div class="event-card">
                        <div class="event-date">
                            <?php echo date('M d', strtotime($event['start_date'])); ?>
                        </div>
                        <div class="event-details">
                            <h4><?php echo htmlspecialchars($event['title']); ?></h4>
                            <p><?php echo htmlspecialchars($event['description']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY"></script>
    <script src="/assets/js/project-map.js"></script>
    <script src="/assets/js/dashboard.js"></script>
    <script src="/assets/js/phase-updates.js"></script>
</body>
</html>