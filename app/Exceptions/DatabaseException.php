<?php

namespace App\Exceptions;

class DatabaseException extends BaseException
{
    public static function connectionFailed(string $message, array $context = []): self
    {
        return new self("Database connection failed: {$message}", $context);
    }

    public static function queryFailed(string $query, string $error, array $context = []): self
    {
        return new self(
            "Database query failed: {$error}",
            array_merge(['query' => $query], $context)
        );
    }
}