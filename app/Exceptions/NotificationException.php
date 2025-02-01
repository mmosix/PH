<?php

namespace App\Exceptions;

class NotificationException extends BaseException
{
    public static function invalidType(string $type, array $allowedTypes): self
    {
        return new self(sprintf(
            'Invalid notification type "%s". Allowed types: %s',
            $type,
            implode(', ', $allowedTypes)
        ));
    }

    public static function messageTooLong(int $length, int $maxLength): self
    {
        return new self(sprintf(
            'Notification message too long (%d characters). Maximum allowed: %d',
            $length,
            $maxLength
        ));
    }

    public static function notFound(int $id): self
    {
        return new self("Notification not found with ID: {$id}");
    }
}