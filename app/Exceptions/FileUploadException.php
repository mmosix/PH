<?php

namespace App\Exceptions;

class FileUploadException extends \RuntimeException
{
    public static function invalidMimeType(string $mimeType, array $allowedTypes): self
    {
        return new self(sprintf(
            'Invalid file type "%s". Allowed types: %s',
            $mimeType,
            implode(', ', $allowedTypes)
        ));
    }

    public static function maxSizeExceeded(int $size, int $maxSize): self
    {
        return new self(sprintf(
            'File size %d bytes exceeds maximum allowed size of %d bytes',
            $size,
            $maxSize
        ));
    }

    public static function uploadFailed(string $message): self
    {
        return new self('File upload failed: ' . $message);
    }
}