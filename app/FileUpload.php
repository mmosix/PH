<?php
class FileUpload {
    public static function upload($projectId, $file, $category) {
        $uploadDir = getenv('UPLOAD_DIR');
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        // Validate file
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception("Invalid file type. Only PDF, JPEG, and PNG files are allowed.");
        }
        if ($file['size'] > $maxSize) {
            throw new Exception("File size exceeds the 5MB limit.");
        }

        // Generate unique filename
        $filename = uniqid() . '_' . basename($file['name']);
        $filepath = $uploadDir . '/' . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception("Failed to upload file.");
        }

        // Save to database
        $pdo = Database::connect();
        $stmt = $pdo->prepare("
            INSERT INTO documents 
            (project_id, filename, filepath, category) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$projectId, $filename, $filepath, $category]);

        return true;
    }

    public static function delete($id) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT filepath FROM documents WHERE id = ?");
        $stmt->execute([$id]);
        $file = $stmt->fetch();

        if ($file && file_exists($file['filepath'])) {
            unlink($file['filepath']);
        }

        $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function getByProject($projectId) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM documents WHERE project_id = ?");
        $stmt->execute([$projectId]);
        return $stmt->fetchAll();
    }
}