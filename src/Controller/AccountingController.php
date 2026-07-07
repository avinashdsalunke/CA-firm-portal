<?php
// src/Controller/AccountingController.php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Accounting.php';

class AccountingController extends BaseController {
    public function handlePost() {
        $action = $_POST['action'] ?? '';
        $this->validateCSRF();

        switch ($action) {
            case 'add_invoice':
                $this->requirePermission('manage_accounting');
                $clientId = intval($_POST['client_id'] ?? 0);
                $invoiceNumber = trim($_POST['invoice_number'] ?? '');
                $amount = floatval($_POST['amount'] ?? 0.0);
                $issueDate = trim($_POST['issue_date'] ?? date('Y-m-d'));
                $dueDate = trim($_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days')));
                $desc = trim($_POST['description'] ?? '');

                // Taxes & Design
                $cgst = floatval($_POST['cgst'] ?? 0.0);
                $sgst = floatval($_POST['sgst'] ?? 0.0);
                $igst = floatval($_POST['igst'] ?? 0.0);
                $tds = floatval($_POST['tds_amount'] ?? 0.0);
                $design = trim($_POST['invoice_design'] ?? '');

                if ($clientId > 0 && !empty($invoiceNumber) && $amount > 0) {
                    $res = Accounting::createInvoice($clientId, $invoiceNumber, $amount, $issueDate, $dueDate, $desc, $cgst, $sgst, $igst, $tds, null, $design);
                    if (isset($res['success'])) {
                        Security::logActivity('add_invoice', "Generated invoice $invoiceNumber");
                        require_once __DIR__ . '/../Cache.php';
                        Cache::delete('financial_stats');
                        return ["success" => "Invoice created successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to create invoice."];
                }
                return ["error" => "Client, Invoice Number, and positive amount are required."];

            case 'record_payment':
                $this->requirePermission('manage_accounting');
                $invoiceId = intval($_POST['invoice_id'] ?? 0);
                $amount = floatval($_POST['amount'] ?? 0.0);
                $paymentDate = trim($_POST['payment_date'] ?? date('Y-m-d'));
                $paymentMethod = trim($_POST['payment_method'] ?? 'Bank Transfer');

                if ($invoiceId > 0 && $amount > 0) {
                    $res = Accounting::recordPayment($invoiceId, $amount, $paymentDate, $paymentMethod);
                    if (isset($res['success'])) {
                        Security::logActivity('record_payment', "Recorded collection of ₹$amount for Invoice ID $invoiceId");
                        require_once __DIR__ . '/../Cache.php';
                        Cache::delete('financial_stats');
                        return ["success" => "Payment recorded successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to record payment."];
                }
                return ["error" => "Invoice ID and positive amount are required."];

            case 'delete_invoice':
                $this->requirePermission('manage_accounting');
                $id = intval($_POST['id'] ?? 0);
                if ($id > 0) {
                    $res = Accounting::deleteInvoice($id);
                    if (isset($res['success'])) {
                        Security::logActivity('delete_invoice', "Deleted invoice ID $id");
                        require_once __DIR__ . '/../Cache.php';
                        Cache::delete('financial_stats');
                        return ["success" => "Invoice deleted successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to delete invoice."];
                }
                return ["error" => "Invalid ID."];

            case 'add_expense':
                // Removed strict requirePermission so employees can log pending expenses
                $category = trim($_POST['category'] ?? '');
                $amount = floatval($_POST['amount'] ?? 0.0);
                $date = trim($_POST['date'] ?? date('Y-m-d'));
                $desc = trim($_POST['description'] ?? '');
                
                $status = (isset($this->user['role']) && ($this->user['role'] === 'super_admin' || $this->user['role'] === 'admin_manager')) ? 'approved' : 'pending';
                $approved_by = $status === 'approved' ? $this->user['id'] : null;

                if (!empty($category) && $amount > 0) {
                    $res = Accounting::recordExpense($category, $amount, $date, $desc, $status, $approved_by);
                    if (isset($res['success'])) {
                        Security::logActivity('add_expense', "Logged firm expense of ₹$amount for $category (Status: $status)");
                        require_once __DIR__ . '/../Cache.php';
                        Cache::delete('financial_stats');
                        return ["success" => "Expense logged successfully (" . ucfirst($status) . ")."];
                    }
                    return ["error" => $res['error'] ?? "Failed to record expense."];
                }
                return ["error" => "Category and positive amount are required."];

            case 'delete_expense':
                $this->requirePermission('manage_accounting');
                $id = intval($_POST['id'] ?? 0);
                if ($id > 0) {
                    $res = Accounting::deleteExpense($id);
                    if (isset($res['success'])) {
                        Security::logActivity('delete_expense', "Deleted expense ID $id");
                        require_once __DIR__ . '/../Cache.php';
                        Cache::delete('financial_stats');
                        return ["success" => "Expense deleted successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to delete expense."];
                }
                return ["error" => "Invalid ID."];

            case 'review_expense':
                $this->requirePermission('manage_accounting');
                $id = intval($_POST['id'] ?? 0);
                $status = trim($_POST['status'] ?? 'pending');
                if ($id > 0 && in_array($status, ['approved', 'rejected'])) {
                    $res = Accounting::reviewExpense($id, $status, $this->user['id']);
                    if (isset($res['success'])) {
                        Security::logActivity('review_expense', "Reviewed expense ID $id as $status");
                        require_once __DIR__ . '/../Cache.php';
                        Cache::delete('financial_stats');
                        return ["success" => "Expense successfully " . $status];
                    }
                    return ["error" => $res['error']];
                }
                return ["error" => "Invalid expense review parameters."];

            case 'reconcile_bank_statement':
                $this->requirePermission('manage_accounting');
                if (isset($_FILES['statement']) && $_FILES['statement']['error'] === UPLOAD_ERR_OK) {
                    $fp = fopen($_FILES['statement']['tmp_name'], 'r');
                    $csvRows = [];
                    while (($row = fgetcsv($fp)) !== false) {
                        $csvRows[] = $row;
                    }
                    fclose($fp);

                    // Skip header row if exists
                    if (!empty($csvRows) && strtolower($csvRows[0][0]) === 'date') {
                        array_shift($csvRows);
                    }

                    $res = Accounting::reconcileBankStatement($csvRows);
                    Security::logActivity('bank_reconciliation', "Reconciled bank statement file: matched " . $res['matched'] . " invoices.");
                    require_once __DIR__ . '/../Cache.php';
                    Cache::delete('financial_stats');

                    return ["success" => "Bank Reconciliation complete: Matched " . $res['matched'] . " payments, Skipped " . $res['skipped'] . " entries."];
                }
                return ["error" => "Please upload a valid CSV bank statement file."];

            case 'add_service':
                $this->requirePermission('manage_accounting');
                $name = trim($_POST['name'] ?? '');
                $charge = floatval($_POST['charge'] ?? 0.0);
                $desc = trim($_POST['description'] ?? '');
                if (!empty($name) && $charge >= 0) {
                    $res = Accounting::createService($name, $charge, $desc);
                    if (isset($res['success'])) {
                        Security::logActivity('add_service', "Created service $name with charge ₹$charge");
                        require_once __DIR__ . '/../Cache.php';
                        Cache::delete('dashboard_stats');
                        return ["success" => "Service created successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to create service."];
                }
                return ["error" => "Service name and valid charge are required."];

            case 'edit_service':
                $this->requirePermission('manage_accounting');
                $id = intval($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $charge = floatval($_POST['charge'] ?? 0.0);
                $desc = trim($_POST['description'] ?? '');
                if ($id > 0 && !empty($name) && $charge >= 0) {
                    $res = Accounting::updateService($id, $name, $charge, $desc);
                    if (isset($res['success'])) {
                        Security::logActivity('edit_service', "Updated service ID $id ($name)");
                        require_once __DIR__ . '/../Cache.php';
                        Cache::delete('dashboard_stats');
                        return ["success" => "Service updated successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to update service."];
                }
                return ["error" => "Invalid parameters to update service."];

            case 'delete_service':
                $this->requirePermission('manage_accounting');
                $id = intval($_POST['id'] ?? 0);
                if ($id > 0) {
                    $res = Accounting::deleteService($id);
                    if (isset($res['success'])) {
                        Security::logActivity('delete_service', "Deleted service ID $id");
                        require_once __DIR__ . '/../Cache.php';
                        Cache::delete('dashboard_stats');
                        return ["success" => "Service deleted successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to delete service."];
                }
                return ["error" => "Invalid ID."];
        }
        return null;
    }
}
