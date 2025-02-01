<?php

namespace App\Services;

use PDO;
use App\Utils\Logger;
use App\Utils\InputValidator;
use App\Exceptions\FileUploadException;
use App\Exceptions\DatabaseException;

class FileUploadService
{
    use InputValidator;

    private PDO $db;
    private Logger $logger;
    private array $config;

    /**
     * @var array Additional MIME type validations beyond fileinfo
     */
    private const MIME_TYPE_EXTRAS = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
    ];

    public function __construct(PDO $db, Logger $logger, array $config = [])
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->config = array_merge([
            'upload_dir' => 'uploads/',
            'max_size' => $_ENV['MAX_UPLOAD_SIZE'] ?? 5242880,
            'allowed_types' => array_map('trim', explode(',', $_ENV['ALLOWED_MIME_TYPES'] ?? 'image/jpeg,image/png,application/pdf')),
            'chunk_size' => 8192, // Read file in chunks of 8KB for MIME validation
        ], $config);
    }

    /**
     * Upload a file for a specific project
     * @param int $projectId Project ID
     * @param array $file File data from $_FILES
     * @param string $category File category
     * @return array Uploaded file details
     * @throws FileUploadException If validation or upload fails
     */
    public function upload(int $projectId, array $file, string $category): array
    {
        try {
            // Basic validation
            $this->validateFileData($file);
            
            // Validate file size
            if ($file['size'] > $this->config['max_size']) {
                throw FileUploadException::maxSizeExceeded($file['size'], $this->config['max_size']);
            }

            // Multiple MIME type validations
            $mimeType = $this->validateMimeType($file);
            
            // Generate secure filename
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $newFilename = $this->generateSecureFilename($extension);
            
            // Ensure upload directory exists
            $uploadDir = $this->getProjectUploadDir($projectId);
            
            $filepath = $uploadDir . $newFilename;
            
            // Move and verify uploaded file
            $this->moveUploadedFile($file['tmp_name'], $filepath);
            
            // Save file record to database
            $fileData = $this->saveFileRecord($projectId, [
                'filename' => $newFilename,
                'original_name' => $file['name'],
                'filepath' => $filepath,
                'type' => $mimeType,
                'size' => $file['size'],
                'category' => $category
            ]);
            
            $this->logger->info('File uploaded successfully', [
                'file_id' => $fileData['id'],
                'project_id' => $projectId,
                'size' => $file['size']
            ]);
            
            return $fileData;
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('file upload', $e->getMessage());
        }
    }

    /**
     * Delete a file by ID
     * @param int $id File ID
     * @param int $userId User ID for authorization
     * @return bool True if deletion successful
     * @throws FileUploadException If file not found or deletion fails
     */
    public function delete(int $id, int $userId): bool
    {
        try {
            // Get file with project info for authorization
            $stmt = $this->db->prepare("
                SELECT f.*, p.client_id, p.contractor_id 
                FROM files f
                JOIN projects p ON f.project_id = p.id
                WHERE f.id = ?
            ");
            $stmt->execute([$id]);
            $file = $stmt->fetch();
            
            if (!$file) {
                throw new FileUploadException('File not found');
            }
            
            // Verify user has permission
            if ($userId !== $file['client_id'] && $userId !== $file['contractor_id']) {
                throw new FileUploadException('Permission denied to delete file');
            }
            
            // Delete physical file
            if (file_exists($file['filepath'])) {
                if (!unlink($file['filepath'])) {
                    throw FileUploadException::uploadFailed('Failed to delete file from filesystem');
                }
            }
            
            // Delete database record
            $stmt = $this->db->prepare("DELETE FROM files WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->logger->info('File deleted', [
                'file_id' => $id,
                'user_id' => $userId
            ]);
            
            return true;
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('file deletion', $e->getMessage());
        }
    }

    /**
     * Get all files for a project
     * @param int $projectId Project ID
     * @param array $filters Optional filters (category, type, etc)
     * @return array List of file records
     */
    public function getByProject(int $projectId, array $filters = []): array
    {
        try {
            $sql = "SELECT * FROM files WHERE project_id = ?";
            $params = [$projectId];
            
            if (!empty($filters['category'])) {
                $sql .= " AND category = ?";
                $params[] = $filters['category'];
            }
            
            if (!empty($filters['type'])) {
                $sql .= " AND type = ?";
                $params[] = $filters['type'];
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('get project files', $e->getMessage());
        }
    }

    /**
     * Validate file upload data
     * @param array $file File data from $_FILES
     * @throws FileUploadException If validation fails
     */
    private function validateFileData(array $file): void
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new FileUploadException('Invalid file upload');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new FileUploadException('File upload error: ' . $file['error']);
        }
    }

    /**
     * Validate file MIME type using multiple methods
     * @param array $file File data
     * @return string Validated MIME type
     * @throws FileUploadException If validation fails
     */
    private function validateMimeType(array $file): string
    {
        // Get extension for additional checks
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Primary check using fileinfo
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!in_array($mimeType, $this->config['allowed_types'], true)) {
            throw FileUploadException::invalidMimeType($mimeType, $this->config['allowed_types']);
        }

        // Secondary check using file signatures
        $this->validateFileSignature($file['tmp_name'], $extension);
        
        // Additional MIME type validation for specific extensions
        if (isset(self::MIME_TYPE_EXTRAS[$extension])) {
            if (!in_array($mimeType, self::MIME_TYPE_EXTRAS[$extension], true)) {
                throw new FileUploadException("MIME type {$mimeType} does not match file extension");
            }
        }

        return $mimeType;
    }

    /**
     * Validate file content using file signatures
     * @param string $filepath File path
     * @param string $extension File extension
     * @throws FileUploadException If validation fails
     */
    private function validateFileSignature(string $filepath, string $extension): void
    {
        $signatures = [
            'jpg' => "\xFF\xD8\xFF",
            'jpeg' => "\xFF\xD8\xFF",
            'png' => "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A",
            'gif' => "GIF8",
            'pdf' => "%PDF-"
        ];

        if (isset($signatures[$extension])) {
            $handle = fopen($filepath, 'rb');
            $contents = fread($handle, 8);
            fclose($handle);

            $signature = $signatures[$extension];
            if (strncmp($contents, $signature, strlen($signature)) !== 0) {
                throw new FileUploadException("File signature does not match extension");
            }
        }
    }

    /**
     * Generate secure filename
     * @param string $extension File extension
     * @return string Generated filename
     */
    private function generateSecureFilename(string $extension): string
    {
        return bin2hex(random_bytes(16)) . '.' . $extension;
    }

    /**
     * Get and ensure project upload directory exists
     * @param int $projectId Project ID
     * @return string Upload directory path
     * @throws FileUploadException If directory creation fails
     */
    private function getProjectUploadDir(int $projectId): string
    {
        $uploadDir = $this->config['upload_dir'] . $projectId . '/';
        
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            throw FileUploadException::uploadFailed('Failed to create upload directory');
        }
        
        return $uploadDir;
    }

    /**
     * Move uploaded file safely
     * @param string $tempPath Temporary file path
     * @param string $destPath Destination path
     * @throws FileUploadException If move fails
     */
    private function moveUploadedFile(string $tempPath, string $destPath): void
    {
        if (!move_uploaded_file($tempPath, $destPath)) {
            throw FileUploadException::uploadFailed('Failed to move uploaded file');
        }
        
        if (!file_exists($destPath)) {
            throw FileUploadException::uploadFailed('File not found after upload');
        }
    }

    /**
     * Save file record to database
     * @param int $projectId Project ID
     * @param array $data File data
     * @return array Created file record
     * @throws DatabaseException If database operation fails
     */
    private function saveFileRecord(int $projectId, array $data): array
    {
        $stmt = $this->db->prepare("
            INSERT INTO files (project_id, filename, original_name, filepath, type, size, category)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $projectId,
            $data['filename'],
            $data['original_name'],
            $data['filepath'],
            $data['type'],
            $data['size'],
            $data['category']
        ]);
        
        $data['id'] = $this->db->lastInsertId();
        return $data;
    }
}