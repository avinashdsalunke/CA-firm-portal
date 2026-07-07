<?php
// src/Report.php

require_once __DIR__ . '/../config/database.php';

class Report {
    /**
     * Get Revenue report data
     */
    public static function getRevenueReport($filters = []) {
        $db = Database::getConnection();
        
        $sql = "
            SELECT i.invoice_number, c.name as client_name, i.amount, i.net_amount, i.status, i.issue_date, i.due_date
            FROM accounting_invoices i
            JOIN clients c ON i.client_id = c.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['start_date'])) {
            $sql .= " AND i.issue_date >= :start_date";
            $params['start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND i.issue_date <= :end_date";
            $params['end_date'] = $filters['end_date'];
        }
        if (!empty($filters['client_id'])) {
            $sql .= " AND i.client_id = :client_id";
            $params['client_id'] = $filters['client_id'];
        }

        $sql .= " ORDER BY i.issue_date DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Calculate aggregates
        $billed = 0.0;
        $collected = 0.0;
        $unpaid = 0.0;

        foreach ($rows as $r) {
            $amt = floatval($r['net_amount']);
            $billed += $amt;
            if ($r['status'] === 'paid') {
                $collected += $amt;
            } else {
                $unpaid += $amt;
            }
        }

        return [
            'data' => $rows,
            'summary' => [
                'billed' => $billed,
                'collected' => $collected,
                'unpaid' => $unpaid
            ]
        ];
    }

    /**
     * Get Client report data
     */
    public static function getClientReport($filters = []) {
        $db = Database::getConnection();
        
        $sql = "
            SELECT c.id, c.name, c.email, c.phone, c.created_at,
                   COALESCE(SUM(i.net_amount), 0.0) as total_billed,
                   COALESCE(SUM(CASE WHEN i.status = 'paid' THEN i.net_amount ELSE 0.0 END), 0.0) as total_collected
            FROM clients c
            LEFT JOIN accounting_invoices i ON c.id = i.client_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['client_id'])) {
            $sql .= " AND c.id = :client_id";
            $params['client_id'] = $filters['client_id'];
        }

        $sql .= " GROUP BY c.id ORDER BY total_billed DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return [
            'data' => $rows,
            'summary' => [
                'total_clients' => count($rows),
                'top_billed_client' => $rows[0]['name'] ?? 'N/A'
            ]
        ];
    }

    /**
     * Get Employee productivity report data
     */
    public static function getEmployeeReport($filters = []) {
        $db = Database::getConnection();
        
        $sql = "
            SELECT u.id, u.name, u.email, u.role,
                   hp.department, hp.designation, hp.salary,
                   COUNT(t.id) as total_tasks,
                   COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks,
                   COALESCE(SUM(wl.hours_spent), 0.0) as total_hours
            FROM users u
            LEFT JOIN employees hp ON u.id = hp.user_id
            LEFT JOIN tasks t ON u.id = t.assigned_to_user_id
            LEFT JOIN work_logs wl ON u.id = wl.user_id
            GROUP BY u.id
            ORDER BY u.name ASC
        ";
        $stmt = $db->query($sql);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['efficiency'] = $row['total_tasks'] > 0 
                ? round(($row['completed_tasks'] / $row['total_tasks']) * 100) 
                : 100;
        }

        return [
            'data' => $rows,
            'summary' => [
                'total_employees' => count($rows),
                'avg_efficiency' => count($rows) > 0 ? round(array_sum(array_column($rows, 'efficiency')) / count($rows)) : 100
            ]
        ];
    }

    /**
     * Get Task report data
     */
    public static function getTaskReport($filters = []) {
        $db = Database::getConnection();
        
        $sql = "
            SELECT t.title, c.name as client_name, u.name as staff_name, t.category, t.status, t.due_date, t.created_at
            FROM tasks t
            LEFT JOIN clients c ON t.client_id = c.id
            LEFT JOIN users u ON t.assigned_to_user_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['start_date'])) {
            $sql .= " AND t.created_at >= :start_date";
            $params['start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND t.created_at <= :end_date";
            $params['end_date'] = $filters['end_date'];
        }

        $sql .= " ORDER BY t.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $completed = 0;
        $inProgress = 0;
        $pending = 0;

        foreach ($rows as $r) {
            if ($r['status'] === 'completed') $completed++;
            elseif ($r['status'] === 'in_progress') $inProgress++;
            else $pending++;
        }

        return [
            'data' => $rows,
            'summary' => [
                'total_tasks' => count($rows),
                'completed' => $completed,
                'in_progress' => $inProgress,
                'pending' => $pending
            ]
        ];
    }

    /**
     * Get Compliance report data
     */
    public static function getComplianceReport($filters = []) {
        $db = Database::getConnection();
        
        $sql = "
            SELECT co.title, c.name as client_name, co.category, co.due_date, co.filing_date, co.status, co.escalated
            FROM compliances co
            JOIN clients c ON co.client_id = c.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['start_date'])) {
            $sql .= " AND co.due_date >= :start_date";
            $params['start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND co.due_date <= :end_date";
            $params['end_date'] = $filters['end_date'];
        }

        $sql .= " ORDER BY co.due_date DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $filed = 0;
        $pending = 0;
        $overdue = 0;
        $escalated = 0;

        foreach ($rows as $r) {
            if ($r['status'] === 'filed') {
                $filed++;
            } else {
                $pending++;
                if (strtotime($r['due_date']) < time()) {
                    $overdue++;
                }
            }
            if ($r['escalated']) {
                $escalated++;
            }
        }

        return [
            'data' => $rows,
            'summary' => [
                'total_filings' => count($rows),
                'filed' => $filed,
                'pending' => $pending,
                'overdue' => $overdue,
                'escalated' => $escalated
            ]
        ];
    }

    /**
     * Get Profit report data (Invoiced vs expenses & salaries)
     */
    public static function getProfitReport($filters = []) {
        $db = Database::getConnection();
        
        // 1. Get Revenue (Billed)
        $revSql = "SELECT COALESCE(SUM(net_amount), 0.0) as total FROM accounting_invoices WHERE 1=1";
        $revParams = [];
        if (!empty($filters['start_date'])) {
            $revSql .= " AND issue_date >= :start_date";
            $revParams['start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $revSql .= " AND issue_date <= :end_date";
            $revParams['end_date'] = $filters['end_date'];
        }
        $stmt = $db->prepare($revSql);
        $stmt->execute($revParams);
        $revenue = floatval($stmt->fetch()['total'] ?? 0.0);

        // 2. Get Custom Expenses (Approved only)
        $expSql = "SELECT COALESCE(SUM(amount), 0.0) as total FROM accounting_expenses WHERE status = 'approved'";
        $expParams = [];
        if (!empty($filters['start_date'])) {
            $expSql .= " AND date >= :start_date";
            $expParams['start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $expSql .= " AND date <= :end_date";
            $expParams['end_date'] = $filters['end_date'];
        }
        $stmt = $db->prepare($expSql);
        $stmt->execute($expParams);
        $customExpenses = floatval($stmt->fetch()['total'] ?? 0.0);

        // 3. Get Employee Payroll Salaries
        $salSql = "SELECT COALESCE(SUM(salary), 0.0) as total FROM employees WHERE status = 'active'";
        $salaries = floatval($db->query($salSql)->fetch()['total'] ?? 0.0);

        // For month scaling, let's assume salary is monthly. If filters have dates, scale salary by month count
        $months = 1;
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $diff = abs(strtotime($filters['end_date']) - strtotime($filters['start_date']));
            $months = max(1, round($diff / (30 * 24 * 60 * 60)));
        }
        $totalSalaries = $salaries * $months;

        $totalExpenses = $customExpenses + $totalSalaries;
        $netProfit = $revenue - $totalExpenses;
        $margin = $revenue > 0 ? round(($netProfit / $revenue) * 100, 2) : 0;

        return [
            'summary' => [
                'revenue' => $revenue,
                'custom_expenses' => $customExpenses,
                'payroll_salaries' => $totalSalaries,
                'total_expenses' => $totalExpenses,
                'net_profit' => $netProfit,
                'margin' => $margin
            ]
        ];
    }
}
