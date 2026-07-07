<?php
// src/Controller/HRMSController.php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../HRMS.php';
require_once __DIR__ . '/../Auth.php';

class HRMSController extends BaseController {
    public function handlePost() {
        $action = $_POST['action'] ?? '';
        $this->validateCSRF();

        switch ($action) {
            case 'clock_in':
                $res = HRMS::clockIn($this->user['id']);
                if (isset($res['success'])) {
                    Security::logActivity('clock_in', "Employee clocked in at " . $res['time']);
                    return ["success" => "Clocked in successfully at " . $res['time']];
                }
                return ["error" => $res['error']];

            case 'clock_out':
                $res = HRMS::clockOut($this->user['id']);
                if (isset($res['success'])) {
                    Security::logActivity('clock_out', "Employee clocked out at " . $res['time']);
                    return ["success" => "Clocked out successfully at " . $res['time']];
                }
                return ["error" => $res['error']];

            case 'request_leave':
                $type = trim($_POST['leave_type'] ?? 'casual');
                $start = trim($_POST['start_date'] ?? '');
                $end = trim($_POST['end_date'] ?? '');
                $reason = trim($_POST['reason'] ?? '');

                if (!empty($start) && !empty($end)) {
                    $res = HRMS::requestLeave($this->user['id'], $type, $start, $end, $reason);
                    if (isset($res['success'])) {
                        Security::logActivity('request_leave', "Requested $type leave from $start to $end");
                        return ["success" => "Leave request submitted successfully."];
                    }
                    return ["error" => $res['error']];
                }
                return ["error" => "Start and End dates are required."];

            case 'review_leave':
                $this->requirePermission('manage_hrms');
                $reqId = intval($_POST['id'] ?? 0);
                $status = trim($_POST['status'] ?? 'pending');
                $comments = trim($_POST['comments'] ?? '');

                if ($reqId > 0 && in_array($status, ['approved', 'rejected'])) {
                    $res = HRMS::reviewLeaveWorkflow($reqId, $this->user['id'], $status, $comments);
                    if (isset($res['success'])) {
                        Security::logActivity('review_leave', "Leave request ID $reqId reviewed as: $status (Comments: $comments)");
                        return ["success" => "Leave request successfully $status."];
                    }
                    return ["error" => $res['error']];
                }
                return ["error" => "Invalid parameters."];

             case 'update_employee_profile':
                $this->requirePermission('manage_hrms');
                $empUserId = intval($_POST['user_id'] ?? 0);
                $dept = trim($_POST['department'] ?? '');
                $desig = trim($_POST['designation'] ?? '');
                $joining = trim($_POST['joining_date'] ?? '');
                $salary = floatval($_POST['salary'] ?? 0.0);
                $status = trim($_POST['status'] ?? 'active');

                // Structured salary breakdown
                $basic = floatval($_POST['basic'] ?? 0.0);
                $hra = floatval($_POST['hra'] ?? 0.0);
                $conveyance = floatval($_POST['conveyance'] ?? 0.0);
                $allowance = floatval($_POST['allowance'] ?? 0.0);
                $pf = floatval($_POST['pf'] ?? 0.0);
                $pt = floatval($_POST['pt'] ?? 0.0);
                $tds = floatval($_POST['tds'] ?? 0.0);

                if ($empUserId > 0 && !empty($dept) && !empty($desig)) {
                    $res = HRMS::updateEmployeeDetails(
                        $empUserId, $dept, $desig, $joining, $salary, $status,
                        $basic, $hra, $conveyance, $allowance, $pf, $pt, $tds
                    );
                    if (isset($res['success'])) {
                        Security::logActivity('update_employee', "Updated HR details and salary structure for user ID $empUserId");
                        return ["success" => "Employee record updated successfully."];
                    }
                    return ["error" => $res['error']];
                }
                return ["error" => "Department and Designation are required."];

            case 'assign_shift':
                $this->requirePermission('manage_hrms');
                $staffId = intval($_POST['user_id'] ?? 0);
                $date = trim($_POST['date'] ?? '');
                $timing = trim($_POST['shift_timing'] ?? '');

                if ($staffId > 0 && !empty($date) && !empty($timing)) {
                    $res = HRMS::assignShift($staffId, $date, $timing);
                    if (isset($res['success'])) {
                        Security::logActivity('assign_shift', "Assigned shift '$timing' for user ID $staffId on $date");
                        return ["success" => "Shift assigned successfully."];
                    }
                    return ["error" => $res['error']];
                }
                return ["error" => "All fields are required to assign a shift."];

            case 'add_staff':
                $this->requirePermission('manage_staff');
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = trim($_POST['password'] ?? '');
                $pin = trim($_POST['pin'] ?? '');
                $role = trim($_POST['role'] ?? 'staff');
                if (!empty($name) && !empty($email) && !empty($password) && !empty($pin)) {
                    $res = Auth::createStaff($name, $email, $password, $pin, $role);
                    if (isset($res['success'])) {
                        Security::logActivity('add_staff', "Created staff member $name");
                        return ["success" => "Staff user created successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to create staff."];
                }
                return ["error" => "All staff fields are required."];

            case 'edit_staff':
                $this->requirePermission('manage_staff');
                $id = intval($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $role = trim($_POST['role'] ?? 'staff');
                $password = !empty($_POST['password']) ? trim($_POST['password']) : null;
                $pin = !empty($_POST['pin']) ? trim($_POST['pin']) : null;
                if ($id > 0 && !empty($name) && !empty($email)) {
                    $res = Auth::updateStaff($id, $name, $email, $role, $password, $pin);
                    if (isset($res['success'])) {
                        Security::logActivity('edit_staff', "Updated staff ID $id details");
                        return ["success" => "Staff user updated successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to update staff."];
                }
                return ["error" => "Invalid inputs."];

            case 'delete_staff':
                $this->requirePermission('manage_staff');
                $id = intval($_POST['id'] ?? 0);
                if ($id > 0) {
                    $res = Auth::deleteStaff($id);
                    if (isset($res['success'])) {
                        Security::logActivity('delete_staff', "Deleted staff member ID $id");
                        return ["success" => "Staff user deleted successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to delete staff."];
                }
                return ["error" => "Invalid ID."];

            case 'generate_salary_slip':
                $this->requirePermission('manage_hrms');
                $empId = intval($_POST['employee_id'] ?? 0);
                $month = trim($_POST['month'] ?? '');
                $basic = floatval($_POST['basic'] ?? 0);
                $hra = floatval($_POST['hra'] ?? 0);
                $conveyance = floatval($_POST['conveyance'] ?? 0);
                $allowance = floatval($_POST['allowance'] ?? 0);
                $pf = floatval($_POST['pf'] ?? 0);
                $pt = floatval($_POST['pt'] ?? 0);
                $tds = floatval($_POST['tds'] ?? 0);

                if ($empId > 0 && !empty($month)) {
                    $res = HRMS::generateSalarySlip($empId, $month, $basic, $hra, $conveyance, $allowance, $pf, $pt, $tds);
                    if (isset($res['success'])) {
                        Security::logActivity('generate_salary_slip', "Generated salary slip for employee ID $empId for month $month");
                        return ["success" => "Salary slip generated successfully."];
                    }
                    return ["error" => $res['error']];
                }
                return ["error" => "Employee and Month parameters are required."];

            case 'pay_salary_slip':
                $this->requirePermission('manage_hrms');
                $slipId = intval($_POST['id'] ?? 0);
                if ($slipId > 0) {
                    $res = HRMS::paySalarySlip($slipId);
                    if (isset($res['success'])) {
                        Security::logActivity('pay_salary_slip', "Paid salary slip ID $slipId");
                        return ["success" => "Salary slip marked as paid."];
                    }
                    return ["error" => $res['error']];
                }
                return ["error" => "Invalid slip ID."];

            case 'update_shift':
                $this->requirePermission('manage_hrms');
                $empUserId = intval($_POST['user_id'] ?? 0);
                $shift = trim($_POST['shift'] ?? 'General');
                if ($empUserId > 0) {
                    $res = HRMS::updateEmployeeShift($empUserId, $shift);
                    if (isset($res['success'])) {
                        Security::logActivity('update_shift', "Updated shift assignment for user ID $empUserId to $shift");
                        return ["success" => "Shift assignment updated to $shift."];
                    }
                    return ["error" => $res['error']];
                }
                return ["error" => "Invalid parameters."];

            case 'simulate_qr_attendance':
                // Verify user is scanning their own QR code
                $res = HRMS::clockIn($this->user['id']);
                if (isset($res['success'])) {
                    Security::logActivity('qr_clock_in', "Employee clocked in via QR Code scan at " . $res['time']);
                    return ["success" => "QR Code scan successful! Clocked in at " . $res['time']];
                }
                return ["error" => $res['error']];

            case 'simulate_biometric':
                $this->requirePermission('manage_hrms');
                $empUserId = intval($_POST['user_id'] ?? 0);
                $date = trim($_POST['date'] ?? date('Y-m-d'));
                $checkIn = trim($_POST['check_in'] ?? '09:00:00');
                $checkOut = trim($_POST['check_out'] ?? '18:00:00');

                if ($empUserId > 0 && !empty($date)) {
                    $res = HRMS::logBiometricRecord($empUserId, $date, $checkIn, $checkOut);
                    if (isset($res['success'])) {
                        Security::logActivity('biometric_sim', "Logged biometric entry for user ID $empUserId on $date");
                        return ["success" => "Simulated Biometric log stored successfully."];
                    }
                    return ["error" => $res['error']];
                }
                return ["error" => "Invalid biometric parameters."];
        }
        return null;
    }
}
