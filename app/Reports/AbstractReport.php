<?php

namespace App\Reports;

use App\Utils\Logger;

abstract class AbstractReport implements ReportInterface
{
    protected static Logger $logger;
    protected array $supportedFormats = ['csv', 'json', 'pdf'];
    protected string $name;

    public static function initialize(): void
    {
        self::$logger = Logger::getInstance();
    }

    /**
     * Get available export formats
     * @return array List of supported formats
     */
    public function getAvailableFormats(): array
    {
        return $this->supportedFormats;
    }

    /**
     * Export report data in specified format
     * @param array $data Report data
     * @param string $format Export format
     * @return string Exported content
     */
    public function export(array $data, string $format): string
    {
        $method = "exportTo" . strtoupper($format);
        if (!method_exists($this, $method)) {
            throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }

        self::$logger->info('Exporting report', [
            'report' => $this->name,
            'format' => $format
        ]);

        return $this->$method($data);
    }

    /**
     * Export to CSV format
     * @param array $data Report data
     * @return string CSV content
     */
    protected function exportToCSV(array $data): string
    {
        $output = fopen('php://temp', 'r+');
        
        // Write headers
        if (!empty($data)) {
            fputcsv($output, array_keys(reset($data)));
        }
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);
        
        return $content;
    }

    /**
     * Export to JSON format
     * @param array $data Report data
     * @return string JSON content
     */
    protected function exportToJSON(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Export to PDF format
     * @param array $data Report data
     * @return string PDF content
     */
    protected function exportToPDF(array $data): string
    {
        // Implementation would depend on PDF library being used
        throw new \RuntimeException('PDF export not implemented');
    }
}