<?php
// src/Client.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Util.php';

class Client {
    /**
     * Get all clients
     */
    public static function getClients($limit = null, $offset = null) {
        $db = Database::getConnection();
        if ($limit !== null && $offset !== null) {
            $stmt = $db->prepare("SELECT * FROM clients ORDER BY name ASC LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':limit', intval($limit), PDO::PARAM_INT);
            $stmt->bindValue(':offset', intval($offset), PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        }
        $stmt = $db->query("SELECT * FROM clients ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public static function getClientsCount() {
        $db = Database::getConnection();
        return intval($db->query("SELECT COUNT(*) FROM clients")->fetchColumn());
    }

    /**
     * Get client by ID
     */
    public static function getClient($id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM clients WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Create client
     */
    public static function createClient($name, $email, $phone = null) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("INSERT INTO clients (name, email, phone) VALUES (:name, :email, :phone)");
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'phone' => $phone
            ]);
            $clientId = $db->lastInsertId();
            
            self::addTimelineEvent($clientId, null, 'client_created', "Client account created for $name.");
            return ["success" => true, "id" => $clientId];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Update client
     */
    public static function updateClient($id, $name, $email, $phone = null) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("UPDATE clients SET name = :name, email = :email, phone = :phone WHERE id = :id");
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'id' => $id
            ]);
            
            self::addTimelineEvent($id, null, 'client_updated', "Client details updated.");
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Delete client
     */
    public static function deleteClient($id) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("DELETE FROM clients WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Generate or rotate client portal token
     */
    public static function generatePortalToken($clientId, $expiryDays = 7) {
        $db = Database::getConnection();
        $token = Util::generateToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiryDays days"));

        try {
            $stmt = $db->prepare("UPDATE clients SET portal_token = :token, portal_token_expires_at = :expires_at WHERE id = :id");
            $stmt->execute([
                'token' => $token,
                'expires_at' => $expiresAt,
                'id' => $clientId
            ]);
            
            self::addTimelineEvent($clientId, null, 'token_generated', "Secure portal access token generated (valid for $expiryDays days).");
            return ["success" => true, "token" => $token, "expires_at" => $expiresAt];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Revoke / rotate portal token
     */
    public static function rotatePortalToken($clientId) {
        return self::generatePortalToken($clientId);
    }

    /**
     * Find client by valid portal token
     */
    public static function findClientByPortalToken($token) {
        if (empty($token)) return null;
        
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM clients WHERE portal_token = :token AND (portal_token_expires_at IS NULL OR portal_token_expires_at > NOW()) LIMIT 1");
        $stmt->execute(['token' => $token]);
        return $stmt->fetch();
    }

    /**
     * Add event to Client activity timeline
     */
    public static function addTimelineEvent($clientId, $userId, $eventType, $description) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("INSERT INTO client_timeline (client_id, user_id, event_type, description) VALUES (:client_id, :user_id, :event_type, :description)");
            $stmt->execute([
                'client_id' => $clientId,
                'user_id' => $userId,
                'event_type' => $eventType,
                'description' => $description
            ]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get client activity timeline
     */
    public static function getClientTimeline($clientId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT t.*, u.name as user_name 
            FROM client_timeline t 
            LEFT JOIN users u ON t.user_id = u.id 
            WHERE t.client_id = :client_id 
            ORDER BY t.created_at DESC
        ");
        $stmt->execute(['client_id' => $clientId]);
        return $stmt->fetchAll();
    }
}
