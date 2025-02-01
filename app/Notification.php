<?php
class Notification {
    public static function create($userId, $message, $type) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("
            INSERT INTO notifications 
            (user_id, message, type) 
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$userId, $message, $type]);
    }

    public static function getUnread($userId) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? AND is_read = false
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function markAsRead($id) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = true 
            WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }
}