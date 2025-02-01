<?php

namespace App\Models;

use PDO;
use App\Exceptions\ProjectException;
use App\Exceptions\DatabaseException;

class Project {
    private static $logger;
    
    // Project statuses
    const STATUS_ACTIVE = 'Active';
    const STATUS_COMPLETED = 'Completed';
    const STATUS_ON_HOLD = 'On-Hold';
    
    const ALLOWED_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_COMPLETED,
        self::STATUS_ON_HOLD
    ];

    public static function initialize(): void {
        self::$logger = new \Monolog\Logger('project');
        self::$logger->pushHandler(new \Monolog\Handler\StreamHandler(
            __DIR__.'/../../logs/project.log',
            \Monolog\Logger::INFO
        ));
    }

    public static function create(array $data): array {
        global $db;
        
        try {
            self::validateProjectData($data);
            
            $fields = implode(', ', array_keys($data));
            $values = implode(', ', array_fill(0, count($data), '?'));
            
            $stmt = $db->prepare("INSERT INTO projects ({$fields}) VALUES ({$values})");
            $stmt->execute(array_values($data));
            
            $projectId = $db->lastInsertId();
            self::$logger->info('Project created', ['project_id' => $projectId]);
            
            return self::getById($projectId);
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('project creation', $e->getMessage());
        }
    }

    public static function update(int $id, array $data): array {
        global $db;
        
        try {
            if (!self::exists($id)) {
                throw ProjectException::notFound($id);
            }
            
            self::validateProjectData($data, true);
            
            $updates = [];
            foreach ($data as $field => $value) {
                $updates[] = "{$field} = ?";
            }
            $updateStr = implode(', ', $updates);
            
            $values = array_values($data);
            $values[] = $id;
            
            $stmt = $db->prepare("UPDATE projects SET {$updateStr} WHERE id = ?");
            $stmt->execute($values);
            
            return self::getById($id);
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('project update', $e->getMessage());
        }
    }

    public static function getById(int $id): array {
        global $db;
        
        try {
            $stmt = $db->prepare("
                SELECT p.*, 
                       u1.name as client_name,
                       u2.name as contractor_name,
                       u3.name as project_manager_name,
                       (SELECT COUNT(*) FROM project_phases WHERE project_id = p.id) as total_phases,
                       (SELECT COUNT(*) FROM project_phases WHERE project_id = p.id AND status = 'Completed') as completed_phases,
                       (SELECT SUM(amount) FROM payment_milestones WHERE project_id = p.id) as total_budget,
                       (SELECT SUM(amount) FROM payment_milestones WHERE project_id = p.id AND status = 'Paid') as amount_spent
                FROM projects p
                LEFT JOIN users u1 ON p.client_id = u1.id
                LEFT JOIN users u2 ON p.contractor_id = u2.id
                LEFT JOIN users u3 ON p.project_manager_id = u3.id
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            
            if ($project = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Calculate overall completion percentage
                $project['completion_percentage'] = $project['total_phases'] > 0 
                    ? ($project['completed_phases'] / $project['total_phases']) * 100 
                    : 0;
                
                // Calculate remaining balance
                $project['remaining_balance'] = $project['total_budget'] - $project['amount_spent'];
                
                return $project;
            }
            
            throw ProjectException::notFound($id);
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('project fetch', $e->getMessage());
        }
    }

    public static function getPhases(int $projectId): array {
        global $db;
        
        try {
            $stmt = $db->prepare("
                SELECT ph.*,
                       (SELECT COUNT(*) FROM phase_milestones WHERE phase_id = ph.id) as total_milestones,
                       (SELECT COUNT(*) FROM phase_milestones WHERE phase_id = ph.id AND status = 'Completed') as completed_milestones
                FROM project_phases ph
                WHERE ph.project_id = ?
                ORDER BY ph.start_date ASC
            ");
            $stmt->execute([$projectId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('phases fetch', $e->getMessage());
        }
    }

    public static function getTeamMembers(int $projectId): array {
        global $db;
        
        try {
            $stmt = $db->prepare("
                SELECT tm.*, u.name, u.email, u.phone
                FROM team_members tm
                JOIN users u ON tm.user_id = u.id
                WHERE tm.project_id = ?
                ORDER BY tm.role ASC
            ");
            $stmt->execute([$projectId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('team members fetch', $e->getMessage());
        }
    }

    public static function getPaymentMilestones(int $projectId): array {
        global $db;
        
        try {
            $stmt = $db->prepare("
                SELECT *
                FROM payment_milestones
                WHERE project_id = ?
                ORDER BY due_date ASC
            ");
            $stmt->execute([$projectId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('payment milestones fetch', $e->getMessage());
        }
    }

    public static function getWorkLogs(int $projectId): array {
        global $db;
        
        try {
            $stmt = $db->prepare("
                SELECT wl.*, u.name as user_name
                FROM work_logs wl
                JOIN users u ON wl.user_id = u.id
                WHERE wl.project_id = ?
                ORDER BY wl.log_date DESC
            ");
            $stmt->execute([$projectId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('work logs fetch', $e->getMessage());
        }
    }

    private static function validateProjectData(array $data, bool $isUpdate = false): void {
        $errors = [];
        
        if (!$isUpdate) {
            // Required fields for new projects
            $required = [
                'title',
                'description',
                'client_id',
                'property_address',
                'estimated_completion_date',
                'budget'
            ];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $errors[] = "Missing required field: {$field}";
                }
            }
        }
        
        // Validate status if provided
        if (isset($data['status']) && !in_array($data['status'], self::ALLOWED_STATUSES, true)) {
            throw ProjectException::invalidStatus($data['status'], self::ALLOWED_STATUSES);
        }
        
        // Validate dates if provided
        if (isset($data['estimated_completion_date'])) {
            $date = \DateTime::createFromFormat('Y-m-d', $data['estimated_completion_date']);
            if (!$date || $date->format('Y-m-d') !== $data['estimated_completion_date']) {
                $errors[] = "Invalid estimated completion date format";
            }
        }
        
        // Validate numeric fields
        if (isset($data['budget']) && (!is_numeric($data['budget']) || $data['budget'] < 0)) {
            $errors[] = "Invalid budget amount";
        }
        
        if (isset($data['completion_percentage'])) {
            if (!is_numeric($data['completion_percentage']) || 
                $data['completion_percentage'] < 0 || 
                $data['completion_percentage'] > 100) {
                $errors[] = "Completion percentage must be between 0 and 100";
            }
        }
        
        if (!empty($errors)) {
            throw ProjectException::validationFailed($errors);
        }
    }
}