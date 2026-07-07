<?php
// src/CRM.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Client.php';
require_once __DIR__ . '/Auth.php';

class CRM {
    // ==================== LEAD MANAGEMENT ====================
    
    public static function getLeads($status = null) {
        $db = Database::getConnection();
        if ($status) {
            $stmt = $db->prepare("SELECT * FROM leads WHERE status = :status ORDER BY created_at DESC");
            $stmt->execute(['status' => $status]);
        } else {
            $stmt = $db->query("SELECT * FROM leads ORDER BY created_at DESC");
        }
        return $stmt->fetchAll();
    }

    public static function createLead($name, $email, $phone = null, $source = 'Direct', $notes = null) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                INSERT INTO leads (name, email, phone, source, status, notes) 
                VALUES (:name, :email, :phone, :source, 'new', :notes)
            ");
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'source' => $source,
                'notes' => $notes
            ]);
            return ["success" => true, "id" => $db->lastInsertId()];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    public static function updateLeadStatus($leadId, $status) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("UPDATE leads SET status = :status WHERE id = :id");
            $stmt->execute([
                'status' => $status,
                'id' => $leadId
            ]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    public static function deleteLead($leadId) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("DELETE FROM leads WHERE id = :id");
            $stmt->execute(['id' => $leadId]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    // ==================== OPPORTUNITY PIPELINE ====================
    
    public static function getOpportunities($stage = null) {
        $db = Database::getConnection();
        $sql = "
            SELECT o.*, l.name as lead_name, c.name as client_name 
            FROM opportunities o 
            LEFT JOIN leads l ON o.lead_id = l.id 
            LEFT JOIN clients c ON o.client_id = c.id
        ";
        if ($stage) {
            $sql .= " WHERE o.stage = :stage ORDER BY o.updated_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute(['stage' => $stage]);
        } else {
            $sql .= " ORDER BY o.updated_at DESC";
            $stmt = $db->query($sql);
        }
        return $stmt->fetchAll();
    }

    public static function createOpportunity($title, $value, $stage = 'discovery', $probability = 10, $closeDate = null, $leadId = null, $clientId = null) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                INSERT INTO opportunities (lead_id, client_id, title, value, stage, probability, close_date) 
                VALUES (:lead_id, :client_id, :title, :value, :stage, :probability, :close_date)
            ");
            $stmt->execute([
                'lead_id' => $leadId ?: null,
                'client_id' => $clientId ?: null,
                'title' => $title,
                'value' => $value,
                'stage' => $stage,
                'probability' => intval($probability),
                'close_date' => $closeDate ?: null
            ]);
            return ["success" => true, "id" => $db->lastInsertId()];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    public static function updateOpportunityStage($oppId, $stage, $probability = null) {
        $db = Database::getConnection();
        try {
            if ($probability === null) {
                // Auto guess probability based on stage
                $map = ['discovery' => 10, 'proposal' => 40, 'negotiation' => 70, 'won' => 100, 'lost' => 0];
                $probability = $map[$stage] ?? 10;
            }
            $stmt = $db->prepare("UPDATE opportunities SET stage = :stage, probability = :probability WHERE id = :id");
            $stmt->execute([
                'stage' => $stage,
                'probability' => $probability,
                'id' => $oppId
            ]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    // ==================== CLIENT NOTES ====================
    
    public static function getClientNotes($clientId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT cn.*, u.name as staff_name 
            FROM client_notes cn 
            JOIN users u ON cn.user_id = u.id 
            WHERE cn.client_id = :client_id 
            ORDER BY cn.created_at DESC
        ");
        $stmt->execute(['client_id' => $clientId]);
        return $stmt->fetchAll();
    }

    public static function addClientNote($clientId, $userId, $content) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("INSERT INTO client_notes (client_id, user_id, content) VALUES (:client_id, :user_id, :content)");
            $stmt->execute([
                'client_id' => $clientId,
                'user_id' => $userId,
                'content' => $content
            ]);
            Client::addTimelineEvent($clientId, $userId, 'note_added', "Added a private file note.");
            return ["success" => true, "id" => $db->lastInsertId()];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    public static function deleteClientNote($noteId) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("DELETE FROM client_notes WHERE id = :id");
            $stmt->execute(['id' => $noteId]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    // ==================== MEETINGS HISTORY ====================
    
    public static function getMeetings($clientId = null) {
        $db = Database::getConnection();
        if ($clientId) {
            $stmt = $db->prepare("SELECT * FROM meetings WHERE client_id = :client_id ORDER BY meeting_date DESC");
            $stmt->execute(['client_id' => $clientId]);
            return $stmt->fetchAll();
        } else {
            $stmt = $db->query("SELECT m.*, c.name as client_name FROM meetings m JOIN clients c ON m.client_id = c.id ORDER BY m.meeting_date DESC");
            return $stmt->fetchAll();
        }
    }

    public static function scheduleMeeting($clientId, $title, $description = null, $meetingDate, $videoLink = null) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                INSERT INTO meetings (client_id, title, description, meeting_date, status, video_link) 
                VALUES (:client_id, :title, :description, :meeting_date, 'scheduled', :video_link)
            ");
            $stmt->execute([
                'client_id' => $clientId,
                'title' => $title,
                'description' => $description,
                'meeting_date' => $meetingDate,
                'video_link' => $videoLink
            ]);
            Client::addTimelineEvent($clientId, null, 'meeting_scheduled', "Scheduled meeting '$title' on $meetingDate.");
            return ["success" => true, "id" => $db->lastInsertId()];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    public static function updateMeetingStatus($meetingId, $status) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("UPDATE meetings SET status = :status WHERE id = :id");
            $stmt->execute([
                'status' => $status,
                'id' => $meetingId
            ]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    // ==================== INTEGRATIONS (WHATSAPP & EMAIL LOGS) ====================
    
    public static function logWhatsApp($clientId, $message, $sender = 'staff', $status = 'sent') {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("INSERT INTO whatsapp_logs (client_id, sender, message, status) VALUES (:client_id, :sender, :message, :status)");
            $stmt->execute([
                'client_id' => $clientId,
                'sender' => $sender,
                'message' => $message,
                'status' => $status
            ]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    public static function getWhatsAppLogs($clientId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM whatsapp_logs WHERE client_id = :client_id ORDER BY created_at DESC");
        $stmt->execute(['client_id' => $clientId]);
        return $stmt->fetchAll();
    }

    public static function logEmail($clientId, $subject, $body, $status = 'sent') {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("INSERT INTO email_logs (client_id, subject, body, status) VALUES (:client_id, :subject, :body, :status)");
            $stmt->execute([
                'client_id' => $clientId,
                'subject' => $subject,
                'body' => $body,
                'status' => $status
            ]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    public static function getEmailLogs($clientId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM email_logs WHERE client_id = :client_id ORDER BY created_at DESC");
        $stmt->execute(['client_id' => $clientId]);
        return $stmt->fetchAll();
    }
}
