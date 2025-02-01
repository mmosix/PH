<?php

namespace App\Services;

use PDO;
use App\Utils\Logger;
use App\Utils\InputValidator;
use App\Exceptions\NotificationException;
use App\Exceptions\DatabaseException;

class NotificationService
{
    use InputValidator;

    private PDO $db;
    private Logger $logger;
    private const ALLOWED_TYPES = ['info', 'warning', 'error', 'success'];
    private const MAX_MESSAGE_LENGTH = 1000;

    public function __construct(PDO $db, Logger $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Create a new notification
     * @param int $userId User ID to notify
     * @param string $message Notification message
     * @param string $type Notification type
     * @return array Created notification data
     * @throws NotificationException If validation fails
     * @throws DatabaseException If database operation fails
     */
    public function create(int $userId, string $message, string $type = 'info'): array
    {
        // Validate type
        if (!$this->validateAllowed($type, self::ALLOWED_TYPES)) {
            throw NotificationException::invalidType($type, self::ALLOWED_TYPES);
        }

        // Validate message length
        if (!$this->validateLength($message, 1, self::MAX_MESSAGE_LENGTH)) {
            throw NotificationException::messageTooLong(
                mb_strlen($message),
                self::MAX_MESSAGE_LENGTH
            );
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, message, type, created_at)
                VALUES (?, ?, ?, NOW())
            ");

            $stmt->execute([$userId, $message, $type]);
            $id = $this->db->lastInsertId();

            $this->logger->info('Notification created', [
                'user_id' => $userId,
                'type' => $type,
                'id' => $id
            ]);

            return [
                'id' => $id,
                'user_id' => $userId,
                'message' => $message,
                'type' => $type,
                'read' => false,
                'created_at' => date('Y-m-d H:i:s')
            ];
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('notification creation', $e->getMessage());
        }
    }

    /**
     * Get unread notifications for a user
     * @param int $userId User ID
     * @return array List of unread notifications
     * @throws DatabaseException If query fails
     */
    public function getUnread(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT *
                FROM notifications 
                WHERE user_id = ? AND read = false
                ORDER BY created_at DESC
            ");

            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('unread notifications fetch', $e->getMessage());
        }
    }

    /**
     * Get all notifications for a user with pagination
     * @param int $userId User ID
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array List of notifications and pagination data
     */
    public function getUserNotifications(int $userId, int $page = 1, int $perPage = 20): array
    {
        try {
            // Get total count
            $countStmt = $this->db->prepare("
                SELECT COUNT(*) FROM notifications WHERE user_id = ?
            ");
            $countStmt->execute([$userId]);
            $total = (int)$countStmt->fetchColumn();

            // Calculate offset
            $offset = ($page - 1) * $perPage;

            // Get notifications
            $stmt = $this->db->prepare("
                SELECT *
                FROM notifications
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");

            $stmt->execute([$userId, $perPage, $offset]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'data' => $notifications,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => ceil($total / $perPage)
                ]
            ];
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('notifications fetch', $e->getMessage());
        }
    }

    /**
     * Mark a notification as read
     * @param int $id Notification ID
     * @param int $userId User ID for verification
     * @return bool True if successful
     * @throws NotificationException If notification not found
     * @throws DatabaseException If update fails
     */
    public function markAsRead(int $id, int $userId): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE notifications 
                SET read = true 
                WHERE id = ? AND user_id = ?
            ");

            $result = $stmt->execute([$id, $userId]);

            if ($result && $stmt->rowCount() > 0) {
                $this->logger->debug('Notification marked as read', ['id' => $id]);
                return true;
            }

            // If no rows affected, check if notification exists
            $checkStmt = $this->db->prepare("
                SELECT id FROM notifications WHERE id = ?
            ");
            $checkStmt->execute([$id]);

            if (!$checkStmt->fetch()) {
                throw NotificationException::notFound($id);
            }

            return false;
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('notification update', $e->getMessage());
        }
    }

    /**
     * Delete old notifications
     * @param int $daysOld Number of days old to delete
     * @return int Number of notifications deleted
     */
    public function deleteOldNotifications(int $daysOld = 30): int
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM notifications
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                AND read = true
            ");

            $stmt->execute([$daysOld]);
            $deleted = $stmt->rowCount();

            if ($deleted > 0) {
                $this->logger->info('Old notifications deleted', [
                    'count' => $deleted,
                    'days_old' => $daysOld
                ]);
            }

            return $deleted;
        } catch (\PDOException $e) {
            throw DatabaseException::queryFailed('notifications cleanup', $e->getMessage());
        }
    }
}