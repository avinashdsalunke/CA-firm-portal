<?php
// database/run_update_v11.php

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    echo "Applying update_v11.sql schema upgrades...\n";

    $sql = file_get_contents(__DIR__ . '/update_v11.sql');
    $db->exec($sql);
    echo "Tables altered/created successfully.\n";

    // Seed default email templates
    $templates = [
        [
            'name' => 'compliance_due_reminder',
            'subject' => 'Statutory Compliance Due Date Reminder',
            'body' => "Dear Client,\n\nThis is to remind you that your statutory filing for {filing_title} is due on {due_date}.\n\nPlease upload the required documents as soon as possible via the client portal.\n\nBest Regards,\nCA Associates Team"
        ],
        [
            'name' => 'invoice_outstanding',
            'subject' => 'Invoice Outstanding Notice - CA Associates',
            'body' => "Dear Client,\n\nInvoice {invoice_number} for the amount of ₹{amount} issued on {issue_date} remains unpaid.\n\nPlease clear the balance of ₹{net_amount} by the due date of {due_date}.\n\nBest Regards,\nCA Associates Finance Team"
        ],
        [
            'name' => 'client_portal_welcome',
            'subject' => 'Welcome to CA Firm Client Portal',
            'body' => "Dear {client_name},\n\nYour secure client portal session has been set up.\n\nYou can access your portal vault at any time using your personal access token: {portal_token}\n\nBest Regards,\nCA Associates Support Team"
        ]
    ];

    $stmt = $db->prepare("INSERT INTO email_templates (template_name, subject, body) VALUES (:name, :subject, :body) ON DUPLICATE KEY UPDATE subject = VALUES(subject), body = VALUES(body)");
    foreach ($templates as $t) {
        $stmt->execute([
            'name' => $t['name'],
            'subject' => $t['subject'],
            'body' => $t['body']
        ]);
        echo "Seeded email template: {$t['name']}\n";
    }

    echo "Update complete!\n";
} catch (Exception $e) {
    die("Database migration error: " . $e->getMessage() . "\n");
}
