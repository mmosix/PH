<?php

namespace App;

use App\Reports\ReportInterface;
use App\Reports\ProjectStatusReport;
use App\Reports\FinancialReport;

class ReportManager
{
    private array $reports;

    public function __construct()
    {
        $this->reports = [
            'project_status' => new ProjectStatusReport(),
            'financial' => new FinancialReport()
        ];
    }

    /**
     * Get report instance by type
     * @param string $type Report type
     * @return ReportInterface Report instance
     * @throws \InvalidArgumentException If report type not found
     */
    public function getReport(string $type): ReportInterface
    {
        if (!isset($this->reports[$type])) {
            throw new \InvalidArgumentException("Report type not found: {$type}");
        }
        return $this->reports[$type];
    }

    /**
     * Get available report types
     * @return array List of available report types with their supported formats
     */
    public function getAvailableReportTypes(): array
    {
        $types = [];
        foreach ($this->reports as $type => $report) {
            $types[$type] = [
                'formats' => $report->getAvailableFormats()
            ];
        }
        return $types;
    }

    /**
     * Generate and export a report
     * @param string $type Report type
     * @param array $params Report parameters
     * @param string $format Export format
     * @return string Generated report content
     */
    public function generateReport(string $type, array $params, string $format): string
    {
        $report = $this->getReport($type);
        $data = $report->generate($params);
        return $report->export($data, $format);
    }
}