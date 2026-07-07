<?php
// src/Controller/ReportController.php

require_once __DIR__ . '/../Report.php';
require_once __DIR__ . '/../Util.php';

class ReportController {
    public function dispatch($action) {
        switch ($action) {
            case 'export_report_csv':
                $type = trim($_POST['report_type'] ?? 'revenue');
                $filters = [
                    'start_date' => $_POST['start_date'] ?? '',
                    'end_date' => $_POST['end_date'] ?? '',
                    'client_id' => intval($_POST['client_id'] ?? 0)
                ];

                if ($type === 'revenue') {
                    $res = Report::getRevenueReport($filters);
                    $headers = ['Invoice Number', 'Client Name', 'Billed Amount', 'Status', 'Issue Date', 'Due Date'];
                    $data = [];
                    foreach ($res['data'] as $row) {
                        $data[] = [
                            $row['invoice_number'],
                            $row['client_name'],
                            $row['net_amount'],
                            $row['status'],
                            $row['issue_date'],
                            $row['due_date']
                        ];
                    }
                    Util::exportCSV('revenue_report.csv', $headers, $data);
                } elseif ($type === 'client') {
                    $res = Report::getClientReport($filters);
                    $headers = ['Client Name', 'Email', 'Phone', 'Created On', 'Total Billed (INR)', 'Total Collected (INR)'];
                    $data = [];
                    foreach ($res['data'] as $row) {
                        $data[] = [
                            $row['name'],
                            $row['email'],
                            $row['phone'],
                            $row['created_at'],
                            $row['total_billed'],
                            $row['total_collected']
                        ];
                    }
                    Util::exportCSV('client_report.csv', $headers, $data);
                } elseif ($type === 'employee') {
                    $res = Report::getEmployeeReport($filters);
                    $headers = ['Employee Name', 'Role', 'Department', 'Designation', 'Total Tasks', 'Completed Tasks', 'Hours Logged', 'Efficiency (%)'];
                    $data = [];
                    foreach ($res['data'] as $row) {
                        $data[] = [
                            $row['name'],
                            $row['role'],
                            $row['department'] ?? 'N/A',
                            $row['designation'] ?? 'N/A',
                            $row['total_tasks'],
                            $row['completed_tasks'],
                            $row['total_hours'],
                            $row['efficiency']
                        ];
                    }
                    Util::exportCSV('employee_report.csv', $headers, $data);
                } elseif ($type === 'task') {
                    $res = Report::getTaskReport($filters);
                    $headers = ['Task Title', 'Client', 'Assigned To', 'Category', 'Status', 'Due Date', 'Created Date'];
                    $data = [];
                    foreach ($res['data'] as $row) {
                        $data[] = [
                            $row['title'],
                            $row['client_name'],
                            $row['staff_name'] ?? 'Unassigned',
                            $row['category'],
                            $row['status'],
                            $row['due_date'],
                            $row['created_at']
                        ];
                    }
                    Util::exportCSV('task_report.csv', $headers, $data);
                } elseif ($type === 'compliance') {
                    $res = Report::getComplianceReport($filters);
                    $headers = ['Filing Title', 'Client Name', 'Category', 'Due Date', 'Filing Date', 'Status', 'Escalated'];
                    $data = [];
                    foreach ($res['data'] as $row) {
                        $data[] = [
                            $row['title'],
                            $row['client_name'],
                            $row['category'],
                            $row['due_date'],
                            $row['filing_date'] ?? 'N/A',
                            $row['status'],
                            $row['escalated'] ? 'YES' : 'NO'
                        ];
                    }
                    Util::exportCSV('compliance_report.csv', $headers, $data);
                }
                break;
        }
        return null;
    }
}
