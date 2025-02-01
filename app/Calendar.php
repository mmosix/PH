<?php
class Calendar {
    public static function getMilestones($userId, $role) {
        $pdo = Database::connect();
        
        if ($role === 'admin') {
            $stmt = $pdo->query("
                SELECT milestones.*, projects.name as project_name 
                FROM milestones
                JOIN projects ON milestones.project_id = projects.id
            ");
        } else {
            $stmt = $pdo->prepare("
                SELECT milestones.*, projects.name as project_name 
                FROM milestones
                JOIN projects ON milestones.project_id = projects.id
                WHERE projects.client_id = ? OR projects.contractor_id = ?
            ");
            $stmt->execute([$userId, $userId]);
        }
        
        return $stmt->fetchAll();
    }
}