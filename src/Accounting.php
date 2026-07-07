<?php
// src/Accounting.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Client.php';

class Accounting {
    /**
     * Get all invoices with optional client filtering
     */
    public static function getInvoices($filters = []) {
        $db = Database::getConnection();
        $sql = "
            SELECT i.*, c.name as client_name 
            FROM accounting_invoices i 
            JOIN clients c ON i.client_id = c.id 
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['client_id'])) {
            $sql .= " AND i.client_id = :client_id";
            $params['client_id'] = $filters['client_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND i.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['start_date'])) {
            $sql .= " AND i.issue_date >= :start_date";
            $params['start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND i.issue_date <= :end_date";
            $params['end_date'] = $filters['end_date'];
        }

        $sql .= " ORDER BY i.issue_date DESC, i.id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get single invoice
     */
    public static function getInvoice($id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT i.*, c.name as client_name, c.email as client_email, c.phone as client_phone
            FROM accounting_invoices i 
            JOIN clients c ON i.client_id = c.id 
            WHERE i.id = :id LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Create an invoice
     */
    public static function createInvoice($clientId, $invoiceNumber, $amount, $issueDate, $dueDate, $description = null, $cgst = 0, $sgst = 0, $igst = 0, $tds_amount = 0, $net_amount = null, $invoice_design = null) {
        $db = Database::getConnection();
        // Check for duplicate invoice number
        $stmtCheck = $db->prepare("SELECT id FROM accounting_invoices WHERE invoice_number = :num LIMIT 1");
        $stmtCheck->execute(['num' => $invoiceNumber]);
        if ($stmtCheck->fetch()) {
            return ["error" => "Invoice number '$invoiceNumber' already exists. Please use a unique invoice number."];
        }

        if ($net_amount === null) {
            $net_amount = floatval($amount) + floatval($cgst) + floatval($sgst) + floatval($igst) - floatval($tds_amount);
        }
        try {
            $stmt = $db->prepare("
                INSERT INTO accounting_invoices (client_id, invoice_number, amount, status, issue_date, due_date, description, cgst, sgst, igst, tds_amount, net_amount, invoice_design) 
                VALUES (:client_id, :invoice_number, :amount, 'unpaid', :issue_date, :due_date, :description, :cgst, :sgst, :igst, :tds_amount, :net_amount, :invoice_design)
            ");
            $stmt->execute([
                'client_id' => $clientId,
                'invoice_number' => $invoiceNumber,
                'amount' => $amount,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'description' => $description,
                'cgst' => $cgst,
                'sgst' => $sgst,
                'igst' => $igst,
                'tds_amount' => $tds_amount,
                'net_amount' => $net_amount,
                'invoice_design' => $invoice_design
            ]);
            $invoiceId = $db->lastInsertId();

            Client::addTimelineEvent($clientId, null, 'invoice_created', "Invoice #$invoiceNumber for amount $amount (Net: $net_amount) created.");
            return ["success" => true, "id" => $invoiceId];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Record a payment against an invoice
     */
    public static function recordPayment($invoiceId, $amount, $paymentDate, $paymentMethod) {
        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            // Insert payment record
            $stmt = $db->prepare("
                INSERT INTO accounting_payments (invoice_id, amount, payment_date, payment_method) 
                VALUES (:invoice_id, :amount, :payment_date, :payment_method)
            ");
            $stmt->execute([
                'invoice_id' => $invoiceId,
                'amount' => $amount,
                'payment_date' => $paymentDate,
                'payment_method' => $paymentMethod
            ]);

            // Calculate total paid for this invoice
            $stmtPaid = $db->prepare("SELECT SUM(amount) as total_paid FROM accounting_payments WHERE invoice_id = :invoice_id");
            $stmtPaid->execute(['invoice_id' => $invoiceId]);
            $paidData = $stmtPaid->fetch();
            $totalPaid = floatval($paidData['total_paid'] ?? 0.0);

            // Fetch invoice details
            $invoice = self::getInvoice($invoiceId);
            if (!$invoice) {
                throw new Exception("Invoice not found.");
            }

            // Update invoice status if fully paid
            if ($totalPaid >= floatval($invoice['amount'])) {
                $stmtUpdate = $db->prepare("UPDATE accounting_invoices SET status = 'paid' WHERE id = :id");
                $stmtUpdate->execute(['id' => $invoiceId]);
            }

            Client::addTimelineEvent($invoice['client_id'], null, 'payment_recorded', "Payment of $amount received for Invoice #{$invoice['invoice_number']}.");
            $db->commit();
            return ["success" => true];
        } catch (Exception $e) {
            $db->rollBack();
            return ["error" => $e->getMessage()];
        }
    }

    /**
     * Get payments for a specific invoice or all payments
     */
    public static function getPayments($invoiceId = null) {
        $db = Database::getConnection();
        if ($invoiceId) {
            $stmt = $db->prepare("SELECT * FROM accounting_payments WHERE invoice_id = :invoice_id ORDER BY payment_date DESC");
            $stmt->execute(['invoice_id' => $invoiceId]);
        } else {
            $stmt = $db->query("
                SELECT p.*, i.invoice_number, c.name as client_name 
                FROM accounting_payments p
                JOIN accounting_invoices i ON p.invoice_id = i.id
                JOIN clients c ON i.client_id = c.id
                ORDER BY p.payment_date DESC, p.id DESC
            ");
        }
        return $stmt->fetchAll();
    }

    /**
     * Delete an invoice
     */
    public static function deleteInvoice($id) {
        $db = Database::getConnection();
        try {
            $invoice = self::getInvoice($id);
            if ($invoice) {
                $stmt = $db->prepare("DELETE FROM accounting_invoices WHERE id = :id");
                $stmt->execute(['id' => $id]);
                Client::addTimelineEvent($invoice['client_id'], null, 'invoice_deleted', "Invoice #{$invoice['invoice_number']} deleted.");
            }
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Get all expenses
     */
    public static function getExpenses($filters = []) {
        $db = Database::getConnection();
        $sql = "SELECT * FROM accounting_expenses WHERE 1=1";
        $params = [];

        if (!empty($filters['category'])) {
            $sql .= " AND category = :category";
            $params['category'] = $filters['category'];
        }
        if (!empty($filters['start_date'])) {
            $sql .= " AND date >= :start_date";
            $params['start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND date <= :end_date";
            $params['end_date'] = $filters['end_date'];
        }
        
        $sql .= " ORDER BY date DESC, id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function recordExpense($category, $amount, $date, $description = null, $status = 'pending', $approved_by = null) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                INSERT INTO accounting_expenses (category, amount, date, description, status, approved_by) 
                VALUES (:category, :amount, :date, :description, :status, :approved_by)
            ");
            $stmt->execute([
                'category' => $category,
                'amount' => $amount,
                'date' => $date,
                'description' => $description,
                'status' => $status,
                'approved_by' => $approved_by
            ]);
            return ["success" => true, "id" => $db->lastInsertId()];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Delete an expense
     */
    public static function deleteExpense($id) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("DELETE FROM accounting_expenses WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Review / Approve Expense Workflow
     */
    public static function reviewExpense($expenseId, $status, $approverId) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("UPDATE accounting_expenses SET status = :status, approved_by = :approved_by WHERE id = :id");
            $stmt->execute([
                'status' => $status,
                'approved_by' => $approverId,
                'id' => $expenseId
            ]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Generate dynamic Profit & Loss Statement
     */
    public static function generateProfitAndLoss($startDate, $endDate) {
        $db = Database::getConnection();

        // Operating revenue (paid invoices net amount)
        $stmtRev = $db->prepare("SELECT SUM(amount) as total FROM accounting_payments WHERE payment_date >= :start AND payment_date <= :end");
        $stmtRev->execute(['start' => $startDate, 'end' => $endDate]);
        $revenue = floatval($stmtRev->fetch()['total'] ?? 0.0);

        // Operating expenses (approved expenses)
        $stmtExp = $db->prepare("SELECT SUM(amount) as total FROM accounting_expenses WHERE status = 'approved' AND date >= :start AND date <= :end");
        $stmtExp->execute(['start' => $startDate, 'end' => $endDate]);
        $expenses = floatval($stmtExp->fetch()['total'] ?? 0.0);

        // Employee Salaries (paid salary slips)
        $stmtSal = $db->prepare("SELECT SUM(net_salary) as total FROM salary_slips WHERE status = 'paid' AND (paid_date >= :start AND paid_date <= :end OR (paid_date IS NULL AND CONCAT(month, '-01') >= :start2 AND CONCAT(month, '-01') <= :end2))");
        $stmtSal->execute(['start' => $startDate, 'end' => $endDate, 'start2' => $startDate, 'end2' => $endDate]);
        $salaries = floatval($stmtSal->fetch()['total'] ?? 0.0);

        $grossProfit = $revenue;
        $totalExpenses = $expenses + $salaries;
        $netIncome = $grossProfit - $totalExpenses;

        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'salaries' => $salaries,
            'gross_profit' => $grossProfit,
            'total_expenses' => $totalExpenses,
            'net_income' => $netIncome
        ];
    }

    /**
     * Generate dynamic Balance Sheet
     */
    public static function generateBalanceSheet() {
        $db = Database::getConnection();

        // Assets: Unpaid Invoices (accounts receivable) + Bank Cash (Simulated as accumulated net profit starting with 50,000)
        $stmtAR = $db->query("SELECT SUM(net_amount) as total FROM accounting_invoices WHERE status = 'unpaid'");
        $accountsReceivable = floatval($stmtAR->fetch()['total'] ?? 0.0);

        $stmtPayments = $db->query("SELECT SUM(amount) as total FROM accounting_payments");
        $collected = floatval($stmtPayments->fetch()['total'] ?? 0.0);

        $stmtExp = $db->query("SELECT SUM(amount) as total FROM accounting_expenses WHERE status = 'approved'");
        $exp = floatval($stmtExp->fetch()['total'] ?? 0.0);

        $stmtSal = $db->query("SELECT SUM(net_salary) as total FROM salary_slips WHERE status = 'paid'");
        $sal = floatval($stmtSal->fetch()['total'] ?? 0.0);

        $bankCash = 150000.00 + $collected - $exp - $sal; // Start capital buffer + operating net profit
        $totalAssets = $bankCash + $accountsReceivable;

        // Liabilities: Pending TDS collected to be deposited
        $stmtTds = $db->query("SELECT SUM(tds_amount) as total FROM accounting_invoices WHERE status = 'paid'");
        $tdsLiability = floatval($stmtTds->fetch()['total'] ?? 0.0);

        // Equity: Billed Retained earnings
        $retainedEarnings = $totalAssets - $tdsLiability;

        return [
            'bank_cash' => $bankCash,
            'accounts_receivable' => $accountsReceivable,
            'total_assets' => $totalAssets,
            'tds_liability' => $tdsLiability,
            'retained_earnings' => $retainedEarnings,
            'total_equity_liabilities' => $tdsLiability + $retainedEarnings
        ];
    }

    /**
     * Generate dynamic Cash Flow Statement
     */
    public static function generateCashFlow($startDate, $endDate) {
        $db = Database::getConnection();

        // Cash Inflows: Recorded payments received
        $stmtIn = $db->prepare("SELECT SUM(amount) as total FROM accounting_payments WHERE payment_date >= :start AND payment_date <= :end");
        $stmtIn->execute(['start' => $startDate, 'end' => $endDate]);
        $inflows = floatval($stmtIn->fetch()['total'] ?? 0.0);

        // Cash Outflows: Paid Expenses + Paid Salaries
        $stmtOutExp = $db->prepare("SELECT SUM(amount) as total FROM accounting_expenses WHERE status = 'approved' AND date >= :start AND date <= :end");
        $stmtOutExp->execute(['start' => $startDate, 'end' => $endDate]);
        $outflowExpenses = floatval($stmtOutExp->fetch()['total'] ?? 0.0);

        $stmtOutSal = $db->prepare("SELECT SUM(net_salary) as total FROM salary_slips WHERE status = 'paid' AND (paid_date >= :start AND paid_date <= :end OR (paid_date IS NULL AND CONCAT(month, '-01') >= :start2 AND CONCAT(month, '-01') <= :end2))");
        $stmtOutSal->execute(['start' => $startDate, 'end' => $endDate, 'start2' => $startDate, 'end2' => $endDate]);
        $outflowSalaries = floatval($stmtOutSal->fetch()['total'] ?? 0.0);

        $netCashFlow = $inflows - ($outflowExpenses + $outflowSalaries);

        return [
            'cash_inflows' => $inflows,
            'cash_outflows_expenses' => $outflowExpenses,
            'cash_outflows_salaries' => $outflowSalaries,
            'net_cash_flow' => $netCashFlow
        ];
    }

    /**
     * Parse simulated statement CSV and match/reconcile invoices
     */
    public static function reconcileBankStatement($csvRows) {
        $db = Database::getConnection();
        $matched = 0;
        $skipped = 0;

        foreach ($csvRows as $row) {
            // Row expected format: Date, Details/Reference, Amount
            if (count($row) < 3) continue;

            $ref = trim($row[1]);
            $amount = floatval($row[2]);

            // Attempt matching using reference code matching Invoice Number e.g. "INV-1002"
            $stmt = $db->prepare("SELECT * FROM accounting_invoices WHERE INSTR(:ref, invoice_number) > 0 AND status = 'unpaid' LIMIT 1");
            $stmt->execute(['ref' => $ref]);
            $inv = $stmt->fetch();

            if ($inv && floatval($inv['net_amount']) == $amount) {
                // Perform automated payment matching
                self::recordPayment($inv['id'], $amount, date('Y-m-d'), 'Bank Transfer');
                $matched++;
            } else {
                $skipped++;
            }
        }
        return ['matched' => $matched, 'skipped' => $skipped];
    }

    /**
     * Fetch accounting KPIs
     */
    public static function getFinancialStats() {
        $db = Database::getConnection();
        
        // Total Invoiced
        $stmt1 = $db->query("SELECT SUM(net_amount) as total FROM accounting_invoices WHERE status != 'cancelled'");
        $invoiced = floatval($stmt1->fetch()['total'] ?? 0.0);
        
        // Total Collected
        $stmt2 = $db->query("SELECT SUM(amount) as total FROM accounting_payments");
        $collected = floatval($stmt2->fetch()['total'] ?? 0.0);
        
        // Total Expenses
        $stmt3 = $db->query("SELECT SUM(amount) as total FROM accounting_expenses WHERE status = 'approved'");
        $expenses = floatval($stmt3->fetch()['total'] ?? 0.0);
        
        // Outstanding (unpaid invoices)
        $outstanding = max(0.0, $invoiced - $collected);
        
        // Net Profit (Collected revenue - Expenses)
        $netProfit = $collected - $expenses;
        
        return [
            'total_invoiced' => $invoiced,
            'total_collected' => $collected,
            'outstanding' => $outstanding,
            'total_expenses' => $expenses,
            'net_profit' => $netProfit
        ];
    }

    /**
     * Get all standard services from catalog
     */
    public static function getServices() {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM services ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    /**
     * Create a new service
     */
    public static function createService($name, $charge, $description = null) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("INSERT INTO services (name, charge, description) VALUES (:name, :charge, :description)");
            $stmt->execute([
                'name' => $name,
                'charge' => $charge,
                'description' => $description
            ]);
            return ["success" => true, "id" => $db->lastInsertId()];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Update an existing service
     */
    public static function updateService($id, $name, $charge, $description = null) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("UPDATE services SET name = :name, charge = :charge, description = :description WHERE id = :id");
            $stmt->execute([
                'id' => $id,
                'name' => $name,
                'charge' => $charge,
                'description' => $description
            ]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Delete a service
     */
    public static function deleteService($id) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("DELETE FROM services WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    public static function getNextInvoiceNumber() {
        $db = Database::getConnection();
        $year = date('Y');
        $stmt = $db->prepare("SELECT invoice_number FROM accounting_invoices WHERE invoice_number LIKE :prefix");
        $stmt->execute(['prefix' => "INV-$year-%"]);
        $numbers = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $maxNum = 0;
        $maxLen = 2; // Default to 2 digits (e.g. 01, 13)
        
        foreach ($numbers as $numStr) {
            if (preg_match('/INV-\d+-(\d+)$/', $numStr, $matches)) {
                $val = intval($matches[1]);
                if ($val > $maxNum) {
                    $maxNum = $val;
                    $maxLen = strlen($matches[1]);
                }
            }
        }
        
        $nextVal = $maxNum + 1;
        $nextNumStr = str_pad($nextVal, $maxLen, '0', STR_PAD_LEFT);
        return "INV-$year-$nextNumStr";
    }
}
