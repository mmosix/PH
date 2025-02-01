<?php

namespace App\Reports;

interface ReportInterface
{
    /**
     * Generate the report
     * @param array $params Report parameters
     * @return array Report data
     */
    public function generate(array $params): array;

    /**
     * Get available report formats
     * @return array List of supported formats
     */
    public function getAvailableFormats(): array;

    /**
     * Export the report in specified format
     * @param array $data Report data
     * @param string $format Export format
     * @return string Exported content
     */
    public function export(array $data, string $format): string;
}