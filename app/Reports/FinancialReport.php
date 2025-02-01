<?php

namespace App\Reports;

use App\Exceptions\DatabaseException;

class FinancialReport extends AbstractReport
{
    protected string $name = 'Financial Report';
    protected array $supportedFormats = ['csv', 'json', 'pdf', 'xlsx'];

    /**
     * Generate financial report
     * @param array $params Report parameters
     * @return array Report data
     */
    public function generate(array $params): array
    {
        global $db;
        
        try {
            $startDate = $params['start_date'] ?? date('Y-m-01'); // First day of current month
            $endDate = $params['end_date'] ?? date('Y-m-t'); // Last day of current month
            
            $sql = "
                SELECT 
                    p.id,
                    p.title,
                    p.budget,
                    p.status,
                    SUM(t.amount) as total_payments,
                    p.budget - COALESCE(SUM(t.amount), 0) as remaining_budget,
                    u1.name as client_name,
                    u2.name as contractor_name
                FROM projects p
                LEFT JOIN users u1 ON p.client_id = u1.id
                LEFT JOIN users u2 ON p.contractor_id = u2.id
                LEFT JOIN transactions t ON p.id = t.project_id
                WHERE p.created_at BETWEEN ? AND ?
                GROUP BY p.id, p.title, p.budget, p.status, u1.name, u2.name
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$startDate, $endDate]);
            $projects = $stmt->fetchAll();
            
            // Calculate financial metrics
            $metrics = [
                'total_budget' => 0,
                'total_spent' => 0,
                'total_remaining' => 0,
                'avg_project_budget' => 0,
                'projects_over_budget' => 0
            ];
            
            foreach ($projects as $project) {
                $metrics['total_budget'] += $project['budget'];
                $metrics['total_spent'] += $project['total_payments'];
                $metrics['total_remaining'] += $project['remaining_budget'];
                
                if ($project['total_payments'] > $project['budget']) {
                    $metrics['projects_over_budget']++;
                }
            }
            
            $metrics['avg_project_budget'] = count($projects) > 0 
                ? $metrics['total_budget'] / count($projects) 
                : 0;
            
            return [
                'generated_at' => date('Y-m-d H:i:s'),
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate
                ],
                'metrics' => $metrics,
                'projects' => $projects
            ];
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('financial report', $e->getMessage());
        }
    }

    /**
     * Export to XLSX format
     * @param array $data Report data
     * @return string XLSX content
     */
    protected function exportToXLSX(array $data): string
    {
        // Implementation would depend on Excel library being used (e.g., PhpSpreadsheet)
        throw new \RuntimeException('XLSX export not implemented');
    }
}