<?php
// src/Automation.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Communication.php';
require_once __DIR__ . '/Accounting.php';
require_once __DIR__ . '/Report.php';

class Automation {
    /**
     * Backup Database structure and contents
     */
    public static function backupDatabase() {
        $db = Database::getConnection();
        $backupDir = __DIR__ . '/../public/uploads/backups/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        $filename = 'db_backup_' . date('Ymd_His') . '.sql';
        $filePath = $backupDir . $filename;

        // Fetch all tables
        $tables = [];
        $result = $db->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $sqlContent = "-- CA CRM Database Backup --\n";
        $sqlContent .= "-- Generated: " . date('Y-m-d H:i:s') . " --\n\n";
        $sqlContent .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            // Drop statement
            $sqlContent .= "DROP TABLE IF EXISTS `$table`;\n";
            
            // Create Table structure
            $createTable = $db->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
            $sqlContent .= $createTable['Create Table'] . ";\n\n";

            // Insert Statements
            $rows = $db->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $keys = array_map(function($k) { return "`$k`"; }, array_keys($row));
                $values = array_map(function($v) use ($db) {
                    if ($v === null) return 'NULL';
                    return $db->quote($v);
                }, array_values($row));

                $sqlContent .= "INSERT INTO `$table` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n";
            }
            $sqlContent .= "\n";
        }

        $sqlContent .= "SET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($filePath, $sqlContent);
        return $filename;
    }

    /**
     * Parse templated text with replacements dictionary
     */
    public static function parseTemplate($name, $replacements) {
        $tpl = Communication::getEmailTemplate($name);
        if (!$tpl) return null;

        $subject = $tpl['subject'];
        $body = $tpl['body'];

        foreach ($replacements as $key => $val) {
            $subject = str_replace('{' . $key . '}', $val, $subject);
            $body = str_replace('{' . $key . '}', $val, $body);
        }

        return [
            'subject' => $subject,
            'body' => $body
        ];
    }

    /**
     * Generate Monthly Base Professional Invoices on the 1st of the month
     */
    public static function generateMonthlyInvoices() {
        $db = Database::getConnection();
        // Check if invoice for current month already generated
        $currentMonth = date('Y-m');
        $invoiceNumPattern = 'INV-' . date('Ym') . '-%';

        $stmt = $db->prepare("SELECT COUNT(*) as count FROM accounting_invoices WHERE invoice_number LIKE :pattern");
        $stmt->execute(['pattern' => $invoiceNumPattern]);
        if ($stmt->fetch()['count'] > 0) {
            // Already generated for this month
            return 0;
        }

        // Get all clients
        $clients = $db->query("SELECT id, name FROM clients")->fetchAll();
        $generatedCount = 0;

        foreach ($clients as $c) {
            $invoiceNumber = 'INV-' . date('Ym') . '-' . sprintf('%03d', $c['id']);
            $baseAmount = 5000.00; // Professional consulting retainer fees
            $cgst = $baseAmount * 0.09; // 9% CGST
            $sgst = $baseAmount * 0.09; // 9% SGST
            $netAmount = $baseAmount + $cgst + $sgst;

            $res = Accounting::createInvoice(
                $c['id'], 
                $invoiceNumber, 
                $baseAmount, 
                date('Y-m-d'), 
                date('Y-m-d', strtotime('+15 days')), 
                'CA retainer professional services fee for ' . date('F Y'), 
                $cgst, 
                $sgst, 
                0, 
                0, 
                $netAmount
            );

            if (isset($res['success'])) {
                $generatedCount++;
            }
        }

        return $generatedCount;
    }

    /**
     * Create Monthly Report snapshot
     */
    public static function generateScheduledReportSnapshot() {
        $reportsDir = __DIR__ . '/../public/uploads/reports/';
        if (!is_dir($reportsDir)) {
            mkdir($reportsDir, 0777, true);
        }

        $filters = [
            'start_date' => date('Y-m-01', strtotime('-1 month')),
            'end_date' => date('Y-m-t', strtotime('-1 month'))
        ];

        $revenue = Report::getRevenueReport($filters);
        $profit = Report::getProfitReport($filters);

        $reportSnapshot = [
            'period' => $filters['start_date'] . ' to ' . $filters['end_date'],
            'generated_at' => date('Y-m-d H:i:s'),
            'billed_revenue' => $revenue['summary']['billed'],
            'collected_revenue' => $revenue['summary']['collected'],
            'operating_expenses' => $profit['summary']['custom_expenses'],
            'payroll_salaries' => $profit['summary']['payroll_salaries'],
            'total_expenses' => $profit['summary']['total_expenses'],
            'net_profit' => $profit['summary']['net_profit'],
            'profit_margin' => $profit['summary']['margin']
        ];

        $filename = 'monthly_report_' . date('Ym') . '.json';
        file_put_contents($reportsDir . $filename, json_encode($reportSnapshot, JSON_PRETTY_PRINT));
        return $filename;
    }

    /**
     * Scan tasks due in 2 days and notify employee/client
     */
    public static function runTaskReminderEngine() {
        $db = Database::getConnection();
        // Get tasks due in 2 days
        $stmt = $db->query("
            SELECT t.*, u.name as staff_name, u.id as staff_id, c.name as client_name, c.email as client_email
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to_user_id = u.id
            LEFT JOIN clients c ON t.client_id = c.id
            WHERE t.status = 'in_progress'
              AND t.due_date = DATE_ADD(CURDATE(), INTERVAL 2 DAY)
        ");
        $tasks = $stmt->fetchAll();
        $remindersCount = 0;

        foreach ($tasks as $t) {
            // Send internal system notification to assigned employee
            if (!empty($t['staff_id'])) {
                Communication::createNotification(
                    $t['staff_id'],
                    "Task Deadline Reminder",
                    "Task '{$t['title']}' is due in 2 days. Please complete any outstanding items."
                );
            }

            // Enqueue email reminder for the client
            if (!empty($t['client_email'])) {
                $db->prepare("
                    INSERT INTO automation_queue (event_type, recipient_email, subject, body, status)
                    VALUES ('task_reminder', :email, :subject, :body, 'pending')
                ")->execute([
                    'email' => $t['client_email'],
                    'subject' => "Status Update Reminder: " . $t['title'],
                    'body' => "Dear {$t['client_name']},\n\nThis is a status notification regarding the task '{$t['title']}' which is currently in progress and scheduled for completion on {$t['due_date']}.\n\nBest Regards,\nCA Associates Desk"
                ]);
            }

            $remindersCount++;
        }

        return $remindersCount;
    }
}
