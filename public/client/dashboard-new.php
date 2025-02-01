<?php
require_once __DIR__.'/../../app/Auth.php';
require_once __DIR__.'/../../app/Models/Project.php';
require_once __DIR__.'/../../app/Calendar.php';
require_once __DIR__.'/../../app/FileUpload.php';
require_once __DIR__.'/../../app/Notification.php';

// Ensure user is logged in and has client role
Auth::checkRole(['client']);
$userId = $_SESSION['user']['id'];

// Get all client's projects
$projects = Project::getByClient($userId);

// Get active project if one is selected
$activeProject = null;
if (isset($_GET['project_id'])) {
    $activeProject = Project::getById($_GET['project_id']);
    $projectPhases = Project::getPhases($_GET['project_id']);
    $teamMembers = Project::getTeamMembers($_GET['project_id']);
    $paymentMilestones = Project::getPaymentMilestones($_GET['project_id']);
    $workLogs = Project::getWorkLogs($_GET['project_id']);
    $documents = FileUpload::getByProject($_GET['project_id']);
}

// Get notifications
$notifications = Notification::getUnread($userId);

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['submit_feedback'])) {
            $feedbackData = [
                'project_id' => $_POST['project_id'],
                'user_id' => $userId,
                'subject' => $_POST['subject'],
                'message' => $_POST['message'],
                'status' => 'Open'
            ];
            Project::addFeedback($feedbackData);
            $success = "Feedback submitted successfully";
        } elseif (isset($_POST['submit_support_ticket'])) {
            $ticketData = [
                'project_id' => $_POST['project_id'],
                'user_id' => $userId,
                'subject' => $_POST['subject'],
                'description' => $_POST['description'],
                'priority' => $_POST['priority'],
                'status' => 'Open'
            ];
            Project::addSupportTicket($ticketData);
            $success = "Support ticket created successfully";
        }
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
    <title>Client Dashboard</title>
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

        <?php if ($activeProject): ?>
            <!-- Project Overview -->
            <div class="project-overview">
                <h2><?php echo htmlspecialchars($activeProject['title']); ?></h2>
                <div class="project-details">
                    <div class="detail-grid">
                        <div class="detail">
                            <label>Project ID:</label>
                            <span><?php echo $activeProject['id']; ?></span>
                        </div>
                        <div class="detail">
                            <label>Status:</label>
                            <span class="status-badge <?php echo strtolower($activeProject['status']); ?>">
                                <?php echo $activeProject['status']; ?>
                            </span>
                        </div>
                        <div class="detail">
                            <label>Location:</label>
                            <span><?php echo htmlspecialchars($activeProject['property_address']); ?></span>
                        </div>
                        <div class="detail">
                            <label>Estimated Completion:</label>
                            <span><?php echo date('M d, Y', strtotime($activeProject['estimated_completion_date'])); ?></span>
                        </div>
                    </div>
                    <div class="completion-tracker">
                        <label>Project Completion:</label>
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

                <!-- Live Camera Feed -->
                <div class="live-feed">
                    <h3>Live Site View</h3>
                    <div id="liveCameraFeed" class="camera-feed"></div>
                    <div class="feed-controls">
                        <button onclick="switchCamera('drone')">Drone View</button>
                        <button onclick="switchCamera('site')">Site Camera</button>
                    </div>
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="financial-summary">
                <h3>Financial Overview</h3>
                <div class="finance-grid">
                    <div class="finance-card">
                        <label>Total Budget:</label>
                        <span class="amount">$<?php echo number_format($activeProject['total_budget'], 2); ?></span>
                    </div>
                    <div class="finance-card">
                        <label>Amount Spent:</label>
                        <span class="amount">$<?php echo number_format($activeProject['amount_spent'], 2); ?></span>
                    </div>
                    <div class="finance-card">
                        <label>Remaining Balance:</label>
                        <span class="amount">$<?php echo number_format($activeProject['remaining_balance'], 2); ?></span>
                    </div>
                </div>

                <!-- Payment Milestones -->
                <div class="payment-schedule">
                    <h4>Payment Schedule</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Milestone</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paymentMilestones as $milestone): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($milestone['title']); ?></td>
                                    <td>$<?php echo number_format($milestone['amount'], 2); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($milestone['due_date'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($milestone['status']); ?>">
                                            <?php echo $milestone['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($milestone['status'] === 'Pending'): ?>
                                            <button class="btn-small" onclick="processPayment(<?php echo $milestone['id']; ?>)">
                                                Pay Now
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Loan Information -->
                <div class="loan-info">
                    <h4>Financing Details</h4>
                    <div class="loan-details">
                        <div class="loan-item">
                            <label>Loan Amount:</label>
                            <span>$<?php echo number_format($activeProject['loan_amount'], 2); ?></span>
                        </div>
                        <div class="loan-item">
                            <label>Interest Rate:</label>
                            <span><?php echo $activeProject['loan_interest_rate']; ?>%</span>
                        </div>
                        <div class="loan-item">
                            <label>Monthly Payment:</label>
                            <span>$<?php echo number_format($activeProject['monthly_payment'], 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Project Phases -->
            <div class="project-phases">
                <h3>Construction Progress</h3>
                <div class="phase-timeline">
                    <?php foreach ($projectPhases as $phase): ?>
                        <div class="phase-card">
                            <div class="phase-header">
                                <h4><?php echo htmlspecialchars($phase['name']); ?></h4>
                                <span class="status-badge <?php echo strtolower($phase['status']); ?>">
                                    <?php echo $phase['status']; ?>
                                </span>
                            </div>
                            <div class="phase-progress">
                                <div class="progress-bar">
                                    <div class="progress" style="width: <?php echo $phase['completion_percentage']; ?>%">
                                        <?php echo number_format($phase['completion_percentage'], 1); ?>%
                                    </div>
                                </div>
                            </div>
                            <div class="phase-dates">
                                <span class="start-date">
                                    Start: <?php echo date('M d', strtotime($phase['start_date'])); ?>
                                </span>
                                <span class="end-date">
                                    End: <?php echo date('M d, Y', strtotime($phase['end_date'])); ?>
                                </span>
                            </div>
                            <?php if (!empty($phase['delay_notes'])): ?>
                                <div class="delay-notes">
                                    <strong>Delay Note:</strong>
                                    <?php echo htmlspecialchars($phase['delay_notes']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Team Information -->
            <div class="team-info">
                <h3>Project Team</h3>
                <div class="team-grid">
                    <?php foreach ($teamMembers as $member): ?>
                        <div class="team-card">
                            <div class="member-info">
                                <h4><?php echo htmlspecialchars($member['name']); ?></h4>
                                <span class="role"><?php echo $member['role']; ?></span>
                            </div>
                            <div class="contact-options">
                                <a href="mailto:<?php echo $member['email']; ?>" class="contact-btn">
                                    <i class="fas fa-envelope"></i> Email
                                </a>
                                <a href="tel:<?php echo $member['phone']; ?>" class="contact-btn">
                                    <i class="fas fa-phone"></i> Call
                                </a>
                                <button class="contact-btn" onclick="startVideoCall('<?php echo $member['id']; ?>')">
                                    <i class="fas fa-video"></i> Video Call
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Document Center -->
            <div class="document-center">
                <h3>Project Documents</h3>
                <div class="document-grid">
                    <?php 
                    $documentCategories = [
                        'contracts' => 'Contracts',
                        'blueprints' => 'Blueprints',
                        'permits' => 'Permits',
                        'inspections' => 'Inspection Reports',
                        'designs' => 'Design Renderings'
                    ];
                    
                    foreach ($documentCategories as $category => $title): ?>
                        <div class="document-category">
                            <h4><?php echo $title; ?></h4>
                            <div class="document-list">
                                <?php 
                                $categoryDocs = array_filter($documents, function($doc) use ($category) {
                                    return $doc['category'] === $category;
                                });
                                foreach ($categoryDocs as $doc): ?>
                                    <div class="document-item">
                                        <span class="doc-name">
                                            <?php echo htmlspecialchars($doc['title']); ?>
                                        </span>
                                        <div class="doc-actions">
                                            <a href="/documents/download.php?id=<?php echo $doc['id']; ?>" 
                                               class="btn-small">
                                                Download
                                            </a>
                                            <button class="btn-small" 
                                                    onclick="previewDocument(<?php echo $doc['id']; ?>)">
                                                Preview
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($category !== 'contracts' && $category !== 'permits'): ?>
                                <div class="upload-section">
                                    <button class="upload-btn" 
                                            onclick="showUploadForm('<?php echo $category; ?>')">
                                        Upload New Document
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Project Updates -->
            <div class="project-updates">
                <h3>Recent Updates</h3>
                <div class="updates-grid">
                    <div class="work-logs">
                        <h4>Work Log Summary</h4>
                        <?php foreach ($workLogs as $log): ?>
                            <div class="log-entry">
                                <div class="log-header">
                                    <span class="date"><?php echo date('M d, Y', strtotime($log['log_date'])); ?></span>
                                    <span class="author"><?php echo htmlspecialchars($log['user_name']); ?></span>
                                </div>
                                <div class="log-content">
                                    <?php echo nl2br(htmlspecialchars($log['description'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Photo Gallery -->
                    <div class="photo-gallery">
                        <h4>Construction Progress Photos</h4>
                        <div class="gallery-controls">
                            <button onclick="filterPhotos('all')">All</button>
                            <button onclick="filterPhotos('before')">Before</button>
                            <button onclick="filterPhotos('during')">Progress</button>
                            <button onclick="filterPhotos('after')">Completed</button>
                        </div>
                        <div class="gallery-grid" id="photoGallery">
                            <!-- Photos will be loaded dynamically -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Interaction -->
            <div class="customer-interaction">
                <div class="feedback-section">
                    <h3>Provide Feedback</h3>
                    <form method="POST" action="" class="feedback-form">
                        <input type="hidden" name="project_id" value="<?php echo $activeProject['id']; ?>">
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" required></textarea>
                        </div>
                        <button type="submit" name="submit_feedback">Submit Feedback</button>
                    </form>
                </div>

                <div class="support-section">
                    <h3>Support Tickets</h3>
                    <form method="POST" action="" class="support-form">
                        <input type="hidden" name="project_id" value="<?php echo $activeProject['id']; ?>">
                        <div class="form-group">
                            <label for="ticket_subject">Subject</label>
                            <input type="text" id="ticket_subject" name="subject" required>
                        </div>
                        <div class="form-group">
                            <label for="ticket_description">Description</label>
                            <textarea id="ticket_description" name="description" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="priority">Priority</label>
                            <select id="priority" name="priority" required>
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>
                        <button type="submit" name="submit_support_ticket">Create Ticket</button>
                    </form>
                </div>
            </div>

        <?php else: ?>
            <!-- Project Selection -->
            <div class="project-selection">
                <h2>Your Projects</h2>
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
                                <div class="project-meta">
                                    <span class="location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($project['property_address']); ?>
                                    </span>
                                    <span class="completion-date">
                                        <i class="fas fa-calendar"></i>
                                        Est. Completion: <?php echo date('M d, Y', strtotime($project['estimated_completion_date'])); ?>
                                    </span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- JavaScript -->
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY"></script>
    <script src="/assets/js/project-map.js"></script>
    <script src="/assets/js/live-feed.js"></script>
    <script src="/assets/js/photo-gallery.js"></script>
    <script src="/assets/js/payment-processing.js"></script>
    <script src="/assets/js/video-call.js"></script>
    <script src="/assets/js/document-preview.js"></script>
    <script>
        function processPayment(milestoneId) {
            // Implementation for payment processing
        }

        function startVideoCall(userId) {
            // Implementation for video call initialization
        }

        function showUploadForm(category) {
            // Implementation for document upload form
        }

        function previewDocument(docId) {
            // Implementation for document preview
        }

        function filterPhotos(type) {
            // Implementation for photo gallery filtering
        }

        function switchCamera(view) {
            // Implementation for switching camera views
        }
    </script>
</body>
</html>