<?php

namespace App\Exceptions;

class CalendarException extends BaseException
{
    public static function invalidDate(string $date): self
    {
        return new self("Invalid date format: {$date}");
    }

    public static function eventNotFound(int $id): self
    {
        return new self("Calendar event not found with ID: {$id}");
    }

    public static function invalidDateRange(string $start, string $end): self
    {
        return new self("Invalid date range: {$start} to {$end}");
    }

    public static function eventOverlap(string $start, string $end): self
    {
        return new self("Event overlaps with existing event: {$start} to {$end}");
    }
}