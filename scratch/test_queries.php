<?php
require_once __DIR__ . '/../config/database.php';

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

echo "Revenue Labels: " . json_encode($revenueLabels) . "\n";
echo "Invoiced Data: " . json_encode($invoicedData) . "\n";
echo "Collected Data: " . json_encode($collectedData) . "\n";
echo "Client Labels: " . json_encode($clientLabels) . "\n";
echo "Client Counts: " . json_encode($clientCounts) . "\n";
