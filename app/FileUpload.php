<?php

namespace App;

use App\Exceptions\FileUploadException;
use App\Utils\Logger;

class FileUpload {
    private const UPLOAD_DIR = 'uploads/';
    private static Logger $logger;
    
    public static function initialize(): void
    {
        self::$logger = Logger::getInstance();
    }
    
    /**
     * Upload a file for a specific project
     * @param int $projectId Project ID
     * @param array $file File data from $_FILES
     * @param string $category File category
     * @return array Uploaded file details
     * @throws FileUploadException If validation or upload fails
     */
    public static function upload(int $projectId, array $file, string $category): array
    {
        try {
            // Get allowed types and max size from environment
            $allowedTypes = array_map('trim', explode(',', $_ENV['ALLOWED_MIME_TYPES']));
            $maxSize = (int)($_ENV['MAX_UPLOAD_SIZE'] ?? 5242880); // 5MB default
            
            // Validate file size
            if ($file['size'] > $maxSize) {
                throw FileUploadException::maxSizeExceeded($file['size'], $maxSize);
            }
            
            // Validate MIME type using fileinfo
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            
            if (!in_array($mimeType, $allowedTypes, true)) {
                throw FileUploadException::invalidMimeType($mimeType, $allowedTypes);
            }
            
            // Generate secure filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newFilename = bin2hex(random_bytes(16)) . '.' . $extension;
            
            // Ensure upload directory exists
            $uploadDir = self::UPLOAD_DIR . $projectId . '/';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                throw FileUploadException::uploadFailed('Failed to create upload directory');
            }
            
            $filepath = $uploadDir . $newFilename;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw FileUploadException::uploadFailed('Failed to move uploaded file');
            }
            
            // Verify the uploaded file
            if (!file_exists($filepath)) {
                throw FileUploadException::uploadFailed('File not found after upload');
            }
            
            // Save file record to database
            global $db;
            $stmt = $db->prepare("
                INSERT INTO files (project_id, filename, original_name, filepath, type, size, category)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $projectId,
                $newFilename,
                $file['name'],
                $filepath,
                $mimeType,
                $file['size'],
                $category
            ]);
            
            $fileId = $db->lastInsertId();
            self::$logger->info('File uploaded successfully', [
                'file_id' => $fileId,
                'project_id' => $projectId,
                'size' => $file['size']
            ]);
            
            return [
                'id' => $fileId,
                'filename' => $newFilename,
                'original_name' => $file['name'],
                'filepath' => $filepath,
                'type' => $mimeType,
                'size' => $file['size'],
                'category' => $category
            ];
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('file upload', $e->getMessage());
        }
    }

    /**
     * Delete a file by ID
     * @param int $id File ID
     * @return bool True if deletion successful
     * @throws FileUploadException If file not found or deletion fails
     */
    public static function delete(int $id): bool
    {
        global $db;
        
        try {
            $stmt = $db->prepare("SELECT * FROM files WHERE id = ?");
            $stmt->execute([$id]);
            $file = $stmt->fetch();
            
            if (!$file) {
                throw new FileUploadException('File not found');
            }
            
            if (file_exists($file['filepath'])) {
                if (!unlink($file['filepath'])) {
                    throw FileUploadException::uploadFailed('Failed to delete file from filesystem');
                }
            }
            
            $stmt = $db->prepare("DELETE FROM files WHERE id = ?");
            $stmt->execute([$id]);
            
            self::$logger->info('File deleted', ['file_id' => $id]);
            return true;
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('file deletion', $e->getMessage());
        }
    }

    /**
     * Get all files for a project
     * @param int $projectId Project ID
     * @return array List of file records
     */
    public static function getByProject(int $projectId): array
    {
        global $db;
        
        try {
            $stmt = $db->prepare("SELECT * FROM files WHERE project_id = ? ORDER BY created_at DESC");
            $stmt->execute([$projectId]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('get project files', $e->getMessage());
        }
    }
}