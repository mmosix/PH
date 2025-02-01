<?php
require_once __DIR__.'/../../app/Auth.php';
require_once __DIR__.'/../../app/Models/Project.php';
require_once __DIR__.'/../../app/Calendar.php';
require_once __DIR__.'/../../app/FileUpload.php';
require_once __DIR__.'/../../app/Notification.php';

// Ensure user is logged in and has admin role
Auth::checkRole(['admin']);

// Get system overview data
$totalProjects = count(Project::getAll());
$activeProjects = count(array_filter(Project::getAll(), function($p) {
    return $p['status'] === Project::STATUS_ACTIVE;
}));

// Handle project management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['create_project'])) {
            Project::create($_POST);
            $success = "Project created successfully";
        } elseif (isset($_POST['update_project'])) {
            Project::update($_POST['project_id'], $_POST);
            $success = "Project updated successfully";
        } elseif (isset($_POST['delete_project'])) {
            Project::delete($_POST['project_id']);
            $success = "Project deleted successfully";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get active project if one is selected
$activeProject = null;
if (isset($_GET['project_id'])) {
    $activeProject = Project::getById($_GET['project_id']);
    $projectPhases = Project::getPhases($_GET['project_id']);
    $teamMembers = Project::getTeamMembers($_GET['project_id']);
    $paymentMilestones = Project::getPaymentMilestones($_GET['project_id']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
</head>
<body>
    <div class="admin-dashboard">
        <!-- System Overview -->
        <div class="system-overview">
            <h2>System Overview</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Projects</h3>
                    <span class="stat-value"><?php echo $totalProjects; ?></span>
                </div>
                <div class="stat-card">
                    <h3>Active Projects</h3>
                    <span class="stat-value"><?php echo $activeProjects; ?></span>
                </div>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Project Management -->
        <div class="project-management">
            <h2>Project Management</h2>
            
            <!-- Create New Project -->
            <div class="create-project">
                <h3>Create New Project</h3>
                <form method="POST" action="" class="project-form">
                    <div class="form-group">
                        <label for="title">Project Title</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="client_id">Client</label>
                        <select id="client_id" name="client_id" required>
                            <?php foreach (User::getByRole('client') as $client): ?>
                                <option value="<?php echo $client['id']; ?>">
                                    <?php echo htmlspecialchars($client['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="contractor_id">Contractor</label>
                        <select id="contractor_id" name="contractor_id" required>
                            <?php foreach (User::getByRole('contractor') as $contractor): ?>
                                <option value="<?php echo $contractor['id']; ?>">
                                    <?php echo htmlspecialchars($contractor['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="property_address">Property Address</label>
                        <input type="text" id="property_address" name="property_address" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="budget">Budget</label>
                            <input type="number" id="budget" name="budget" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="estimated_completion_date">Estimated Completion</label>
                            <input type="date" id="estimated_completion_date" name="estimated_completion_date" required>
                        </div>
                    </div>
                    <button type="submit" name="create_project">Create Project</button>
                </form>
            </div>

            <?php if ($activeProject): ?>
                <!-- Active Project Details -->
                <div class="active-project">
                    <h3><?php echo htmlspecialchars($activeProject['title']); ?></h3>
                    
                    <!-- Project Overview -->
                    <div class="project-overview">
                        <div class="detail-row">
                            <div class="detail">
                                <label>Status:</label>
                                <span class="status-badge <?php echo strtolower($activeProject['status']); ?>">
                                    <?php echo $activeProject['status']; ?>
                                </span>
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
                        
                        <div class="detail-row">
                            <div class="detail">
                                <label>Client:</label>
                                <span><?php echo htmlspecialchars($activeProject['client_name']); ?></span>
                            </div>
                            <div class="detail">
                                <label>Contractor:</label>
                                <span><?php echo htmlspecialchars($activeProject['contractor_name']); ?></span>
                            </div>
                        </div>
                        
                        <div class="detail">
                            <label>Location:</label>
                            <span><?php echo htmlspecialchars($activeProject['property_address']); ?></span>
                        </div>
                    </div>

                    <!-- Project Phases -->
                    <div class="project-phases">
                        <h4>Construction Phases</h4>
                        <div class="phase-list">
                            <?php foreach ($projectPhases as $phase): ?>
                                <div class="phase-item">
                                    <div class="phase-header">
                                        <h5><?php echo htmlspecialchars($phase['name']); ?></h5>
                                        <span class="status-badge <?php echo strtolower($phase['status']); ?>">
                                            <?php echo $phase['status']; ?>
                                        </span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress" style="width: <?php echo $phase['completion_percentage']; ?>%">
                                            <?php echo number_format($phase['completion_percentage'], 1); ?>%
                                        </div>
                                    </div>
                                    <div class="phase-dates">
                                        <?php echo date('M d', strtotime($phase['start_date'])); ?> -
                                        <?php echo date('M d, Y', strtotime($phase['end_date'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Payment Milestones -->
                    <div class="payment-milestones">
                        <h4>Payment Schedule</h4>
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
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
                                            <button class="btn-small" onclick="updatePaymentStatus(<?php echo $milestone['id']; ?>)">
                                                Update
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Team Members -->
                    <div class="team-members">
                        <h4>Project Team</h4>
                        <div class="team-grid">
                            <?php foreach ($teamMembers as $member): ?>
                                <div class="team-card">
                                    <div class="team-info">
                                        <h5><?php echo htmlspecialchars($member['name']); ?></h5>
                                        <span class="role"><?php echo $member['role']; ?></span>
                                    </div>
                                    <div class="contact-info">
                                        <a href="mailto:<?php echo $member['email']; ?>" class="btn-small">Email</a>
                                        <a href="tel:<?php echo $member['phone']; ?>" class="btn-small">Call</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Project List -->
        <div class="project-list">
            <h3>All Projects</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Client</th>
                        <th>Contractor</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (Project::getAll() as $project): ?>
                        <tr>
                            <td><?php echo $project['id']; ?></td>
                            <td><?php echo htmlspecialchars($project['title']); ?></td>
                            <td><?php echo htmlspecialchars($project['client_name']); ?></td>
                            <td><?php echo htmlspecialchars($project['contractor_name']); ?></td>
                            <td>
                                <span class="status-badge <?php echo strtolower($project['status']); ?>">
                                    <?php echo $project['status']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress" style="width: <?php echo $project['completion_percentage']; ?>%">
                                        <?php echo number_format($project['completion_percentage'], 1); ?>%
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="?project_id=<?php echo $project['id']; ?>" class="btn-small">View</a>
                                <button class="btn-small danger" onclick="confirmDelete(<?php echo $project['id']; ?>)">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="/assets/js/admin-dashboard.js"></script>
    <script>
        function confirmDelete(projectId) {
            if (confirm('Are you sure you want to delete this project?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="project_id" value="${projectId}">
                    <input type="hidden" name="delete_project" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function updatePaymentStatus(milestoneId) {
            // Implementation for updating payment milestone status
        }
    </script>
</body>
</html>