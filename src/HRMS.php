<?php
// src/HRMS.php

require_once __DIR__ . '/../config/database.php';

class HRMS {
    /**
     * Get details of an employee
     */
    public static function getEmployeeDetails($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM employees WHERE user_id = :user_id LIMIT 1");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch();
    }

    /**
     * Create or update employee record
     */
    public static function updateEmployeeDetails($userId, $department, $designation, $joiningDate, $salary, $status = 'active', $basic = 0.00, $hra = 0.00, $conveyance = 0.00, $allowance = 0.00, $pf = 0.00, $pt = 0.00, $tds = 0.00) {
        $db = Database::getConnection();
        try {
            $existing = self::getEmployeeDetails($userId);
            if ($existing) {
                $stmt = $db->prepare("
                    UPDATE employees 
                    SET department = :department, designation = :designation, 
                        joining_date = :joining_date, salary = :salary, status = :status,
                        basic = :basic, hra = :hra, conveyance = :conveyance, allowance = :allowance,
                        pf = :pf, pt = :pt, tds = :tds
                    WHERE user_id = :user_id
                ");
            } else {
                $stmt = $db->prepare("
                    INSERT INTO employees (user_id, department, designation, joining_date, salary, status, basic, hra, conveyance, allowance, pf, pt, tds) 
                    VALUES (:user_id, :department, :designation, :joining_date, :salary, :status, :basic, :hra, :conveyance, :allowance, :pf, :pt, :tds)
                ");
            }
            $stmt->execute([
                'user_id' => $userId,
                'department' => $department,
                'designation' => $designation,
                'joining_date' => $joiningDate,
                'salary' => $salary,
                'status' => $status,
                'basic' => $basic,
                'hra' => $hra,
                'conveyance' => $conveyance,
                'allowance' => $allowance,
                'pf' => $pf,
                'pt' => $pt,
                'tds' => $tds
            ]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Record Check-in (clock in) for today
     */
    public static function clockIn($userId) {
        $db = Database::getConnection();
        $today = date('Y-m-d');
        $time = date('H:i:s');

        try {
            $stmt = $db->prepare("
                INSERT INTO attendance (user_id, date, check_in, status) 
                VALUES (:user_id, :date, :check_in_insert, 'present')
                ON DUPLICATE KEY UPDATE check_in = :check_in_update
            ");
            $stmt->execute([
                'user_id' => $userId,
                'date' => $today,
                'check_in_insert' => $time,
                'check_in_update' => $time
            ]);
            return ["success" => true, "time" => $time];
        } catch (PDOException $e) {
            return ["error" => "Clock-in failed: " . $e->getMessage()];
        }
    }

    /**
     * Record Check-out (clock out) for today
     */
    public static function clockOut($userId) {
        $db = Database::getConnection();
        $today = date('Y-m-d');
        $time = date('H:i:s');

        try {
            $stmt = $db->prepare("
                UPDATE attendance 
                SET check_out = :check_out 
                WHERE user_id = :user_id AND date = :date
            ");
            $stmt->execute([
                'check_out' => $time,
                'user_id' => $userId,
                'date' => $today
            ]);
            return ["success" => true, "time" => $time];
        } catch (PDOException $e) {
            return ["error" => "Clock-out failed: " . $e->getMessage()];
        }
    }

    /**
     * Get attendance record for today
     */
    public static function getTodayAttendance($userId) {
        $db = Database::getConnection();
        $today = date('Y-m-d');
        $stmt = $db->prepare("SELECT * FROM attendance WHERE user_id = :user_id AND date = :date LIMIT 1");
        $stmt->execute([
            'user_id' => $userId,
            'date' => $today
        ]);
        return $stmt->fetch();
    }

    /**
     * Fetch daily timesheet/attendance logs for admin view
     */
    public static function getAttendanceList($date) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT a.*, u.name as staff_name, e.department, e.designation
            FROM users u
            LEFT JOIN attendance a ON u.id = a.user_id AND a.date = :date
            LEFT JOIN employees e ON u.id = e.user_id
            WHERE u.role != 'super_admin'
            ORDER BY u.name ASC
        ");
        $stmt->execute(['date' => $date]);
        return $stmt->fetchAll();
    }

    /**
     * Request Leave
     */
    public static function requestLeave($userId, $leaveType, $startDate, $endDate, $reason) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, reason, status) 
                VALUES (:user_id, :leave_type, :start_date, :end_date, :reason, 'pending')
            ");
            $stmt->execute([
                'user_id' => $userId,
                'leave_type' => $leaveType,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reason' => $reason
            ]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Retrieve leave requests
     */
    public static function getLeaveRequests($filters = []) {
        $db = Database::getConnection();
        $sql = "
            SELECT lr.*, u.name as staff_name, app.name as approved_by_name
            FROM leave_requests lr
            JOIN users u ON lr.user_id = u.id
            LEFT JOIN users app ON lr.approved_by = app.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['user_id'])) {
            $sql .= " AND lr.user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND lr.status = :status";
            $params['status'] = $filters['status'];
        }

        $sql .= " ORDER BY lr.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Approve or reject a leave request
     */
    public static function reviewLeave($requestId, $approverId, $status) {
        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            // Update request
            $stmt = $db->prepare("
                UPDATE leave_requests 
                SET status = :status, approved_by = :approved_by 
                WHERE id = :id
            ");
            $stmt->execute([
                'status' => $status,
                'approved_by' => $approverId,
                'id' => $requestId
            ]);

            // If approved, insert check-ins as 'on_leave' for the dates range
            if ($status === 'approved') {
                $stmtGet = $db->prepare("SELECT * FROM leave_requests WHERE id = :id LIMIT 1");
                $stmtGet->execute(['id' => $requestId]);
                $req = $stmtGet->fetch();
                if ($req) {
                    $start = new DateTime($req['start_date']);
                    $end = new DateTime($req['end_date']);
                    $end->modify('+1 day'); // inclusive
                    $interval = new DateInterval('P1D');
                    $period = new DatePeriod($start, $interval, $end);

                    $stmtAtt = $db->prepare("
                        INSERT INTO attendance (user_id, date, status) 
                        VALUES (:user_id, :date, 'on_leave')
                        ON DUPLICATE KEY UPDATE status = 'on_leave'
                    ");
                    foreach ($period as $dt) {
                        $stmtAtt->execute([
                            'user_id' => $req['user_id'],
                            'date' => $dt->format('Y-m-d')
                        ]);
                    }
                }
            }

            $db->commit();
            return ["success" => true];
        } catch (Exception $e) {
            $db->rollBack();
            return ["error" => "Leave review failed: " . $e->getMessage()];
        }
    }

    /**
     * Create or replace a monthly Salary Slip
     */
    public static function generateSalarySlip($employeeId, $month, $basic, $hra, $conveyance, $allowance, $pf, $pt, $tds) {
        $db = Database::getConnection();
        $netSalary = floatval($basic) + floatval($hra) + floatval($conveyance) + floatval($allowance) - (floatval($pf) + floatval($pt) + floatval($tds));
        try {
            $stmtDel = $db->prepare("DELETE FROM salary_slips WHERE employee_id = :emp_id AND month = :month");
            $stmtDel->execute(['emp_id' => $employeeId, 'month' => $month]);

            $stmt = $db->prepare("
                INSERT INTO salary_slips (employee_id, month, basic, hra, conveyance, allowance, pf, pt, tds, net_salary, status) 
                VALUES (:emp_id, :month, :basic, :hra, :conveyance, :allowance, :pf, :pt, :tds, :net_salary, 'unpaid')
            ");
            $stmt->execute([
                'emp_id' => $employeeId,
                'month' => $month,
                'basic' => $basic,
                'hra' => $hra,
                'conveyance' => $conveyance,
                'allowance' => $allowance,
                'pf' => $pf,
                'pt' => $pt,
                'tds' => $tds,
                'net_salary' => $netSalary
            ]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Failed to generate salary slip: " . $e->getMessage()];
        }
    }

    /**
     * Mark a Salary Slip as Paid
     */
    public static function paySalarySlip($slipId) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("UPDATE salary_slips SET status = 'paid', paid_date = CURDATE() WHERE id = :id");
            $stmt->execute(['id' => $slipId]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Failed to pay salary slip: " . $e->getMessage()];
        }
    }

    /**
     * Get list of salary slips with optional employee filtering
     */
    public static function getSalarySlips($employeeId = null) {
        $db = Database::getConnection();
        $sql = "
            SELECT s.*, u.name as employee_name, u.email as employee_email, emp.department, emp.designation
            FROM salary_slips s
            JOIN users u ON s.employee_id = u.id
            LEFT JOIN employees emp ON u.id = emp.user_id
            WHERE 1=1
        ";
        $params = [];
        if ($employeeId !== null) {
            $sql .= " AND s.employee_id = :emp_id";
            $params['emp_id'] = $employeeId;
        }
        $sql .= " ORDER BY s.month DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get details of a specific Salary Slip
     */
    public static function getSalarySlip($slipId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT s.*, u.name as employee_name, u.email as employee_email, emp.department, emp.designation, emp.joining_date
            FROM salary_slips s
            JOIN users u ON s.employee_id = u.id
            LEFT JOIN employees emp ON u.id = emp.user_id
            WHERE s.id = :id LIMIT 1
        ");
        $stmt->execute(['id' => $slipId]);
        return $stmt->fetch();
    }

    /**
     * Update Employee Shift Assignment
     */
    public static function updateEmployeeShift($userId, $shift) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("UPDATE employees SET shift = :shift WHERE user_id = :user_id");
            $stmt->execute([
                'shift' => $shift,
                'user_id' => $userId
            ]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Review Leave Request with Workflow Comments
     */
    public static function reviewLeaveWorkflow($requestId, $approverId, $status, $comments = '') {
        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                UPDATE leave_requests 
                SET status = :status, approved_by = :approved_by, comments = :comments, workflow_step = 'reviewed_by_manager' 
                WHERE id = :id
            ");
            $stmt->execute([
                'status' => $status,
                'approved_by' => $approverId,
                'comments' => $comments,
                'id' => $requestId
            ]);

            // If approved, insert check-ins as 'on_leave' for the dates range
            if ($status === 'approved') {
                $stmtGet = $db->prepare("SELECT * FROM leave_requests WHERE id = :id LIMIT 1");
                $stmtGet->execute(['id' => $requestId]);
                $req = $stmtGet->fetch();
                if ($req) {
                    $start = new DateTime($req['start_date']);
                    $end = new DateTime($req['end_date']);
                    $interval = new DateInterval('P1D');
                    $period = new DatePeriod($start, $interval, $end->modify('+1 day'));

                    $stmtInsert = $db->prepare("
                        INSERT INTO attendance (user_id, date, status) 
                        VALUES (:user_id, :date, 'on_leave')
                        ON DUPLICATE KEY UPDATE status = 'on_leave'
                    ");
                    foreach ($period as $date) {
                        $stmtInsert->execute([
                            'user_id' => $req['user_id'],
                            'date' => $date->format('Y-m-d')
                        ]);
                    }
                }
            }
            $db->commit();
            return ["success" => true];
        } catch (Exception $e) {
            $db->rollBack();
            return ["error" => "Failed to review leave: " . $e->getMessage()];
        }
    }

    /**
     * Calculate Employee Performance Metrics
     */
    public static function calculatePerformanceMetrics($userId) {
        $db = Database::getConnection();
        
        // Assigned tasks vs Completed tasks
        $stmtTask = $db->prepare("
            SELECT COUNT(*) as total, SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed 
            FROM tasks WHERE assigned_to_user_id = :uid
        ");
        $stmtTask->execute(['uid' => $userId]);
        $taskData = $stmtTask->fetch();
        $totalTasks = intval($taskData['total'] ?? 0);
        $completedTasks = intval($taskData['completed'] ?? 0);
        $taskScore = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 100;

        // Attendance rate (last 30 days)
        $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
        $stmtAtt = $db->prepare("
            SELECT COUNT(*) as total, SUM(CASE WHEN status='present' OR status='on_leave' THEN 1 ELSE 0 END) as active 
            FROM attendance WHERE user_id = :uid AND date >= :thirty_days
        ");
        $stmtAtt->execute(['uid' => $userId, 'thirty_days' => $thirtyDaysAgo]);
        $attData = $stmtAtt->fetch();
        $totalDays = intval($attData['total'] ?? 0);
        $activeDays = intval($attData['active'] ?? 0);
        $attendanceRate = $totalDays > 0 ? round(($activeDays / $totalDays) * 100) : 100;

        // Total hours logged
        $stmtHrs = $db->prepare("SELECT SUM(hours_spent) as total FROM work_logs WHERE user_id = :uid");
        $stmtHrs->execute(['uid' => $userId]);
        $totalHours = floatval($stmtHrs->fetch()['total'] ?? 0.0);

        // Overall performance index
        $overallScore = round(($taskScore + $attendanceRate) / 2);

        return [
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'task_score' => $taskScore,
            'attendance_rate' => $attendanceRate,
            'total_hours' => $totalHours,
            'overall_score' => $overallScore
        ];
    }

    /**
     * Log Simulated Biometric Entry
     */
    public static function logBiometricRecord($userId, $date, $checkIn, $checkOut = null) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                INSERT INTO attendance (user_id, date, check_in, check_out, status) 
                VALUES (:user_id, :date, :check_in, :check_out, 'present')
                ON DUPLICATE KEY UPDATE check_in = :check_in_update, check_out = :check_out_update, status = 'present'
            ");
            $stmt->execute([
                'user_id' => $userId,
                'date' => $date,
                'check_in' => $checkIn,
                'check_out' => $checkOut ?: null,
                'check_in_update' => $checkIn,
                'check_out_update' => $checkOut ?: null
            ]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Get Employee Timeline Logs
     */
    public static function getEmployeeTimeline($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT al.*, u.name as staff_name 
            FROM activity_logs al 
            LEFT JOIN users u ON al.user_id = u.id 
            WHERE al.user_id = :uid 
            ORDER BY al.created_at DESC LIMIT 50
        ");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Assign Daily/Datewise Shift Timing to Employee
     */
    public static function assignShift($userId, $date, $shiftTiming) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                INSERT INTO shift_assignments (user_id, date, shift_timing) 
                VALUES (:user_id, :date, :shift)
                ON DUPLICATE KEY UPDATE shift_timing = :shift_update
            ");
            $stmt->execute([
                'user_id' => $userId,
                'date' => $date,
                'shift' => $shiftTiming,
                'shift_update' => $shiftTiming
            ]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Get Shift Assignments for Employee
     */
    public static function getShiftAssignments($userId = null) {
        $db = Database::getConnection();
        if ($userId !== null) {
            $stmt = $db->prepare("
                SELECT sa.*, u.name as staff_name 
                FROM shift_assignments sa
                JOIN users u ON sa.user_id = u.id
                WHERE sa.user_id = :uid 
                ORDER BY sa.date DESC
            ");
            $stmt->execute(['uid' => $userId]);
            return $stmt->fetchAll();
        } else {
            return $db->query("
                SELECT sa.*, u.name as staff_name 
                FROM shift_assignments sa
                JOIN users u ON sa.user_id = u.id
                ORDER BY sa.date DESC, u.name ASC
            ")->fetchAll();
        }
    }
}
