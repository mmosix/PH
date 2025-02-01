<?php
class Report {
    public static function generateFinancialReport($userId, $role) {
        $pdo = Database::connect();
        
        if ($role === 'admin') {
            $stmt = $pdo->query("
                SELECT 
                    SUM(budget) as total_budget,
                    SUM(amount) as total_payments
                FROM projects
                LEFT JOIN payments ON projects.id = payments.project_id
            ");
        } else {
            $stmt = $pdo->prepare("
                SELECT 
                    SUM(budget) as total_budget,
                    SUM(amount) as total_payments
                FROM projects
                LEFT JOIN payments ON projects.id = payments.project_id
                WHERE client_id = ? OR contractor_id = ?
            ");
            $stmt->execute([$userId, $userId]);
        }
        
        return $stmt->fetch();
    }

    public static function generateProjectReport($userId, $role) {
        $pdo = Database::connect();
        
        if ($role === 'admin') {
            $stmt = $pdo->query("
                SELECT 
                    COUNT(*) as total_projects,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_projects
                FROM projects
            ");
        } else {
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_projects,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_projects
                FROM projects
                WHERE client_id = ? OR contractor_id = ?
            ");
            $stmt->execute([$userId, $userId]);
        }
        
        return $stmt->fetch();
    }
}