<?php

namespace App;

use App\Exceptions\ProjectException;
use App\Exceptions\DatabaseException;
use App\Utils\Logger;

class Project {
    private const ALLOWED_STATUSES = ['pending', 'in_progress', 'completed', 'cancelled'];
    private static Logger $logger;

    public static function initialize(): void
    {
        self::$logger = Logger::getInstance();
    }

    /**
     * Create a new project
     * @param array $data Project data
     * @return array Created project data
     * @throws ProjectException If validation fails
     */
    public static function create(array $data): array
    {
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

    /**
     * Update a project
     * @param int $id Project ID
     * @param array $data Update data
     * @return array Updated project data
     * @throws ProjectException If project not found or validation fails
     */
    public static function update(int $id, array $data): array
    {
        global $db;
        
        try {
            // Verify project exists
            if (!self::exists($id)) {
                throw ProjectException::notFound($id);
            }
            
            self::validateProjectData($data, true);
            
            $updates = implode(', ', array_map(
                fn($field) => "{$field} = ?",
                array_keys($data)
            ));
            
            $stmt = $db->prepare("UPDATE projects SET {$updates} WHERE id = ?");
            $stmt->execute([...array_values($data), $id]);
            
            self::$logger->info('Project updated', [
                'project_id' => $id,
                'fields' => array_keys($data)
            ]);
            
            return self::getById($id);
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('project update', $e->getMessage());
        }
    }

    /**
     * Delete a project
     * @param int $id Project ID
     * @return bool Success status
     * @throws ProjectException If project not found
     */
    public static function delete(int $id): bool
    {
        global $db;
        
        try {
            if (!self::exists($id)) {
                throw ProjectException::notFound($id);
            }
            
            $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
            $stmt->execute([$id]);
            
            self::$logger->info('Project deleted', ['project_id' => $id]);
            return true;
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('project deletion', $e->getMessage());
        }
    }

    /**
     * Get project by ID
     * @param int $id Project ID
     * @return array Project data
     * @throws ProjectException If project not found
     */
    public static function getById(int $id): array
    {
        global $db;
        
        try {
            $stmt = $db->prepare("
                SELECT p.*, 
                       u1.name as client_name,
                       u2.name as contractor_name
                FROM projects p
                LEFT JOIN users u1 ON p.client_id = u1.id
                LEFT JOIN users u2 ON p.contractor_id = u2.id
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            
            if ($project = $stmt->fetch()) {
                return $project;
            }
            
            throw ProjectException::notFound($id);
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('project fetch', $e->getMessage());
        }
    }

    /**
     * Get all projects
     * @return array List of projects
     */
    public static function getAll(): array
    {
        global $db;
        
        try {
            $stmt = $db->prepare("
                SELECT p.*, 
                       u1.name as client_name,
                       u2.name as contractor_name
                FROM projects p
                LEFT JOIN users u1 ON p.client_id = u1.id
                LEFT JOIN users u2 ON p.contractor_id = u2.id
                ORDER BY p.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('projects fetch', $e->getMessage());
        }
    }

    /**
     * Get projects for a client
     * @param int $clientId Client user ID
     * @return array List of projects
     */
    public static function getByClient(int $clientId): array
    {
        global $db;
        
        try {
            $stmt = $db->prepare("
                SELECT p.*, 
                       u1.name as client_name,
                       u2.name as contractor_name
                FROM projects p
                LEFT JOIN users u1 ON p.client_id = u1.id
                LEFT JOIN users u2 ON p.contractor_id = u2.id
                WHERE p.client_id = ?
                ORDER BY p.created_at DESC
            ");
            $stmt->execute([$clientId]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('client projects fetch', $e->getMessage());
        }
    }

    /**
     * Get projects for a contractor
     * @param int $contractorId Contractor user ID
     * @return array List of projects
     */
    public static function getByContractor(int $contractorId): array
    {
        global $db;
        
        try {
            $stmt = $db->prepare("
                SELECT p.*, 
                       u1.name as client_name,
                       u2.name as contractor_name
                FROM projects p
                LEFT JOIN users u1 ON p.client_id = u1.id
                LEFT JOIN users u2 ON p.contractor_id = u2.id
                WHERE p.contractor_id = ?
                ORDER BY p.created_at DESC
            ");
            $stmt->execute([$contractorId]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('contractor projects fetch', $e->getMessage());
        }
    }

    /**
     * Check if a project exists
     * @param int $id Project ID
     * @return bool Whether project exists
     */
    private static function exists(int $id): bool
    {
        global $db;
        $stmt = $db->prepare("SELECT 1 FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        return (bool)$stmt->fetch();
    }

    /**
     * Validate project data
     * @param array $data Project data to validate
     * @param bool $isUpdate Whether this is an update operation
     * @throws ProjectException If validation fails
     */
    private static function validateProjectData(array $data, bool $isUpdate = false): void
    {
        $errors = [];
        
        if (!$isUpdate) {
            // Required fields for new projects
            $required = ['title', 'description', 'client_id', 'budget'];
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
        
        // Validate budget if provided
        if (isset($data['budget']) && (!is_numeric($data['budget']) || $data['budget'] < 0)) {
            $errors[] = "Invalid budget amount";
        }
        
        if (!empty($errors)) {
            throw ProjectException::validationFailed("Project validation failed", $errors);
        }
    }
}