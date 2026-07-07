<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Task.php';
require_once __DIR__ . '/../src/Compliance.php';
require_once __DIR__ . '/../src/HRMS.php';

try {
    $db = Database::getConnection();
    
    $revenueLabels = [];
    $collectedData = [];
    $invoicedData = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $monthLabel = date('M Y', strtotime("-$i months"));
        $revenueLabels[] = $monthLabel;
        
        $stmtInv = $db->prepare("SELECT SUM(amount) as total FROM accounting_invoices WHERE DATE_FORMAT(issue_date, '%Y-%m') = :month AND status != 'cancelled'");
        $stmtInv->execute(['month' => $month]);
        $invoicedData[] = floatval($stmtInv->fetch()['total'] ?? 0.0);
        
        $stmtCol = $db->prepare("SELECT SUM(amount) as total FROM accounting_payments WHERE DATE_FORMAT(payment_date, '%Y-%m') = :month");
        $stmtCol->execute(['month' => $month]);
        $collectedData[] = floatval($stmtCol->fetch()['total'] ?? 0.0);
    }
    
    $clientLabels = [];
    $clientCounts = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $monthLabel = date('M Y', strtotime("-$i months"));
        $clientLabels[] = $monthLabel;
        
        $stmtCli = $db->prepare("SELECT COUNT(*) as total FROM clients WHERE DATE_FORMAT(created_at, '%Y-%m') = :month");
        $stmtCli->execute(['month' => $month]);
        $clientCounts[] = intval($stmtCli->fetch()['total'] ?? 0);
    }

    $calendarEvents = [];
    $allTasks = Task::getTasks();
    foreach ($allTasks as $t) {
        if (!empty($t['due_date'])) {
            $calendarEvents[] = [
                'type' => 'task',
                'title' => $t['title'] . ' (' . $t['client_name'] . ')',
                'date' => $t['due_date']
            ];
        }
    }
    $allCompliances = Compliance::getCompliances();
    foreach ($allCompliances as $c) {
        if (!empty($c['due_date'])) {
            $calendarEvents[] = [
                'type' => 'compliance',
                'title' => $c['title'] . ' (' . $c['client_name'] . ')',
                'date' => $c['due_date']
            ];
        }
    }
    
    echo "Revenue Labels:\n";
    print_r($revenueLabels);
    echo "Invoiced:\n";
    print_r($invoicedData);
    echo "Collected:\n";
    print_r($collectedData);
    echo "Clients:\n";
    print_r($clientCounts);
    echo "Calendar Events:\n";
    print_r($calendarEvents);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
