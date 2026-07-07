<?php
// src/Compliance.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Client.php';

class Compliance {
    /**
     * Get all compliances with optional filtering
     */
    public static function getCompliances($filters = []) {
        $db = Database::getConnection();
        $sql = "
            SELECT co.*, c.name as client_name 
            FROM compliances co 
            JOIN clients c ON co.client_id = c.id 
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['client_id'])) {
            $sql .= " AND co.client_id = :client_id";
            $params['client_id'] = $filters['client_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND co.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['category'])) {
            $sql .= " AND co.category = :category";
            $params['category'] = $filters['category'];
        }
        if (isset($filters['overdue']) && $filters['overdue'] === true) {
            $sql .= " AND co.due_date < CURRENT_DATE() AND co.status = 'pending'";
        }

        $sql .= " ORDER BY co.due_date ASC, co.id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get single compliance
     */
    public static function getCompliance($id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT co.*, c.name as client_name 
            FROM compliances co 
            JOIN clients c ON co.client_id = c.id 
            WHERE co.id = :id LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Create compliance task
     */
    public static function createCompliance($clientId, $title, $category, $dueDate, $notes = null, $financial_year = null, $assessment_year = null, $periodicity = null) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                INSERT INTO compliances (client_id, title, category, due_date, status, notes, financial_year, assessment_year, periodicity) 
                VALUES (:client_id, :title, :category, :due_date, 'pending', :notes, :financial_year, :assessment_year, :periodicity)
            ");
            $stmt->execute([
                'client_id' => $clientId,
                'title' => $title,
                'category' => $category,
                'due_date' => $dueDate,
                'notes' => $notes,
                'financial_year' => $financial_year ?: null,
                'assessment_year' => $assessment_year ?: null,
                'periodicity' => $periodicity ?: null
            ]);
            $complianceId = $db->lastInsertId();

            Client::addTimelineEvent($clientId, null, 'compliance_created', "Compliance task '$title' added (Due: $dueDate).");

            // Fetch client details to enqueue notification
            $client = Client::getClient($clientId);
            if ($client && !empty($client['email'])) {
                try {
                    $subject = "New Compliance Scheduled: " . $title;
                    $body = "Dear " . $client['name'] . ",\n\nA new compliance task '" . $title . "' (Category: " . $category . ") has been scheduled. It is due on " . $dueDate . ".\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.";
                    
                    $stmtQueue = $db->prepare("
                        INSERT INTO automation_queue (event_type, recipient_email, subject, body, status)
                        VALUES ('compliance_created', :email, :subject, :body, 'pending')
                    ");
                    $stmtQueue->execute([
                        'email' => $client['email'],
                        'subject' => $subject,
                        'body' => $body
                    ]);
                } catch (PDOException $ex) {
                    // Suppress error so that compliance creation still succeeds even if email queuing fails
                }
            }

            return ["success" => true, "id" => $complianceId];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Update client response/comments on a compliance
     */
    public static function updateClientResponse($id, $response) {
        $db = Database::getConnection();
        try {
            $comp = self::getCompliance($id);
            if (!$comp) {
                return ["error" => "Compliance record not found."];
            }

            $stmt = $db->prepare("
                UPDATE compliances 
                SET client_response = :response, client_responded_at = CURRENT_TIMESTAMP 
                WHERE id = :id
            ");
            $stmt->execute([
                'response' => $response,
                'id' => $id
            ]);

            Client::addTimelineEvent($comp['client_id'], null, 'compliance_responded', "Client responded to compliance '{$comp['title']}': '$response'");
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Record returns filing information
     */
    public static function recordFiling($id, $filingDate, $acknowledgementNumber, $notes = null) {
        $db = Database::getConnection();
        try {
            $comp = self::getCompliance($id);
            if (!$comp) {
                return ["error" => "Compliance record not found."];
            }

            $stmt = $db->prepare("
                UPDATE compliances 
                SET status = 'filed', filing_date = :filing_date, acknowledgement_number = :acknowledgement_number, notes = :notes 
                WHERE id = :id
            ");
            $stmt->execute([
                'filing_date' => $filingDate,
                'acknowledgement_number' => $acknowledgementNumber,
                'notes' => $notes ?: $comp['notes'],
                'id' => $id
            ]);

            Client::addTimelineEvent($comp['client_id'], null, 'compliance_filed', "Compliance return filed: '{$comp['title']}' (Ack: $acknowledgementNumber).");
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Delete compliance record
     */
    public static function deleteCompliance($id) {
        $db = Database::getConnection();
        try {
            $comp = self::getCompliance($id);
            if ($comp) {
                $stmt = $db->prepare("DELETE FROM compliances WHERE id = :id");
                $stmt->execute(['id' => $id]);
                Client::addTimelineEvent($comp['client_id'], null, 'compliance_deleted', "Compliance return '{$comp['title']}' deleted.");
            }
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Get compliance statistics
     */
    public static function getStats() {
        $db = Database::getConnection();

        $stmtAll = $db->query("SELECT COUNT(*) as total FROM compliances");
        $all = intval($stmtAll->fetch()['total'] ?? 0);

        $stmtPending = $db->query("SELECT COUNT(*) as total FROM compliances WHERE status = 'pending'");
        $pending = intval($stmtPending->fetch()['total'] ?? 0);

        $stmtFiled = $db->query("SELECT COUNT(*) as total FROM compliances WHERE status = 'filed'");
        $filed = intval($stmtFiled->fetch()['total'] ?? 0);

        $stmtOverdue = $db->query("SELECT COUNT(*) as total FROM compliances WHERE status = 'pending' AND due_date < CURRENT_DATE()");
        $overdue = intval($stmtOverdue->fetch()['total'] ?? 0);

        return [
            'total' => $all,
            'pending' => $pending,
            'filed' => $filed,
            'overdue' => $overdue
        ];
    }

    /**
     * Get compliance configuration for all clients
     */
    public static function getComplianceConfigs() {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT ccc.*, c.name as client_name, c.email as client_email, c.phone as client_phone, c.id as client_id
            FROM clients c
            LEFT JOIN client_compliance_configs ccc ON c.id = ccc.client_id
            ORDER BY c.name ASC
        ");
        $rows = $stmt->fetchAll();
        
        // Return with initialized values if missing
        foreach ($rows as &$row) {
            if ($row['id'] === null) {
                $row['auto_gst'] = 0;
                $row['auto_tds'] = 0;
                $row['auto_roc'] = 0;
                $row['auto_itr'] = 0;
                $row['remind_email'] = 1;
                $row['remind_sms'] = 0;
                $row['escalation_days'] = 5;
            }
        }
        return $rows;
    }

    /**
     * Update client compliance config settings
     */
    public static function updateClientConfig($clientId, $data) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                INSERT INTO client_compliance_configs 
                    (client_id, auto_gst, auto_tds, auto_roc, auto_itr, remind_email, remind_sms, escalation_days) 
                VALUES 
                    (:client_id, :auto_gst, :auto_tds, :auto_roc, :auto_itr, :remind_email, :remind_sms, :escalation_days)
                ON DUPLICATE KEY UPDATE 
                    auto_gst = VALUES(auto_gst),
                    auto_tds = VALUES(auto_tds),
                    auto_roc = VALUES(auto_roc),
                    auto_itr = VALUES(auto_itr),
                    remind_email = VALUES(remind_email),
                    remind_sms = VALUES(remind_sms),
                    escalation_days = VALUES(escalation_days)
            ");
            $stmt->execute([
                'client_id' => $clientId,
                'auto_gst' => isset($data['auto_gst']) ? 1 : 0,
                'auto_tds' => isset($data['auto_tds']) ? 1 : 0,
                'auto_roc' => isset($data['auto_roc']) ? 1 : 0,
                'auto_itr' => isset($data['auto_itr']) ? 1 : 0,
                'remind_email' => isset($data['remind_email']) ? 1 : 0,
                'remind_sms' => isset($data['remind_sms']) ? 1 : 0,
                'escalation_days' => intval($data['escalation_days'] ?? 5)
            ]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Run the automated returns generator and reminders/escalations check
     */
    public static function runComplianceAutomation() {
        $db = Database::getConnection();
        
        $clients = self::getComplianceConfigs();
        $generatedCount = 0;
        $remindersCount = 0;
        $escalationsCount = 0;

        foreach ($clients as $cc) {
            $cid = $cc['client_id'];
            if (!$cid) continue;

            $currentYear = date('Y');
            $currentMonth = date('m');
            $quarter = ceil($currentMonth / 3);

            // 1. Auto GST Return Spawning (Monthly)
            if ($cc['auto_gst']) {
                $gstTitle = "GST Return Filing - " . date('M Y');
                $stmtCheck = $db->prepare("SELECT id FROM compliances WHERE client_id = :cid AND title = :title LIMIT 1");
                $stmtCheck->execute(['cid' => $cid, 'title' => $gstTitle]);
                if (!$stmtCheck->fetch()) {
                    $dueDate = date('Y-m-20');
                    self::createCompliance($cid, $gstTitle, 'GST Return', $dueDate, 'Automatically generated monthly GST return task.');
                    $generatedCount++;
                }
            }

            // 2. Auto TDS Return Spawning (Quarterly)
            if ($cc['auto_tds']) {
                $tdsTitle = "TDS Return Filing - Q" . $quarter . " " . $currentYear;
                $stmtCheck = $db->prepare("SELECT id FROM compliances WHERE client_id = :cid AND title = :title LIMIT 1");
                $stmtCheck->execute(['cid' => $cid, 'title' => $tdsTitle]);
                if (!$stmtCheck->fetch()) {
                    $dueDate = date('Y-m-31');
                    self::createCompliance($cid, $tdsTitle, 'TDS Return', $dueDate, 'Automatically generated quarterly TDS return task.');
                    $generatedCount++;
                }
            }

            // 3. Auto ROC Filing Spawning (Yearly)
            if ($cc['auto_roc']) {
                $rocTitle = "Annual ROC Filing - " . $currentYear;
                $stmtCheck = $db->prepare("SELECT id FROM compliances WHERE client_id = :cid AND title = :title LIMIT 1");
                $stmtCheck->execute(['cid' => $cid, 'title' => $rocTitle]);
                if (!$stmtCheck->fetch()) {
                    $dueDate = $currentYear . "-11-30";
                    self::createCompliance($cid, $rocTitle, 'ROC', $dueDate, 'Automatically generated annual ROC return task.');
                    $generatedCount++;
                }
            }

            // 4. Auto Income Tax Filing Spawning (ITR - Yearly)
            if ($cc['auto_itr']) {
                $itrTitle = "Income Tax Return (ITR) Filing - AY " . ($currentYear) . "-" . ($currentYear+1);
                $stmtCheck = $db->prepare("SELECT id FROM compliances WHERE client_id = :cid AND title = :title LIMIT 1");
                $stmtCheck->execute(['cid' => $cid, 'title' => $itrTitle]);
                if (!$stmtCheck->fetch()) {
                    $dueDate = $currentYear . "-07-31";
                    self::createCompliance($cid, $itrTitle, 'ITR', $dueDate, 'Automatically generated annual Income Tax Return task.');
                    $generatedCount++;
                }
            }
        }

        // 5. Send reminders & process escalations for all pending returns
        $stmtPending = $db->query("
            SELECT co.*, c.name as client_name, c.email as client_email, c.phone as client_phone,
                   ccc.remind_email, ccc.remind_sms, ccc.escalation_days
            FROM compliances co
            JOIN clients c ON co.client_id = c.id
            LEFT JOIN client_compliance_configs ccc ON c.id = ccc.client_id
            WHERE co.status = 'pending'
        ");
        $pending = $stmtPending->fetchAll();

        foreach ($pending as $p) {
            $remindEmail = isset($p['remind_email']) ? intval($p['remind_email']) : 1;
            $remindSms = isset($p['remind_sms']) ? intval($p['remind_sms']) : 0;
            $escDays = isset($p['escalation_days']) ? intval($p['escalation_days']) : 5;

            $diffSecs = time() - strtotime($p['due_date']);
            $daysDiff = floor($diffSecs / (60 * 60 * 24));

            // Due Date Reminder: due in 3 days or less
            $dueInSecs = strtotime($p['due_date']) - time();
            $dueInDays = ceil($dueInSecs / (60 * 60 * 24));

            if ($dueInDays >= 0 && $dueInDays <= 3) {
                // Email Reminder
                if ($remindEmail && intval($p['email_reminders_sent']) == 0) {
                    $subject = "Tax Return Compliance Reminder: " . $p['title'];
                    $body = "Dear " . $p['client_name'] . ",\n\nThis is an automated reminder that your return " . $p['title'] . " is due on " . $p['due_date'] . ". Please ensure all files and comments are uploaded to the portal.\n\nBest Regards,\nCA CRM Team.";
                    
                    $stmtQueue = $db->prepare("
                        INSERT INTO automation_queue (event_type, recipient_email, subject, body, status)
                        VALUES ('compliance_reminder', :email, :subject, :body, 'pending')
                    ");
                    $stmtQueue->execute([
                        'email' => $p['client_email'],
                        'subject' => $subject,
                        'body' => $body
                    ]);

                    $db->prepare("UPDATE compliances SET email_reminders_sent = 1 WHERE id = :id")->execute(['id' => $p['id']]);
                    $remindersCount++;
                }

                // SMS Reminder simulation
                if ($remindSms && intval($p['sms_reminders_sent']) == 0 && !empty($p['client_phone'])) {
                    $smsMsg = "CA CRM Reminder: Your return '" . $p['title'] . "' is due on " . $p['due_date'] . ". Please submit documents ASAP.";
                    $stmtSms = $db->prepare("
                        INSERT INTO sms_logs (client_id, phone_number, message, status) 
                        VALUES (:client_id, :phone, :msg, 'sent')
                    ");
                    $stmtSms->execute([
                        'client_id' => $p['client_id'],
                        'phone' => $p['client_phone'],
                        'msg' => $smsMsg
                    ]);

                    $db->prepare("UPDATE compliances SET sms_reminders_sent = 1 WHERE id = :id")->execute(['id' => $p['id']]);
                    $remindersCount++;
                }
            }

            // Escalation Workflow
            if ($daysDiff >= $escDays && !$p['escalated']) {
                $db->prepare("
                    UPDATE compliances 
                    SET escalated = 1, escalated_at = CURRENT_TIMESTAMP 
                    WHERE id = :id
                ")->execute(['id' => $p['id']]);

                // Log escalation timeline event
                Client::addTimelineEvent($p['client_id'], null, 'compliance_escalated', "Filing '" . $p['title'] . "' has been escalated to administration due to non-filing (Overdue: $daysDiff days).");

                // Enqueue email alert to admins
                $stmtQueue = $db->prepare("
                    INSERT INTO automation_queue (event_type, recipient_email, subject, body, status)
                    VALUES ('compliance_escalation', 'admin@cafirm.com', :subject, :body, 'pending')
                ");
                $stmtQueue->execute([
                    'subject' => "URGENT Compliance Escalation Alert: " . $p['client_name'],
                    'body' => "Dear Administrator,\n\nThe compliance task '" . $p['title'] . "' for client " . $p['client_name'] . " was due on " . $p['due_date'] . " and remains UNFILED after the " . $escDays . "-day grace period.\n\nThe case has been automatically escalated.\n\nBest Regards,\nCA CRM Workflow Automation Engine."
                ]);

                $escalationsCount++;
            }
        }

        return [
            "generated" => $generatedCount,
            "reminders" => $remindersCount,
            "escalations" => $escalationsCount
        ];
    }
}
