<?php

namespace App\Exceptions;

class ProjectException extends BaseException
{
    public static function notFound(int $id): self
    {
        return new self("Project not found with ID: {$id}");
    }

    public static function validationFailed(string $message, array $errors = []): self
    {
        return new self($message, ['validation_errors' => $errors]);
    }

    public static function invalidStatus(string $status, array $allowedStatuses): self
    {
        return new self(
            "Invalid project status: {$status}. Allowed statuses: " . implode(', ', $allowedStatuses)
        );
    }

    public static function accessDenied(): self
    {
        return new self('Access denied to this project');
    }
}