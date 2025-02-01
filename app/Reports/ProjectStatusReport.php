<?php

namespace App\Reports;

use App\Exceptions\DatabaseException;

class ProjectStatusReport extends AbstractReport
{
    protected string $name = 'Project Status Report';

    /**
     * Generate project status report
     * @param array $params Report parameters
     * @return array Report data
     */
    public function generate(array $params): array
    {
        global $db;
        
        try {
            $sql = "
                SELECT 
                    p.id,
                    p.title,
                    p.status,
                    p.created_at,
                    COALESCE(p.completed_at, 'Not completed') as completed_at,
                    u1.name as client_name,
                    u2.name as contractor_name,
                    COUNT(f.id) as total_files,
                    COUNT(DISTINCT e.id) as total_events
                FROM projects p
                LEFT JOIN users u1 ON p.client_id = u1.id
                LEFT JOIN users u2 ON p.contractor_id = u2.id
                LEFT JOIN files f ON p.id = f.project_id
                LEFT JOIN calendar_events e ON p.id = e.project_id
                GROUP BY p.id, p.title, p.status, p.created_at, p.completed_at, 
                         u1.name, u2.name
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute();
            
            $data = $stmt->fetchAll();
            
            // Calculate statistics
            $stats = [
                'total_projects' => count($data),
                'status_breakdown' => [],
                'avg_completion_time' => 0
            ];
            
            foreach ($data as $project) {
                $stats['status_breakdown'][$project['status']] = 
                    ($stats['status_breakdown'][$project['status']] ?? 0) + 1;
            }
            
            return [
                'generated_at' => date('Y-m-d H:i:s'),
                'statistics' => $stats,
                'projects' => $data
            ];
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('project status report', $e->getMessage());
        }
    }
}