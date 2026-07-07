<?php
// src/Communication.php

require_once __DIR__ . '/../config/database.php';

class Communication {
    /**
     * Send message to another employee
     */
    public static function sendMessage($senderId, $receiverId, $text) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                INSERT INTO messages (sender_id, receiver_id, message_text, is_read) 
                VALUES (:sender_id, :receiver_id, :message_text, 0)
            ");
            $stmt->execute([
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'message_text' => trim($text)
            ]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Get chat history between two users
     */
    public static function getChatHistory($userId1, $userId2) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT m.*, u1.name as sender_name, u2.name as receiver_name
            FROM messages m
            JOIN users u1 ON m.sender_id = u1.id
            JOIN users u2 ON m.receiver_id = u2.id
            WHERE (m.sender_id = :u1 AND m.receiver_id = :u2)
               OR (m.sender_id = :u3 AND m.receiver_id = :u4)
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([
            'u1' => $userId1,
            'u2' => $userId2,
            'u3' => $userId2,
            'u4' => $userId1
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Mark messages from a sender as read
     */
    public static function markChatAsRead($receiverId, $senderId) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                UPDATE messages 
                SET is_read = 1 
                WHERE receiver_id = :rec AND sender_id = :snd AND is_read = 0
            ");
            $stmt->execute(['rec' => $receiverId, 'snd' => $senderId]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get unread chat message counts grouped by sender
     */
    public static function getUnreadCounts($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT sender_id, COUNT(*) as count 
            FROM messages 
            WHERE receiver_id = :uid AND is_read = 0 
            GROUP BY sender_id
        ");
        $stmt->execute(['uid' => $userId]);
        $rows = $stmt->fetchAll();
        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['sender_id']] = intval($row['count']);
        }
        return $counts;
    }

    /**
     * Post a new general announcement
     */
    public static function createAnnouncement($title, $content, $createdBy) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                INSERT INTO announcements (title, content, created_by) 
                VALUES (:title, :content, :created_by)
            ");
            $stmt->execute([
                'title' => $title,
                'content' => $content,
                'created_by' => $createdBy
            ]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Get announcements
     */
    public static function getAnnouncements() {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT a.*, u.name as author_name 
            FROM announcements a 
            JOIN users u ON a.created_by = u.id 
            ORDER BY a.created_at DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Delete an announcement
     */
    public static function deleteAnnouncement($id) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("DELETE FROM announcements WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Create a notification for a user
     */
    public static function createNotification($userId, $title, $message) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                INSERT INTO notifications (user_id, title, message, is_read) 
                VALUES (:user_id, :title, :message, 0)
            ");
            $stmt->execute([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message
            ]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Get unread notifications for a user
     */
    public static function getUnreadNotifications($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM notifications 
            WHERE user_id = :user_id AND is_read = 0 
            ORDER BY created_at DESC
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Mark all notifications for a user as read
     */
    public static function markNotificationsAsRead($userId) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $userId]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Get all email templates
     */
    public static function getEmailTemplates() {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM email_templates ORDER BY template_name ASC");
        return $stmt->fetchAll();
    }

    /**
     * Get a specific email template by name
     */
    public static function getEmailTemplate($name) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM email_templates WHERE template_name = :name LIMIT 1");
        $stmt->execute(['name' => $name]);
        return $stmt->fetch();
    }

    /**
     * Update an email template's subject and body
     */
    public static function updateEmailTemplate($id, $subject, $body) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                UPDATE email_templates 
                SET subject = :subject, body = :body 
                WHERE id = :id
            ");
            $stmt->execute([
                'subject' => $subject,
                'body' => $body,
                'id' => $id
            ]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }
}
