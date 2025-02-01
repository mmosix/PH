<?php

namespace App\Exceptions;

class ReportException extends BaseException
{
    public static function invalidFormat(string $format, array $supportedFormats): self
    {
        return new self(sprintf(
            'Unsupported report format "%s". Supported formats: %s',
            $format,
            implode(', ', $supportedFormats)
        ));
    }

    public static function invalidDateRange(string $start, string $end): self
    {
        return new self(sprintf(
            'Invalid date range: %s to %s',
            $start,
            $end
        ));
    }

    public static function generationFailed(string $type, string $reason): self
    {
        return new self(sprintf(
            'Failed to generate %s report: %s',
            $type,
            $reason
        ));
    }

    public static function exportFailed(string $format, string $reason): self
    {
        return new self(sprintf(
            'Failed to export report to %s: %s',
            strtoupper($format),
            $reason
        ));
    }
}