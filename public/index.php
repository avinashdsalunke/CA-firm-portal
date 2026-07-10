<?php
// public/index.php

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Client.php';
require_once __DIR__ . '/../src/Task.php';
require_once __DIR__ . '/../src/Document.php';
require_once __DIR__ . '/../src/Util.php';
require_once __DIR__ . '/../src/Accounting.php';
require_once __DIR__ . '/../src/Compliance.php';
require_once __DIR__ . '/../src/RBAC.php';
require_once __DIR__ . '/../src/Security.php';
require_once __DIR__ . '/../src/HRMS.php';
require_once __DIR__ . '/../src/Communication.php';
require_once __DIR__ . '/../src/Cache.php';
require_once __DIR__ . '/../src/AIService.php';
require_once __DIR__ . '/../src/CRM.php';
require_once __DIR__ . '/../src/Report.php';
require_once __DIR__ . '/../src/Tenant.php';
require_once __DIR__ . '/../src/OfficeSuite.php';

// Controllers
require_once __DIR__ . '/../src/Controller/ClientController.php';
require_once __DIR__ . '/../src/Controller/TaskController.php';
require_once __DIR__ . '/../src/Controller/AccountingController.php';
require_once __DIR__ . '/../src/Controller/HRMSController.php';
require_once __DIR__ . '/../src/Controller/CommunicationController.php';
require_once __DIR__ . '/../src/Controller/ComplianceController.php';
require_once __DIR__ . '/../src/Controller/CRMController.php';
require_once __DIR__ . '/../src/Controller/ReportController.php';

Util::startSession();
Util::setSecurityHeaders();

// Session activity timeout check (15 minutes)
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
        Auth::logout();
        Util::redirect('login.php?timeout=1');
    }
    $_SESSION['last_activity'] = time();
}

// Access Guard: must be logged in to view dashboard
Auth::requireLogin();

$user = Auth::getCurrentUser();
$isAdmin = ($user['role'] === 'super_admin' || $user['role'] === 'admin_manager');

$activeTab = trim($_GET['tab'] ?? 'dashboard');

// Ajax handler for Mega Search query queries
if (isset($_GET['action']) && $_GET['action'] === 'mega_search') {
    header('Content-Type: application/json');
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) {
        echo json_encode([]);
        exit;
    }
    $db = Database::getConnection();
    $results = [];

    // Search clients
    $stmt = $db->prepare("SELECT id, name, email FROM clients WHERE name LIKE ? OR email LIKE ? LIMIT 5");
    $stmt->execute(["%$q%", "%$q%"]);
    foreach ($stmt->fetchAll() as $r) {
        $results[] = [
            'title' => $r['name'],
            'subtitle' => 'Client: ' . $r['email'],
            'url' => 'index.php?tab=clients&client_id=' . $r['id']
        ];
    }

    // Search tasks
    $stmt = $db->prepare("SELECT id, title, status FROM tasks WHERE title LIKE ? LIMIT 5");
    $stmt->execute(["%$q%"]);
    foreach ($stmt->fetchAll() as $r) {
        $results[] = [
            'title' => $r['title'],
            'subtitle' => 'Task Status: ' . strtoupper($r['status']),
            'url' => 'index.php?tab=tasks'
        ];
    }

    // Search compliances
    $stmt = $db->prepare("SELECT id, title, status FROM compliances WHERE title LIKE ? LIMIT 5");
    $stmt->execute(["%$q%"]);
    foreach ($stmt->fetchAll() as $r) {
        $results[] = [
            'title' => $r['title'],
            'subtitle' => 'Compliance filing: ' . strtoupper($r['status']),
            'url' => 'index.php?tab=compliances'
        ];
    }

    echo json_encode($results);
    exit;
}

// Ajax handler for AI Queries
if (isset($_GET['action']) && $_GET['action'] === 'ai_query') {
    header('Content-Type: application/json');
    $type = $_GET['type'] ?? '';
    if ($type === 'chat') {
        $prompt = $_GET['prompt'] ?? '';
        echo json_encode(['response' => AIService::chatResponse($prompt)]);
    } elseif ($type === 'email') {
        $clientName = $_GET['client_name'] ?? '';
        $topic = $_GET['topic'] ?? '';
        echo json_encode(['draft' => AIService::generateEmailDraft($clientName, $topic)]);
    } elseif ($type === 'subtasks') {
        $title = $_GET['title'] ?? '';
        echo json_encode(['subtasks' => AIService::suggestSubtasks($title)]);
    } elseif ($type === 'report_summary') {
        $rawData = $_POST['data'] ?? '{}';
        $reportData = json_decode($rawData, true);
        echo json_encode(['summary' => AIService::summarizeReport($reportData)]);
    } elseif ($type === 'parse_doc') {
        $filePath = $_GET['file_path'] ?? '';
        $mimeType = $_GET['mime_type'] ?? '';
        $realBase = realpath(__DIR__ . '/uploads');
        $realTarget = realpath($filePath);
        if ($realTarget && strpos($realTarget, $realBase) === 0 && file_exists($realTarget)) {
            $parseResult = AIService::parseUploadedDocument($realTarget, $mimeType);
            echo json_encode($parseResult);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid file path or file not found.']);
        }
    }
    exit;
}
if (!$isAdmin && !in_array($activeTab, ['dashboard', 'tasks', 'logs', 'compliances', 'hrms', 'chat'])) {
    $activeTab = 'dashboard';
}

$error = null;
$success = $_SESSION['flash_success'] ?? null;
if ($success) {
    unset($_SESSION['flash_success']);
}

// Controller dispatching for POST actions (MVC pattern)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        $res = null;
        if (in_array($action, ['add_client', 'edit_client', 'delete_client', 'generate_token', 'add_request', 'review_request', 'delete_document', 'staff_upload_document'])) {
            $ctrl = new ClientController();
            $res = $ctrl->handlePost();
        } elseif (in_array($action, ['add_lead', 'update_lead_status', 'delete_lead', 'add_opportunity', 'edit_opportunity_stage', 'add_note', 'delete_note', 'schedule_meeting', 'update_meeting_status', 'send_whatsapp', 'send_email'])) {
            $ctrl = new CRMController();
            $res = $ctrl->handlePost();
        } elseif (in_array($action, ['add_compliance', 'record_filing', 'delete_compliance'])) {
            $ctrl = new ComplianceController();
            $res = $ctrl->handlePost();
        } elseif (in_array($action, ['add_task', 'edit_task', 'delete_task', 'update_task_status', 'log_work', 'add_template', 'spawn_tasks'])) {
            $ctrl = new TaskController();
            $res = $ctrl->handlePost();
        } elseif (in_array($action, ['add_invoice', 'record_payment', 'delete_invoice', 'add_expense', 'delete_expense', 'add_service', 'edit_service', 'delete_service'])) {
            $ctrl = new AccountingController();
            $res = $ctrl->handlePost();
        } elseif (in_array($action, ['clock_in', 'clock_out', 'request_leave', 'review_leave', 'update_employee_profile', 'assign_shift', 'add_staff', 'edit_staff', 'delete_staff', 'generate_salary_slip', 'pay_salary_slip'])) {
            $ctrl = new HRMSController();
            $res = $ctrl->handlePost();
        } elseif (in_array($action, ['send_chat_message', 'add_announcement', 'delete_announcement', 'update_permissions'])) {
            $ctrl = new CommunicationController();
            $res = $ctrl->handlePost();
        } elseif ($action === 'run_cron_manually') {
            $output = [];
            $retval = 0;
            exec('php ' . escapeshellarg(__DIR__ . '/../bin/cron_runner.php'), $output, $retval);
            $res = ["success" => "Cron executed successfully. Details: " . implode(" | ", $output)];
        } elseif (in_array($action, ['toggle_2fa', 'add_ip_whitelist', 'delete_ip_whitelist', 'logout_other_devices', 'save_encrypted_tax_data'])) {
            if ($action === 'toggle_2fa') {
                $db = Database::getConnection();
                $stmt = $db->prepare("UPDATE users SET two_fa_enabled = :val WHERE id = :id");
                $stmt->execute([
                    'val' => intval($_POST['two_fa_enabled'] ?? 0),
                    'id' => $user['id']
                ]);
                $res = ["success" => "Two-Factor Authentication configuration updated."];
            } elseif ($action === 'add_ip_whitelist') {
                if ($isAdmin) {
                    $res = Security::addWhitelistedIP($_POST['ip_address'] ?? '', $_POST['description'] ?? '');
                } else {
                    $res = ["error" => "Unauthorized access."];
                }
            } elseif ($action === 'delete_ip_whitelist') {
                if ($isAdmin) {
                    $res = Security::deleteWhitelistedIP($_POST['id'] ?? 0);
                } else {
                    $res = ["error" => "Unauthorized access."];
                }
            } elseif ($action === 'logout_other_devices') {
                $currentToken = $_SESSION['session_token'] ?? '';
                if (Security::logoutOtherDevices($user['id'], $currentToken)) {
                    $res = ["success" => "Successfully logged out other devices."];
                } else {
                    $res = ["error" => "Failed to terminate other sessions."];
                }
            } elseif ($action === 'save_encrypted_tax_data') {
                if ($isAdmin) {
                    $cId = intval($_POST['client_id'] ?? 0);
                    $taxVal = trim($_POST['tax_data'] ?? '');
                    $encVal = Security::encryptData($taxVal);
                    $db = Database::getConnection();
                    $stmt = $db->prepare("UPDATE clients SET encrypted_tax_data = :val WHERE id = :id");
                    $stmt->execute(['val' => $encVal, 'id' => $cId]);
                    $res = ["success" => "Tax data securely encrypted and saved."];
                } else {
                    $res = ["error" => "Unauthorized access."];
                }
            }
            } elseif ($action === 'add_dsc') {
                $cId = intval($_POST['client_id'] ?? 0);
                $dName = trim($_POST['director_name'] ?? '');
                $expDate = trim($_POST['expiry_date'] ?? '');
                $pwd = trim($_POST['password_hint'] ?? '');
                $pin = trim($_POST['pin_hint'] ?? '');
                if ($cId > 0 && !empty($dName) && !empty($expDate)) {
                    $res = OfficeSuite::addDSCToken($cId, $dName, $expDate, $pwd, $pin);
                } else {
                    $res = ["error" => "Client, Director Name and Expiry Date are required."];
                }
            } elseif ($action === 'delete_dsc') {
                $id = intval($_POST['id'] ?? 0);
                $res = OfficeSuite::deleteDSCToken($id);
            } elseif ($action === 'add_expiry') {
                $cId = intval($_POST['client_id'] ?? 0);
                $docType = trim($_POST['doc_type'] ?? '');
                $expDate = trim($_POST['expiry_date'] ?? '');
                if ($cId > 0 && !empty($docType) && !empty($expDate)) {
                    $res = OfficeSuite::addDocumentExpiry($cId, $docType, $expDate);
                } else {
                    $res = ["error" => "Client, Document Type and Expiry Date are required."];
                }
            } elseif ($action === 'delete_expiry') {
                $id = intval($_POST['id'] ?? 0);
                $res = OfficeSuite::deleteDocumentExpiry($id);
            } elseif ($action === 'add_timesheet') {
                $cId = intval($_POST['client_id'] ?? 0);
                $hours = floatval($_POST['hours'] ?? 0);
                $desc = trim($_POST['description'] ?? '');
                $dateLogged = trim($_POST['date_logged'] ?? date('Y-m-d'));
                if ($cId > 0 && $hours > 0 && !empty($desc)) {
                    $res = OfficeSuite::addTimesheet($user['id'], $cId, $hours, $desc, $dateLogged);
                } else {
                    $res = ["error" => "Client, Hours, and Description are required."];
                }
            } elseif ($action === 'delete_timesheet') {
                $id = intval($_POST['id'] ?? 0);
                $res = OfficeSuite::deleteTimesheet($id);
            } elseif ($action === 'bill_timesheet') {
                if ($isAdmin) {
                    $tsId = intval($_POST['id'] ?? 0);
                    $rate = floatval($_POST['hourly_rate'] ?? 500);
                    $db = Database::getConnection();
                    $stmt = $db->prepare("SELECT * FROM timesheets WHERE id = :id LIMIT 1");
                    $stmt->execute(['id' => $tsId]);
                    $ts = $stmt->fetch();
                    if ($ts && $ts['billed_status'] === 'pending') {
                        $amount = $ts['hours'] * $rate;
                        $invoiceNum = 'INV-TS-' . time();
                        $issueDate = date('Y-m-d');
                        $dueDate = date('Y-m-d', strtotime('+7 days'));
                        $invDesc = "Billing for logged timesheet hours: " . $ts['description'] . " (" . $ts['hours'] . " hours @ ₹" . $rate . "/hr)";
                        $invRes = Accounting::createInvoice($ts['client_id'], $invoiceNum, $amount, $issueDate, $dueDate, $invDesc);
                        if (isset($invRes['success'])) {
                            $stmtInv = $db->prepare("SELECT id FROM accounting_invoices WHERE invoice_number = :inv LIMIT 1");
                            $stmtInv->execute(['inv' => $invoiceNum]);
                            $inv = $stmtInv->fetch();
                            if ($inv) {
                                OfficeSuite::billTimesheet($tsId, $inv['id']);
                                $res = ["success" => "Timesheet billed successfully. Invoice generated: " . $invoiceNum];
                            } else {
                                $res = ["error" => "Invoice generated but failed to fetch invoice ID."];
                            }
                        } else {
                            $res = ["error" => "Failed to create invoice."];
                        }
                    } else {
                        $res = ["error" => "Timesheet not found or already billed."];
                    }
                } else {
                    $res = ["error" => "Unauthorized access."];
                }
            } elseif ($action === 'add_tax_computation') {
                $cId = intval($_POST['client_id'] ?? 0);
                $fy = trim($_POST['financial_year'] ?? '');
                $grossSalary = floatval($_POST['gross_salary'] ?? 0);
                $houseProperty = floatval($_POST['house_property'] ?? 0);
                $capGains = floatval($_POST['cap_gains'] ?? 0);
                $businessIncome = floatval($_POST['business_income'] ?? 0);
                $otherSources = floatval($_POST['other_sources'] ?? 0);
                $deductionsOld = floatval($_POST['deductions_old'] ?? 0);
                
                $taxData = OfficeSuite::calculateTaxRegimes($grossSalary, $houseProperty, $capGains, $businessIncome, $otherSources, $deductionsOld);
                
                if ($cId > 0 && !empty($fy)) {
                    $res = OfficeSuite::addTaxComputation(
                        $cId, $fy, $grossSalary, $houseProperty, $capGains, $businessIncome, $otherSources, $deductionsOld,
                        $taxData['tax_old'], $taxData['tax_new'], $taxData['suggested']
                    );
                } else {
                    $res = ["error" => "Client and Financial Year are required."];
                }
            } elseif ($action === 'delete_tax_computation') {
                $id = intval($_POST['id'] ?? 0);
                $res = OfficeSuite::deleteTaxComputation($id);
            } elseif ($action === 'register_tenant') {
            if ($user['role'] === 'super_admin') {
                $res = Tenant::createTenant($_POST['tenant_name'] ?? '', $_POST['plan_name'] ?? 'basic');
            } else {
                $res = ["error" => "Unauthorized. Only Super Admin can register firms."];
            }
        }

        if ($res) {
            if (isset($res['success'])) {
                $_SESSION['flash_success'] = $res['success'];
                Cache::delete('dashboard_stats');
                
                // PRG Pattern: Redirect to self with clean GET params to prevent duplicate submissions on refresh
                $redirectUrl = 'index.php?' . http_build_query($_GET);
                header('Location: ' . $redirectUrl);
                exit;
            }
            if (isset($res['error'])) $error = $res['error'];
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// CSRF token generation
$csrf = Util::getCSRFToken();

// Sidebar active flag helper
function isActive($tab, $activeTab) {
    return $tab === $activeTab ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CA Firm CRM - Admin Portal</title>
    <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="js/lucide.min.js"></script>
    <script src="js/chart.min.js"></script>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar" id="app-sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i data-lucide="shield-check"></i>
                    <span>CA CRM Portal</span>
                </div>
                <button id="sidebar-toggle-btn" style="background:transparent; border:none; color:var(--text-muted); cursor:pointer; padding:0.25rem;" class="no-print">
                    <i data-lucide="menu" style="width:20px; height:20px;"></i>
                </button>
            </div>
            
            <ul class="sidebar-menu">
                <li class="sidebar-item item-dashboard <?= isActive('dashboard', $activeTab) ?>">
                    <a href="index.php?tab=dashboard">
                        <i data-lucide="layout-dashboard"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <?php if (RBAC::hasPermission($user['role'], 'manage_clients')): ?>
                    <li class="sidebar-item item-clients <?= isActive('clients', $activeTab) ?>">
                        <a href="index.php?tab=clients">
                            <i data-lucide="users"></i>
                            <span>Client CRM</span>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php if (RBAC::hasPermission($user['role'], 'manage_staff')): ?>
                    <li class="sidebar-item item-staff <?= isActive('staff', $activeTab) ?>">
                        <a href="index.php?tab=staff">
                            <i data-lucide="user-cog"></i>
                            <span>Employee Mgmt</span>
                        </a>
                    </li>
                <?php endif; ?>
                
                <li class="sidebar-item item-tasks <?= isActive('tasks', $activeTab) ?>">
                    <a href="index.php?tab=tasks">
                        <i data-lucide="clipboard-list"></i>
                        <span>Task Board</span>
                    </a>
                </li>
                
                <li class="sidebar-item item-compliances <?= isActive('compliances', $activeTab) ?>">
                    <a href="index.php?tab=compliances">
                        <i data-lucide="award"></i>
                        <span>Compliance Tracker</span>
                    </a>
                </li>

                <li class="sidebar-item item-hrms <?= isActive('hrms', $activeTab) ?>">
                    <a href="index.php?tab=hrms">
                        <i data-lucide="contact-2"></i>
                        <span>HRMS Portal</span>
                    </a>
                </li>

                <li class="sidebar-item item-chat <?= isActive('chat', $activeTab) ?>">
                    <a href="index.php?tab=chat">
                        <i data-lucide="message-square"></i>
                        <span>Communication</span>
                    </a>
                </li>

                <?php if (RBAC::hasPermission($user['role'], 'manage_accounting')): ?>
                    <li class="sidebar-item item-accounting <?= isActive('accounting', $activeTab) ?>">
                        <a href="index.php?tab=accounting">
                            <i data-lucide="indian-rupee"></i>
                            <span>Accounting</span>
                        </a>
                    </li>
                    <li class="sidebar-item item-services <?= isActive('services', $activeTab) ?>">
                        <a href="index.php?tab=services">
                            <i data-lucide="briefcase"></i>
                            <span>Service Catalog</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (RBAC::hasPermission($user['role'], 'view_reports')): ?>
                    <li class="sidebar-item item-reports <?= isActive('reports', $activeTab) ?>">
                        <a href="index.php?tab=reports">
                            <i data-lucide="bar-chart-3"></i>
                            <span>Reports & Analytics</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (RBAC::hasPermission($user['role'], 'edit_roles')): ?>
                    <li class="sidebar-item item-rbac <?= isActive('rbac', $activeTab) ?>">
                        <a href="index.php?tab=rbac">
                            <i data-lucide="key-round"></i>
                            <span>RBAC Policies</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (RBAC::hasPermission($user['role'], 'view_security_logs')): ?>
                    <li class="sidebar-item item-security <?= isActive('security', $activeTab) ?>">
                        <a href="index.php?tab=security">
                            <i data-lucide="shield-alert"></i>
                            <span>Security Logs</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($isAdmin): ?>
                    <li class="sidebar-item item-templates <?= isActive('templates', $activeTab) ?>">
                        <a href="index.php?tab=templates">
                            <i data-lucide="repeat"></i>
                            <span>Recurring Spawners</span>
                        </a>
                    </li>
                    <li class="sidebar-item item-requests <?= isActive('requests', $activeTab) ?>">
                        <a href="index.php?tab=requests">
                            <i data-lucide="file-symlink"></i>
                            <span>Document Request</span>
                        </a>
                    </li>
                    <li class="sidebar-item item-automation <?= isActive('automation', $activeTab) ?>">
                        <a href="index.php?tab=automation">
                            <i data-lucide="cpu"></i>
                            <span>Automation Hub</span>
                        </a>
                    </li>
                    <?php if ($user['role'] === 'super_admin'): ?>
                        <li class="sidebar-item item-saas <?= isActive('saas', $activeTab) ?>">
                            <a href="index.php?tab=saas">
                                <i data-lucide="building-2"></i>
                                <span>SaaS Portal</span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
                
                <li class="sidebar-item item-logs <?= isActive('logs', $activeTab) ?>">
                    <a href="index.php?tab=logs">
                        <i data-lucide="clock"></i>
                        <span>Work Timesheets</span>
                    </a>
                </li>
                
                <li class="sidebar-item item-requests <?= isActive('dsc', $activeTab) ?>">
                    <a href="index.php?tab=dsc">
                        <i data-lucide="key"></i>
                        <span>DSC & Expiries</span>
                    </a>
                </li>
                
                <li class="sidebar-item item-clients <?= isActive('tax', $activeTab) ?>">
                    <a href="index.php?tab=tax">
                        <i data-lucide="calculator"></i>
                        <span>Tax Computation</span>
                    </a>
                </li>
                
                <li class="sidebar-item item-dashboard <?= isActive('gstr', $activeTab) ?>">
                    <a href="index.php?tab=gstr">
                        <i data-lucide="file-check-2"></i>
                        <span>GSTR Reconciler</span>
                    </a>
                </li>
            </ul>
            
            <div class="sidebar-footer">
                <div style="background-color: var(--bg-card); padding: 0.75rem; border-radius: var(--radius-md); box-shadow: var(--clay-shadow-flat); text-align: center;">
                    <div style="font-size: 0.85rem; font-weight: 700;"><?= htmlspecialchars($user['name']) ?></div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-top: 0.15rem; font-weight: 600;"><?= str_replace('_', ' ', $user['role']) ?></div>
                </div>
                <a href="logout.php" class="btn btn-secondary" style="width:100%;display:flex;gap:.5rem;justify-content:center; align-items:center;">
                    <i data-lucide="log-out" style="width:16px;height:16px;"></i>
                    <span>Log Out</span>
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Top Navigation -->
            <nav class="top-nav glass-header">
                <div class="page-title">
                    <?php
                    switch($activeTab) {
                        case 'clients': echo 'Client CRM Directory'; break;
                        case 'staff': echo 'Employee Management'; break;
                        case 'tasks': echo 'Work Task Board'; break;
                        case 'templates': echo 'Compliance Task Spawners'; break;
                        case 'requests': echo 'Document Center'; break;
                        case 'logs': echo 'Staff Timesheets'; break;
                        case 'accounting': echo 'Financial Ledger & Billing'; break;
                        case 'compliances': echo 'Statutory Compliance Filings'; break;
                        case 'hrms': echo 'HRMS & Attendance Portal'; break;
                        case 'chat': echo 'Internal Communications Chat'; break;
                        case 'reports': echo 'Operations Reports & Analytics'; break;
                        case 'rbac': echo 'Role-Based Access Management'; break;
                        case 'security': echo 'Security Auditing System Logs'; break;
                        case 'dsc': echo 'DSC & Document Expiries'; break;
                        case 'tax': echo 'Tax Computation Sheets'; break;
                        case 'gstr': echo 'GSTR-2B vs Purchases Reconciliation'; break;
                        default: echo 'Operational & Financial Dashboard'; break;
                    }
                    ?>
                </div>
                <div class="user-profile" style="display:flex; align-items:center; gap:0.75rem;">
                    <!-- Inline Search Bar -->
                    <div style="position:relative;" class="no-print">
                        <div style="display:flex; align-items:center; gap:0.5rem; background:rgba(0,0,0,0.15); border:1px solid rgba(255,255,255,0.05); border-radius:15px; padding:0.25rem 0.75rem;">
                            <i data-lucide="search" style="width:14px; height:14px; color:var(--text-muted);"></i>
                            <input type="text" id="inline-search-input" style="background:transparent; border:none; color:var(--text-main); font-size:0.8rem; outline:none; width:150px; transition: width 0.3s;" placeholder="Search... (Ctrl+K)" autocomplete="off">
                        </div>
                        <div id="inline-search-results" style="display:none; position:absolute; right:0; top:40px; width:300px; background:var(--bg-surface); border:1px solid rgba(255,255,255,0.08); border-radius:var(--radius-md); box-shadow:var(--clay-shadow-flat); z-index:9999; padding:0.5rem; max-height:350px; overflow-y:auto;">
                            <div style="padding:0.5rem; text-align:center; color:var(--text-muted); font-size:0.75rem;">Type to search...</div>
                        </div>
                    </div>

                    <!-- Theme Switcher Toggle -->
                    <button type="button" id="theme-toggle-btn" class="btn btn-secondary no-print" style="padding:0.4rem; border-radius:50%; min-width:32px; height:32px; display:flex; align-items:center; justify-content:center;">
                        <i data-lucide="sun" id="theme-sun-icon" style="width:16px; height:16px;"></i>
                        <i data-lucide="moon" id="theme-moon-icon" style="width:16px; height:16px; display:none;"></i>
                    </button>

                    <?php 
                    $unreadNotifs = Communication::getUnreadNotifications($user['id']);
                    $notifCount = count($unreadNotifs);
                    ?>
                    <div style="position:relative; margin-right:0.5rem;" class="no-print">
                        <button type="button" class="btn btn-secondary" style="padding:0.4rem; border-radius:50%; position:relative; min-width:32px; height:32px; display:flex; align-items:center; justify-content:center;" onclick="toggleNotifDropdown()">
                            <i data-lucide="bell" style="width:16px; height:16px;"></i>
                            <?php if ($notifCount > 0): ?>
                                <span style="position:absolute; top:-5px; right:-5px; background-color:var(--danger); color:#fff; border-radius:50%; font-size:0.65rem; padding:0.1rem 0.3rem; font-weight:bold;"><?= $notifCount ?></span>
                            <?php endif; ?>
                        </button>
                        <div id="notif-dropdown" style="display:none; position:absolute; right:0; top:40px; width:280px; background:var(--bg-surface); border:1px solid rgba(255,255,255,0.08); border-radius:var(--radius-md); box-shadow:var(--clay-shadow-flat); z-index:9999; padding:0.5rem; max-height:350px; overflow-y:auto;">
                            <div style="font-size:0.8rem; font-weight:bold; border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:0.35rem; margin-bottom:0.35rem; display:flex; justify-content:space-between; align-items:center;">
                                <span>Recent Notifications</span>
                                <?php if ($notifCount > 0): ?>
                                    <form action="index.php?tab=<?= htmlspecialchars($activeTab) ?>" method="POST" style="margin:0;">
                                        <input type="hidden" name="action" value="mark_notifications_read">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <button type="submit" style="background:none; border:none; color:var(--primary); font-size:0.7rem; cursor:pointer; font-weight:bold;">Clear all</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <?php if ($notifCount === 0): ?>
                                <div style="font-size:0.75rem; color:var(--text-muted); padding:1rem; text-align:center;">No new notifications.</div>
                            <?php else: ?>
                                <?php foreach ($unreadNotifs as $n): ?>
                                    <div style="padding:0.4rem; border-bottom:1px solid rgba(255,255,255,0.02); font-size:0.75rem; text-align:left;">
                                        <div style="font-weight:bold; color:#fff;"><?= htmlspecialchars($n['title']) ?></div>
                                        <div style="color:var(--text-muted); margin-top:0.15rem;"><?= htmlspecialchars($n['message']) ?></div>
                                        <div style="font-size:0.65rem; color:var(--text-muted); margin-top:0.2rem; font-style:italic;"><?= date('h:i A', strtotime($n['created_at'])) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <span style="font-size: 0.9rem; color:var(--text-muted);"><?= date('l, d M Y') ?></span>
                    <div class="avatar"><?= strtoupper(substr($user['name'], 0, 2)) ?></div>
                </div>
                <script>
                    function toggleNotifDropdown() {
                        const dropdown = document.getElementById('notif-dropdown');
                        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
                    }
                    window.addEventListener('click', function(e) {
                        const dropdown = document.getElementById('notif-dropdown');
                        if (dropdown && !dropdown.parentNode.contains(e.target)) {
                            dropdown.style.display = 'none';
                        }
                    });
                </script>
            </nav>

            <?php if ($success): ?>
                <div style="background-color: var(--success-glow); color: var(--success); padding: 0.75rem 1rem; border-radius: var(--radius-md); font-size: 0.9rem; font-weight: 600; box-shadow: var(--clay-shadow-flat); display:flex; gap:0.5rem; align-items:center;">
                    <i data-lucide="check-circle" style="width:18px; height:18px;"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div style="background-color: var(--danger-glow); color: var(--danger); padding: 0.75rem 1rem; border-radius: var(--radius-md); font-size: 0.9rem; font-weight: 600; box-shadow: var(--clay-shadow-flat); display:flex; gap:0.5rem; align-items:center;">
                    <i data-lucide="alert-triangle" style="width:18px; height:18px;"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- ================== DASHBOARD TAB ================== -->
            <?php if ($activeTab === 'dashboard'): 
                if (isset($_GET['refresh'])) {
                    Cache::delete('dashboard_stats');
                    Cache::delete('financial_stats');
                }
                // Caching layer for dashboard performance optimization
                $cached = Cache::get('dashboard_stats');
                if ($cached) {
                    $stats = $cached['stats'];
                    $finStats = $cached['finStats'];
                    $compStats = $cached['compStats'];
                } else {
                    $stats = Task::getAdminStats();
                    $finStats = Accounting::getFinancialStats();
                    $compStats = Compliance::getStats();
                    Cache::set('dashboard_stats', [
                        'stats' => $stats,
                        'finStats' => $finStats,
                        'compStats' => $compStats
                    ], 300);
                }
                
                $db = Database::getConnection();
                
                // Clocked in today
                $stmtClocked = $db->query("SELECT COUNT(DISTINCT user_id) as count FROM attendance WHERE date = CURRENT_DATE() AND status = 'present'");
                $clockedInToday = intval($stmtClocked->fetch()['count'] ?? 0);
                // Total staff
                $stmtStaff = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'staff'");
                $totalStaff = intval($stmtStaff->fetch()['count'] ?? 0);
                
                // Pending document requests
                $stmtDocReq = $db->query("SELECT COUNT(*) as count FROM document_requests WHERE status = 'pending'");
                $pendingDocRequests = intval($stmtDocReq->fetch()['count'] ?? 0);
                
                // Revenue data for last 6 months
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
                
                // Client growth data for last 6 months
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

                $notices = Communication::getAnnouncements();
                $recentTasks = array_slice(Task::getTasks(['status' => 'pending']), 0, 5);
                $pendingCompliances = array_slice(Compliance::getCompliances(['status' => 'pending']), 0, 8);
                $attendanceList = HRMS::getAttendanceList(date('Y-m-d'));
                $recentActivities = Security::getActivityLogs(8);
                
                // Calendar Events loading
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
                $allLeaves = HRMS::getLeaveRequests(['status' => 'approved']);
                foreach ($allLeaves as $l) {
                    $start = strtotime($l['start_date']);
                    $end = strtotime($l['end_date']);
                    for ($date = $start; $date <= $end; $date = strtotime("+1 day", $date)) {
                        $calendarEvents[] = [
                            'type' => 'leave',
                            'title' => $l['staff_name'] . ' (' . $l['leave_type'] . ')',
                            'date' => date('Y-m-d', $date)
                        ];
                    }
                }

                // Live Notifications
                $notifications = [];
                foreach ($allTasks as $t) {
                    if ($t['status'] !== 'completed' && !empty($t['due_date']) && strtotime($t['due_date']) < time()) {
                        $notifications[] = [
                            'type' => 'overdue',
                            'title' => 'Task Overdue',
                            'desc' => $t['title'] . ' (Assigned to: ' . ($t['staff_name'] ?: 'Unassigned') . ')',
                            'time' => 'Due ' . $t['due_date']
                        ];
                    }
                }
                foreach ($allCompliances as $c) {
                    if ($c['status'] === 'pending' && !empty($c['due_date'])) {
                        $diff = strtotime($c['due_date']) - time();
                        if ($diff < 0) {
                            $notifications[] = [
                                'type' => 'overdue',
                                'title' => 'Compliance Overdue',
                                'desc' => $c['title'] . ' for client ' . $c['client_name'],
                                'time' => 'Due ' . $c['due_date']
                            ];
                        } elseif ($diff <= (3 * 86400)) {
                            $notifications[] = [
                                'type' => 'warning',
                                'title' => 'Urgent Compliance Return',
                                'desc' => $c['title'] . ' for client ' . $c['client_name'],
                                'time' => 'Due in ' . ceil($diff / 86400) . ' day(s)'
                            ];
                        }
                    }
                }
                if ($isAdmin) {
                    $pendingLeaves = HRMS::getLeaveRequests(['status' => 'pending']);
                    foreach ($pendingLeaves as $pl) {
                        $notifications[] = [
                            'type' => 'info',
                            'title' => 'Pending Leave Request',
                            'desc' => $pl['staff_name'] . ' - ' . $pl['leave_type'],
                            'time' => $pl['start_date'] . ' to ' . $pl['end_date']
                        ];
                    }
                }
                
                // Expiring DSCs and Documents check (within 30 days)
                $expiringDSCs = [];
                $expiringDocs = [];
                try {
                    $stmtDSCExp = $db->query("SELECT d.*, c.name as client_name FROM dsc_tokens d JOIN clients c ON d.client_id = c.id WHERE d.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY d.expiry_date ASC");
                    $expiringDSCs = $stmtDSCExp->fetchAll();
                    
                    $stmtDocExp = $db->query("SELECT de.*, c.name as client_name FROM document_expiries de JOIN clients c ON de.client_id = c.id WHERE de.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY de.expiry_date ASC");
                    $expiringDocs = $stmtDocExp->fetchAll();
                } catch (Exception $e) {}
            ?>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; width:100%;" class="no-print">
                    <h3 style="font-size:1.15rem; font-weight:700; color:var(--text-main); margin:0; display:flex; align-items:center; gap:0.5rem;">
                        <i data-lucide="activity" style="color:var(--primary); width:18px;"></i> Overview Statistics
                    </h3>
                    <a href="index.php?tab=dashboard&refresh=1" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.4rem 0.75rem; font-size:0.8rem;">
                        <i data-lucide="refresh-cw" style="width:14px; height:14px;"></i> Sync & Refresh Stats
                    </a>
                </div>

                <!-- Expiry Alerts Center -->
                <?php if (!empty($expiringDSCs) || !empty($expiringDocs)): ?>
                    <div class="card glass-card" style="border-left: 5px solid #f59e0b; margin-bottom: 1.5rem; background: rgba(245, 158, 11, 0.05);">
                        <h4 style="font-weight: 700; color: #f59e0b; display:flex; align-items:center; gap:0.5rem; margin: 0 0 0.75rem 0;">
                            <i data-lucide="alert-triangle" style="width:20px;height:20px;"></i> CA Expiry Alert Center (30 Days Warning)
                        </h4>
                        <div style="font-size:0.85rem; display:flex; flex-direction:column; gap:0.4rem; line-height: 1.4;">
                            <?php foreach ($expiringDSCs as $d): 
                                $days = round((strtotime($d['expiry_date']) - time()) / 86400);
                                $dayStr = ($days < 0) ? "Expired" : "expires in $days days";
                                $color = ($days < 0) ? "var(--danger)" : "#f59e0b";
                            ?>
                                <div>
                                    <span style="color: <?= $color ?>; font-weight:700;"><i data-lucide="key" style="width:14px;height:14px;vertical-align:middle;margin-right:0.25rem;"></i> DSC Token:</span>
                                    Director <strong><?= htmlspecialchars($d['director_name']) ?></strong> (<?= htmlspecialchars($d['client_name']) ?>) <?= $dayStr ?> on <strong><?= date('d M Y', strtotime($d['expiry_date'])) ?></strong>.
                                </div>
                            <?php endforeach; ?>
                            <?php foreach ($expiringDocs as $doc): 
                                $days = round((strtotime($doc['expiry_date']) - time()) / 86400);
                                $dayStr = ($days < 0) ? "Expired" : "expires in $days days";
                                $color = ($days < 0) ? "var(--danger)" : "#f59e0b";
                            ?>
                                <div>
                                    <span style="color: <?= $color ?>; font-weight:700;"><i data-lucide="file-warning" style="width:14px;height:14px;vertical-align:middle;margin-right:0.25rem;"></i> Document Expiry:</span>
                                    License <strong><?= htmlspecialchars($doc['doc_type']) ?></strong> (<?= htmlspecialchars($doc['client_name']) ?>) <?= $dayStr ?> on <strong><?= date('d M Y', strtotime($doc['expiry_date'])) ?></strong>.
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Advanced KPI Analytics Grid -->
                <div class="grid-stats">
                    <div class="stat-card gradient-emerald-teal">
                        <div class="stat-info">
                            <span class="stat-label">Total Clients</span>
                            <span class="stat-value"><?= $stats['clients'] ?></span>
                            <span style="font-size:0.75rem; color:#fff; opacity:0.9; font-weight:600; margin-top:0.25rem;">+<?= count(array_filter($clientCounts)) ?> this month</span>
                        </div>
                        <div class="stat-icon" style="color: #fff;"><i data-lucide="users"></i></div>
                    </div>
                    <div class="stat-card gradient-amber-orange">
                        <div class="stat-info">
                            <span class="stat-label">Active Tasks</span>
                            <span class="stat-value"><?= $stats['tasks'] ?></span>
                            <span style="font-size:0.75rem; color:#fff; opacity:0.9; font-weight:600; margin-top:0.25rem;">Pending execution</span>
                        </div>
                        <div class="stat-icon" style="color: #fff;"><i data-lucide="check-square"></i></div>
                    </div>
                    <div class="stat-card gradient-cyan-sky">
                        <div class="stat-info">
                            <span class="stat-label">Collected Revenue</span>
                            <span class="stat-value">₹<?= number_format($finStats['total_collected'], 0) ?></span>
                            <span style="font-size:0.75rem; color:#fff; opacity:0.9; font-weight:600; margin-top:0.25rem;">Outstanding: ₹<?= number_format($finStats['outstanding'], 0) ?></span>
                        </div>
                        <div class="stat-icon" style="color: #fff;"><i data-lucide="indian-rupee"></i></div>
                    </div>
                    <div class="stat-card gradient-purple-pink">
                        <div class="stat-info">
                            <span class="stat-label">Filing Ratio</span>
                            <span class="stat-value"><?= $compStats['filed'] ?> / <?= $compStats['total'] ?></span>
                            <?php 
                            $ratio = $compStats['total'] > 0 ? round(($compStats['filed'] / $compStats['total']) * 100) : 100;
                            ?>
                            <span style="font-size:0.75rem; color:#fff; opacity:0.9; font-weight:600; margin-top:0.25rem;"><?= $ratio ?>% Filed successfully</span>
                        </div>
                        <div class="stat-icon" style="color: #fff;"><i data-lucide="award"></i></div>
                    </div>
                    <!-- Additional Advanced KPIs -->
                    <div class="stat-card gradient-blue-indigo">
                        <div class="stat-info">
                            <span class="stat-label">Document Requests</span>
                            <span class="stat-value"><?= $pendingDocRequests ?></span>
                            <span style="font-size:0.75rem; color:#fff; opacity:0.9; font-weight:600; margin-top:0.25rem;">Awaiting client upload</span>
                        </div>
                        <div class="stat-icon" style="color: #fff;"><i data-lucide="file-text"></i></div>
                    </div>
                    <div class="stat-card gradient-red-rose">
                        <div class="stat-info">
                            <span class="stat-label">Staff Present</span>
                            <span class="stat-value"><?= $clockedInToday ?> / <?= $totalStaff ?></span>
                            <span style="font-size:0.75rem; color:#fff; opacity:0.9; font-weight:600; margin-top:0.25rem;">On-duty today</span>
                        </div>
                        <div class="stat-icon" style="color: #fff;"><i data-lucide="user-check"></i></div>
                    </div>
                </div>

                <!-- AI Diagnostic Insights board -->
                <?php 
                $aiInsights = AIService::generateDashboardInsights($finStats);
                if (!empty($aiInsights)):
                ?>
                    <div style="display:flex; flex-direction:column; gap:0.5rem; margin-top:1.5rem;" class="no-print">
                        <div class="card glass-card" style="padding:1rem; border-left: 5px solid var(--primary);">
                            <div style="display:flex; align-items:center; gap:0.5rem; font-weight:800; color:var(--primary); font-size:0.90rem; margin-bottom:0.5rem;">
                                <i data-lucide="sparkles" style="width:16px; height:16px;"></i> CA FIRM AI REALTIME DIAGNOSTIC INSIGHTS
                            </div>
                            <div style="display:flex; flex-direction:column; gap:0.5rem;">
                                <?php foreach ($aiInsights as $insight): ?>
                                    <div style="font-size:0.8rem; line-height:1.4;">
                                        <strong><?= htmlspecialchars($insight['title']) ?>:</strong> <?= htmlspecialchars($insight['desc']) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Quick Actions & Operations Panel -->
                <div class="card glass-card" style="margin-top: 1.5rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 0.75rem;">
                        <h3 class="card-title" style="margin:0;"><i data-lucide="zap" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:var(--primary);"></i>Quick Operations Panel</h3>
                        
                        <!-- Small Clock In/Out Button Container -->
                        <div style="background: rgba(255,255,255,0.03); border-radius: var(--radius-sm); padding: 0.35rem 0.75rem; display: flex; flex-direction: column; gap: 0.25rem; border: 1px solid rgba(255,255,255,0.05); box-shadow: var(--clay-shadow-flat); align-items: flex-start;">
                            <?php 
                            $att = HRMS::getTodayAttendance($user['id']); 
                            $db = Database::getConnection();
                            $stmtTodayShift = $db->prepare("SELECT shift_timing FROM shift_assignments WHERE user_id = :uid AND date = :dt LIMIT 1");
                            $stmtTodayShift->execute(['uid' => $user['id'], 'dt' => date('Y-m-d')]);
                            $todayShiftRow = $stmtTodayShift->fetch();
                            $empShift = $todayShiftRow ? $todayShiftRow['shift_timing'] : 'General Shift (09:00 AM - 06:00 PM)';
                            ?>
                            <div style="font-size:0.7rem; color:var(--primary); font-weight:700;">Shift: <?= htmlspecialchars($empShift) ?></div>
                            <div style="display:flex; align-items:center; gap:0.5rem;">
                                <span style="font-size:0.75rem; color:var(--text-muted); font-weight:700; text-transform:uppercase;">Attendance:</span>
                                <?php if ($att && $att['check_in'] && !$att['check_out']): ?>
                                    <form action="index.php?tab=dashboard" method="POST" style="margin:0;">
                                        <input type="hidden" name="action" value="clock_out">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <button type="submit" class="btn btn-danger" style="font-size:0.75rem; padding:0.25rem 0.5rem;">Clock Out</button>
                                    </form>
                                <?php elseif ($att && $att['check_out']): ?>
                                    <span class="badge badge-progress" style="font-size:0.7rem; padding:0.2rem 0.4rem;">Done</span>
                                <?php else: ?>
                                    <form action="index.php?tab=dashboard" method="POST" style="margin:0;">
                                        <input type="hidden" name="action" value="clock_in">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <button type="submit" class="btn btn-primary" style="font-size:0.75rem; padding:0.25rem 0.5rem;">Clock In</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    
                    <!-- 2x2 Grid for Action Menu items -->
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-top: 1rem;">
                        <div class="action-btn-card" onclick="window.location.href='index.php?tab=clients'" style="flex-direction:row; justify-content:flex-start; gap:1rem; padding:0.75rem 1.25rem; margin:0;">
                            <i data-lucide="user-plus" style="color: var(--primary); width:20px; height:20px;"></i>
                            <div style="text-align:left;">
                                <div style="font-size:0.85rem; font-weight:700;">Register Client</div>
                                <span style="font-size:0.7rem; color:var(--text-muted);">Add new business entity</span>
                            </div>
                        </div>
                        <div class="action-btn-card" onclick="window.location.href='index.php?tab=compliances'" style="flex-direction:row; justify-content:flex-start; gap:1rem; padding:0.75rem 1.25rem; margin:0;">
                            <i data-lucide="award" style="color:#a855f7; width:20px; height:20px;"></i>
                            <div style="text-align:left;">
                                <div style="font-size:0.85rem; font-weight:700;">Schedule Return</div>
                                <span style="font-size:0.7rem; color:var(--text-muted);">Create statutory compliance</span>
                            </div>
                        </div>
                        <div class="action-btn-card" onclick="window.location.href='index.php?tab=accounting'" style="flex-direction:row; justify-content:flex-start; gap:1rem; padding:0.75rem 1.25rem; margin:0;">
                            <i data-lucide="file-plus" style="color:var(--success); width:20px; height:20px;"></i>
                            <div style="text-align:left;">
                                <div style="font-size:0.85rem; font-weight:700;">Log Invoice</div>
                                <span style="font-size:0.7rem; color:var(--text-muted);">Bill a client account</span>
                            </div>
                        </div>
                        <div class="action-btn-card" onclick="window.location.href='index.php?tab=tasks'" style="flex-direction:row; justify-content:flex-start; gap:1rem; padding:0.75rem 1.25rem; margin:0;">
                            <i data-lucide="plus-circle" style="color:var(--warning); width:20px; height:20px;"></i>
                            <div style="text-align:left;">
                                <div style="font-size:0.85rem; font-weight:700;">Assign Task</div>
                                <span style="font-size:0.7rem; color:var(--text-muted);">Create staff task item</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advanced Analytics Charts Row (Parallel) -->
                <div class="dashboard-charts-grid">
                    <div class="card glass-card">
                        <h3 class="card-title"><i data-lucide="trending-up" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:var(--success);"></i>Revenue Summary (Last 6 Months)</h3>
                        <div style="height: 250px; margin-top: 1rem; position: relative;">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                    <div class="card glass-card">
                        <h3 class="card-title"><i data-lucide="user-plus" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:#a855f7;"></i>Client Growth Metric</h3>
                        <div style="height: 250px; margin-top: 1rem; position: relative;">
                            <canvas id="clientGrowthChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Live Notifications & Notice Board (Side by Side) -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
                    <!-- Live Notifications Feed -->
                    <div class="card glass-card">
                        <h3 class="card-title">
                            <span><i data-lucide="bell" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:var(--danger);"></i>Live Notifications</span>
                            <?php if (!empty($notifications)): ?>
                                <span class="badge badge-progress" style="font-size:0.75rem; padding:0.15rem 0.5rem; border-radius:10px; background-color:var(--danger);"><?= count($notifications) ?></span>
                            <?php endif; ?>
                        </h3>
                        <div style="margin-top: 1rem; display:flex; flex-direction:column; gap:0.75rem; max-height: 300px; overflow-y:auto; padding-right:0.25rem;">
                            <?php if (empty($notifications)): ?>
                                <p style="color:var(--text-muted); font-size:0.85rem; text-align:center;">All systems normal. No immediate alerts.</p>
                            <?php else: ?>
                                <?php foreach ($notifications as $notif): 
                                    $cardClass = 'notif-info';
                                    $notifIcon = 'info';
                                    if ($notif['type'] === 'overdue') {
                                        $cardClass = 'notif-overdue';
                                        $notifIcon = 'alert-triangle';
                                    } elseif ($notif['type'] === 'warning') {
                                        $cardClass = 'notif-warning';
                                        $notifIcon = 'clock';
                                    }
                                ?>
                                    <div class="notification-card <?= $cardClass ?>">
                                        <div class="notification-icon"><i data-lucide="<?= $notifIcon ?>" style="width:16px;height:16px;"></i></div>
                                        <div class="notification-info">
                                            <div class="notification-title"><?= htmlspecialchars($notif['title']) ?></div>
                                            <div class="notification-desc"><?= htmlspecialchars($notif['desc']) ?></div>
                                            <span class="notification-time"><?= htmlspecialchars($notif['time']) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Notices Board -->
                    <div class="card glass-card">
                        <h2 class="card-title"><i data-lucide="megaphone" style="vertical-align:middle; margin-right:0.5rem; width:20px; color:var(--warning);"></i>Notices Board</h2>
                        <div style="margin-top: 1rem; display:flex; flex-direction:column; gap:1rem; max-height: 300px; overflow-y:auto;">
                            <?php if (empty($notices)): ?>
                                <p style="color:var(--text-muted); font-size:0.9rem; text-align:center;">No announcements today.</p>
                            <?php else: ?>
                                <?php foreach ($notices as $note): ?>
                                    <div style="background-color: var(--bg-input); padding: 1rem; border-radius: var(--radius-md); box-shadow: var(--clay-shadow-flat);">
                                        <div style="display:flex; justify-content:space-between; align-items:center;">
                                            <span style="font-weight:700; color:var(--primary); font-size:0.95rem;"><?= htmlspecialchars($note['title']) ?></span>
                                            <span style="font-size:0.75rem; color:var(--text-muted);"><?= date('d M, h:i A', strtotime($note['created_at'])) ?></span>
                                        </div>
                                        <p style="font-size: 0.85rem; margin-top:0.5rem; line-height:1.4;"><?= nl2br(htmlspecialchars($note['content'])) ?></p>
                                        <div style="font-size:0.75rem; color:var(--text-muted); margin-top:0.5rem; text-align:right;">
                                            - By <?= htmlspecialchars($note['author_name']) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Center Workspace Layout Grid (Calendar, Attendance, Filings, Timeline) -->
                <div class="dashboard-layout-grid" style="margin-top: 1.5rem;">
                    <!-- Column 1 -->
                    <div style="display:flex; flex-direction:column; gap:1.5rem;">
                        <!-- Interactive Calendar -->
                        <div class="card glass-card">
                            <h3 class="card-title"><i data-lucide="calendar" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:var(--primary);"></i>Operational Schedule Calendar</h3>
                            <div id="calendar-widget-container" class="calendar-widget" style="margin-top:1rem;"></div>
                            <div style="display:flex; gap:1.25rem; font-size:0.75rem; justify-content:center; margin-top:0.75rem; flex-wrap:wrap;">
                                <div style="display:flex; align-items:center; gap:0.35rem;"><span class="calendar-marker marker-task"></span> Tasks</div>
                                <div style="display:flex; align-items:center; gap:0.35rem;"><span class="calendar-marker marker-compliance"></span> Statutory filings</div>
                                <div style="display:flex; align-items:center; gap:0.35rem;"><span class="calendar-marker marker-leave"></span> Employee Leaves</div>
                            </div>
                        </div>

                        <!-- Employee Attendance Tracker -->
                        <div class="card glass-card">
                            <h3 class="card-title"><i data-lucide="contact-2" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:#3b82f6;"></i>Staff Attendance Status</h3>
                            <div class="table-container" style="margin-top:1rem; max-height:300px; overflow-y:auto;">
                                <table class="data-table" style="font-size:0.85rem;">
                                    <thead>
                                        <tr>
                                            <th>Staff Member</th>
                                            <th>Dept & Role</th>
                                            <th>Status</th>
                                            <th>Today's Timing</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($attendanceList)): ?>
                                            <tr><td colspan="4" style="text-align:center; color:var(--text-muted);">No staff registered or logged today.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($attendanceList as $attRow): 
                                                $badgeClass = 'badge-muted';
                                                $statusText = 'Absent';
                                                if ($attRow['check_in']) {
                                                    $badgeClass = $attRow['check_out'] ? 'badge-progress' : 'badge-completed';
                                                    $statusText = $attRow['check_out'] ? 'Clocked Out' : 'Active';
                                                }
                                            ?>
                                                <tr>
                                                    <td style="font-weight:700;"><?= htmlspecialchars($attRow['staff_name']) ?></td>
                                                    <td><?= htmlspecialchars($attRow['department'] ?: 'Unassigned') ?> (<?= htmlspecialchars($attRow['designation'] ?: 'Staff') ?>)</td>
                                                    <td><span class="badge <?= $badgeClass ?>"><?= $statusText ?></span></td>
                                                    <td style="color:var(--text-muted); font-size:0.8rem;">
                                                        <?= $attRow['check_in'] ? htmlspecialchars($attRow['check_in']) : '--' ?> - 
                                                        <?= $attRow['check_out'] ? htmlspecialchars($attRow['check_out']) : '--' ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Column 2 -->
                    <div style="display:flex; flex-direction:column; gap:1.5rem;">
                        <!-- Daily Shift Schedule (Datewise) -->
                        <div class="card glass-card">
                            <h3 class="card-title"><i data-lucide="calendar-clock" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:var(--primary);"></i>My Shift Schedule</h3>
                            <div class="table-container" style="margin-top: 1rem; max-height: 250px; overflow-y: auto;">
                                <table class="data-table" style="font-size: 0.85rem;">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Assigned Shift</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $myShifts = HRMS::getShiftAssignments($user['id']);
                                        if (empty($myShifts)): 
                                        ?>
                                            <tr><td colspan="2" style="text-align:center; color:var(--text-muted); padding: 1rem 0;">No shift schedule assigned.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($myShifts as $ms): 
                                                $isToday = ($ms['date'] === date('Y-m-d'));
                                            ?>
                                                <tr style="<?= $isToday ? 'background:rgba(99,102,241,0.1); font-weight:700;' : '' ?>">
                                                    <td><?= htmlspecialchars($ms['date']) ?><?= $isToday ? ' <span class="badge badge-completed" style="font-size:0.65rem; padding:0 3px;">Today</span>' : '' ?></td>
                                                    <td><?= htmlspecialchars($ms['shift_timing']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Statutory Return Compliance Tracker -->
                        <div class="card glass-card">
                            <h3 class="card-title"><i data-lucide="award" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:#a855f7;"></i>Filing Compliances Due</h3>
                            <div class="table-container" style="margin-top: 1rem; max-height: 300px; overflow-y: auto;">
                                <table class="data-table" style="font-size: 0.85rem;">
                                    <thead>
                                        <tr>
                                            <th>Filing Detail</th>
                                            <th>Client Entity</th>
                                            <th>Due Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($pendingCompliances)): ?>
                                            <tr><td colspan="3" style="text-align:center; color:var(--text-muted); padding: 1rem 0;">No pending filings.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($pendingCompliances as $comp): 
                                                $dueTime = strtotime($comp['due_date']);
                                                $overdue = ($dueTime < time());
                                            ?>
                                                <tr>
                                                    <td style="font-weight: 700;"><?= htmlspecialchars($comp['title']) ?></td>
                                                    <td><?= htmlspecialchars($comp['client_name']) ?></td>
                                                    <td style="<?= $overdue ? 'color: var(--danger); font-weight:700;' : 'color: var(--text-muted);' ?>">
                                                        <?= htmlspecialchars($comp['due_date']) ?>
                                                        <?= $overdue ? ' (Overdue)' : '' ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Recent Activities Timeline -->
                        <div class="card glass-card">
                            <details style="width: 100%;">
                                <summary style="cursor: pointer; list-style: none; outline: none; display: flex; justify-content: space-between; align-items: center;">
                                    <h3 class="card-title" style="margin:0; display:inline-flex; align-items:center;"><i data-lucide="activity" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:var(--primary);"></i>Activity Log Timeline</h3>
                                    <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700;">Click to Expand</span>
                                </summary>
                                <div class="timeline-container" style="margin-top: 1rem;">
                                    <?php if (empty($recentActivities)): ?>
                                        <p style="color:var(--text-muted); font-size:0.85rem; padding-left:0.5rem;">No activities logged yet.</p>
                                        <?php else: ?>
                                        <?php foreach ($recentActivities as $act): 
                                            $dotClass = 'created';
                                            if (strpos($act['action'], 'delete') !== false) {
                                                $dotClass = 'deleted';
                                            } elseif (strpos($act['action'], 'edit') !== false || strpos($act['action'], 'update') !== false) {
                                                $dotClass = 'updated';
                                            } elseif (strpos($act['action'], 'clock_out') !== false || strpos($act['action'], 'file') !== false || strpos($act['action'], 'complete') !== false) {
                                                $dotClass = 'completed';
                                            }
                                        ?>
                                            <div class="timeline-item">
                                                <div class="timeline-dot <?= $dotClass ?>"></div>
                                                <div class="timeline-content">
                                                    <div class="timeline-header">
                                                        <span style="font-weight:700; color:var(--text-main);"><?= htmlspecialchars($act['user_name'] ?: 'System') ?></span>
                                                        <span><?= date('d M, h:i A', strtotime($act['created_at'])) ?></span>
                                                    </div>
                                                    <div class="timeline-body">
                                                        <span style="color:var(--primary); font-weight:700;"><?= strtoupper(str_replace('_', ' ', $act['action'])) ?></span>: 
                                                        <?= htmlspecialchars($act['details']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>

                <script>
                    window.DashboardData = {
                        revenue: {
                            labels: <?= json_encode($revenueLabels) ?>,
                            collected: <?= json_encode($collectedData) ?>,
                            invoiced: <?= json_encode($invoicedData) ?>
                        },
                        clients: {
                            labels: <?= json_encode($clientLabels) ?>,
                            counts: <?= json_encode($clientCounts) ?>
                        },
                        calendarEvents: <?= json_encode($calendarEvents) ?>
                    };
                </script>
            

            <!-- ================== CLIENT CRM TAB ================== -->
            <!-- ================== CLIENT CRM TAB ================== -->
            <?php elseif ($activeTab === 'clients'): 
                $clientId = intval($_GET['client_id'] ?? 0);
                $subTab = trim($_GET['sub'] ?? 'directory');
                $currentFolder = trim($_GET['folder'] ?? '/');
                $docSearch = trim($_GET['doc_search'] ?? '');
                
                if ($clientId > 0):
                    $cObj = Client::getClient($clientId);
                    if ($cObj):
                        $cTasks = Task::getTasks(['client_id' => $clientId]);
                        $cCompliances = Compliance::getCompliances(['client_id' => $clientId]);
                        $cInvoices = Accounting::getInvoices(['client_id' => $clientId]);
                        $cDocs = Document::getDocuments($clientId, $currentFolder, $docSearch);
                        $cTimeline = Client::getClientTimeline($clientId);
                        $cRequests = Document::getDocumentRequests(['client_id' => $clientId]);
                        
                        // New CRM features data
                        $cNotes = CRM::getClientNotes($clientId);
                        $cMeetings = CRM::getMeetings($clientId);
                        $cWhatsApp = CRM::getWhatsAppLogs($clientId);
                        $cEmails = CRM::getEmailLogs($clientId);
            ?>
                        <!-- Client Profile Detail Subview -->
                        <div style="margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                            <a href="index.php?tab=clients&sub=<?= urlencode($subTab) ?>" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:0.5rem;">
                                <i data-lucide="arrow-left" style="width:16px;"></i> Back to Directory
                            </a>
                            <span class="badge badge-completed">Portal Token: <?= htmlspecialchars($cObj['portal_token'] ?: 'Not Generated') ?></span>
                        </div>

                        <div class="client-details-grid">
                            <!-- Left Details Panel -->
                            <div class="card glass-card" style="height: fit-content;">
                                <div style="text-align:center; padding-bottom:1.5rem; border-bottom:1px solid var(--bg-card); margin-bottom:1.5rem;">
                                    <div class="avatar" style="width:4.5rem; height:4.5rem; font-size:1.75rem; margin:0 auto 1rem auto;"><?= strtoupper(substr($cObj['name'], 0, 2)) ?></div>
                                    <h2 style="font-size:1.35rem; font-weight:800;"><?= htmlspecialchars($cObj['name']) ?></h2>
                                    <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.25rem;">Client ID: #<?= $cObj['id'] ?></p>
                                </div>

                                <div style="display:flex; flex-direction:column; gap:1.25rem; font-size:0.9rem;">
                                    <div>
                                        <div style="color:var(--text-muted); font-size:0.8rem; text-transform:uppercase; font-weight:600;">Email Address</div>
                                        <div style="font-weight:600; margin-top:0.15rem;"><?= htmlspecialchars($cObj['email']) ?></div>
                                    </div>
                                    <div>
                                        <div style="color:var(--text-muted); font-size:0.8rem; text-transform:uppercase; font-weight:600;">Phone Number</div>
                                        <div style="font-weight:600; margin-top:0.15rem;"><?= htmlspecialchars($cObj['phone'] ?: 'N/A') ?></div>
                                    </div>
                                    <div>
                                        <div style="color:var(--text-muted); font-size:0.8rem; text-transform:uppercase; font-weight:600;">Entity Type</div>
                                        <div style="font-weight:600; margin-top:0.15rem;"><?= htmlspecialchars($cObj['client_type'] ?: 'N/A') ?></div>
                                    </div>
                                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.5rem;">
                                        <div>
                                            <div style="color:var(--text-muted); font-size:0.8rem; text-transform:uppercase; font-weight:600;">PAN Card</div>
                                            <div style="font-weight:700; color:var(--primary); margin-top:0.15rem;"><?= htmlspecialchars($cObj['pan'] ?: 'N/A') ?></div>
                                        </div>
                                        <div>
                                            <div style="color:var(--text-muted); font-size:0.8rem; text-transform:uppercase; font-weight:600;">GSTIN</div>
                                            <div style="font-weight:700; color:var(--success); margin-top:0.15rem;"><?= htmlspecialchars($cObj['gstin'] ?: 'N/A') ?></div>
                                        </div>
                                    </div>
                                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.5rem;">
                                        <div>
                                            <div style="color:var(--text-muted); font-size:0.8rem; text-transform:uppercase; font-weight:600;">TAN Number</div>
                                            <div style="font-weight:600; margin-top:0.15rem;"><?= htmlspecialchars($cObj['tan'] ?: 'N/A') ?></div>
                                        </div>
                                        <div>
                                            <div style="color:var(--text-muted); font-size:0.8rem; text-transform:uppercase; font-weight:600;">Incorp. / DOB</div>
                                            <div style="font-weight:600; margin-top:0.15rem;"><?= $cObj['incorporation_date'] ? date('d M Y', strtotime($cObj['incorporation_date'])) : 'N/A' ?></div>
                                        </div>
                                    </div>
                                    <div>
                                        <div style="color:var(--text-muted); font-size:0.8rem; text-transform:uppercase; font-weight:600;">Registered Address</div>
                                        <div style="font-size:0.85rem; color:var(--text-main); margin-top:0.15rem; line-height:1.4;"><?= nl2br(htmlspecialchars($cObj['address'] ?? 'N/A')) ?></div>
                                    </div>
                                    <div>
                                        <div style="color:var(--text-muted); font-size:0.8rem; text-transform:uppercase; font-weight:600;">Secure Portal Link</div>
                                        <?php if ($cObj['portal_token']): ?>
                                            <div style="display:flex; flex-direction:column; gap:0.5rem; margin-top:0.35rem;">
                                                <input type="text" readonly class="form-control" style="font-size: 0.75rem; padding: 0.35rem 0.5rem;" value="<?= htmlspecialchars($cObj['portal_token']) ?>">
                                                <button class="btn btn-secondary" style="padding:0.45rem; font-size:0.75rem; width:100%;" onclick="navigator.clipboard.writeText('<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/portal.php?token=' . $cObj['portal_token'] ?>'); alert('Portal URL copied!');">
                                                    Copy Secure Link
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span style="color:var(--text-muted); font-style:italic;">No active portal token</span>
                                        <?php endif; ?>
                                        <form action="index.php?tab=clients&client_id=<?= $cObj['id'] ?>&sub=<?= urlencode($subTab) ?>" method="POST" style="margin-top:0.5rem;">
                                            <input type="hidden" name="action" value="generate_token">
                                            <input type="hidden" name="id" value="<?= $cObj['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                            <button type="submit" class="btn btn-primary" style="width:100%; font-size:0.75rem; padding:0.45rem;">
                                                Generate / Rotate Key
                                            </button>
                                        </form>
                                    </div>
                                    <?php if ($isAdmin): 
                                        $decryptedTaxData = Security::decryptData($cObj['encrypted_tax_data']);
                                    ?>
                                        <div style="border-top: 1px solid rgba(255,255,255,0.06); padding-top: 1rem; margin-top: 0.5rem;">
                                            <div style="color:var(--text-muted); font-size:0.8rem; text-transform:uppercase; font-weight:600;"><i data-lucide="shield-check" style="width:12px; height:12px; vertical-align:middle; margin-right:4px; color:var(--success);"></i>Secure Tax PAN/GSTIN (AES Encrypted)</div>
                                            <form action="index.php?tab=clients&client_id=<?= $cObj['id'] ?>&sub=<?= urlencode($subTab) ?>" method="POST" style="margin-top:0.35rem; display:flex; flex-direction:column; gap:0.35rem;">
                                                <input type="hidden" name="action" value="save_encrypted_tax_data">
                                                <input type="hidden" name="client_id" value="<?= $cObj['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                <textarea name="tax_data" class="form-control" style="font-size:0.75rem; font-family:monospace; padding:0.35rem;" rows="3" placeholder="PAN: ABCDE1234F&#10;GSTIN: 27AAAAA0000A1Z5"><?= htmlspecialchars($decryptedTaxData ?? '') ?></textarea>
                                                <button type="submit" class="btn btn-secondary" style="font-size:0.7rem; padding:0.35rem;">Encrypt & Save</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Right Info Tabs Panel -->
                            <div style="display:flex; flex-direction:column; gap:1.5rem;">
                                <!-- Navigation tabs inside client detail profile -->
                                <div class="crm-tabs-header">
                                    <button class="crm-tab-btn active" data-tab="tab-overview" onclick="switchCrmTab('tab-overview')">Overview & Tasks</button>
                                    <button class="crm-tab-btn" data-tab="tab-compliances" onclick="switchCrmTab('tab-compliances')">Tax & Compliance</button>
                                    <button class="crm-tab-btn" data-tab="tab-billing" onclick="switchCrmTab('tab-billing')">Billing History</button>
                                    <button class="crm-tab-btn" data-tab="tab-vault" onclick="switchCrmTab('tab-vault')">Document Vault</button>
                                    <button class="crm-tab-btn" data-tab="tab-notes" onclick="switchCrmTab('tab-notes')">Client Notes</button>
                                    <button class="crm-tab-btn" data-tab="tab-meetings" onclick="switchCrmTab('tab-meetings')">Meetings</button>
                                    <button class="crm-tab-btn" data-tab="tab-whatsapp" onclick="switchCrmTab('tab-whatsapp')">WhatsApp Alerts</button>
                                    <button class="crm-tab-btn" data-tab="tab-email" onclick="switchCrmTab('tab-email')">Email Logs</button>
                                </div>

                                <!-- Tab Content: Overview & Tasks -->
                                <div id="tab-overview" class="crm-tab-content active" style="display:flex; flex-direction:column; gap:1.5rem;">
                                    <div class="card glass-card">
                                        <h3 class="card-title"><i data-lucide="clipboard-check" style="vertical-align:middle; margin-right:0.5rem; width:18px;"></i>Active Task Checklist</h3>
                                        <div class="table-container" style="margin-top:0.75rem;">
                                            <table class="data-table">
                                                <thead>
                                                    <tr>
                                                        <th>Title</th>
                                                        <th>Assigned Staff</th>
                                                        <th>Due Date</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($cTasks)): ?>
                                                        <tr><td colspan="4" style="text-align:center; color:var(--text-muted);">No active tasks for this client.</td></tr>
                                                    <?php else: ?>
                                                        <?php foreach ($cTasks as $t): ?>
                                                            <tr>
                                                                <td style="font-weight:600;"><?= htmlspecialchars($t['title']) ?></td>
                                                                <td><?= htmlspecialchars($t['staff_name'] ?: 'Unassigned') ?></td>
                                                                <td><?= htmlspecialchars($t['due_date'] ?: 'N/A') ?></td>
                                                                <td>
                                                                    <span class="badge badge-<?= $t['status'] === 'in_progress' ? 'progress' : $t['status'] ?>">
                                                                        <?= htmlspecialchars($t['status']) ?>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="card glass-card">
                                        <details style="width: 100%;">
                                            <summary style="cursor: pointer; list-style: none; outline: none; display: flex; justify-content: space-between; align-items: center;">
                                                <h3 class="card-title" style="margin:0; display:inline-flex; align-items:center;"><i data-lucide="activity" style="vertical-align:middle; margin-right:0.5rem; width:18px;"></i>Client Activity History</h3>
                                                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700;">Click to Expand</span>
                                            </summary>
                                            <div class="timeline" style="margin-top:1.5rem;">
                                                <?php if (empty($cTimeline)): ?>
                                                    <p style="color:var(--text-muted); font-size:0.85rem;">No timeline actions recorded.</p>
                                                <?php else: ?>
                                                    <?php foreach ($cTimeline as $evt): ?>
                                                        <div class="timeline-item">
                                                            <div class="timeline-marker"></div>
                                                            <div class="timeline-time"><?= htmlspecialchars($evt['created_at']) ?></div>
                                                            <div class="timeline-title"><?= str_replace('_', ' ', strtoupper($evt['event_type'])) ?></div>
                                                            <div class="timeline-desc"><?= htmlspecialchars($evt['description']) ?></div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </details>
                                    </div>
                                </div>

                                <!-- Tab Content: Tax & Compliance -->
                                <div id="tab-compliances" class="crm-tab-content">
                                    <div class="card glass-card">
                                        <h3 class="card-title"><i data-lucide="award" style="vertical-align:middle; margin-right:0.5rem; width:18px;"></i>Tax Returns Compliance</h3>
                                        <div class="table-container" style="margin-top:0.75rem;">
                                            <table class="data-table">
                                                <thead>
                                                    <tr>
                                                        <th>Return Detail</th>
                                                        <th>Category</th>
                                                        <th>Due Date</th>
                                                        <th>Filing Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($cCompliances)): ?>
                                                        <tr><td colspan="4" style="text-align:center; color:var(--text-muted);">No compliances registered.</td></tr>
                                                    <?php else: ?>
                                                        <?php foreach ($cCompliances as $comp): ?>
                                                            <tr>
                                                                <td style="font-weight:600;"><?= htmlspecialchars($comp['title']) ?></td>
                                                                <td><span class="badge badge-outline badge-<?= strtolower(explode(' ', $comp['category'])[0]) ?>"><?= htmlspecialchars($comp['category']) ?></span></td>
                                                                <td><?= htmlspecialchars($comp['due_date']) ?></td>
                                                                <td>
                                                                    <span class="badge badge-<?= $comp['status'] === 'filed' ? 'completed' : ($comp['status'] === 'pending' && strtotime($comp['due_date']) < time() ? 'overdue' : 'progress') ?>">
                                                                        <?= htmlspecialchars($comp['status']) ?>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab Content: Billing -->
                                <div id="tab-billing" class="crm-tab-content">
                                    <div class="card glass-card">
                                        <h3 class="card-title"><i data-lucide="wallet" style="vertical-align:middle; margin-right:0.5rem; width:18px;"></i>Billing & Invoices</h3>
                                        <div class="table-container" style="margin-top:0.75rem;">
                                            <table class="data-table">
                                                <thead>
                                                    <tr>
                                                        <th>Invoice #</th>
                                                        <th>Amount</th>
                                                        <th>Due Date</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($cInvoices)): ?>
                                                        <tr><td colspan="4" style="text-align:center; color:var(--text-muted);">No invoices generated for this client.</td></tr>
                                                    <?php else: ?>
                                                        <?php foreach ($cInvoices as $inv): ?>
                                                            <tr>
                                                                <td style="font-weight:600;"><?= htmlspecialchars($inv['invoice_number']) ?></td>
                                                                <td style="font-weight:700;">₹<?= number_format($inv['amount'], 2) ?></td>
                                                                <td><?= htmlspecialchars($inv['due_date']) ?></td>
                                                                <td>
                                                                    <span class="badge badge-<?= $inv['status'] === 'paid' ? 'completed' : 'progress' ?>">
                                                                        <?= htmlspecialchars($inv['status']) ?>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab Content: Document Vault -->
                                <div id="tab-vault" class="crm-tab-content">
                                    <style>
                                        #drag-drop-area.highlight {
                                            border-color: var(--success) !important;
                                            background: rgba(var(--success-rgb), 0.05) !important;
                                        }
                                    </style>
                                    <div class="card glass-card">
                                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem; margin-bottom:1rem;">
                                            <h3 class="card-title" style="margin:0;"><i data-lucide="folder" style="vertical-align:middle; margin-right:0.5rem; width:18px;"></i>Document Vault Explorer</h3>
                                            <span class="badge badge-outline badge-general" style="font-size:0.75rem;">Current Folder: <?= htmlspecialchars($currentFolder) ?></span>
                                        </div>

                                        <!-- OCR Search Box -->
                                        <form action="index.php" method="GET" style="display:flex; gap:0.5rem; margin-bottom:1.25rem;">
                                            <input type="hidden" name="tab" value="clients">
                                            <input type="hidden" name="client_id" value="<?= $clientId ?>">
                                            <input type="hidden" name="sub" value="<?= htmlspecialchars($subTab) ?>">
                                            <input type="hidden" name="folder" value="<?= htmlspecialchars($currentFolder) ?>">
                                            <input type="text" name="doc_search" class="form-control" placeholder="🔍 OCR Index Search (e.g. GST, TDS, Invoice, Salary)..." value="<?= htmlspecialchars($docSearch) ?>" style="flex:1;">
                                            <button type="submit" class="btn btn-primary">Search</button>
                                            <?php if (!empty($docSearch)): ?>
                                                <a href="index.php?tab=clients&client_id=<?= $clientId ?>&sub=<?= htmlspecialchars($subTab) ?>&folder=<?= htmlspecialchars($currentFolder) ?>" class="btn btn-secondary">Clear</a>
                                            <?php endif; ?>
                                        </form>

                                        <!-- Virtual Folder Selector -->
                                        <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-bottom:1.5rem; background:rgba(255,255,255,0.02); padding:0.5rem; border-radius:var(--radius-md); border:1px solid rgba(255,255,255,0.05);">
                                            <?php 
                                            $folders = [
                                                '/' => '📁 Root (/)',
                                                '/GST' => '📁 GST Filings',
                                                '/TDS' => '📁 TDS Returns',
                                                '/Invoices' => '📁 Invoices',
                                                '/ROC' => '📁 ROC filings',
                                                '/Legals' => '📁 Contracts/PAN'
                                            ];
                                            foreach ($folders as $path => $label) {
                                                $isActive = ($currentFolder === $path);
                                                $url = "index.php?tab=clients&client_id=$clientId&sub=" . urlencode($subTab) . "&folder=" . urlencode($path);
                                                if (!empty($docSearch)) $url .= "&doc_search=" . urlencode($docSearch);
                                                echo '<a href="' . $url . '" class="btn ' . ($isActive ? 'btn-primary' : 'btn-secondary') . '" style="padding:0.35rem 0.75rem; font-size:0.8rem; border-radius:15px;">' . $label . '</a>';
                                            }
                                            ?>
                                        </div>

                                        <!-- Drag & Drop Upload Area -->
                                        <form action="index.php?tab=clients&client_id=<?= $clientId ?>&sub=<?= urlencode($subTab) ?>&folder=<?= urlencode($currentFolder) ?>" method="POST" enctype="multipart/form-data" style="margin-bottom:2rem; background:rgba(255,255,255,0.01); border:1px solid rgba(255,255,255,0.03); padding:1rem; border-radius:var(--radius-md);" id="doc-upload-form">
                                            <input type="hidden" name="action" value="staff_upload_document">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                            <input type="hidden" name="client_id" value="<?= $clientId ?>">
                                            <input type="hidden" name="folder" value="<?= htmlspecialchars($currentFolder) ?>">

                                            <div id="drag-drop-area" style="border: 2px dashed var(--primary); border-radius: var(--radius-md); padding: 1.5rem; text-align: center; cursor: pointer; transition: all 0.2s; background: rgba(var(--primary-rgb), 0.01);" onclick="document.getElementById('drag-drop-file-input').click()">
                                                <i data-lucide="upload-cloud" style="width:2.5rem; height:2.5rem; color:var(--primary); margin:0 auto 0.5rem auto;"></i>
                                                <div style="font-weight:700; font-size:0.9rem;">Drag & Drop File Here or Click to Upload</div>
                                                <p style="font-size:0.75rem; color:var(--text-muted); margin-top:0.25rem;">Supports PDF, PNG, JPG, Excel, Word (Max 5MB)</p>
                                                <div id="drag-drop-status" style="font-size:0.8rem; font-weight:700; color:var(--success); margin-top:0.5rem;"></div>
                                                <input type="file" id="drag-drop-file-input" name="document" style="display:none;" onchange="document.getElementById('drag-drop-status').innerText = 'Selected: ' + this.files[0].name" required>
                                            </div>

                                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-top:1rem; align-items:center;">
                                                <div class="form-group" style="margin-bottom:0;">
                                                    <label class="form-label" style="font-size:0.75rem;">Sharing Scope / Permission</label>
                                                    <select name="sharing_scope" class="form-control" style="padding:0.4rem; font-size:0.85rem;">
                                                        <option value="client_shared">Visible to Client Portal</option>
                                                        <option value="internal_only">Internal CA Staff Only</option>
                                                    </select>
                                                </div>
                                                <div style="display:flex; align-items:flex-end; height:100%; margin-top:1.25rem;">
                                                    <button type="submit" class="btn btn-primary" style="width:100%; padding:0.55rem; font-size:0.85rem;">Upload Document</button>
                                                </div>
                                            </div>
                                        </form>

                                        <div class="table-container">
                                            <table class="data-table">
                                                <thead>
                                                    <tr>
                                                        <th>File Name</th>
                                                        <th>Version</th>
                                                        <th>Scope</th>
                                                        <th>Signature</th>
                                                        <th>Uploader</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($cDocs)): ?>
                                                        <tr><td colspan="6" style="text-align:center; color:var(--text-muted);">No documents found in folder '<?= htmlspecialchars($currentFolder) ?>'.</td></tr>
                                                    <?php else: ?>
                                                        <?php foreach ($cDocs as $d): ?>
                                                            <tr>
                                                                <td style="font-weight:600;">
                                                                    <i data-lucide="file-text" style="width:14px; height:14px; vertical-align:middle; margin-right:4px; color:var(--primary);"></i>
                                                                    <?= htmlspecialchars($d['file_name']) ?>
                                                                </td>
                                                                <td><span class="badge badge-outline badge-general" style="font-size:0.75rem;">v<?= htmlspecialchars($d['version']) ?></span></td>
                                                                <td>
                                                                    <span class="badge badge-<?= $d['sharing_scope'] === 'client_shared' ? 'completed' : 'muted' ?>" style="font-size:0.7rem;">
                                                                        <?= $d['sharing_scope'] === 'client_shared' ? 'Portal Shared' : 'Internal' ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <?php if ($d['signature_status'] === 'signed'): ?>
                                                                        <span class="badge badge-completed" style="font-size:0.7rem;" title="Signed by <?= htmlspecialchars($d['signed_by']) ?> on <?= $d['signed_at'] ?>">
                                                                            ✓ Signed
                                                                        </span>
                                                                    <?php else: ?>
                                                                        <span class="badge badge-overdue" style="font-size:0.7rem;">Unsigned</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td><span style="font-size:0.8rem;"><?= htmlspecialchars($d['uploaded_by']) ?></span></td>
                                                                <td>
                                                                    <div style="display:flex; gap:0.25rem;">
                                                                        <a href="<?= htmlspecialchars($d['file_path']) ?>" download class="btn btn-secondary" style="padding:0.25rem 0.5rem; font-size:0.75rem;" title="Download File">
                                                                            <i data-lucide="download" style="width:12px;height:12px;"></i>
                                                                        </a>
                                                                        <button type="button" class="btn btn-secondary" style="padding:0.25rem 0.5rem; font-size:0.75rem;" onclick="openPdfPreview('<?= htmlspecialchars($d['file_path']) ?>', '<?= htmlspecialchars(addslashes($d['file_name'])) ?>')" title="Preview Document">
                                                                            <i data-lucide="eye" style="width:12px;height:12px;"></i>
                                                                        </button>
                                                                        <button type="button" class="btn btn-secondary" style="padding:0.25rem 0.5rem; font-size:0.75rem; color:var(--color-crm);" onclick="analyzeDocumentWithAI('<?= htmlspecialchars(addslashes($d['file_path'])) ?>', '<?= htmlspecialchars(addslashes($d['file_name'])) ?>')" title="AI Analyze Document">
                                                                            <i data-lucide="sparkles" style="width:12px;height:12px;"></i>
                                                                        </button>
                                                                        <?php if ($d['signature_status'] !== 'signed'): ?>
                                                                            <button type="button" class="btn btn-primary" style="padding:0.25rem 0.5rem; font-size:0.75rem;" onclick="openSignaturePad(<?= $d['id'] ?>, '<?= htmlspecialchars(addslashes($d['file_name'])) ?>')" title="Apply Signature">
                                                                                <i data-lucide="pen-tool" style="width:12px;height:12px;vertical-align:middle;margin-right:2px;"></i> Sign
                                                                            </button>
                                                                        <?php endif; ?>
                                                                        <?php if ($isAdmin): ?>
                                                                            <form action="index.php?tab=clients&client_id=<?= $clientId ?>&sub=<?= urlencode($subTab) ?>&folder=<?= urlencode($currentFolder) ?>" method="POST" style="margin:0;" onsubmit="return confirm('Delete document version?')">
                                                                                <input type="hidden" name="action" value="delete_document">
                                                                                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                                                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                                                <button type="submit" class="btn btn-danger" style="padding:0.25rem 0.5rem; font-size:0.75rem;" title="Delete">
                                                                                    <i data-lucide="trash-2" style="width:12px;height:12px;"></i>
                                                                                </button>
                                                                            </form>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab Content: Client Notes -->
                                <div id="tab-notes" class="crm-tab-content">
                                    <div class="card glass-card">
                                        <h3 class="card-title"><i data-lucide="sticky-note" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:var(--warning);"></i>Client File Notes</h3>
                                        
                                        <!-- Add Note Form -->
                                        <form action="index.php?tab=clients&client_id=<?= $clientId ?>&sub=<?= urlencode($subTab) ?>" method="POST" style="margin-top: 1rem;">
                                            <input type="hidden" name="action" value="add_note">
                                            <input type="hidden" name="client_id" value="<?= $clientId ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                            <div class="form-group">
                                                <label class="form-label" for="note-content">Add Private Client Note</label>
                                                <textarea name="content" id="note-content" class="form-control" rows="3" placeholder="Type important remarks, billing agreements, or client status notes here..." required></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary" style="font-size:0.85rem; padding:0.5rem 1rem;">Save Note</button>
                                        </form>

                                        <div style="margin-top: 1.5rem; display:flex; flex-direction:column; gap:1rem;">
                                            <?php if (empty($cNotes)): ?>
                                                <p style="color:var(--text-muted); font-size:0.85rem;">No private notes logged for this client.</p>
                                            <?php else: ?>
                                                <?php foreach ($cNotes as $note): ?>
                                                    <div style="background-color: var(--bg-input); padding: 1rem; border-radius: var(--radius-md); border: 1px solid rgba(255,255,255,0.03);">
                                                        <p style="font-size: 0.9rem; line-height: 1.4;"><?= nl2br(htmlspecialchars($note['content'])) ?></p>
                                                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:0.75rem; font-size:0.75rem; color:var(--text-muted);">
                                                            <span>By: <?= htmlspecialchars($note['staff_name']) ?> | <?= date('d M Y, h:i A', strtotime($note['created_at'])) ?></span>
                                                            <form action="index.php?tab=clients&client_id=<?= $clientId ?>&sub=<?= urlencode($subTab) ?>" method="POST" style="margin:0;" onsubmit="return confirm('Delete this note?')">
                                                                <input type="hidden" name="action" value="delete_note">
                                                                <input type="hidden" name="note_id" value="<?= $note['id'] ?>">
                                                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                                <button type="submit" style="background:none; border:none; color:var(--danger); cursor:pointer;">Delete</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab Content: Meetings -->
                                <div id="tab-meetings" class="crm-tab-content">
                                    <div class="card glass-card">
                                        <div style="display:flex; justify-content:space-between; align-items:center;">
                                            <h3 class="card-title"><i data-lucide="video" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:var(--primary);"></i>Meeting History</h3>
                                            <button class="btn btn-primary" style="font-size:0.75rem; padding:0.4rem 0.75rem;" data-open-modal="schedule-meeting-modal">
                                                Schedule Meeting
                                            </button>
                                        </div>

                                        <div class="table-container" style="margin-top:1rem;">
                                            <table class="data-table" style="font-size:0.85rem;">
                                                <thead>
                                                    <tr>
                                                        <th>Meeting Agenda</th>
                                                        <th>Date & Time</th>
                                                        <th>Status</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($cMeetings)): ?>
                                                        <tr><td colspan="4" style="text-align:center; color:var(--text-muted);">No meetings scheduled.</td></tr>
                                                    <?php else: ?>
                                                        <?php foreach ($cMeetings as $m): ?>
                                                            <tr>
                                                                <td style="font-weight:700;"><?= htmlspecialchars($m['title']) ?><br><span style="font-size:0.75rem; color:var(--text-muted); font-weight:normal;"><?= htmlspecialchars($m['description'] ?: 'No details') ?></span></td>
                                                                <td><?= date('d M Y, h:i A', strtotime($m['meeting_date'])) ?></td>
                                                                <td>
                                                                    <span class="badge badge-<?= $m['status'] === 'completed' ? 'completed' : ($m['status'] === 'cancelled' ? 'muted' : 'progress') ?>">
                                                                        <?= strtoupper($m['status']) ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <?php if ($m['status'] === 'scheduled'): ?>
                                                                        <div style="display:flex; gap:0.25rem;">
                                                                            <?php if (!empty($m['video_link'])): ?>
                                                                                <a href="<?= htmlspecialchars($m['video_link']) ?>" target="_blank" class="btn btn-primary" style="padding:0.25rem 0.4rem; font-size:0.7rem; background-color:#22c55e; border-color:#22c55e;">Join Call</a>
                                                                            <?php endif; ?>
                                                                            <form action="index.php?tab=clients&client_id=<?= $clientId ?>&sub=<?= urlencode($subTab) ?>" method="POST" style="margin:0;">
                                                                                <input type="hidden" name="action" value="update_meeting_status">
                                                                                <input type="hidden" name="meeting_id" value="<?= $m['id'] ?>">
                                                                                <input type="hidden" name="status" value="completed">
                                                                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                                                <button type="submit" class="btn btn-primary" style="padding:0.25rem 0.4rem; font-size:0.7rem;">Done</button>
                                                                            </form>
                                                                            <form action="index.php?tab=clients&client_id=<?= $clientId ?>&sub=<?= urlencode($subTab) ?>" method="POST" style="margin:0;">
                                                                                <input type="hidden" name="action" value="update_meeting_status">
                                                                                <input type="hidden" name="meeting_id" value="<?= $m['id'] ?>">
                                                                                <input type="hidden" name="status" value="cancelled">
                                                                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                                                <button type="submit" class="btn btn-secondary" style="padding:0.25rem 0.4rem; font-size:0.7rem; color:var(--danger);">Cancel</button>
                                                                            </form>
                                                                        </div>
                                                                    <?php else: ?>
                                                                        --
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab Content: WhatsApp Integration -->
                                <div id="tab-whatsapp" class="crm-tab-content">
                                    <div class="card glass-card">
                                        <h3 class="card-title"><i data-lucide="phone-call" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:#22c55e;"></i>WhatsApp Alert Simulator</h3>
                                        
                                        <form action="index.php?tab=clients&client_id=<?= $clientId ?>&sub=<?= urlencode($subTab) ?>" method="POST" style="margin-top:1rem;">
                                            <input type="hidden" name="action" value="send_whatsapp">
                                            <input type="hidden" name="client_id" value="<?= $clientId ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                            <div class="form-group">
                                                <label class="form-label" for="wa-msg">Message Template Alert</label>
                                                <textarea name="message" id="wa-msg" class="form-control" rows="3" placeholder="Hello, this is a reminder that your GST return is due on 20th. Kindly provide documents." required></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary" style="font-size:0.85rem; padding:0.5rem 1rem;">Send WhatsApp Notification</button>
                                        </form>

                                        <div style="margin-top:1.5rem;">
                                            <h4 style="font-size:0.9rem; font-weight:700; margin-bottom:0.75rem;">Sent WhatsApp Log History</h4>
                                            <div style="display:flex; flex-direction:column; gap:0.75rem; max-height: 250px; overflow-y:auto; padding-right:0.25rem;">
                                                <?php if (empty($cWhatsApp)): ?>
                                                    <p style="color:var(--text-muted); font-size:0.8rem;">No WhatsApp alerts logged.</p>
                                                <?php else: ?>
                                                    <?php foreach ($cWhatsApp as $wa): ?>
                                                        <div style="background-color: var(--bg-input); padding: 0.75rem; border-radius: var(--radius-sm); border-left: 3px solid #22c55e;">
                                                            <p style="font-size:0.85rem; line-height:1.4;"><?= htmlspecialchars($wa['message']) ?></p>
                                                            <div style="display:flex; justify-content:space-between; margin-top:0.5rem; font-size:0.7rem; color:var(--text-muted);">
                                                                <span>Status: <span style="color:#22c55e; font-weight:bold;"><?= strtoupper($wa['status']) ?></span></span>
                                                                <span><?= date('d M Y, h:i A', strtotime($wa['created_at'])) ?></span>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab Content: Email Tracking -->
                                <div id="tab-email" class="crm-tab-content">
                                    <div class="card glass-card">
                                        <h3 class="card-title"><i data-lucide="mail" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:var(--primary);"></i>Email Tracking & Log</h3>
                                        
                                        <form action="index.php?tab=clients&client_id=<?= $clientId ?>&sub=<?= urlencode($subTab) ?>" method="POST" style="margin-top:1rem;">
                                            <input type="hidden" name="action" value="send_email">
                                            <input type="hidden" name="client_id" value="<?= $clientId ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                            <div class="form-group">
                                                <label class="form-label" for="email-subject">Subject</label>
                                                <input type="text" name="subject" id="email-subject" class="form-control" placeholder="Tax Assessment Filing Complete" required>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label" for="email-body">Email Message Body</label>
                                                <textarea name="body" id="email-body" class="form-control" rows="3" placeholder="Dear Client, please find attached the details of filing return..." required></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary" style="font-size:0.85rem; padding:0.5rem 1rem;">Send Tracking Email</button>
                                        </form>

                                        <div style="margin-top:1.5rem;">
                                            <h4 style="font-size:0.9rem; font-weight:700; margin-bottom:0.75rem;">Email Dispatch & Open Records</h4>
                                            <div style="display:flex; flex-direction:column; gap:0.75rem; max-height: 250px; overflow-y:auto; padding-right:0.25rem;">
                                                <?php if (empty($cEmails)): ?>
                                                    <p style="color:var(--text-muted); font-size:0.8rem;">No email communication logged.</p>
                                                <?php else: ?>
                                                    <?php foreach ($cEmails as $em): ?>
                                                        <div style="background-color: var(--bg-input); padding: 0.75rem; border-radius: var(--radius-sm);">
                                                            <div style="font-weight:700; font-size:0.85rem; color:var(--primary);"><?= htmlspecialchars($em['subject']) ?></div>
                                                            <p style="font-size:0.8rem; margin-top:0.25rem; line-height:1.4; color:var(--text-main);"><?= htmlspecialchars($em['body']) ?></p>
                                                            <div style="display:flex; justify-content:space-between; margin-top:0.5rem; font-size:0.7rem; color:var(--text-muted);">
                                                                <span>Status: <span style="color:var(--success);"><?= strtoupper($em['status']) ?> (Delivered)</span></span>
                                                                <span><?= date('d M Y, h:i A', strtotime($em['created_at'])) ?></span>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Schedule Meeting Modal -->
                        <div class="modal-overlay" id="schedule-meeting-modal">
                            <div class="modal-container">
                                <div class="modal-header">
                                    <h3 style="font-size:1.15rem; font-weight:700;">Schedule Client Meeting</h3>
                                    <button class="modal-close" data-close-modal="schedule-meeting-modal">&times;</button>
                                </div>
                                <form action="index.php?tab=clients&client_id=<?= $clientId ?>&sub=<?= urlencode($subTab) ?>" method="POST">
                                    <input type="hidden" name="action" value="schedule_meeting">
                                    <input type="hidden" name="client_id" value="<?= $clientId ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    
                                    <div class="form-group">
                                        <label for="meet-title" class="form-label">Meeting Agenda / Title</label>
                                        <input type="text" id="meet-title" name="title" class="form-control" required placeholder="e.g. Audit Return Review">
                                    </div>
                                    <div class="form-group">
                                        <label for="meet-date" class="form-label">Date & Time</label>
                                        <input type="datetime-local" id="meet-date" name="meeting_date" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="meet-desc" class="form-label">Brief Description</label>
                                        <textarea id="meet-desc" name="description" class="form-control" rows="3" placeholder="Provide zoom link, office location, or files needed..."></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="meet-vlink" class="form-label">Video Conference Link (Zoom / Meet URL)</label>
                                        <input type="url" id="meet-vlink" name="video_link" class="form-control" placeholder="https://meet.google.com/xxx-xxxx-xxx">
                                    </div>
                                    <button type="submit" class="btn btn-primary" style="width:100%; margin-top:0.5rem; padding:0.75rem;">Schedule</button>
                                </form>
                            </div>
                        </div>

                        <!-- Staff Upload Document Modal -->
                        <div class="modal-overlay" id="staff-upload-doc-modal">
                            <div class="modal-container">
                                <div class="modal-header">
                                    <h3 style="font-size:1.15rem; font-weight:700;">Upload Document to Client Folder</h3>
                                    <button class="modal-close" data-close-modal="staff-upload-doc-modal">&times;</button>
                                </div>
                                <form action="index.php?tab=clients&client_id=<?= $clientId ?>&sub=<?= urlencode($subTab) ?>" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="staff_upload_document">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="client_id" value="<?= $clientId ?>">
                                    
                                    <div class="form-group">
                                        <label for="doc-file" class="form-label">Select File (PDF, Images, Excel, Word)</label>
                                        <input type="file" id="doc-file" name="document" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="doc-cat" class="form-label">Folder / Category</label>
                                        <select id="doc-cat" name="category" class="form-control">
                                            <option value="General">General / Administrative</option>
                                            <option value="GST Certificate">GST Certificate</option>
                                            <option value="PAN Card">PAN Card</option>
                                            <option value="ITR Filings">ITR Filings</option>
                                            <option value="Audit Report">Audit Report</option>
                                            <option value="Tally Backup">Tally Backup</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="doc-version" class="form-label">Document Version</label>
                                        <input type="number" id="doc-version" name="version" class="form-control" value="1" min="1" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="doc-desc" class="form-label">Brief Description</label>
                                        <textarea id="doc-desc" name="description" class="form-control" rows="2" placeholder="e.g. GST Registration Certificate updated May 2026"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary" style="width:100%; margin-top:0.5rem; padding:0.75rem;">Upload Document</button>
                                </form>
                            </div>
                        </div>

            <?php
                    endif;
                else: 
                    // Main CRM Navigation header
            ?>
                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:1rem; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
                        <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                            <a href="index.php?tab=clients&sub=directory" class="btn <?= $subTab === 'directory' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:0.85rem; padding:0.5rem 1rem;">Client CRM Directory</a>
                            <a href="index.php?tab=clients&sub=leads" class="btn <?= $subTab === 'leads' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:0.85rem; padding:0.5rem 1rem;">Lead Management</a>
                            <a href="index.php?tab=clients&sub=pipeline" class="btn <?= $subTab === 'pipeline' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:0.85rem; padding:0.5rem 1rem;">Opportunity Pipeline</a>
                        </div>
                        
                        <?php if ($subTab === 'directory'): ?>
                            <button class="btn btn-primary" data-open-modal="add-client-modal" style="font-size:0.85rem; padding:0.5rem 1rem;">
                                <i data-lucide="plus" style="width:16px;height:16px;vertical-align:middle;margin-right:2px;"></i> Add New Client
                            </button>
                        <?php elseif ($subTab === 'leads'): ?>
                            <button class="btn btn-primary" data-open-modal="add-lead-modal" style="font-size:0.85rem; padding:0.5rem 1rem;">
                                <i data-lucide="plus" style="width:16px;height:16px;vertical-align:middle;margin-right:2px;"></i> Add New Lead
                            </button>
                        <?php elseif ($subTab === 'pipeline'): ?>
                            <button class="btn btn-primary" data-open-modal="add-opportunity-modal" style="font-size:0.85rem; padding:0.5rem 1rem;">
                                <i data-lucide="plus" style="width:16px;height:16px;vertical-align:middle;margin-right:2px;"></i> Add Opportunity
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if ($subTab === 'directory'): 
                        $limit = 10;
                        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                        if ($page < 1) $page = 1;
                        $offset = ($page - 1) * $limit;
                        $totalClients = Client::getClientsCount();
                        $totalPages = ceil($totalClients / $limit);
                        if ($totalPages < 1) $totalPages = 1;
                        $clients = Client::getClients($limit, $offset);
                    ?>
                        <div class="card glass-card">
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Client Name</th>
                                            <th>Primary Email</th>
                                            <th>Contact Phone</th>
                                            <th>Action Link</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($clients)): ?>
                                            <tr>
                                                <td colspan="4" style="text-align: center; color: var(--text-muted);">No clients registered yet.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($clients as $c): ?>
                                                <tr>
                                                    <td style="font-weight: 700;">
                                                        <a href="index.php?tab=clients&client_id=<?= $c['id'] ?>&sub=directory" style="color:var(--primary); font-weight:700;">
                                                            <?= htmlspecialchars($c['name']) ?>
                                                        </a>
                                                    </td>
                                                    <td><?= htmlspecialchars($c['email']) ?></td>
                                                    <td><?= htmlspecialchars($c['phone'] ?: 'N/A') ?></td>
                                                    <td>
                                                        <div style="display: flex; gap: 0.5rem;">
                                                            <a href="index.php?tab=clients&client_id=<?= $c['id'] ?>&sub=directory" class="btn btn-secondary" style="padding: 0.45rem 0.85rem; font-size: 0.8rem;">
                                                                Open Profile
                                                            </a>
                                                            <button class="btn btn-secondary" style="padding: 0.45rem 0.85rem; font-size: 0.8rem;" onclick="openEditClient(<?= $c['id'] ?>, '<?= addslashes($c['name']) ?>', '<?= addslashes($c['email']) ?>', '<?= addslashes($c['phone'] ?? '') ?>', '<?= addslashes($c['pan'] ?? '') ?>', '<?= addslashes($c['gstin'] ?? '') ?>', '<?= addslashes($c['tan'] ?? '') ?>', '<?= addslashes($c['address'] ?? '') ?>', '<?= addslashes($c['client_type'] ?? '') ?>', '<?= addslashes($c['incorporation_date'] ?? '') ?>')">
                                                                Edit
                                                            </button>
                                                            <form action="index.php?tab=clients&sub=directory" method="POST" onsubmit="return confirm('Are you sure?')" style="margin: 0;">
                                                                <input type="hidden" name="action" value="delete_client">
                                                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                                <button type="submit" class="btn btn-danger" style="padding: 0.45rem 0.85rem; font-size: 0.8rem;">
                                                                    Delete
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Pagination Controls -->
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:1rem; padding-top:0.75rem; border-top:1px solid rgba(255,255,255,0.05); font-size:0.85rem;" class="no-print">
                                <span style="color:var(--text-muted);">Showing page <strong><?= $page ?></strong> of <?= $totalPages ?> (Total: <?= $totalClients ?> clients)</span>
                                <div style="display:flex; gap:0.5rem;">
                                    <?php if ($page > 1): ?>
                                        <a href="index.php?tab=clients&sub=directory&page=<?= $page - 1 ?>" class="btn btn-secondary" style="padding:0.35rem 0.75rem; font-size:0.8rem;">&laquo; Previous</a>
                                    <?php endif; ?>
                                    <?php if ($page < $totalPages): ?>
                                        <a href="index.php?tab=clients&sub=directory&page=<?= $page + 1 ?>" class="btn btn-secondary" style="padding:0.35rem 0.75rem; font-size:0.8rem;">Next &raquo;</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($subTab === 'leads'): 
                        $leads = CRM::getLeads();
                    ?>
                        <div class="card glass-card">
                            <h3 class="card-title">Sales & Consultation Leads</h3>
                            <div class="table-container" style="margin-top: 1rem;">
                                <table class="data-table" style="font-size:0.85rem;">
                                    <thead>
                                        <tr>
                                            <th>Lead Name</th>
                                            <th>Contact Email</th>
                                            <th>Phone Number</th>
                                            <th>Source</th>
                                            <th>Lead Status</th>
                                            <th>Remarks</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($leads)): ?>
                                            <tr><td colspan="7" style="text-align:center; color:var(--text-muted);">No sales leads logged.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($leads as $l): ?>
                                                <tr>
                                                    <td style="font-weight:700;"><?= htmlspecialchars($l['name']) ?></td>
                                                    <td><?= htmlspecialchars($l['email']) ?></td>
                                                    <td><?= htmlspecialchars($l['phone'] ?: 'N/A') ?></td>
                                                    <td><span class="badge badge-progress" style="font-size:0.75rem;"><?= htmlspecialchars($l['source']) ?></span></td>
                                                    <td>
                                                        <form action="index.php?tab=clients&sub=leads" method="POST" style="margin:0;">
                                                            <input type="hidden" name="action" value="update_lead_status">
                                                            <input type="hidden" name="lead_id" value="<?= $l['id'] ?>">
                                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                            <select name="status" class="form-control" style="font-size:0.75rem; padding:0.25rem; width:auto;" onchange="this.form.submit()">
                                                                <option value="new" <?= $l['status'] === 'new' ? 'selected' : '' ?>>NEW</option>
                                                                <option value="contacted" <?= $l['status'] === 'contacted' ? 'selected' : '' ?>>CONTACTED</option>
                                                                <option value="qualified" <?= $l['status'] === 'qualified' ? 'selected' : '' ?>>QUALIFIED</option>
                                                                <option value="disqualified" <?= $l['status'] === 'disqualified' ? 'selected' : '' ?>>DISQUALIFIED</option>
                                                            </select>
                                                        </form>
                                                    </td>
                                                    <td style="color:var(--text-muted); font-size:0.8rem;"><?= htmlspecialchars($l['notes'] ?: '--') ?></td>
                                                    <td>
                                                        <div style="display:flex; gap:0.25rem;">
                                                            <!-- Convert Lead to Client -->
                                                            <form action="index.php?tab=clients&sub=directory" method="POST" style="margin:0;">
                                                                <input type="hidden" name="action" value="add_client">
                                                                <input type="hidden" name="name" value="<?= htmlspecialchars($l['name']) ?>">
                                                                <input type="hidden" name="email" value="<?= htmlspecialchars($l['email']) ?>">
                                                                <input type="hidden" name="phone" value="<?= htmlspecialchars($l['phone']) ?>">
                                                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                                <button type="submit" class="btn btn-primary" style="padding:0.25rem 0.5rem; font-size:0.75rem;" onclick="return confirm('Convert this lead to a fully registered client?')">Convert</button>
                                                            </form>
                                                            <!-- Delete Lead -->
                                                            <form action="index.php?tab=clients&sub=leads" method="POST" style="margin:0;" onsubmit="return confirm('Delete this lead?')">
                                                                <input type="hidden" name="action" value="delete_lead">
                                                                <input type="hidden" name="lead_id" value="<?= $l['id'] ?>">
                                                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                                <button type="submit" class="btn btn-secondary" style="padding:0.25rem 0.5rem; font-size:0.75rem; color:var(--danger);">Delete</button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    <?php elseif ($subTab === 'pipeline'): 
                        $opps = CRM::getOpportunities();
                        $stages = [
                            'discovery' => 'Discovery',
                            'proposal' => 'Proposal Sent',
                            'negotiation' => 'Negotiating',
                            'won' => 'Closed Won',
                            'lost' => 'Closed Lost'
                        ];
                    ?>
                        <div class="pipeline-kanban-board">
                            <?php foreach ($stages as $stageKey => $stageTitle): ?>
                                <div class="pipeline-column">
                                    <div class="pipeline-column-header">
                                        <span><?= $stageTitle ?></span>
                                        <?php 
                                            $filteredOpps = array_filter($opps, function($o) use ($stageKey) {
                                                return $o['stage'] === $stageKey;
                                            });
                                            $columnSum = array_sum(array_column($filteredOpps, 'value'));
                                        ?>
                                        <span style="font-size:0.75rem; color:var(--primary); font-weight:700;">₹<?= number_format($columnSum, 0) ?></span>
                                    </div>
                                    
                                    <div style="display:flex; flex-direction:column; gap:0.75rem; height:100%; min-height: 300px; overflow-y:auto; max-height: 500px;">
                                        <?php if (empty($filteredOpps)): ?>
                                            <p style="color:var(--text-muted); font-size:0.8rem; text-align:center; padding: 2rem 0;">No active deals</p>
                                        <?php else: ?>
                                            <?php foreach ($filteredOpps as $op): ?>
                                                <div class="pipeline-card">
                                                    <div style="font-weight:700; font-size:0.9rem; color:var(--text-main);"><?= htmlspecialchars($op['title']) ?></div>
                                                    <div style="font-weight:800; font-size:1.05rem; color:var(--primary); margin:0.35rem 0;">₹<?= number_format($op['value'], 2) ?></div>
                                                    
                                                    <div style="font-size:0.75rem; color:var(--text-muted); margin-bottom:0.5rem;">
                                                        Prospect: <?= htmlspecialchars($op['lead_name'] ?: $op['client_name'] ?: 'General') ?>
                                                    </div>
                                                    
                                                    <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid rgba(255,255,255,0.05); padding-top:0.5rem; margin-top:0.5rem;">
                                                        <span style="font-size:0.7rem; color:var(--text-muted); font-weight:bold;"><?= $op['probability'] ?>% Win rate</span>
                                                        
                                                        <form action="index.php?tab=clients&sub=pipeline" method="POST" style="margin:0;">
                                                            <input type="hidden" name="action" value="edit_opportunity_stage">
                                                            <input type="hidden" name="opportunity_id" value="<?= $op['id'] ?>">
                                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                            <select name="stage" class="form-control" style="font-size:0.7rem; padding:0.15rem; width:auto;" onchange="this.form.submit()">
                                                                <?php foreach ($stages as $sk => $st): ?>
                                                                    <option value="<?= $sk ?>" <?= $op['stage'] === $sk ? 'selected' : '' ?>><?= $st ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </form>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Add Lead Modal -->
                    <div class="modal-overlay" id="add-lead-modal">
                        <div class="modal-container">
                            <div class="modal-header">
                                <h3 style="font-size: 1.15rem; font-weight: 700;">Add New Sales Lead</h3>
                                <button class="modal-close" data-close-modal="add-lead-modal">&times;</button>
                            </div>
                            <form action="index.php?tab=clients&sub=leads" method="POST">
                                <input type="hidden" name="action" value="add_lead">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                
                                <div class="form-group">
                                    <label for="l-name" class="form-label">Full Name</label>
                                    <input type="text" id="l-name" name="name" class="form-control" required placeholder="e.g. Amit Kumar">
                                </div>
                                <div class="form-group">
                                    <label for="l-email" class="form-label">Email Address</label>
                                    <input type="email" id="l-email" name="email" class="form-control" required placeholder="name@company.com">
                                </div>
                                <div class="form-group">
                                    <label for="l-phone" class="form-label">Phone Number</label>
                                    <input type="text" id="l-phone" name="phone" class="form-control" placeholder="+91 XXXXX XXXXX">
                                </div>
                                <div class="form-group">
                                    <label for="l-source" class="form-label">Lead Source</label>
                                    <select id="l-source" name="source" class="form-control">
                                        <option value="Website Contact">Website Form</option>
                                        <option value="Referral">Client Referral</option>
                                        <option value="Cold Call">Outreach / Cold Call</option>
                                        <option value="Social Media">Social Media</option>
                                        <option value="Direct">Direct / Walk-in</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="l-notes" class="form-label">Initial Discussion / Requirements</label>
                                    <textarea id="l-notes" name="notes" class="form-control" rows="3" placeholder="Needs GST filing setup and monthly consulting services."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem; padding: 0.75rem;">Create Lead</button>
                            </form>
                        </div>
                    </div>

                    <!-- Add Opportunity Modal -->
                    <div class="modal-overlay" id="add-opportunity-modal">
                        <div class="modal-container">
                            <div class="modal-header">
                                <h3 style="font-size: 1.15rem; font-weight: 700;">Add Pipeline Opportunity</h3>
                                <button class="modal-close" data-close-modal="add-opportunity-modal">&times;</button>
                            </div>
                            <form action="index.php?tab=clients&sub=pipeline" method="POST">
                                <input type="hidden" name="action" value="add_opportunity">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                
                                <div class="form-group">
                                    <label for="opp-title" class="form-label">Deal / Service Title</label>
                                    <input type="text" id="opp-title" name="title" class="form-control" required placeholder="e.g. GSTR Monthly Audit Setup">
                                </div>
                                <div class="form-group">
                                    <label for="opp-value" class="form-label">Estimated Deal Value (INR)</label>
                                    <input type="number" id="opp-value" name="value" class="form-control" required min="0" value="5000" step="100">
                                </div>
                                <div class="form-group">
                                    <label for="opp-stage" class="form-label">Pipeline Stage</label>
                                    <select id="opp-stage" name="stage" class="form-control">
                                        <option value="discovery">Discovery (10%)</option>
                                        <option value="proposal">Proposal Sent (40%)</option>
                                        <option value="negotiation">Negotiation (70%)</option>
                                        <option value="won">Closed Won (100%)</option>
                                        <option value="lost">Closed Lost (0%)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="opp-lead" class="form-label">Link to Lead (Optional)</label>
                                    <select id="opp-lead" name="lead_id" class="form-control">
                                        <option value="">-- None --</option>
                                        <?php 
                                            $allLeads = CRM::getLeads();
                                            foreach ($allLeads as $ld) {
                                                echo '<option value="' . $ld['id'] . '">' . htmlspecialchars($ld['name']) . '</option>';
                                            }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="opp-client" class="form-label">Link to Existing Client (Optional)</label>
                                    <select id="opp-client" name="client_id" class="form-control">
                                        <option value="">-- None --</option>
                                        <?php 
                                            $allClients = Client::getClients();
                                            foreach ($allClients as $cl) {
                                                echo '<option value="' . $cl['id'] . '">' . htmlspecialchars($cl['name']) . '</option>';
                                            }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="opp-close" class="form-label">Expected Close Date</label>
                                    <input type="date" id="opp-close" name="close_date" class="form-control">
                                </div>
                                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem; padding: 0.75rem;">Create Deal</button>
                            </form>
                        </div>
                    </div>

                    <!-- Add Client Modal -->
                    <div class="modal-overlay" id="add-client-modal">
                        <div class="modal-container" style="max-width: 550px;">
                            <div class="modal-header">
                                <h3 style="font-size: 1.15rem; font-weight: 700;">Add Client</h3>
                                <button class="modal-close" data-close-modal="add-client-modal">&times;</button>
                            </div>
                            <form action="index.php?tab=clients" method="POST" style="display:grid; gap:0.75rem;">
                                <input type="hidden" name="action" value="add_client">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.75rem;">
                                    <div class="form-group">
                                        <label for="c-name" class="form-label">Client / Firm Name</label>
                                        <input type="text" id="c-name" name="name" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="c-email" class="form-label">Primary Email</label>
                                        <input type="email" id="c-email" name="email" class="form-control" required>
                                    </div>
                                </div>
                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.75rem;">
                                    <div class="form-group">
                                        <label for="c-phone" class="form-label">Phone / Contact</label>
                                        <input type="text" id="c-phone" name="phone" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="c-type" class="form-label">Client Entity Type</label>
                                        <select id="c-type" name="client_type" class="form-control">
                                            <option value="Individual">Individual / Proprietorship</option>
                                            <option value="Partnership">Partnership Firm</option>
                                            <option value="LLP">Limited Liability Partnership (LLP)</option>
                                            <option value="Private Limited">Private Limited Company</option>
                                            <option value="Public Limited">Public Limited Company</option>
                                            <option value="HUF">HUF</option>
                                            <option value="Trust">Trust / Society</option>
                                        </select>
                                    </div>
                                </div>
                                <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:0.75rem;">
                                    <div class="form-group">
                                        <label for="c-pan" class="form-label">PAN Card</label>
                                        <input type="text" id="c-pan" name="pan" class="form-control" placeholder="ABCDE1234F" pattern="[a-zA-Z]{5}[0-9]{4}[a-zA-Z]{1}" maxlength="10">
                                    </div>
                                    <div class="form-group">
                                        <label for="c-gstin" class="form-label">GSTIN</label>
                                        <input type="text" id="c-gstin" name="gstin" class="form-control" placeholder="27ABCDE1234F1Z5" pattern="[0-9]{2}[a-zA-Z]{5}[0-9]{4}[a-zA-Z]{1}[0-9a-zA-Z]{3}" maxlength="15">
                                    </div>
                                    <div class="form-group">
                                        <label for="c-tan" class="form-label">TAN Number</label>
                                        <input type="text" id="c-tan" name="tan" class="form-control" placeholder="ABCD12345E" pattern="[a-zA-Z]{4}[0-9]{5}[a-zA-Z]{1}" maxlength="10">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="c-inc-date" class="form-label">Date of Birth / Incorporation</label>
                                    <input type="date" id="c-inc-date" name="incorporation_date" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="c-address" class="form-label">Registered Address</label>
                                    <textarea id="c-address" name="address" class="form-control" rows="2" placeholder="Complete address..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem; padding: 0.75rem;">Create Client</button>
                            </form>
                        </div>
                    </div>

                    <!-- Edit Client Modal -->
                    <div class="modal-overlay" id="edit-client-modal">
                        <div class="modal-container" style="max-width: 550px;">
                            <div class="modal-header">
                                <h3 style="font-size: 1.15rem; font-weight: 700;">Edit Client</h3>
                                <button class="modal-close" data-close-modal="edit-client-modal">&times;</button>
                            </div>
                            <form action="index.php?tab=clients" method="POST" style="display:grid; gap:0.75rem;">
                                <input type="hidden" name="action" value="edit_client">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" id="edit-c-id" name="id">
                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.75rem;">
                                    <div class="form-group">
                                        <label for="edit-c-name" class="form-label">Client Name</label>
                                        <input type="text" id="edit-c-name" name="name" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit-c-email" class="form-label">Email</label>
                                        <input type="email" id="edit-c-email" name="email" class="form-control" required>
                                    </div>
                                </div>
                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.75rem;">
                                    <div class="form-group">
                                        <label for="edit-c-phone" class="form-label">Phone</label>
                                        <input type="text" id="edit-c-phone" name="phone" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="edit-c-type" class="form-label">Client Entity Type</label>
                                        <select id="edit-c-type" name="client_type" class="form-control">
                                            <option value="Individual">Individual / Proprietorship</option>
                                            <option value="Partnership">Partnership Firm</option>
                                            <option value="LLP">Limited Liability Partnership (LLP)</option>
                                            <option value="Private Limited">Private Limited Company</option>
                                            <option value="Public Limited">Public Limited Company</option>
                                            <option value="HUF">HUF</option>
                                            <option value="Trust">Trust / Society</option>
                                        </select>
                                    </div>
                                </div>
                                <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:0.75rem;">
                                    <div class="form-group">
                                        <label for="edit-c-pan" class="form-label">PAN Card</label>
                                        <input type="text" id="edit-c-pan" name="pan" class="form-control" placeholder="ABCDE1234F" pattern="[a-zA-Z]{5}[0-9]{4}[a-zA-Z]{1}" maxlength="10">
                                    </div>
                                    <div class="form-group">
                                        <label for="edit-c-gstin" class="form-label">GSTIN</label>
                                        <input type="text" id="edit-c-gstin" name="gstin" class="form-control" placeholder="27ABCDE1234F1Z5" pattern="[0-9]{2}[a-zA-Z]{5}[0-9]{4}[a-zA-Z]{1}[0-9a-zA-Z]{3}" maxlength="15">
                                    </div>
                                    <div class="form-group">
                                        <label for="edit-c-tan" class="form-label">TAN Number</label>
                                        <input type="text" id="edit-c-tan" name="tan" class="form-control" placeholder="ABCD12345E" pattern="[a-zA-Z]{4}[0-9]{5}[a-zA-Z]{1}" maxlength="10">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="edit-c-inc-date" class="form-label">Date of Birth / Incorporation</label>
                                    <input type="date" id="edit-c-inc-date" name="incorporation_date" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="edit-c-address" class="form-label">Registered Address</label>
                                    <textarea id="edit-c-address" name="address" class="form-control" rows="2" placeholder="Complete address..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem; padding: 0.75rem;">Save Changes</button>
                            </form>
                        </div>
                    </div>

                    <script>
                        function openEditClient(id, name, email, phone, pan, gstin, tan, address, client_type, incorporation_date) {
                            document.getElementById('edit-c-id').value = id;
                            document.getElementById('edit-c-name').value = name;
                            document.getElementById('edit-c-email').value = email;
                            document.getElementById('edit-c-phone').value = phone;
                            document.getElementById('edit-c-pan').value = pan || '';
                            document.getElementById('edit-c-gstin').value = gstin || '';
                            document.getElementById('edit-c-tan').value = tan || '';
                            document.getElementById('edit-c-address').value = address || '';
                            document.getElementById('edit-c-type').value = client_type || 'Individual';
                            document.getElementById('edit-c-inc-date').value = incorporation_date || '';
                            App.openModal('edit-client-modal');
                        }
                    </script>
            <?php 
                endif;
            ?>

            <!-- ================== EMPLOYEE MANAGEMENT ================== -->
            <?php elseif ($activeTab === 'staff'): 
                $staffList = Auth::getStaffList();
                
                $performanceList = [];
                $db = Database::getConnection();
                foreach ($staffList as $st) {
                    $stmtAssigned = $db->prepare("SELECT COUNT(*) as total FROM tasks WHERE assigned_to_user_id = :uid");
                    $stmtAssigned->execute(['uid' => $st['id']]);
                    $assignedCount = intval($stmtAssigned->fetch()['total'] ?? 0);
                    
                    $stmtDone = $db->prepare("SELECT COUNT(*) as total FROM tasks WHERE assigned_to_user_id = :uid AND status = 'completed'");
                    $stmtDone->execute(['uid' => $st['id']]);
                    $doneCount = intval($stmtDone->fetch()['total'] ?? 0);
                    
                    $stmtHrs = $db->prepare("SELECT SUM(hours_spent) as total FROM work_logs WHERE user_id = :uid");
                    $stmtHrs->execute(['uid' => $st['id']]);
                    $totalHrs = floatval($stmtHrs->fetch()['total'] ?? 0.0);

                    $score = $assignedCount > 0 ? round(($doneCount / $assignedCount) * 100) : 100;
                    
                    $empProfile = HRMS::getEmployeeDetails($st['id']);
                    
                    $performanceList[] = [
                        'id' => $st['id'],
                        'name' => $st['name'],
                        'email' => $st['email'],
                        'role' => $st['role'],
                        'registered' => $st['created_at'],
                        'assigned' => $assignedCount,
                        'completed' => $doneCount,
                        'hours' => $totalHrs,
                        'efficiency' => $score,
                        'hrms' => $empProfile
                    ];
                }
            ?>
                <!-- Employee List Table and Admin Add Tool -->
                <div style="display: flex; justify-content: space-between; align-items:center;">
                    <h2 style="font-size:1.25rem; font-weight:700;">Employee HR & Operations Dashboard</h2>
                    <button class="btn btn-primary" data-open-modal="add-staff-modal">
                        <i data-lucide="plus" style="width:16px;height:16px;"></i> Add Staff Member
                    </button>
                </div>

                <div class="card glass-card" style="margin-top:1rem;">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Employee Name</th>
                                    <th>Access Role</th>
                                    <th>HR details</th>
                                    <th>Salary (₹)</th>
                                    <th>Workload</th>
                                    <th>Efficiency Rate</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($performanceList as $perf): ?>
                                    <tr>
                                        <td style="font-weight: 700;"><?= htmlspecialchars($perf['name']) ?></td>
                                        <td>
                                            <span class="badge badge-<?= $perf['role'] === 'staff' ? 'progress' : 'completed' ?>">
                                                <?= str_replace('_', ' ', $perf['role']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($perf['hrms']): ?>
                                                <span style="font-weight:600;"><?= htmlspecialchars($perf['hrms']['designation']) ?></span>
                                                <div style="font-size:0.75rem; color:var(--text-muted);"><?= htmlspecialchars($perf['hrms']['department']) ?></div>
                                                <div style="font-size:0.70rem; color:var(--primary); font-weight:700;">Shift: <?= htmlspecialchars($perf['hrms']['shift'] ?? 'General Shift (09:00 AM - 06:00 PM)') ?></div>
                                            <?php else: ?>
                                                <span style="color:var(--text-muted); font-style:italic;">No HR details</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-weight:700; color:var(--success);">
                                            ₹<?= number_format($perf['hrms']['salary'] ?? 0.0, 2) ?>
                                        </td>
                                        <td>
                                            <span style="font-weight:700;"><?= $perf['assigned'] ?></span> tasks
                                        </td>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:0.5rem; width:150px;">
                                                <div class="progress-bar-container" style="flex:1; margin-top:0; height:8px;">
                                                    <div class="progress-bar-fill" style="width: <?= $perf['efficiency'] ?>%; background-color: <?= $perf['efficiency'] >= 80 ? 'var(--success)' : ($perf['efficiency'] >= 50 ? 'var(--warning)' : 'var(--danger)') ?>;"></div>
                                                </div>
                                                <span style="font-weight:700; font-size:0.85rem;"><?= $perf['efficiency'] ?>%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 0.5rem;">
                                                <button class="btn btn-primary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem; display:inline-flex; align-items:center; gap:0.25rem;" onclick="openHRMSModal(<?= $perf['id'] ?>, '<?= addslashes($perf['name']) ?>', '<?= addslashes($perf['email']) ?>', '<?= addslashes($perf['hrms']['department'] ?? '') ?>', '<?= addslashes($perf['hrms']['designation'] ?? '') ?>', '<?= $perf['hrms']['joining_date'] ?? date('Y-m-d') ?>', <?= floatval($perf['hrms']['salary'] ?? 0.0) ?>, '<?= $perf['hrms']['status'] ?? 'active' ?>', <?= floatval($perf['hrms']['basic'] ?? 0.0) ?>, <?= floatval($perf['hrms']['hra'] ?? 0.0) ?>, <?= floatval($perf['hrms']['conveyance'] ?? 0.0) ?>, <?= floatval($perf['hrms']['allowance'] ?? 0.0) ?>, <?= floatval($perf['hrms']['pf'] ?? 0.0) ?>, <?= floatval($perf['hrms']['pt'] ?? 0.0) ?>, <?= floatval($perf['hrms']['tds'] ?? 0.0) ?>, '<?= addslashes($perf['hrms']['shift'] ?? 'General Shift (09:00 AM - 06:00 PM)') ?>')">
                                                    <i data-lucide="clock" style="width:12px; height:12px;"></i> Assign Shift & HR
                                                </button>
                                                <button class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;" onclick="openEditStaff(<?= $perf['id'] ?>, '<?= addslashes($perf['name']) ?>', '<?= addslashes($perf['email']) ?>', '<?= $perf['role'] ?>')">
                                                    Edit Auth
                                                </button>
                                                <form action="index.php?tab=staff" method="POST" onsubmit="return confirm('Delete this user?')" style="margin: 0;">
                                                    <input type="hidden" name="action" value="delete_staff">
                                                    <input type="hidden" name="id" value="<?= $perf['id'] ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                    <button type="submit" class="btn btn-danger" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add Staff Modal -->
                <div class="modal-overlay" id="add-staff-modal">
                    <div class="modal-container">
                        <div class="modal-header">
                            <h3 style="font-size: 1.15rem; font-weight: 700;">Add Staff Member</h3>
                            <button class="modal-close" data-close-modal="add-staff-modal">&times;</button>
                        </div>
                        <form action="index.php?tab=staff" method="POST">
                            <input type="hidden" name="action" value="add_staff">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <div class="form-group">
                                <label for="s-name" class="form-label">Full Name</label>
                                <input type="text" id="s-name" name="name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="s-email" class="form-label">Email (Login ID)</label>
                                <input type="email" id="s-email" name="email" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="s-pass" class="form-label">Password</label>
                                <input type="password" id="s-pass" name="password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="s-pin" class="form-label">4-Digit Security PIN</label>
                                <input type="password" id="s-pin" name="pin" class="form-control" maxlength="4" placeholder="e.g. 1234" required>
                            </div>
                            <div class="form-group">
                                <label for="s-role" class="form-label">Role</label>
                                <select id="s-role" name="role" class="form-control">
                                    <option value="staff">Staff member</option>
                                    <option value="admin_manager">Admin Manager</option>
                                    <option value="super_admin">Super Admin</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem; padding: 0.75rem;">Create Staff</button>
                        </form>
                    </div>
                </div>

                <!-- Edit Staff Modal -->
                <div class="modal-overlay" id="edit-staff-modal">
                    <div class="modal-container">
                        <div class="modal-header">
                            <h3 style="font-size: 1.15rem; font-weight: 700;">Edit Staff Details</h3>
                            <button class="modal-close" data-close-modal="edit-staff-modal">&times;</button>
                        </div>
                        <form action="index.php?tab=staff" method="POST">
                            <input type="hidden" name="action" value="edit_staff">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" id="edit-s-id" name="id">
                            <div class="form-group">
                                <label for="edit-s-name" class="form-label">Full Name</label>
                                <input type="text" id="edit-s-name" name="name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="edit-s-email" class="form-label">Email</label>
                                <input type="email" id="edit-s-email" name="email" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="edit-s-pass" class="form-label">New Password (leave blank to keep current)</label>
                                <input type="password" id="edit-s-pass" name="password" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="edit-s-pin" class="form-label">New 4-Digit Security PIN (leave blank to keep)</label>
                                <input type="password" id="edit-s-pin" name="pin" class="form-control" maxlength="4">
                            </div>
                            <div class="form-group">
                                <label for="edit-s-role" class="form-label">Role</label>
                                <select id="edit-s-role" name="role" class="form-control">
                                    <option value="staff">Staff member</option>
                                    <option value="admin_manager">Admin Manager</option>
                                    <option value="super_admin">Super Admin</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem; padding: 0.75rem;">Save Changes</button>
                        </form>
                    </div>
                </div>

                <!-- Update HRMS Details Modal -->
                <div class="modal-overlay" id="hrms-staff-modal">
                    <div class="modal-container">
                        <div class="modal-header">
                            <h3 style="font-size: 1.15rem; font-weight: 700;">HR & Payroll Details</h3>
                            <button class="modal-close" data-close-modal="hrms-staff-modal">&times;</button>
                        </div>
                        <form action="index.php?tab=staff" method="POST">
                            <div style="max-height: 400px; overflow-y: auto; padding-right: 8px; margin-bottom: 1rem;">
                                <input type="hidden" name="action" value="update_employee_profile">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" id="hrms-s-id" name="user_id">
                                
                                <div class="form-group">
                                    <label class="form-label">Employee Name</label>
                                    <input type="text" id="hrms-s-name" readonly class="form-control" style="background-color:var(--bg-card);">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Employee Email ID</label>
                                    <input type="text" id="hrms-s-email" readonly class="form-control" style="background-color:var(--bg-card);">
                                </div>
                                <div class="form-group">
                                    <label for="hrms-s-dept" class="form-label">Department</label>
                                    <input type="text" id="hrms-s-dept" name="department" class="form-control" placeholder="e.g. Audit, Taxation" required>
                                </div>
                                <div class="form-group">
                                    <label for="hrms-s-desig" class="form-label">Designation</label>
                                    <input type="text" id="hrms-s-desig" name="designation" class="form-control" placeholder="e.g. Senior Tax Consultant" required>
                                </div>
                                <div class="form-group">
                                    <label for="hrms-s-join" class="form-label">Joining Date</label>
                                    <input type="date" id="hrms-s-join" name="joining_date" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="hrms-s-salary" class="form-label">Monthly Gross Salary (₹)</label>
                                    <input type="number" id="hrms-s-salary" name="salary" step="0.01" min="0.00" class="form-control" required>
                                </div>
                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.5rem; background:var(--bg-input); padding:0.75rem; border-radius:var(--radius-md); margin-bottom:1rem; box-shadow: var(--clay-shadow-input);">
                                    <div class="form-group" style="margin-bottom:0.5rem;">
                                        <label class="form-label" style="font-size:0.75rem;">Basic (₹)</label>
                                        <input type="number" id="hrms-s-basic" name="basic" step="0.01" value="0.00" class="form-control" required style="padding:0.4rem;">
                                    </div>
                                    <div class="form-group" style="margin-bottom:0.5rem;">
                                        <label class="form-label" style="font-size:0.75rem;">HRA (₹)</label>
                                        <input type="number" id="hrms-s-hra" name="hra" step="0.01" value="0.00" class="form-control" required style="padding:0.4rem;">
                                    </div>
                                    <div class="form-group" style="margin-bottom:0.5rem;">
                                        <label class="form-label" style="font-size:0.75rem;">Conveyance (₹)</label>
                                        <input type="number" id="hrms-s-conveyance" name="conveyance" step="0.01" value="0.00" class="form-control" required style="padding:0.4rem;">
                                    </div>
                                    <div class="form-group" style="margin-bottom:0.5rem;">
                                        <label class="form-label" style="font-size:0.75rem;">Special Allowance (₹)</label>
                                        <input type="number" id="hrms-s-allowance" name="allowance" step="0.01" value="0.00" class="form-control" required style="padding:0.4rem;">
                                    </div>
                                    <div class="form-group" style="margin-bottom:0.5rem;">
                                        <label class="form-label" style="font-size:0.75rem;">PF Deduct (₹)</label>
                                        <input type="number" id="hrms-s-pf" name="pf" step="0.01" value="0.00" class="form-control" required style="padding:0.4rem;">
                                    </div>
                                    <div class="form-group" style="margin-bottom:0.5rem;">
                                        <label class="form-label" style="font-size:0.75rem;">PT Deduct (₹)</label>
                                        <input type="number" id="hrms-s-pt" name="pt" step="0.01" value="0.00" class="form-control" required style="padding:0.4rem;">
                                    </div>
                                    <div class="form-group" style="grid-column: span 2; margin-bottom:0;">
                                        <label class="form-label" style="font-size:0.75rem;">TDS Deduct (₹)</label>
                                        <input type="number" id="hrms-s-tds" name="tds" step="0.01" value="0.00" class="form-control" required style="padding:0.4rem;">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="hrms-s-status" class="form-label">Employee Status</label>
                                    <select id="hrms-s-status" name="status" class="form-control">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem; padding: 0.75rem;">Save Employee Record</button>
                        </form>
                    </div>
                </div>

                <script>
                    function openEditStaff(id, name, email, role) {
                        document.getElementById('edit-s-id').value = id;
                        document.getElementById('edit-s-name').value = name;
                        document.getElementById('edit-s-email').value = email;
                        document.getElementById('edit-s-role').value = role;
                        App.openModal('edit-staff-modal');
                    }
                    function openHRMSModal(id, name, email, dept, desig, joining, salary, status, basic, hra, conveyance, allowance, pf, pt, tds, shift) {
                        document.getElementById('hrms-s-id').value = id;
                        document.getElementById('hrms-s-name').value = name;
                        document.getElementById('hrms-s-email').value = email;
                        document.getElementById('hrms-s-dept').value = dept;
                        document.getElementById('hrms-s-desig').value = desig;
                        document.getElementById('hrms-s-join').value = joining;
                        document.getElementById('hrms-s-salary').value = salary;
                        document.getElementById('hrms-s-status').value = status;
                        document.getElementById('hrms-s-basic').value = basic || '0.00';
                        document.getElementById('hrms-s-hra').value = hra || '0.00';
                        document.getElementById('hrms-s-conveyance').value = conveyance || '0.00';
                        document.getElementById('hrms-s-allowance').value = allowance || '0.00';
                        document.getElementById('hrms-s-pf').value = pf || '0.00';
                        document.getElementById('hrms-s-pt').value = pt || '0.00';
                        document.getElementById('hrms-s-tds').value = tds || '0.00';

                        App.openModal('hrms-staff-modal');
                    }
                </script>

            <!-- ================== TASK BOARD TAB ================== -->
            <?php elseif ($activeTab === 'tasks'): 
                $staffOptions = Auth::getStaffList();
                $clientOptions = Client::getClients();
                
                $filters = [];
                if (!$isAdmin) {
                    $filters['assigned_to'] = $user['id'];
                } else {
                    $filters['assigned_to'] = trim($_GET['assigned_to'] ?? '');
                }
                $filters['status'] = trim($_GET['status'] ?? '');
                $filters['priority'] = trim($_GET['priority'] ?? '');
                
                $taskList = Task::getTasks($filters);
                $layoutMode = trim($_GET['layout'] ?? 'table');
            ?>
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
                    <div style="display: flex; gap: 0.75rem;">
                        <?php if ($isAdmin): ?>
                            <button class="btn btn-primary" data-open-modal="add-task-modal">
                                <i data-lucide="plus" style="width:16px;height:16px;"></i> Create Task
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($layoutMode === 'kanban'): ?>
                            <a href="index.php?tab=tasks&layout=table&assigned_to=<?= urlencode($_GET['assigned_to'] ?? '') ?>&status=<?= urlencode($_GET['status'] ?? '') ?>&priority=<?= urlencode($_GET['priority'] ?? '') ?>" class="btn btn-secondary">
                                <i data-lucide="list" style="width:16px;height:16px;"></i> Switch Table View
                            </a>
                        <?php else: ?>
                            <a href="index.php?tab=tasks&layout=kanban&assigned_to=<?= urlencode($_GET['assigned_to'] ?? '') ?>&status=<?= urlencode($_GET['status'] ?? '') ?>&priority=<?= urlencode($_GET['priority'] ?? '') ?>" class="btn btn-secondary">
                                <i data-lucide="kanban" style="width:16px;height:16px;"></i> Switch Kanban View
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Filter interface -->
                    <form action="index.php" method="GET" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <input type="hidden" name="tab" value="tasks">
                        <input type="hidden" name="layout" value="<?= $layoutMode ?>">
                        
                        <?php if ($isAdmin): ?>
                            <select name="assigned_to" class="form-control" style="width: 150px; padding: 0.5rem;" onchange="this.form.submit()">
                                <option value="">Filter: All Staff</option>
                                <?php foreach ($staffOptions as $opt): ?>
                                    <option value="<?= $opt['id'] ?>" <?= (($_GET['assigned_to'] ?? '') == $opt['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($opt['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>

                        <select name="status" class="form-control" style="width: 140px; padding: 0.5rem;" onchange="this.form.submit()">
                            <option value="">Filter: Status</option>
                            <option value="pending" <?= (($_GET['status'] ?? '') === 'pending') ? 'selected' : '' ?>>Pending</option>
                            <option value="in_progress" <?= (($_GET['status'] ?? '') === 'in_progress') ? 'selected' : '' ?>>In Progress</option>
                            <option value="completed" <?= (($_GET['status'] ?? '') === 'completed') ? 'selected' : '' ?>>Completed</option>
                        </select>

                        <select name="priority" class="form-control" style="width: 140px; padding: 0.5rem;" onchange="this.form.submit()">
                            <option value="">Filter: Priority</option>
                            <option value="low" <?= (($_GET['priority'] ?? '') === 'low') ? 'selected' : '' ?>>Low</option>
                            <option value="medium" <?= (($_GET['priority'] ?? '') === 'medium') ? 'selected' : '' ?>>Medium</option>
                            <option value="high" <?= (($_GET['priority'] ?? '') === 'high') ? 'selected' : '' ?>>High</option>
                        </select>
                        <a href="index.php?tab=tasks&layout=<?= $layoutMode ?>" class="btn btn-secondary" style="padding: 0.5rem 1rem;">Clear</a>
                    </form>
                </div>

                <!-- TABLE MODE -->
                <?php if ($layoutMode !== 'kanban'): ?>
                    <div class="card glass-card" style="margin-top:1rem;">
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Task Title</th>
                                        <th>Category</th>
                                        <th>Client / Firm</th>
                                        <th>Assigned Staff</th>
                                        <th>Due Date</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($taskList)): ?>
                                        <tr>
                                            <td colspan="8" style="text-align: center; color: var(--text-muted);">No tasks match the active filters.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($taskList as $t): ?>
                                            <tr>
                                                <td style="font-weight: 700;">
                                                    <div><?= htmlspecialchars($t['title']) ?></div>
                                                    <?php if (!empty($t['financial_year']) || !empty($t['periodicity'])): ?>
                                                        <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal; margin-top: 0.15rem;">
                                                            <?= htmlspecialchars($t['financial_year']) ?> <?= !empty($t['assessment_year']) ? '('.htmlspecialchars($t['assessment_year']).')' : '' ?> • <?= htmlspecialchars($t['periodicity']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div><?= htmlspecialchars($t['category']) ?></div>
                                                    <?php if ($t['estimated_fees'] > 0): ?>
                                                        <div style="font-size: 0.75rem; color: var(--success); font-weight: 600; margin-top: 0.15rem;">
                                                            Est: ₹<?= number_format($t['estimated_fees'], 0) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($t['client_name']) ?></td>
                                                <td><?= htmlspecialchars($t['staff_name'] ?: 'Unassigned') ?></td>
                                                <td><?= htmlspecialchars($t['due_date'] ?: 'N/A') ?></td>
                                                <td>
                                                    <span style="font-weight:700; font-size: 0.8rem; text-transform:uppercase; color: <?= $t['priority'] === 'high' ? 'var(--danger)' : ($t['priority'] === 'medium' ? 'var(--warning)' : 'var(--text-muted)') ?>">
                                                        <?= htmlspecialchars($t['priority']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?= $t['status'] === 'in_progress' ? 'progress' : $t['status'] ?>">
                                                        <?= str_replace('_', ' ', $t['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                                                        <button class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;" onclick="openWorkLog(<?= $t['id'] ?>, '<?= addslashes($t['title']) ?>')">
                                                            Log Work
                                                        </button>
                                                        
                                                        <?php if ($isAdmin): ?>
                                                            <button class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;" onclick="openEditTask(<?= $t['id'] ?>, <?= $t['client_id'] ?>, <?= intval($t['assigned_to_user_id']) ?>, '<?= addslashes($t['title']) ?>', '<?= addslashes($t['description']) ?>', '<?= $t['status'] ?>', '<?= $t['priority'] ?>', '<?= addslashes($t['category']) ?>', '<?= $t['due_date'] ?>', '<?= addslashes($t['financial_year'] ?? '') ?>', '<?= addslashes($t['assessment_year'] ?? '') ?>', '<?= addslashes($t['periodicity'] ?? '') ?>', '<?= addslashes($t['estimated_fees'] ?? '') ?>')">
                                                                Edit
                                                            </button>
                                                            <form action="index.php?tab=tasks" method="POST" onsubmit="return confirm('Delete this task?')" style="margin: 0;">
                                                                <input type="hidden" name="action" value="delete_task">
                                                                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                                <button type="submit" class="btn btn-danger" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                                                                    Delete
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <form action="index.php?tab=tasks" method="POST" style="margin: 0;">
                                                                <input type="hidden" name="action" value="update_task_status">
                                                                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                                <select name="status" class="form-control" style="font-size: 0.75rem; padding: 0.25rem 0.45rem; width:110px;" onchange="this.form.submit()">
                                                                    <option value="pending" <?= $t['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                                    <option value="in_progress" <?= $t['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                                                    <option value="completed" <?= $t['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                                                </select>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                
                <!-- KANBAN MODE -->
                <?php else: 
                    $kanbanCols = [
                        'pending' => [],
                        'in_progress' => [],
                        'completed' => [],
                        'overdue' => []
                    ];
                    
                    foreach ($taskList as $tk) {
                        $dueDateStamp = $tk['due_date'] ? strtotime($tk['due_date']) : null;
                        if ($tk['status'] !== 'completed' && $dueDateStamp && $dueDateStamp < strtotime(date('Y-m-d'))) {
                            $kanbanCols['overdue'][] = $tk;
                        } else {
                            $kanbanCols[$tk['status']][] = $tk;
                        }
                    }
                ?>
                    <div class="kanban-container" style="margin-top:1.5rem;">
                        <!-- PENDING COLUMN -->
                        <div class="kanban-column">
                            <div class="kanban-header">
                                <span>Pending / Backlog</span>
                                <span class="kanban-count"><?= count($kanbanCols['pending']) ?></span>
                            </div>
                            <div class="kanban-cards">
                                <?php foreach ($kanbanCols['pending'] as $cTask): ?>
                                    <div class="kanban-card priority-<?= $cTask['priority'] ?>">
                                        <div class="kanban-card-title"><?= htmlspecialchars($cTask['title']) ?></div>
                                        <div style="font-size:0.8rem; color:var(--text-muted);"><?= htmlspecialchars($cTask['client_name']) ?></div>
                                        
                                        <div class="kanban-card-meta">
                                            <span><i data-lucide="user" style="width:12px; height:12px; vertical-align:middle; margin-right:2px;"></i><?= htmlspecialchars($cTask['staff_name'] ?: 'Unassigned') ?></span>
                                            <span style="color:var(--warning);"><?= htmlspecialchars($cTask['due_date'] ?: 'No Due') ?></span>
                                        </div>
                                        
                                        <form action="index.php?tab=tasks&layout=kanban" method="POST" style="margin-top:0.75rem; border-top:1px solid var(--bg-base); padding-top:0.5rem;">
                                            <input type="hidden" name="action" value="update_task_status">
                                            <input type="hidden" name="id" value="<?= $cTask['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                            <select name="status" class="form-control" style="font-size:0.75rem; padding:0.2rem; height:auto;" onchange="this.form.submit()">
                                                <option value="pending" selected>Pending</option>
                                                <option value="in_progress">Start Task</option>
                                                <option value="completed">Complete</option>
                                            </select>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- IN PROGRESS COLUMN -->
                        <div class="kanban-column">
                            <div class="kanban-header">
                                <span>In Progress</span>
                                <span class="kanban-count"><?= count($kanbanCols['in_progress']) ?></span>
                            </div>
                            <div class="kanban-cards">
                                <?php foreach ($kanbanCols['in_progress'] as $cTask): ?>
                                    <div class="kanban-card priority-<?= $cTask['priority'] ?>">
                                        <div class="kanban-card-title"><?= htmlspecialchars($cTask['title']) ?></div>
                                        <div style="font-size:0.8rem; color:var(--text-muted);"><?= htmlspecialchars($cTask['client_name']) ?></div>
                                        
                                        <div class="kanban-card-meta">
                                            <span><i data-lucide="user" style="width:12px; height:12px; vertical-align:middle; margin-right:2px;"></i><?= htmlspecialchars($cTask['staff_name'] ?: 'Unassigned') ?></span>
                                            <span><?= htmlspecialchars($cTask['due_date'] ?: 'No Due') ?></span>
                                        </div>
                                        
                                        <form action="index.php?tab=tasks&layout=kanban" method="POST" style="margin-top:0.75rem; border-top:1px solid var(--bg-base); padding-top:0.5rem;">
                                            <input type="hidden" name="action" value="update_task_status">
                                            <input type="hidden" name="id" value="<?= $cTask['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                            <select name="status" class="form-control" style="font-size:0.75rem; padding:0.2rem; height:auto;" onchange="this.form.submit()">
                                                <option value="pending">Pending</option>
                                                <option value="in_progress" selected>In Progress</option>
                                                <option value="completed">Complete</option>
                                            </select>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- COMPLETED COLUMN -->
                        <div class="kanban-column">
                            <div class="kanban-header">
                                <span>Completed</span>
                                <span class="kanban-count"><?= count($kanbanCols['completed']) ?></span>
                            </div>
                            <div class="kanban-cards">
                                <?php foreach ($kanbanCols['completed'] as $cTask): ?>
                                    <div class="kanban-card priority-<?= $cTask['priority'] ?>" style="opacity:0.75;">
                                        <div class="kanban-card-title" style="text-decoration:line-through;"><?= htmlspecialchars($cTask['title']) ?></div>
                                        <div style="font-size:0.8rem; color:var(--text-muted);"><?= htmlspecialchars($cTask['client_name']) ?></div>
                                        
                                        <div class="kanban-card-meta">
                                            <span><i data-lucide="user" style="width:12px; height:12px; vertical-align:middle; margin-right:2px;"></i><?= htmlspecialchars($cTask['staff_name'] ?: 'Unassigned') ?></span>
                                            <span><?= htmlspecialchars($cTask['due_date'] ?: 'No Due') ?></span>
                                        </div>
                                        
                                        <form action="index.php?tab=tasks&layout=kanban" method="POST" style="margin-top:0.75rem; border-top:1px solid var(--bg-base); padding-top:0.5rem;">
                                            <input type="hidden" name="action" value="update_task_status">
                                            <input type="hidden" name="id" value="<?= $cTask['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                            <select name="status" class="form-control" style="font-size:0.75rem; padding:0.2rem; height:auto;" onchange="this.form.submit()">
                                                <option value="pending">Re-open (Pending)</option>
                                                <option value="in_progress">Re-open (In Progress)</option>
                                                <option value="completed" selected>Completed</option>
                                            </select>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- OVERDUE COLUMN -->
                        <div class="kanban-column">
                            <div class="kanban-header">
                                <span style="color:var(--danger);">Overdue Tasks</span>
                                <span class="kanban-count" style="background-color:var(--danger-glow); color:var(--danger);"><?= count($kanbanCols['overdue']) ?></span>
                            </div>
                            <div class="kanban-cards">
                                <?php foreach ($kanbanCols['overdue'] as $cTask): ?>
                                    <div class="kanban-card priority-high" style="border-left-color: var(--danger);">
                                        <div class="kanban-card-title"><?= htmlspecialchars($cTask['title']) ?></div>
                                        <div style="font-size:0.8rem; color:var(--text-muted);"><?= htmlspecialchars($cTask['client_name']) ?></div>
                                        
                                        <div class="kanban-card-meta">
                                            <span><i data-lucide="user" style="width:12px; height:12px; vertical-align:middle; margin-right:2px;"></i><?= htmlspecialchars($cTask['staff_name'] ?: 'Unassigned') ?></span>
                                            <span style="color:var(--danger); font-weight:700;"><?= htmlspecialchars($cTask['due_date']) ?></span>
                                        </div>
                                        
                                        <form action="index.php?tab=tasks&layout=kanban" method="POST" style="margin-top:0.75rem; border-top:1px solid var(--bg-base); padding-top:0.5rem;">
                                            <input type="hidden" name="action" value="update_task_status">
                                            <input type="hidden" name="id" value="<?= $cTask['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                            <select name="status" class="form-control" style="font-size:0.75rem; padding:0.2rem; height:auto;" onchange="this.form.submit()">
                                                <option value="pending" selected>Pending</option>
                                                <option value="in_progress">Start Task</option>
                                                <option value="completed">Complete</option>
                                            </select>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Add Task Modal -->
                <div class="modal-overlay" id="add-task-modal">
                    <div class="modal-container" style="max-width: 550px;">
                        <div class="modal-header">
                            <h3 style="font-size: 1.15rem; font-weight: 700;">Create Task</h3>
                            <button class="modal-close" data-close-modal="add-task-modal">&times;</button>
                        </div>
                        <form action="index.php?tab=tasks" method="POST" style="display:grid; gap:0.75rem;">
                            <input type="hidden" name="action" value="add_task">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.75rem;">
                                <div class="form-group">
                                    <label for="t-client" class="form-label">Client / Entity</label>
                                    <select id="t-client" name="client_id" class="form-control" required>
                                        <option value="">Select client...</option>
                                        <?php foreach ($clientOptions as $c): ?>
                                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="t-assigned" class="form-label">Assign To</label>
                                    <select id="t-assigned" name="assigned_to" class="form-control">
                                        <option value="">Unassigned</option>
                                        <?php foreach ($staffOptions as $s): ?>
                                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="t-title" class="form-label">Task Title</label>
                                <input type="text" id="t-title" name="title" class="form-control" required placeholder="e.g. GST GSTR-3B Return Filing">
                            </div>
                            <div class="form-group">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.25rem;">
                                    <label for="t-desc" class="form-label" style="margin:0;">Description / Instructions</label>
                                    <button type="button" class="btn btn-secondary" style="font-size:0.7rem; padding:0.15rem 0.4rem; display:flex; align-items:center; gap:0.25rem;" onclick="suggestAISubtasks()">
                                        <i data-lucide="sparkles" style="width:12px; height:12px; color:var(--primary);"></i> AI Checklist
                                    </button>
                                </div>
                                <textarea id="t-desc" name="description" class="form-control" rows="2"></textarea>
                            </div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.75rem;">
                                <div class="form-group">
                                    <label for="t-category" class="form-label">Category</label>
                                    <input type="text" id="t-category" name="category" class="form-control" placeholder="GST, TDS, ROC, Audit" required>
                                </div>
                                <div class="form-group">
                                    <label for="t-periodicity" class="form-label">Periodicity</label>
                                    <select id="t-periodicity" name="periodicity" class="form-control">
                                        <option value="One-Time" selected>One-Time</option>
                                        <option value="Monthly">Monthly</option>
                                        <option value="Quarterly">Quarterly</option>
                                        <option value="Half-Yearly">Half-Yearly</option>
                                        <option value="Annually">Annually</option>
                                    </select>
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.75rem;">
                                <div class="form-group">
                                    <label for="t-fy" class="form-label">Financial Year (FY)</label>
                                    <select id="t-fy" name="financial_year" class="form-control">
                                        <option value="">N/A</option>
                                        <option value="FY 2026-27">FY 2026-27</option>
                                        <option value="FY 2025-26" selected>FY 2025-26</option>
                                        <option value="FY 2024-25">FY 2024-25</option>
                                        <option value="FY 2023-24">FY 2023-24</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="t-ay" class="form-label">Assessment Year (AY)</label>
                                    <select id="t-ay" name="assessment_year" class="form-control">
                                        <option value="">N/A</option>
                                        <option value="AY 2027-28">AY 2027-28</option>
                                        <option value="AY 2026-27" selected>AY 2026-27</option>
                                        <option value="AY 2025-26">AY 2025-26</option>
                                        <option value="AY 2024-25">AY 2024-25</option>
                                    </select>
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:0.75rem;">
                                <div class="form-group">
                                    <label for="t-priority" class="form-label">Priority</label>
                                    <select id="t-priority" name="priority" class="form-control">
                                        <option value="low">Low</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="t-due" class="form-label">Due Date</label>
                                    <input type="date" id="t-due" name="due_date" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="t-fees" class="form-label">Est. Fees (₹)</label>
                                    <input type="number" id="t-fees" name="estimated_fees" class="form-control" placeholder="5000">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem; padding: 0.75rem;">Create Task</button>
                        </form>
                    </div>
                </div>

                <!-- Edit Task Modal -->
                <div class="modal-overlay" id="edit-task-modal">
                    <div class="modal-container" style="max-width: 550px;">
                        <div class="modal-header">
                            <h3 style="font-size: 1.15rem; font-weight: 700;">Edit Task</h3>
                            <button class="modal-close" data-close-modal="edit-task-modal">&times;</button>
                        </div>
                        <form action="index.php?tab=tasks" method="POST" style="display:grid; gap:0.75rem;">
                            <input type="hidden" name="action" value="edit_task">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" id="edit-t-id" name="id">
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.75rem;">
                                <div class="form-group">
                                    <label for="edit-t-client" class="form-label">Client</label>
                                    <select id="edit-t-client" name="client_id" class="form-control" required>
                                        <?php foreach ($clientOptions as $c): ?>
                                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="edit-t-assigned" class="form-label">Assign To</label>
                                    <select id="edit-t-assigned" name="assigned_to" class="form-control">
                                        <option value="">Unassigned</option>
                                        <?php foreach ($staffOptions as $s): ?>
                                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="edit-t-title" class="form-label">Task Title</label>
                                <input type="text" id="edit-t-title" name="title" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="edit-t-desc" class="form-label">Description</label>
                                <textarea id="edit-t-desc" name="description" class="form-control" rows="2"></textarea>
                            </div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:0.75rem;">
                                <div class="form-group">
                                    <label for="edit-t-status" class="form-label">Status</label>
                                    <select id="edit-t-status" name="status" class="form-control">
                                        <option value="pending">Pending</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="completed">Completed</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="edit-t-category" class="form-label">Category</label>
                                    <input type="text" id="edit-t-category" name="category" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit-t-periodicity" class="form-label">Periodicity</label>
                                    <select id="edit-t-periodicity" name="periodicity" class="form-control">
                                        <option value="One-Time">One-Time</option>
                                        <option value="Monthly">Monthly</option>
                                        <option value="Quarterly">Quarterly</option>
                                        <option value="Half-Yearly">Half-Yearly</option>
                                        <option value="Annually">Annually</option>
                                    </select>
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.75rem;">
                                <div class="form-group">
                                    <label for="edit-t-fy" class="form-label">Financial Year (FY)</label>
                                    <select id="edit-t-fy" name="financial_year" class="form-control">
                                        <option value="">N/A</option>
                                        <option value="FY 2026-27">FY 2026-27</option>
                                        <option value="FY 2025-26">FY 2025-26</option>
                                        <option value="FY 2024-25">FY 2024-25</option>
                                        <option value="FY 2023-24">FY 2023-24</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="edit-t-ay" class="form-label">Assessment Year (AY)</label>
                                    <select id="edit-t-ay" name="assessment_year" class="form-control">
                                        <option value="">N/A</option>
                                        <option value="AY 2027-28">AY 2027-28</option>
                                        <option value="AY 2026-27">AY 2026-27</option>
                                        <option value="AY 2025-26">AY 2025-26</option>
                                        <option value="AY 2024-25">AY 2024-25</option>
                                    </select>
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:0.75rem;">
                                <div class="form-group">
                                    <label for="edit-t-priority" class="form-label">Priority</label>
                                    <select id="edit-t-priority" name="priority" class="form-control">
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="edit-t-due" class="form-label">Due Date</label>
                                    <input type="date" id="edit-t-due" name="due_date" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="edit-t-fees" class="form-label">Est. Fees (₹)</label>
                                    <input type="number" id="edit-t-fees" name="estimated_fees" class="form-control">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem; padding: 0.75rem;">Save Changes</button>
                        </form>
                    </div>
                </div>

                <!-- Log Work Hours Modal -->
                <div class="modal-overlay" id="log-work-modal">
                    <div class="modal-container">
                        <div class="modal-header">
                            <h3 style="font-size: 1.15rem; font-weight: 700;">Log Work Hours</h3>
                            <button class="modal-close" data-close-modal="log-work-modal">&times;</button>
                        </div>
                        <form action="index.php?tab=tasks" method="POST">
                            <input type="hidden" name="action" value="log_work">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" id="log-w-task-id" name="task_id">
                            <div class="form-group">
                                <label class="form-label">Task</label>
                                <input type="text" id="log-w-task-title" readonly class="form-control" style="background-color: var(--bg-card);">
                            </div>
                            <div class="form-group">
                                <label for="log-w-date" class="form-label">Date Worked</label>
                                <input type="date" id="log-w-date" name="log_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="log-w-hours" class="form-label">Hours Spent</label>
                                <input type="number" id="log-w-hours" name="hours" step="0.25" min="0.25" max="24" class="form-control" placeholder="e.g. 2.5" required>
                            </div>
                            <div class="form-group">
                                <label for="log-w-desc" class="form-label">Activity Description</label>
                                <textarea id="log-w-desc" name="description" class="form-control" rows="3" placeholder="Describe the specific work completed..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem; padding: 0.75rem;">Submit Work Log</button>
                        </form>
                    </div>
                </div>

                <script>
                    function openEditTask(id, client, staff, title, desc, status, priority, category, due, fy, ay, periodicity, fees) {
                        document.getElementById('edit-t-id').value = id;
                        document.getElementById('edit-t-client').value = client;
                        document.getElementById('edit-t-assigned').value = staff;
                        document.getElementById('edit-t-title').value = title;
                        document.getElementById('edit-t-desc').value = desc;
                        document.getElementById('edit-t-status').value = status;
                        document.getElementById('edit-t-priority').value = priority;
                        document.getElementById('edit-t-category').value = category;
                        document.getElementById('edit-t-due').value = due;
                        document.getElementById('edit-t-fy').value = fy || '';
                        document.getElementById('edit-t-ay').value = ay || '';
                        document.getElementById('edit-t-periodicity').value = periodicity || 'One-Time';
                        document.getElementById('edit-t-fees').value = fees || '';
                        App.openModal('edit-task-modal');
                    }
                    function openWorkLog(taskId, taskTitle) {
                        document.getElementById('log-w-task-id').value = taskId;
                        document.getElementById('log-w-task-title').value = taskTitle;
                        App.openModal('log-work-modal');
                    }
                </script>

            <!-- ================== COMPLIANCE TRACKER TAB ================== -->
            <?php elseif ($activeTab === 'compliances'): 
                $clientOptions = Client::getClients();
                
                $filters = [];
                $filters['status'] = trim($_GET['c_status'] ?? '');
                $filters['category'] = trim($_GET['c_category'] ?? '');
                
                $compliancesList = Compliance::getCompliances($filters);
            ?>
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                    <div style="display:flex; gap:0.5rem; align-items:center;">
                        <button class="btn btn-primary" data-open-modal="add-compliance-modal">
                            <i data-lucide="plus" style="width:16px;height:16px;vertical-align:middle;margin-right:2px;"></i> Log New Return
                        </button>
                        <form action="index.php?tab=compliances" method="POST" style="margin:0;">
                            <input type="hidden" name="action" value="trigger_automation_run">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <button type="submit" class="btn btn-secondary">
                                <i data-lucide="play-circle" style="width:16px;height:16px;vertical-align:middle;margin-right:2px;color:var(--success);"></i> Run Automation Engine
                            </button>
                        </form>
                    </div>

                    <!-- Filters -->
                    <form action="index.php" method="GET" style="display:flex; gap:0.5rem; margin:0;">
                        <input type="hidden" name="tab" value="compliances">
                        <select name="c_category" class="form-control" style="width:140px;" onchange="this.form.submit()">
                            <option value="">Filter Category</option>
                            <option value="GST Return" <?= ($_GET['c_category'] ?? '') === 'GST Return' ? 'selected' : '' ?>>GST Return</option>
                            <option value="TDS Return" <?= ($_GET['c_category'] ?? '') === 'TDS Return' ? 'selected' : '' ?>>TDS Return</option>
                            <option value="ITR" <?= ($_GET['c_category'] ?? '') === 'ITR' ? 'selected' : '' ?>>ITR</option>
                            <option value="ROC" <?= ($_GET['c_category'] ?? '') === 'ROC' ? 'selected' : '' ?>>ROC</option>
                            <option value="Audit" <?= ($_GET['c_category'] ?? '') === 'Audit' ? 'selected' : '' ?>>Audit</option>
                        </select>

                        <select name="c_status" class="form-control" style="width:130px;" onchange="this.form.submit()">
                            <option value="">Filter Status</option>
                            <option value="pending" <?= ($_GET['c_status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="filed" <?= ($_GET['c_status'] ?? '') === 'filed' ? 'selected' : '' ?>>Filed</option>
                        </select>
                        <a href="index.php?tab=compliances" class="btn btn-secondary">Clear</a>
                    </form>
                </div>

                <div class="card glass-card" style="margin-top:1rem;">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Client / Entity</th>
                                    <th>Return Title</th>
                                    <th>Category</th>
                                    <th>Due Date</th>
                                    <th>Filing Date</th>
                                    <th>Ack Receipt #</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($compliancesList)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align:center; color:var(--text-muted);">No compliance tasks found matching criteria.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($compliancesList as $c): ?>
                                        <tr>
                                            <td style="font-weight:700;"><?= htmlspecialchars($c['client_name']) ?></td>
                                            <td>
                                                <div style="font-weight:700;"><?= htmlspecialchars($c['title']) ?></div>
                                                <?php if (!empty($c['financial_year']) || !empty($c['periodicity'])): ?>
                                                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal; margin-top: 0.15rem;">
                                                        <?= htmlspecialchars($c['financial_year']) ?> <?= !empty($c['assessment_year']) ? '('.htmlspecialchars($c['assessment_year']).')' : '' ?> • <?= htmlspecialchars($c['periodicity']) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!empty($c['client_response'])): ?>
                                                    <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.25rem;">
                                                        <span style="font-weight:600; color:var(--primary);">Client Response:</span> <?= htmlspecialchars($c['client_response']) ?>
                                                        <span style="font-size:0.75rem; color:var(--text-muted); font-style:italic;"> (<?= date('d M Y, h:i A', strtotime($c['client_responded_at'])) ?>)</span>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge badge-outline badge-<?= strtolower(explode(' ', $c['category'])[0]) ?>"><?= htmlspecialchars($c['category']) ?></span></td>
                                            <td style="font-weight:600; color: <?= (strtotime($c['due_date']) < time() && $c['status'] !== 'filed') ? 'var(--danger)' : 'inherit' ?>;"><?= htmlspecialchars($c['due_date']) ?></td>
                                            <td><?= htmlspecialchars($c['filing_date'] ?: 'N/A') ?></td>
                                            <td style="font-family:monospace; font-size:0.85rem;"><?= htmlspecialchars($c['acknowledgement_number'] ?: 'N/A') ?></td>
                                            <td>
                                                <span class="badge badge-<?= $c['status'] === 'filed' ? 'completed' : ($c['status'] === 'pending' && strtotime($c['due_date']) < time() ? 'overdue' : 'progress') ?>">
                                                    <?= htmlspecialchars($c['status'] === 'pending' && strtotime($c['due_date']) < time() ? 'overdue' : $c['status']) ?>
                                                </span>
                                                <?php if ($c['status'] !== 'filed' && $c['escalated']): ?>
                                                    <span class="badge badge-overdue" style="background-color:var(--danger); color:#fff; margin-top:0.25rem; display:block; text-align:center;">ESCALATED</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="display:flex; gap:0.5rem;">
                                                    <?php if ($c['status'] !== 'filed'): ?>
                                                        <button class="btn btn-secondary" style="padding:0.35rem 0.65rem; font-size:0.75rem;" onclick="openRecordFiling(<?= $c['id'] ?>, '<?= addslashes($c['title']) ?>')">
                                                            Record Filing
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($isAdmin): ?>
                                                        <form action="index.php?tab=compliances" method="POST" onsubmit="return confirm('Delete this record?')" style="margin:0;">
                                                            <input type="hidden" name="action" value="delete_compliance">
                                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                            <button type="submit" class="btn btn-danger" style="padding:0.35rem 0.65rem; font-size:0.75rem;">
                                                                Delete
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if ($isAdmin): 
                    $compConfigs = Compliance::getComplianceConfigs();
                ?>
                    <div class="card glass-card" style="margin-top:2rem;">
                        <h3 class="card-title"><i data-lucide="settings" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:var(--primary);"></i>Automatic Compliance Settings per Client</h3>
                        <p style="font-size:0.8rem; color:var(--text-muted); margin-top:0.25rem; margin-bottom:1rem;">
                            Enable automated monthly/quarterly/yearly return task spawning, due date SMS/Email reminders, and admin escalations on a per-client basis.
                        </p>
                        <div class="table-container">
                            <table class="data-table" style="font-size:0.85rem;">
                                <thead>
                                    <tr>
                                        <th>Client Name</th>
                                        <th>Auto GST (Monthly)</th>
                                        <th>Auto TDS (Quarterly)</th>
                                        <th>Auto ROC (Yearly)</th>
                                        <th>Auto ITR (Yearly)</th>
                                        <th>Reminders</th>
                                        <th>Escalation Days</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($compConfigs as $cc): ?>
                                        <form action="index.php?tab=compliances" method="POST" style="margin:0;">
                                            <input type="hidden" name="action" value="update_compliance_config">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                            <input type="hidden" name="client_id" value="<?= $cc['client_id'] ?>">
                                            <tr>
                                                <td style="font-weight:700;"><?= htmlspecialchars($cc['client_name']) ?></td>
                                                <td>
                                                    <input type="checkbox" name="auto_gst" value="1" <?= $cc['auto_gst'] ? 'checked' : '' ?>> GST
                                                </td>
                                                <td>
                                                    <input type="checkbox" name="auto_tds" value="1" <?= $cc['auto_tds'] ? 'checked' : '' ?>> TDS
                                                </td>
                                                <td>
                                                    <input type="checkbox" name="auto_roc" value="1" <?= $cc['auto_roc'] ? 'checked' : '' ?>> ROC
                                                </td>
                                                <td>
                                                    <input type="checkbox" name="auto_itr" value="1" <?= $cc['auto_itr'] ? 'checked' : '' ?>> ITR
                                                </td>
                                                <td>
                                                    <label style="display:inline-flex; align-items:center; gap:0.25rem; margin-right:0.5rem; font-size:0.75rem;">
                                                        <input type="checkbox" name="remind_email" value="1" <?= $cc['remind_email'] ? 'checked' : '' ?>> Email
                                                    </label>
                                                    <label style="display:inline-flex; align-items:center; gap:0.25rem; font-size:0.75rem;">
                                                        <input type="checkbox" name="remind_sms" value="1" <?= $cc['remind_sms'] ? 'checked' : '' ?>> SMS
                                                    </label>
                                                </td>
                                                <td>
                                                    <input type="number" name="escalation_days" value="<?= intval($cc['escalation_days'] ?: 5) ?>" min="1" max="30" class="form-control" style="width:60px; padding:0.25rem; font-size:0.8rem; text-align:center;">
                                                </td>
                                                <td>
                                                    <button type="submit" class="btn btn-primary" style="padding:0.35rem 0.5rem; font-size:0.75rem;">
                                                        Save
                                                    </button>
                                                </td>
                                            </tr>
                                        </form>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Add Compliance Modal -->
                <div class="modal-overlay" id="add-compliance-modal">
                    <div class="modal-container" style="max-width: 550px;">
                        <div class="modal-header">
                            <h3 style="font-size:1.15rem; font-weight:700;">Add Compliance Task</h3>
                            <button class="modal-close" data-close-modal="add-compliance-modal">&times;</button>
                        </div>
                        <form action="index.php?tab=compliances" method="POST" style="display:grid; gap:0.75rem;">
                            <input type="hidden" name="action" value="add_compliance">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.75rem;">
                                <div class="form-group">
                                    <label for="c-comp-client" class="form-label">Client</label>
                                    <select id="c-comp-client" name="client_id" class="form-control" required>
                                        <option value="">Select client...</option>
                                        <?php foreach ($clientOptions as $cl): ?>
                                            <option value="<?= $cl['id'] ?>"><?= htmlspecialchars($cl['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="c-comp-title" class="form-label">Compliance Title</label>
                                    <input type="text" id="c-comp-title" name="title" class="form-control" required placeholder="e.g. GSTR-1 return filing">
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.75rem;">
                                <div class="form-group">
                                    <label for="c-comp-cat" class="form-label">Category</label>
                                    <select id="c-comp-cat" name="category" class="form-control">
                                        <option value="GST Return">GST Return</option>
                                        <option value="TDS Return">TDS Return</option>
                                        <option value="ITR">Income Tax Return (ITR)</option>
                                        <option value="ROC">ROC filings</option>
                                        <option value="Audit">Tax Audit</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="c-comp-periodicity" class="form-label">Periodicity</label>
                                    <select id="c-comp-periodicity" name="periodicity" class="form-control">
                                        <option value="One-Time" selected>One-Time</option>
                                        <option value="Monthly">Monthly</option>
                                        <option value="Quarterly">Quarterly</option>
                                        <option value="Half-Yearly">Half-Yearly</option>
                                        <option value="Annually">Annually</option>
                                    </select>
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.75rem;">
                                <div class="form-group">
                                    <label for="c-comp-fy" class="form-label">Financial Year (FY)</label>
                                    <select id="c-comp-fy" name="financial_year" class="form-control">
                                        <option value="">N/A</option>
                                        <option value="FY 2026-27">FY 2026-27</option>
                                        <option value="FY 2025-26" selected>FY 2025-26</option>
                                        <option value="FY 2024-25">FY 2024-25</option>
                                        <option value="FY 2023-24">FY 2023-24</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="c-comp-ay" class="form-label">Assessment Year (AY)</label>
                                    <select id="c-comp-ay" name="assessment_year" class="form-control">
                                        <option value="">N/A</option>
                                        <option value="AY 2027-28">AY 2027-28</option>
                                        <option value="AY 2026-27" selected>AY 2026-27</option>
                                        <option value="AY 2025-26">AY 2025-26</option>
                                        <option value="AY 2024-25">AY 2024-25</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="c-comp-due" class="form-label">Due Date</label>
                                <input type="date" id="c-comp-due" name="due_date" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="c-comp-notes" class="form-label">Notes</label>
                                <textarea id="c-comp-notes" name="notes" class="form-control" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:0.5rem; padding:0.75rem;">Create Compliance Task</button>
                        </form>
                    </div>
                </div>

                <!-- Record Filing Modal -->
                <div class="modal-overlay" id="record-filing-modal">
                    <div class="modal-container">
                        <div class="modal-header">
                            <h3 style="font-size:1.15rem; font-weight:700;">Record Return Filing</h3>
                            <button class="modal-close" data-close-modal="record-filing-modal">&times;</button>
                        </div>
                        <form action="index.php?tab=compliances" method="POST">
                            <input type="hidden" name="action" value="record_filing">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" id="filing-id" name="id">
                            <div class="form-group">
                                <label class="form-label">Return Detail</label>
                                <input type="text" id="filing-title" readonly class="form-control" style="background-color:var(--bg-card);">
                            </div>
                            <div class="form-group">
                                <label for="filing-date" class="form-label">Filing Date</label>
                                <input type="date" id="filing-date" name="filing_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="filing-ack" class="form-label">Acknowledgement Number</label>
                                <input type="text" id="filing-ack" name="acknowledgement_number" class="form-control" placeholder="Enter ARN or Ack Number" required>
                            </div>
                            <div class="form-group">
                                <label for="filing-notes" class="form-label">Notes</label>
                                <textarea id="filing-notes" name="notes" class="form-control" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:0.5rem; padding:0.75rem;">Record Filing Completed</button>
                        </form>
                <script>
                    function openRecordFiling(id, title) {
                        document.getElementById('filing-id').value = id;
                        document.getElementById('filing-title').value = title;
                        App.openModal('record-filing-modal');
                    }
                </script>

            <!-- ================== HRMS PORTAL TAB ================== -->
            <?php elseif ($activeTab === 'hrms'): 
                $myAttendance = HRMS::getTodayAttendance($user['id']);
                $leaveRequests = HRMS::getLeaveRequests($isAdmin ? [] : ['user_id' => $user['id']]);
                $clockLogs = HRMS::getAttendanceList(date('Y-m-d'));
                $empDetails = HRMS::getEmployeeDetails($user['id']);
                $myPerf = HRMS::calculatePerformanceMetrics($user['id']);
                $myTimeline = HRMS::getEmployeeTimeline($user['id']);

                // Month-wise employee history parsing
                $histEmployeeId = intval($_GET['hist_employee_id'] ?? ($isAdmin ? 0 : $user['id']));
                $histMonth = trim($_GET['hist_month'] ?? date('Y-m'));

                $histAttendance = [];
                $histWorkHours = 0.0;
                $histWorkLogs = [];
                $histLeaves = [];
                
                if ($histEmployeeId > 0 && !empty($histMonth)) {
                    $db = Database::getConnection();
                    
                    // Fetch attendance
                    $stmtHistAtt = $db->prepare("
                        SELECT * FROM attendance 
                        WHERE user_id = :uid 
                          AND DATE_FORMAT(date, '%Y-%m') = :month
                        ORDER BY date DESC
                    ");
                    $stmtHistAtt->execute(['uid' => $histEmployeeId, 'month' => $histMonth]);
                    $histAttendance = $stmtHistAtt->fetchAll();
                    
                    // Fetch work logs
                    $stmtHistLogs = $db->prepare("
                        SELECT wl.*, t.title as task_title
                        FROM work_logs wl
                        JOIN tasks t ON wl.task_id = t.id
                        WHERE wl.user_id = :uid 
                          AND DATE_FORMAT(wl.log_date, '%Y-%m') = :month
                        ORDER BY wl.log_date DESC
                    ");
                    $stmtHistLogs->execute(['uid' => $histEmployeeId, 'month' => $histMonth]);
                    $histWorkLogs = $stmtHistLogs->fetchAll();
                    foreach ($histWorkLogs as $wl) {
                        $histWorkHours += floatval($wl['hours_spent']);
                    }
                    
                    // Fetch leaves
                    $stmtHistLvs = $db->prepare("
                        SELECT * FROM leave_requests
                        WHERE user_id = :uid
                          AND (DATE_FORMAT(start_date, '%Y-%m') = :month1 OR DATE_FORMAT(end_date, '%Y-%m') = :month2)
                    ");
                    $stmtHistLvs->execute(['uid' => $histEmployeeId, 'month1' => $histMonth, 'month2' => $histMonth]);
                    $histLeaves = $stmtHistLvs->fetchAll();
                }
                
                // Fetch list of salary slips
                $slipsList = HRMS::getSalarySlips($isAdmin ? null : $user['id']);

                // Fetch default salary structures for staff mapping
                $db = Database::getConnection();
                $employeeStructures = $db->query("
                    SELECT u.id, e.basic, e.hra, e.conveyance, e.allowance, e.pf, e.pt, e.tds, e.shift
                    FROM users u
                    JOIN employees e ON u.id = e.user_id
                    WHERE u.role != 'super_admin'
                ")->fetchAll();
            ?>
                <!-- HRMS Operational Section: 2 Columns Grid -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                    
                    <!-- Column 1: Attendance Punch, Shift Assignment & Stats -->
                    <div style="display:flex; flex-direction:column; gap:1.5rem;">
                        <!-- Time Punch Card with QR Scan -->
                        <div class="attendance-card glass-card" style="padding:1.5rem; text-align:center;">
                            <div style="font-size:0.75rem; text-transform:uppercase; font-weight:800; color:var(--primary); letter-spacing:0.05em; margin-bottom:0.25rem;">Shift: <?= htmlspecialchars($empDetails['shift'] ?? 'General') ?></div>
                            <div class="clock-timer" id="live-timer" style="font-size:2rem; font-weight:800; margin:0.5rem 0;">00:00:00</div>
                            <p style="color:var(--text-muted); font-size:0.8rem; margin:0 0 1rem 0;">Date: <?= date('d-m-Y') ?></p>
                            
                            <!-- QR Attendance Scanner Simulator -->
                            <div style="background:var(--bg-input); border:1px dashed var(--border); padding:1rem; border-radius:var(--radius-md); margin-bottom:1rem; display:flex; flex-direction:column; align-items:center; gap:0.5rem;">
                                <div style="width:100px; height:100px; background: linear-gradient(45deg, #1e293b, #334155); display:flex; align-items:center; justify-content:center; border-radius:var(--radius-sm); border:2px solid var(--primary-glow); box-shadow: var(--clay-shadow-input); position:relative;">
                                    <!-- Simple CSS mock QR code visual -->
                                    <div style="width:70px; height:70px; border: 4px solid var(--primary); display:grid; grid-template-columns: repeat(3, 1fr); gap: 4px; padding:2px;">
                                        <div style="background:var(--primary); width:15px; height:15px;"></div>
                                        <div></div>
                                        <div style="background:var(--primary); width:15px; height:15px;"></div>
                                        <div></div>
                                        <div style="background:var(--primary); width:15px; height:15px;"></div>
                                        <div></div>
                                        <div style="background:var(--primary); width:15px; height:15px;"></div>
                                        <div></div>
                                        <div style="background:var(--primary); width:15px; height:15px;"></div>
                                    </div>
                                    <span style="font-size:0.55rem; color:#fff; position:absolute; bottom:2px; background:rgba(0,0,0,0.6); padding:1px 4px; border-radius:3px;">OFFICE QR</span>
                                </div>
                                <form action="index.php?tab=hrms" method="POST" style="width:100%;">
                                    <input type="hidden" name="action" value="simulate_qr_attendance">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <button type="submit" class="btn btn-secondary" style="width:100%; font-size:0.75rem; padding:0.45rem;">
                                        <i data-lucide="scan" style="width:14px; height:14px; vertical-align:middle; margin-right:4px;"></i> Scan & Clock In
                                    </button>
                                </form>
                            </div>

                            <div style="display:flex; justify-content:center; gap:0.75rem;">
                                <?php if ($myAttendance && $myAttendance['check_in'] && !$myAttendance['check_out']): ?>
                                    <form action="index.php?tab=hrms" method="POST" style="width:100%;">
                                        <input type="hidden" name="action" value="clock_out">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <button type="submit" class="btn btn-danger" style="width:100%; padding:0.6rem;">Clock Out</button>
                                    </form>
                                <?php elseif ($myAttendance && $myAttendance['check_out']): ?>
                                    <button class="btn btn-secondary" style="width:100%; padding:0.6rem;" disabled>Checked Out Today</button>
                                <?php else: ?>
                                    <form action="index.php?tab=hrms" method="POST" style="width:100%;">
                                        <input type="hidden" name="action" value="clock_in">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <button type="submit" class="btn btn-primary" style="width:100%; padding:0.6rem;">Manual Clock In</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Shift Assignment / Update for Admin -->
                        <?php if ($isAdmin): ?>
                            <div class="card glass-card">
                                <h3 class="card-title">Assign Shift Time (Datewise)</h3>
                                <form action="index.php?tab=hrms" method="POST" style="margin-top:0.75rem;">
                                    <input type="hidden" name="action" value="assign_shift">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <div class="form-group" style="margin-bottom:0.75rem;">
                                        <label for="shift-user-id" class="form-label" style="font-size:0.8rem;">Select Employee</label>
                                        <select id="shift-user-id" name="user_id" class="form-control" required style="padding:0.4rem; font-size:0.85rem;">
                                            <option value="">Choose Staff...</option>
                                            <?php foreach (Auth::getStaffList() as $st): ?>
                                                <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group" style="margin-bottom:0.75rem;">
                                        <label for="shift-date" class="form-label" style="font-size:0.8rem;">Target Date</label>
                                        <input type="date" id="shift-date" name="date" class="form-control" required style="padding:0.4rem; font-size:0.85rem;">
                                    </div>
                                    <div class="form-group" style="margin-bottom:0.75rem;">
                                        <label for="shift-timing" class="form-label" style="font-size:0.8rem;">Shift Assignment</label>
                                        <select id="shift-timing" name="shift_timing" class="form-control" required style="padding:0.4rem; font-size:0.85rem;">
                                            <option value="General Shift (09:00 AM - 06:00 PM)">General Shift (09:00 AM - 06:00 PM)</option>
                                            <option value="Morning Shift (07:00 AM - 04:00 PM)">Morning Shift (07:00 AM - 04:00 PM)</option>
                                            <option value="Evening Shift (02:00 PM - 11:00 PM)">Evening Shift (02:00 PM - 11:00 PM)</option>
                                            <option value="Night Shift (10:00 PM - 06:00 AM)">Night Shift (10:00 PM - 06:00 AM)</option>
                                            <option value="Flexible Shift">Flexible Shift</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary" style="width:100%; padding:0.5rem; font-size:0.85rem;">Assign Shift</button>
                                </form>
                            </div>
                        <?php endif; ?>

                        <!-- Performance Metrics -->
                        <div class="card glass-card">
                            <h3 class="card-title">My Performance Stats</h3>
                            <div style="margin-top:1rem; display:flex; flex-direction:column; gap:0.75rem;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <span style="font-size:0.85rem; font-weight:600; color:var(--text-muted);">Overall Score Index</span>
                                    <span style="font-size:1.15rem; font-weight:800; color:var(--success);"><?= $myPerf['overall_score'] ?>/100</span>
                                </div>
                                <div class="progress-bar-container" style="margin-top:0; height:8px;">
                                    <div class="progress-bar-fill" style="width: <?= $myPerf['overall_score'] ?>%; background-color: var(--success);"></div>
                                </div>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem; margin-top:0.25rem; font-size:0.8rem;">
                                    <div style="background:var(--bg-input); padding:0.5rem; border-radius:var(--radius-sm);">
                                        <div style="color:var(--text-muted); font-size:0.7rem;">Attendance Rate</div>
                                        <div style="font-weight:700; font-size:0.95rem; margin-top:2px;"><?= $myPerf['attendance_rate'] ?>%</div>
                                    </div>
                                    <div style="background:var(--bg-input); padding:0.5rem; border-radius:var(--radius-sm);">
                                        <div style="color:var(--text-muted); font-size:0.7rem;">Hours Spent</div>
                                        <div style="font-weight:700; font-size:0.95rem; margin-top:2px;"><?= number_format($myPerf['total_hours'], 1) ?> hrs</div>
                                    </div>
                                    <div style="background:var(--bg-input); padding:0.5rem; border-radius:var(--radius-sm); grid-column: span 2; display:flex; justify-content:space-between; align-items:center;">
                                        <span style="color:var(--text-muted); font-size:0.7rem;">Task Completion Ratio:</span>
                                        <span style="font-weight:700;"><?= $myPerf['completed_tasks'] ?> / <?= $myPerf['total_tasks'] ?> (<?= $myPerf['task_score'] ?>%)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Column 2: Leave, Biometric Simulator & Shift Schedule -->
                    <div style="display:flex; flex-direction:column; gap:1.5rem;">
                        <!-- Apply for Leave / Leave Status -->
                        <div class="card glass-card">
                            <h3 class="card-title">Apply for Leave</h3>
                            <form action="index.php?tab=hrms" method="POST" style="margin-top:0.75rem;">
                                <input type="hidden" name="action" value="request_leave">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                
                                <div class="form-group" style="margin-bottom:0.5rem;">
                                    <label for="lv-type" class="form-label" style="font-size:0.8rem;">Leave Type</label>
                                    <select id="lv-type" name="leave_type" class="form-control" style="padding:0.4rem; font-size:0.85rem;">
                                        <option value="casual">Casual Leave</option>
                                        <option value="sick">Sick Leave</option>
                                        <option value="earned">Earned Leave</option>
                                    </select>
                                </div>
                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.5rem; margin-bottom:0.5rem;">
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label for="lv-start" class="form-label" style="font-size:0.8rem;">Start Date</label>
                                        <input type="date" id="lv-start" name="start_date" class="form-control" required style="padding:0.4rem; font-size:0.85rem;">
                                    </div>
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label for="lv-end" class="form-label" style="font-size:0.8rem;">End Date</label>
                                        <input type="date" id="lv-end" name="end_date" class="form-control" required style="padding:0.4rem; font-size:0.85rem;">
                                    </div>
                                </div>
                                <div class="form-group" style="margin-bottom:0.75rem;">
                                    <label for="lv-reason" class="form-label" style="font-size:0.8rem;">Reason / Details</label>
                                    <textarea id="lv-reason" name="reason" class="form-control" rows="2" placeholder="State reason clearly..." required style="padding:0.4rem; font-size:0.85rem;"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary" style="width:100%; padding:0.5rem; font-size:0.85rem;">Submit Leave</button>
                            </form>
                        </div>

                        <!-- Biometric Integration Simulator & Shift Schedule (Admin Only) -->
                        <?php if ($isAdmin): ?>
                            <div class="card glass-card">
                                <h3 class="card-title">Biometric Device Simulator</h3>
                                <p style="font-size:0.75rem; color:var(--text-muted); margin-bottom:0.5rem;">Simulate card/fingerprint records from office biometric device.</p>
                                <form action="index.php?tab=hrms" method="POST">
                                    <input type="hidden" name="action" value="simulate_biometric">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <div class="form-group" style="margin-bottom:0.5rem;">
                                        <select name="user_id" class="form-control" required style="padding:0.4rem; font-size:0.85rem;">
                                            <option value="">Select Employee...</option>
                                            <?php foreach (Auth::getStaffList() as $st): ?>
                                                <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.5rem; margin-bottom:0.5rem;">
                                        <div class="form-group" style="margin-bottom:0;">
                                            <label class="form-label" style="font-size:0.7rem;">Punch Date</label>
                                            <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required style="padding:0.4rem; font-size:0.8rem;">
                                        </div>
                                        <div class="form-group" style="margin-bottom:0;">
                                            <label class="form-label" style="font-size:0.7rem;">In Time</label>
                                            <input type="text" name="check_in" class="form-control" value="09:15:00" required style="padding:0.4rem; font-size:0.8rem; font-family:monospace;">
                                        </div>
                                    </div>
                                    <div class="form-group" style="margin-bottom:0.75rem;">
                                        <label class="form-label" style="font-size:0.7rem;">Out Time (Optional)</label>
                                        <input type="text" name="check_out" class="form-control" value="18:30:00" style="padding:0.4rem; font-size:0.8rem; font-family:monospace;">
                                    </div>
                                    <button type="submit" class="btn btn-secondary" style="width:100%; padding:0.5rem; font-size:0.85rem;">Inject Biometric Log</button>
                                </form>
                            </div>

                            <div class="card glass-card">
                                <h3 class="card-title"><i data-lucide="calendar" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:var(--primary);"></i>Assigned Shifts Schedule</h3>
                                <div style="max-height: 250px; overflow-y: auto; margin-top: 0.5rem;">
                                    <table class="data-table" style="font-size:0.8rem;">
                                        <thead>
                                            <tr>
                                                <th>Staff</th>
                                                <th>Date</th>
                                                <th>Shift</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $allAssignments = HRMS::getShiftAssignments();
                                            if (empty($allAssignments)):
                                            ?>
                                                <tr><td colspan="3" style="text-align:center; color:var(--text-muted); padding:1rem 0;">No assignments registered.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($allAssignments as $sa): ?>
                                                    <tr>
                                                        <td><strong><?= htmlspecialchars($sa['staff_name']) ?></strong></td>
                                                        <td><?= htmlspecialchars($sa['date']) ?></td>
                                                        <td><span class="badge badge-progress"><?= htmlspecialchars($sa['shift_timing']) ?></span></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- HRMS Analytics, Payroll & System Logs: 2 Columns Grid -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                    
                    <!-- Column 1: Salary Slips & Payroll Records (Adjusted with Payroll) -->
                    <div style="display:flex; flex-direction:column; gap:1.5rem;">
                        <div class="card glass-card">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                                <h3 class="card-title">Salary Slips & Payroll Records</h3>
                                <?php if ($isAdmin): ?>
                                    <button class="btn btn-primary" data-open-modal="generate-slip-modal" style="padding: 0.45rem 0.85rem; font-size: 0.8rem;">
                                        <i data-lucide="plus" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:2px;"></i> Generate Slip
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Month</th>
                                            <th>Employee</th>
                                            <th>Basic (₹)</th>
                                            <th>Net Salary (₹)</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($slipsList)): ?>
                                            <tr><td colspan="6" style="text-align:center; color:var(--text-muted);">No payroll slips generated yet.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($slipsList as $slp): ?>
                                                <tr>
                                                    <td style="font-weight:700;"><?= htmlspecialchars($slp['month']) ?></td>
                                                    <td><?= htmlspecialchars($slp['employee_name']) ?></td>
                                                    <td>₹<?= number_format($slp['basic'], 2) ?></td>
                                                    <td style="font-weight:700; color:var(--success);">₹<?= number_format($slp['net_salary'], 2) ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= $slp['status'] === 'paid' ? 'completed' : 'progress' ?>">
                                                            <?= htmlspecialchars($slp['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div style="display:flex; gap:0.25rem;">
                                                            <a href="salary_slip_print.php?id=<?= $slp['id'] ?>" target="_blank" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; text-decoration:none;">
                                                                Print View
                                                            </a>
                                                            <?php if ($isAdmin && $slp['status'] !== 'paid'): ?>
                                                                <form action="index.php?tab=hrms" method="POST" style="margin:0;">
                                                                    <input type="hidden" name="action" value="pay_salary_slip">
                                                                    <input type="hidden" name="id" value="<?= $slp['id'] ?>">
                                                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                                    <button type="submit" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; background-color: var(--success); border: 0; color: #fff;">
                                                                        Mark Paid
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Column 2: Collapsible Timeline -->
                    <div style="display:flex; flex-direction:column; gap:1.5rem;">
                        <div class="card glass-card">
                            <details style="width: 100%;">
                                <summary style="cursor: pointer; list-style: none; outline: none; display: flex; justify-content: space-between; align-items: center;">
                                    <h3 class="card-title" style="margin:0; display:inline-flex; align-items:center;"><i data-lucide="activity" style="vertical-align:middle; margin-right:0.5rem; width:18px;"></i>My System Log Timeline</h3>
                                    <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700;">Click to Expand</span>
                                </summary>
                                <div class="timeline" style="margin-top:1.25rem; max-height: 250px; overflow-y:auto; padding-right:5px;">
                                    <?php if (empty($myTimeline)): ?>
                                        <p style="color:var(--text-muted); font-style:italic; font-size:0.8rem;">No activity log recorded.</p>
                                    <?php else: ?>
                                        <?php foreach ($myTimeline as $evt): ?>
                                            <div style="border-left: 2px solid var(--primary-glow); padding-left:0.75rem; margin-bottom:0.75rem; position:relative;">
                                                <div style="width:8px; height:8px; background:var(--primary); border-radius:50%; position:absolute; left:-5px; top:4px;"></div>
                                                <div style="font-size:0.7rem; color:var(--text-muted);"><?= date('d M, H:i', strtotime($evt['created_at'])) ?></div>
                                                <div style="font-size:0.8rem; font-weight:700; margin-top:1px;"><?= strtoupper(str_replace('_', ' ', $evt['action'])) ?></div>
                                                <div style="font-size:0.75rem; color:var(--text-muted); margin-top:1px;"><?= htmlspecialchars($evt['details']) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>

                <!-- Month-wise Employee Activity History Panel (Full Width at Bottom) -->
                <div class="card glass-card" id="employee-history-section" style="margin-top: 2rem; margin-bottom: 2rem; width: 100%;">
                    <h3 class="card-title">Employee History (Month-Wise)</h3>
                    <form method="GET" action="index.php" style="margin-top: 1rem; display: flex; gap: 0.5rem; align-items: flex-end; background-color: var(--bg-input); padding: 0.75rem; border-radius: var(--radius-md);">
                        <input type="hidden" name="tab" value="hrms">
                        <?php if ($isAdmin): ?>
                            <div style="flex:1;">
                                <label style="font-size:0.75rem; font-weight:700; color:var(--text-muted);">Employee</label>
                                <select name="hist_employee_id" class="form-control" style="padding:0.4rem; font-size:0.85rem;" required>
                                    <option value="">Select Employee...</option>
                                    <?php foreach (Auth::getStaffList() as $st): ?>
                                        <option value="<?= $st['id'] ?>" <?= $histEmployeeId == $st['id'] ? 'selected' : '' ?>><?= htmlspecialchars($st['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="hist_employee_id" value="<?= $user['id'] ?>">
                        <?php endif; ?>
                        <div style="flex:1;">
                            <label style="font-size:0.75rem; font-weight:700; color:var(--text-muted);">Select Month</label>
                            <input type="month" name="hist_month" class="form-control" value="<?= htmlspecialchars($histMonth) ?>" style="padding:0.4rem; font-size:0.85rem;" required>
                        </div>
                        <button type="submit" class="btn btn-primary" style="padding:0.5rem 1rem; font-size:0.85rem;">View History</button>
                    </form>

                    <div style="margin-top: 1.5rem; display:flex; flex-direction:column; gap:1.25rem;">
                        <!-- KPI metrics for selected month -->
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                            <div style="background-color: var(--bg-input); padding:0.75rem; border-radius:var(--radius-md); text-align:center; box-shadow: var(--clay-shadow-input);">
                                <div style="font-size:0.75rem; color:var(--text-muted);">Days Tracked</div>
                                <div style="font-size:1.5rem; font-weight:800; color:var(--primary);"><?= count($histAttendance) ?></div>
                            </div>
                            <div style="background-color: var(--bg-input); padding:0.75rem; border-radius:var(--radius-md); text-align:center; box-shadow: var(--clay-shadow-input);">
                                <div style="font-size:0.75rem; color:var(--text-muted);">Total Hours Logged</div>
                                <div style="font-size:1.5rem; font-weight:800; color:var(--success);"><?= number_format($histWorkHours, 1) ?> hrs</div>
                            </div>
                        </div>

                        <!-- Detailed Attendance status for the month -->
                        <div>
                            <h4 style="font-size:0.9rem; font-weight:700; border-bottom: 1px solid var(--border); padding-bottom:0.25rem;">Monthly Attendance Log</h4>
                            <div class="table-container" style="max-height: 150px; overflow-y:auto; margin-top:0.5rem;">
                                <table class="data-table" style="font-size:0.8rem;">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Check-in</th>
                                            <th>Check-out</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($histAttendance)): ?>
                                            <tr><td colspan="4" style="text-align:center; color:var(--text-muted);">No records found.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($histAttendance as $att): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($att['date']) ?></td>
                                                    <td><?= htmlspecialchars($att['check_in'] ?: '--:--') ?></td>
                                                    <td><?= htmlspecialchars($att['check_out'] ?: '--:--') ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= $att['status'] === 'present' ? 'completed' : 'progress' ?>">
                                                            <?= htmlspecialchars($att['status']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Detailed Timesheet logs for the month -->
                        <div>
                            <h4 style="font-size:0.9rem; font-weight:700; border-bottom: 1px solid var(--border); padding-bottom:0.25rem;">Monthly Timesheet Details</h4>
                            <div class="table-container" style="max-height: 150px; overflow-y:auto; margin-top:0.5rem;">
                                <table class="data-table" style="font-size:0.8rem;">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Task</th>
                                            <th>Hours</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($histWorkLogs)): ?>
                                            <tr><td colspan="3" style="text-align:center; color:var(--text-muted);">No timesheet logs.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($histWorkLogs as $wl): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($wl['log_date']) ?></td>
                                                    <td style="font-weight:600;"><?= htmlspecialchars($wl['task_title']) ?></td>
                                                    <td><?= number_format($wl['hours_spent'], 1) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Generate Salary Slip Modal -->
                <?php if ($isAdmin): ?>
                <div class="modal-overlay" id="generate-slip-modal">
                    <div class="modal-container">
                        <div class="modal-header">
                            <h3 style="font-size:1.15rem; font-weight:700;">Generate Employee Salary Slip</h3>
                            <button class="modal-close" data-close-modal="generate-slip-modal">&times;</button>
                        </div>
                        <form action="index.php?tab=hrms" method="POST">
                            <input type="hidden" name="action" value="generate_salary_slip">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <div class="form-group">
                                <label for="slip-emp" class="form-label">Select Employee</label>
                                <select id="slip-emp" name="employee_id" class="form-control" required onchange="prefillSalarySlip(this.value)">
                                    <option value="">Select Employee...</option>
                                    <?php foreach (Auth::getStaffList() as $st): ?>
                                        <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:0.5rem; margin-bottom:1rem;">
                                <div style="flex:1;">
                                    <label for="slip-month" class="form-label">Salary Month</label>
                                    <input type="month" id="slip-month" name="month" class="form-control" value="<?= date('Y-m') ?>" required>
                                </div>
                                <button type="button" class="btn btn-secondary" onclick="triggerAttendanceSalaryCalculation()" style="padding: 0.5rem 1rem; font-size: 0.8rem; height: 38px; display:flex; align-items:center; gap:0.25rem; white-space:nowrap;">
                                    <i data-lucide="calculator" style="width:14px;height:14px;"></i> Autocalculate
                                </button>
                            </div>
                            <div id="attendance-proration-badge" style="display:none; background-color: var(--success-glow); color: var(--success); padding: 0.5rem; border-radius: var(--radius-sm); font-size: 0.8rem; font-weight: 600; margin-bottom: 1rem; text-align: center;">
                                Paid Days: 0/0 (Ratio: 0%)
                            </div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label class="form-label">Basic Salary (₹)</label>
                                    <input type="number" name="basic" step="0.01" value="0.00" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">HRA (₹)</label>
                                    <input type="number" name="hra" step="0.01" value="0.00" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Conveyance (₹)</label>
                                    <input type="number" name="conveyance" step="0.01" value="0.00" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Special Allowance (₹)</label>
                                    <input type="number" name="allowance" step="0.01" value="0.00" class="form-control" required>
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.5rem; margin-top: 0.5rem;">
                                <div class="form-group">
                                    <label class="form-label">PF Deduct (₹)</label>
                                    <input type="number" name="pf" step="0.01" value="0.00" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">PT Deduct (₹)</label>
                                    <input type="number" name="pt" step="0.01" value="0.00" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">TDS Deduct (₹)</label>
                                    <input type="number" name="tds" step="0.01" value="0.00" class="form-control" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:1rem; padding:0.75rem;">Generate Salary Slip</button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <script>
                    const employeeSalaryStructures = {
                        <?php foreach ($employeeStructures as $es): ?>
                            "<?= $es['id'] ?>": {
                                "basic": <?= floatval($es['basic'] ?? 0.0) ?>,
                                "hra": <?= floatval($es['hra'] ?? 0.0) ?>,
                                "conveyance": <?= floatval($es['conveyance'] ?? 0.0) ?>,
                                "allowance": <?= floatval($es['allowance'] ?? 0.0) ?>,
                                "pf": <?= floatval($es['pf'] ?? 0.0) ?>,
                                "pt": <?= floatval($es['pt'] ?? 0.0) ?>,
                                "tds": <?= floatval($es['tds'] ?? 0.0) ?>
                            },
                        <?php endforeach; ?>
                    };
                    
                    function prefillSalarySlip(empId) {
                        if (empId && employeeSalaryStructures[empId]) {
                            const struct = employeeSalaryStructures[empId];
                            document.querySelector('#generate-slip-modal [name="basic"]').value = struct.basic.toFixed(2);
                            document.querySelector('#generate-slip-modal [name="hra"]').value = struct.hra.toFixed(2);
                            document.querySelector('#generate-slip-modal [name="conveyance"]').value = struct.conveyance.toFixed(2);
                            document.querySelector('#generate-slip-modal [name="allowance"]').value = struct.allowance.toFixed(2);
                            document.querySelector('#generate-slip-modal [name="pf"]').value = struct.pf.toFixed(2);
                            document.querySelector('#generate-slip-modal [name="pt"]').value = struct.pt.toFixed(2);
                            document.querySelector('#generate-slip-modal [name="tds"]').value = struct.tds.toFixed(2);
                            
                            // Reset proration badge on select change
                            const badge = document.getElementById('attendance-proration-badge');
                            if (badge) badge.style.display = 'none';
                        }
                    }

                    async function triggerAttendanceSalaryCalculation() {
                        const empId = document.getElementById('slip-emp').value;
                        const month = document.getElementById('slip-month').value;
                        
                        if (!empId || !month) {
                            alert("Please select both an Employee and a Salary Month.");
                            return;
                        }
                        
                        const badge = document.getElementById('attendance-proration-badge');
                        badge.style.display = 'block';
                        badge.style.backgroundColor = 'var(--bg-input)';
                        badge.style.color = 'var(--text-muted)';
                        badge.textContent = "Calculating from attendance records...";
                        
                        try {
                            const response = await fetch(`api.php?action=calculate_attendance_salary&employee_id=${empId}&month=${month}`);
                            const data = await response.json();
                            
                            if (data.success) {
                                // Populate fields
                                document.querySelector('#generate-slip-modal [name="basic"]').value = data.basic.toFixed(2);
                                document.querySelector('#generate-slip-modal [name="hra"]').value = data.hra.toFixed(2);
                                document.querySelector('#generate-slip-modal [name="conveyance"]').value = data.conveyance.toFixed(2);
                                document.querySelector('#generate-slip-modal [name="allowance"]').value = data.allowance.toFixed(2);
                                document.querySelector('#generate-slip-modal [name="pf"]').value = data.pf.toFixed(2);
                                document.querySelector('#generate-slip-modal [name="pt"]').value = data.pt.toFixed(2);
                                document.querySelector('#generate-slip-modal [name="tds"]').value = data.tds.toFixed(2);
                                
                                // Show ratio badge
                                badge.style.backgroundColor = 'var(--success-glow)';
                                badge.style.color = 'var(--success)';
                                badge.textContent = `Paid Days: ${data.paid_days} / ${data.total_days} (Prorated: ${data.ratio_percent}%)`;
                            } else {
                                alert("Failed to calculate salary: " + (data.error || "Unknown error"));
                                badge.style.display = 'none';
                            }
                        } catch (err) {
                            console.error(err);
                            alert("An error occurred during calculation.");
                            badge.style.display = 'none';
                        }
                    }

                    setInterval(() => {
                        const d = new Date();
                        document.getElementById('live-timer').textContent = d.toTimeString().split(' ')[0];
                    }, 1000);

                    // Auto-scroll to history panel if query is present in URL
                    const urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.has('hist_employee_id') || urlParams.has('hist_month')) {
                        setTimeout(() => {
                            const el = document.getElementById('employee-history-section');
                            if (el) {
                                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            }
                        }, 200);
                    }
                </script>

            <!-- ================== COMMUNICATION TAB ================== -->
            <?php elseif ($activeTab === 'chat'): 
                $employees = Auth::getStaffList();
                $employees = array_values(array_filter($employees, function($e) use ($user) { return $e['id'] != $user['id']; }));
                
                $chatWithId = intval($_GET['chat_with'] ?? 0);
                $activeChatUser = null;
                $messagesList = [];
                
                if ($chatWithId > 0) {
                    $db = Database::getConnection();
                    $stmtChat = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
                    $stmtChat->execute(['id' => $chatWithId]);
                    $activeChatUser = $stmtChat->fetch();
                    
                    if ($activeChatUser) {
                        Communication::markChatAsRead($user['id'], $chatWithId);
                        $messagesList = Communication::getChatHistory($user['id'], $chatWithId);
                    }
                }
                
                $unreadMap = Communication::getUnreadCounts($user['id']);
            ?>
                <!-- Communication Sub-Navigation Tabs -->
                <div style="display:flex; gap:0.5rem; margin-bottom:1.5rem; background:rgba(255,255,255,0.02); padding:0.5rem; border-radius:var(--radius-md); border:1px solid rgba(255,255,255,0.05);" class="no-print">
                    <?php 
                    $chatSub = trim($_GET['sub'] ?? 'messages');
                    $chatSubs = [
                        'messages' => '💬 Internal Chat',
                        'announcements' => '📢 Announcement Board'
                    ];
                    if ($isAdmin) {
                        $chatSubs['templates'] = '📧 Email Templates';
                    }
                    foreach ($chatSubs as $subKey => $subLabel) {
                        $isActiveSub = ($chatSub === $subKey);
                        echo '<a href="index.php?tab=chat&sub=' . $subKey . '" class="btn ' . ($isActiveSub ? 'btn-primary' : 'btn-secondary') . '" style="font-size:0.8rem; border-radius:15px; padding:0.4rem 0.8rem;">' . $subLabel . '</a>';
                    }
                    ?>
                </div>

                <!-- 1. Internal Chat View -->
                <?php if ($chatSub === 'messages'): ?>
                    <div class="chat-container">
                        <div class="chat-contacts glass-card">
                            <h4 style="font-size:0.85rem; text-transform:uppercase; color:var(--text-muted); font-weight:700; margin-bottom:0.5rem;">Select Recipient</h4>
                            <?php foreach ($employees as $emp): 
                                $unread = $unreadMap[$emp['id']] ?? 0;
                                $isActiveChat = ($emp['id'] === $chatWithId) ? 'active' : '';
                            ?>
                                <a href="index.php?tab=chat&sub=messages&chat_with=<?= $emp['id'] ?>" class="chat-contact-item <?= $isActiveChat ?>">
                                    <div style="display:flex; align-items:center; gap:0.5rem;">
                                        <div class="avatar" style="width:1.75rem; height:1.75rem; font-size:0.75rem;"><?= strtoupper(substr($emp['name'], 0, 2)) ?></div>
                                        <span style="font-weight:600; font-size:0.9rem; color:var(--text-main);"><?= htmlspecialchars($emp['name']) ?></span>
                                    </div>
                                    <?php if ($unread > 0): ?>
                                        <span class="badge badge-completed" style="background-color:var(--danger); padding:0.15rem 0.4rem; font-size:0.75rem;"><?= $unread ?></span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <!-- Right: Message dialog box -->
                        <div class="chat-window glass-card">
                            <?php if ($activeChatUser): ?>
                                <div class="chat-header">
                                    <div style="display:flex; align-items:center; gap:0.5rem;">
                                        <div class="avatar" style="width:2.25rem; height:2.25rem; font-size:0.9rem;"><?= strtoupper(substr($activeChatUser['name'], 0, 2)) ?></div>
                                        <div>
                                            <div style="font-weight:700;"><?= htmlspecialchars($activeChatUser['name']) ?></div>
                                            <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:600;"><?= str_replace('_', ' ', $activeChatUser['role']) ?></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="chat-messages" id="chat-scroller">
                                    <?php if (empty($messagesList)): ?>
                                        <p style="text-align:center; color:var(--text-muted); font-size:0.85rem; margin:auto 0;">No messages yet. Send a greeting below.</p>
                                    <?php else: ?>
                                        <?php foreach ($messagesList as $msg): 
                                            $msgClass = ($msg['sender_id'] == $user['id']) ? 'sent' : 'received';
                                        ?>
                                            <div class="chat-bubble <?= $msgClass ?>">
                                                <div><?= nl2br(htmlspecialchars($msg['message_text'])) ?></div>
                                                <div style="font-size:0.7rem; color:rgba(255,255,255,0.6); text-align:right; margin-top:0.25rem;"><?= date('h:i A', strtotime($msg['created_at'])) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <form action="index.php?tab=chat&sub=messages&chat_with=<?= $activeChatUser['id'] ?>" method="POST" class="chat-input-area" id="chat-form">
                                    <input type="hidden" name="action" value="send_chat_message">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="receiver_id" value="<?= $activeChatUser['id'] ?>">
                                    
                                    <input type="text" name="message_text" class="form-control" placeholder="Write secure message..." required autocomplete="off" id="chat-input-box">
                                    <button type="submit" class="btn btn-primary" style="display:flex; align-items:center; gap:0.25rem; padding:0 1.25rem;">
                                        <span>Send</span>
                                        <i data-lucide="send" style="width:14px; height:14px;"></i>
                                    </button>
                                </form>
                                
                                <script>
                                    const box = document.getElementById('chat-scroller');
                                    if (box) {
                                        box.scrollTop = box.scrollHeight;
                                    }
                                </script>
                            <?php else: ?>
                                <div style="display:flex; flex-direction:column; justify-content:center; align-items:center; height:100%; color:var(--text-muted); gap:1rem;">
                                    <i data-lucide="message-square" style="width:48px; height:48px; color:var(--text-muted); opacity:0.5;"></i>
                                    <p style="font-size:0.95rem;">Select an employee from the left column to begin private conversation.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <!-- 2. Announcement Board View -->
                <?php elseif ($chatSub === 'announcements'): 
                    $announcements = Communication::getAnnouncements();
                ?>
                    <div style="display:grid; grid-template-columns: 1fr; gap:1.5rem;">
                        <?php if ($isAdmin): ?>
                            <div class="card glass-card">
                                <h3 class="card-title">Publish Announcement</h3>
                                <form action="index.php?tab=chat&sub=announcements" method="POST" style="display:grid; gap:1rem;">
                                    <input type="hidden" name="action" value="add_announcement">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <div class="form-group">
                                        <label class="form-label">Title</label>
                                        <input type="text" name="title" class="form-control" required placeholder="e.g. Office Holiday Notice">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Content</label>
                                        <textarea name="content" class="form-control" rows="3" required placeholder="Write details here..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary" style="align-self:flex-start;">Publish Announcement</button>
                                </form>
                            </div>
                        <?php endif; ?>

                        <div class="card glass-card">
                            <h3 class="card-title">Broadcast History</h3>
                            <div style="display:flex; flex-direction:column; gap:1rem;">
                                <?php if (empty($announcements)): ?>
                                    <p style="color:var(--text-muted); font-style:italic;">No announcements posted yet.</p>
                                <?php else: ?>
                                    <?php foreach ($announcements as $ann): ?>
                                        <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:1rem; border-radius:var(--radius-md); position:relative;">
                                            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                                <h4 style="font-size:1.05rem; font-weight:700; color:var(--primary);"><?= htmlspecialchars($ann['title']) ?></h4>
                                                <?php if ($isAdmin): ?>
                                                    <form action="index.php?tab=chat&sub=announcements" method="POST" style="margin:0;">
                                                        <input type="hidden" name="action" value="delete_announcement">
                                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                        <input type="hidden" name="id" value="<?= $ann['id'] ?>">
                                                        <button type="submit" class="btn btn-secondary" style="padding:0.25rem 0.5rem; font-size:0.75rem; color:var(--danger); border-color:rgba(239,68,68,0.2);">Delete</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                            <p style="font-size:0.9rem; color:var(--text-main); margin-top:0.5rem; line-height:1.4; white-space:pre-line;"><?= htmlspecialchars($ann['content']) ?></p>
                                            <div style="font-size:0.75rem; color:var(--text-muted); margin-top:0.75rem; display:flex; gap:1rem;">
                                                <span>Posted by: <strong><?= htmlspecialchars($ann['author_name']) ?></strong></span>
                                                <span>•</span>
                                                <span><?= date('d M Y, h:i A', strtotime($ann['created_at'])) ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                <!-- 3. Email Templates Editor View -->
                <?php elseif ($chatSub === 'templates' && $isAdmin): 
                    $emailTemplates = Communication::getEmailTemplates();
                    $selectedTemplateId = intval($_GET['template_id'] ?? ($emailTemplates[0]['id'] ?? 0));
                    $activeTemplate = null;
                    foreach ($emailTemplates as $t) {
                        if ($t['id'] === $selectedTemplateId) {
                            $activeTemplate = $t;
                            break;
                        }
                    }
                ?>
                    <div style="display:grid; grid-template-columns: 250px 1fr; gap:1.5rem;">
                        <div class="card glass-card" style="padding:1rem;">
                            <h4 style="font-size:0.85rem; text-transform:uppercase; color:var(--text-muted); font-weight:700; margin-bottom:0.75rem;">Email Templates</h4>
                            <div style="display:flex; flex-direction:column; gap:0.5rem;">
                                <?php foreach ($emailTemplates as $t): ?>
                                    <a href="index.php?tab=chat&sub=templates&template_id=<?= $t['id'] ?>" class="btn <?= $t['id'] === $selectedTemplateId ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:0.8rem; text-align:left; justify-content:flex-start; padding:0.5rem 0.75rem; white-space:normal; line-height:1.2;">
                                        <?= htmlspecialchars(str_replace('_', ' ', $t['template_name'])) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="card glass-card">
                            <?php if ($activeTemplate): ?>
                                <h3 class="card-title">Edit Template: <?= htmlspecialchars(str_replace('_', ' ', $activeTemplate['template_name'])) ?></h3>
                                <form action="index.php?tab=chat&sub=templates&template_id=<?= $activeTemplate['id'] ?>" method="POST" style="display:grid; gap:1rem;">
                                    <input type="hidden" name="action" value="update_email_template">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="template_id" value="<?= $activeTemplate['id'] ?>">

                                    <div class="form-group">
                                        <label class="form-label">Subject</label>
                                        <input type="text" name="subject" class="form-control" value="<?= htmlspecialchars($activeTemplate['subject']) ?>" required>
                                    </div>

                                    <div class="form-group">
                                         <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.25rem;">
                                             <label class="form-label" style="margin:0;">Body Text</label>
                                             <button type="button" class="btn btn-secondary" style="font-size:0.7rem; padding:0.15rem 0.4rem; display:flex; align-items:center; gap:0.25rem;" onclick="generateAIEmailDraft('<?= htmlspecialchars($activeTemplate['template_name']) ?>')">
                                                 <i data-lucide="sparkles" style="width:12px; height:12px; color:var(--primary);"></i> AI Draft
                                             </button>
                                         </div>
                                         <textarea id="template-body-textarea" name="body" class="form-control" rows="8" required style="font-family:monospace; font-size:0.85rem;"><?= htmlspecialchars($activeTemplate['body']) ?></textarea>
                                     </div>

                                    <div style="background:rgba(99,102,241,0.05); border:1px solid rgba(99,102,241,0.15); padding:0.75rem; border-radius:var(--radius-sm); font-size:0.75rem; color:var(--primary);">
                                        <strong>Available Placeholders:</strong><br>
                                        Use placeholders like `{client_name}`, `{filing_title}`, `{due_date}`, `{invoice_number}`, `{amount}`, `{net_amount}`, or `{portal_token}` within the templates.
                                    </div>

                                    <button type="submit" class="btn btn-primary" style="align-self:flex-start;">Save Template</button>
                                </form>
                            <?php else: ?>
                                <p style="color:var(--text-muted); font-style:italic;">Please select a template to edit.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Post Announcement Notice Modal -->
                <div class="modal-overlay" id="post-announce-modal">
                    <div class="modal-container">
                        <div class="modal-header">
                            <h3 style="font-size: 1.15rem; font-weight: 700;">Publish Notice</h3>
                            <button class="modal-close" data-close-modal="post-announce-modal">&times;</button>
                        </div>
                        <form action="index.php?tab=chat" method="POST">
                            <input type="hidden" name="action" value="add_announcement">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <div class="form-group">
                                <label for="an-title" class="form-label">Notice Title</label>
                                <input type="text" id="an-title" name="title" class="form-control" placeholder="e.g. Office Holiday Notice" required>
                            </div>
                            <div class="form-group">
                                <label for="an-content" class="form-label">Content / Description</label>
                                <textarea id="an-content" name="content" class="form-control" rows="5" placeholder="Write notice details..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem; padding: 0.75rem;">Post Notice Billboard</button>
                        </form>
                    </div>
                </div>

            <!-- ================== REPORTS & ANALYTICS ================== -->
            <?php elseif ($activeTab === 'reports' && RBAC::hasPermission($user['role'], 'view_reports')): 
                $clientOptions = Client::getClients();
                $staffOptions = Auth::getStaffList();
                
                $reportType = trim($_GET['rep_type'] ?? 'timesheet');
            ?>
                <!-- Selection menu -->
                <div class="card glass-card" style="margin-bottom:1.5rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                        <div style="display:flex; gap:0.5rem;">
                            <a href="index.php?tab=reports&rep_type=timesheet" class="btn <?= $reportType === 'timesheet' ? 'btn-primary' : 'btn-secondary' ?>">Timesheet Report</a>
                            <a href="index.php?tab=reports&rep_type=compliance" class="btn <?= $reportType === 'compliance' ? 'btn-primary' : 'btn-secondary' ?>">Compliance filings</a>
                            <a href="index.php?tab=reports&rep_type=accounting" class="btn <?= $reportType === 'accounting' ? 'btn-primary' : 'btn-secondary' ?>">Revenue Statements</a>
                        </div>
                    </div>
                </div>

                <?php if ($reportType === 'timesheet'): 
                    $userIdFilter = intval($_GET['rep_user_id'] ?? 0);
                    $logsFilters = [];
                    if ($userIdFilter > 0) $logsFilters['user_id'] = $userIdFilter;
                    $logsList = Task::getWorkLogs($logsFilters);
                ?>
                    <div class="card glass-card">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:1rem;">
                            <h3 class="card-title">Employee Timesheets logs</h3>
                            <form action="index.php" method="GET" style="display:flex; gap:0.5rem;">
                                <input type="hidden" name="tab" value="reports">
                                <input type="hidden" name="rep_type" value="timesheet">
                                <select name="rep_user_id" class="form-control" onchange="this.form.submit()">
                                    <option value="">All Employees</option>
                                    <?php foreach ($staffOptions as $opt): ?>
                                        <option value="<?= $opt['id'] ?>" <?= ($userIdFilter == $opt['id']) ? 'selected' : '' ?>><?= htmlspecialchars($opt['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </div>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Employee</th>
                                        <th>Client</th>
                                        <th>Task</th>
                                        <th>Hours Worked</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logsList as $l): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($l['log_date']) ?></td>
                                            <td style="font-weight:700;"><?= htmlspecialchars($l['staff_name']) ?></td>
                                            <td><?= htmlspecialchars($l['client_name']) ?></td>
                                            <td><?= htmlspecialchars($l['task_title']) ?></td>
                                            <td style="font-weight:700; color:var(--primary);"><?= htmlspecialchars($l['hours_spent']) ?> hrs</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php elseif ($reportType === 'compliance'): 
                    $catFilter = trim($_GET['rep_comp_cat'] ?? '');
                    $compFilters = [];
                    if (!empty($catFilter)) $compFilters['category'] = $catFilter;
                    $compList = Compliance::getCompliances($compFilters);
                ?>
                    <div class="card glass-card">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:1rem;">
                            <h3 class="card-title">Statutory Returns filings Logs</h3>
                            <form action="index.php" method="GET" style="display:flex; gap:0.5rem;">
                                <input type="hidden" name="tab" value="reports">
                                <input type="hidden" name="rep_type" value="compliance">
                                <select name="rep_comp_cat" class="form-control" onchange="this.form.submit()">
                                    <option value="">All return types</option>
                                    <option value="GST Return" <?= $catFilter === 'GST Return' ? 'selected' : '' ?>>GST Return</option>
                                    <option value="TDS Return" <?= $catFilter === 'TDS Return' ? 'selected' : '' ?>>TDS Return</option>
                                    <option value="ITR" <?= $catFilter === 'ITR' ? 'selected' : '' ?>>ITR</option>
                                    <option value="ROC" <?= $catFilter === 'ROC' ? 'selected' : '' ?>>ROC</option>
                                    <option value="Audit" <?= $catFilter === 'Audit' ? 'selected' : '' ?>>Audit</option>
                                </select>
                            </form>
                        </div>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Client</th>
                                        <th>Return Title</th>
                                        <th>Category</th>
                                        <th>Due Date</th>
                                        <th>Filing Date</th>
                                        <th>ARN / Ack</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($compList as $cp): ?>
                                        <tr>
                                            <td style="font-weight:700;"><?= htmlspecialchars($cp['client_name']) ?></td>
                                            <td><?= htmlspecialchars($cp['title']) ?></td>
                                            <td><span class="badge badge-outline"><?= htmlspecialchars($cp['category']) ?></span></td>
                                            <td><?= htmlspecialchars($cp['due_date']) ?></td>
                                            <td><?= htmlspecialchars($cp['filing_date'] ?: 'N/A') ?></td>
                                            <td style="font-family:monospace;"><?= htmlspecialchars($cp['acknowledgement_number'] ?: 'N/A') ?></td>
                                            <td>
                                                <span class="badge badge-<?= $cp['status'] === 'filed' ? 'completed' : 'progress' ?>">
                                                    <?= htmlspecialchars($cp['status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php elseif ($reportType === 'accounting'): 
                    $invList = Accounting::getInvoices();
                    $finStats = Accounting::getFinancialStats();
                ?>
                    <div class="card glass-card">
                        <h3 class="card-title">Invoicing Ledger & Revenue Report</h3>
                        <div class="table-container" style="margin-top:1rem;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Client</th>
                                        <th>Amount</th>
                                        <th>Issue Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($invList as $inv): ?>
                                        <tr>
                                            <td style="font-weight:700;"><?= htmlspecialchars($inv['invoice_number']) ?></td>
                                            <td><?= htmlspecialchars($inv['client_name']) ?></td>
                                            <td style="font-weight:700; color:var(--success);">₹<?= number_format($inv['amount'], 2) ?></td>
                                            <td><?= htmlspecialchars($inv['issue_date']) ?></td>
                                            <td>
                                                <span class="badge badge-<?= $inv['status'] === 'paid' ? 'completed' : 'progress' ?>">
                                                    <?= htmlspecialchars($inv['status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

            <!-- ================== RBAC CONFIG TAB ================== -->
            <?php elseif ($activeTab === 'rbac' && RBAC::hasPermission($user['role'], 'edit_roles')): 
                $allPerms = [
                    'manage_clients' => 'Manage Client Directory',
                    'manage_tasks' => 'Create & Edit Tasks',
                    'manage_accounting' => 'Generate Invoices & Expenses',
                    'manage_compliance' => 'Trigger Compliance Return filings',
                    'manage_hrms' => 'Manage Employee Leaves & Attendance Board',
                    'view_reports' => 'Access Operations Analytics Reports',
                ];
            ?>
                <div class="client-details-grid">
                    <!-- Manager Permissions -->
                    <div class="card glass-card">
                        <h3 class="card-title"><i data-lucide="shield-check" style="vertical-align:middle; margin-right:0.5rem; width:18px;"></i>Manager Permissions Mapping</h3>
                        <form action="index.php?tab=rbac" method="POST" style="margin-top:1.5rem;">
                            <input type="hidden" name="action" value="update_permissions">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="target_role" value="admin_manager">
                            
                            <div style="display:flex; flex-direction:column; gap:0.85rem;">
                                <?php foreach ($allPerms as $pkey => $plabel): 
                                    $checked = RBAC::hasPermission('admin_manager', $pkey) ? 'checked' : '';
                                ?>
                                    <label class="rbac-check-item">
                                        <input type="checkbox" name="perms[]" value="<?= $pkey ?>" <?= $checked ?>>
                                        <span style="font-weight:600; font-size:0.9rem;"><?= $plabel ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:1.5rem; padding:0.75rem;">Apply Manager Policies</button>
                        </form>
                    </div>

                    <!-- Staff Permissions -->
                    <div class="card glass-card">
                        <h3 class="card-title"><i data-lucide="user-check" style="vertical-align:middle; margin-right:0.5rem; width:18px;"></i>Staff Permissions Mapping</h3>
                        <form action="index.php?tab=rbac" method="POST" style="margin-top:1.5rem;">
                            <input type="hidden" name="action" value="update_permissions">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="target_role" value="staff">
                            
                            <div style="display:flex; flex-direction:column; gap:0.85rem;">
                                <?php foreach ($allPerms as $pkey => $plabel): 
                                    $checked = RBAC::hasPermission('staff', $pkey) ? 'checked' : '';
                                ?>
                                    <label class="rbac-check-item">
                                        <input type="checkbox" name="perms[]" value="<?= $pkey ?>" <?= $checked ?>>
                                        <span style="font-weight:600; font-size:0.9rem;"><?= $plabel ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:1.5rem; padding:0.75rem;">Apply Staff Policies</button>
                        </form>
                    </div>
                </div>

            <!-- ================== SECURITY AUDITING TAB ================== -->
            <?php elseif ($activeTab === 'security' && RBAC::hasPermission($user['role'], 'view_security_logs')): 
                $logins = Security::getLoginLogs(50);
                $activities = Security::getActivityLogs(50);
                
                $secMode = trim($_GET['sec_type'] ?? 'activity');
            ?>
                <div style="display:flex; gap:0.5rem; margin-bottom:1.5rem; flex-wrap:wrap;">
                    <a href="index.php?tab=security&sec_type=activity" class="btn <?= $secMode === 'activity' ? 'btn-primary' : 'btn-secondary' ?>">Activity Audit Log</a>
                    <a href="index.php?tab=security&sec_type=auth" class="btn <?= $secMode === 'auth' ? 'btn-primary' : 'btn-secondary' ?>">Authentication Logins</a>
                    <a href="index.php?tab=security&sec_type=devices" class="btn <?= $secMode === 'devices' ? 'btn-primary' : 'btn-secondary' ?>">Device & 2FA Settings</a>
                    <a href="index.php?tab=security&sec_type=network" class="btn <?= $secMode === 'network' ? 'btn-primary' : 'btn-secondary' ?>">IP Restrictions</a>
                </div>

                <?php if ($secMode === 'auth'): ?>
                    <div class="card glass-card">
                        <h3 class="card-title"><i data-lucide="shield-alert" style="vertical-align:middle; margin-right:0.5rem; width:18px;"></i>Portal Authentication attempts</h3>
                        <div class="table-container" style="margin-top:1rem;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>Attempted Email</th>
                                        <th>IP Address</th>
                                        <th>Status</th>
                                        <th>Browser Client</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logins as $ll): ?>
                                        <tr>
                                            <td style="font-weight:600;"><?= htmlspecialchars($ll['created_at']) ?></td>
                                            <td><?= htmlspecialchars($ll['email_attempted']) ?></td>
                                            <td style="font-family:monospace;"><?= htmlspecialchars($ll['ip_address']) ?></td>
                                            <td>
                                                <span class="badge badge-<?= $ll['status'] === 'success' ? 'completed' : 'progress' ?>" style="background-color: <?= $ll['status'] === 'success' ? 'var(--success)' : 'var(--danger)' ?>; color:#fff;">
                                                    <?= htmlspecialchars($ll['status']) ?>
                                                </span>
                                            </td>
                                            <td style="font-size:0.8rem; color:var(--text-muted); max-width: 250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($ll['user_agent']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php elseif ($secMode === 'devices'): 
                    $sessions = Security::getSessionsForUser($user['id']);
                    $db = Database::getConnection();
                    $stmtUser = $db->prepare("SELECT two_fa_enabled FROM users WHERE id = :id");
                    $stmtUser->execute(['id' => $user['id']]);
                    $userData = $stmtUser->fetch();
                    $twoFaEnabled = intval($userData['two_fa_enabled'] ?? 0);
                ?>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:1.5rem;">
                        <div class="card glass-card">
                            <h3 class="card-title"><i data-lucide="key" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:var(--primary);"></i>Two-Factor Authentication (2FA)</h3>
                            <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:1.5rem;">Secure your staff account by enabling 2FA. We will request a one-time verification code on your email address upon login.</p>
                            
                            <form action="index.php?tab=security&sec_type=devices" method="POST" style="margin:0;">
                                <input type="hidden" name="action" value="toggle_2fa">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <div class="form-group" style="display:flex; align-items:center; gap:0.5rem; margin-bottom:1.5rem;">
                                    <input type="checkbox" id="two_fa_enabled_cb" name="two_fa_enabled" value="1" <?= $twoFaEnabled ? 'checked' : '' ?> style="width:18px; height:18px; cursor:pointer;">
                                    <label for="two_fa_enabled_cb" class="form-label" style="margin:0; cursor:pointer; font-weight:700;">Enable Email 2FA Code</label>
                                </div>
                                <button type="submit" class="btn btn-primary" style="padding:0.6rem 1.2rem;">Save Settings</button>
                            </form>
                        </div>

                        <div class="card glass-card">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem;">
                                <h3 class="card-title" style="margin:0;"><i data-lucide="monitor" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:var(--success);"></i>Active Sessions</h3>
                                <form action="index.php?tab=security&sec_type=devices" method="POST" style="margin:0;">
                                    <input type="hidden" name="action" value="logout_other_devices">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <button type="submit" class="btn btn-secondary" style="padding:0.35rem 0.65rem; font-size:0.75rem; color:var(--danger); border-color:rgba(239,68,68,0.2);">Log Out Other Devices</button>
                                </form>
                            </div>
                            <div style="display:flex; flex-direction:column; gap:0.5rem;">
                                <?php foreach ($sessions as $s): 
                                    $isCurrent = ($s['session_token'] === ($_SESSION['session_token'] ?? ''));
                                ?>
                                    <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:0.75rem; border-radius:var(--radius-sm); font-size:0.8rem; display:flex; justify-content:space-between; align-items:center;">
                                        <div>
                                            <div style="font-weight:700; color:#fff;">IP: <?= htmlspecialchars($s['ip_address']) ?> <?php if ($isCurrent): ?><span class="badge badge-completed" style="font-size:0.65rem; padding:0.05rem 0.25rem; margin-left:0.25rem;">Current Device</span><?php endif; ?></div>
                                            <div style="color:var(--text-muted); font-size:0.75rem; max-width:280px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; margin-top:0.15rem;"><?= htmlspecialchars($s['user_agent']) ?></div>
                                            <div style="color:var(--text-muted); font-size:0.7rem; font-style:italic; margin-top:0.25rem;">Last active: <?= date('d M Y, h:i A', strtotime($s['last_active'])) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php elseif ($secMode === 'network'): 
                    $ipsList = Security::getWhitelistedIPs();
                ?>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap:1.5rem;">
                        <div class="card glass-card">
                            <h3 class="card-title">Whitelist IP Restriction Rule</h3>
                            <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:1rem;">When IP restrictions are configured, login access is blocked for any IP not whitelisted here (excluding localhost/development environments).</p>
                            
                            <form action="index.php?tab=security&sec_type=network" method="POST" style="display:grid; gap:1rem;">
                                <input type="hidden" name="action" value="add_ip_whitelist">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <div class="form-group">
                                    <label class="form-label">Authorized IP Address</label>
                                    <input type="text" name="ip_address" class="form-control" required placeholder="e.g. 192.168.1.50" value="<?= htmlspecialchars(Security::getIP()) ?>">
                                    <div style="font-size:0.75rem; color:var(--text-muted); margin-top:0.25rem;">Your current IP address is shown by default.</div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Description / Label</label>
                                    <input type="text" name="description" class="form-control" placeholder="e.g. Head Office Firewall">
                                </div>
                                <button type="submit" class="btn btn-primary" style="align-self:flex-start;">Whitelist IP Address</button>
                            </form>
                        </div>

                        <div class="card glass-card">
                            <h3 class="card-title"><i data-lucide="globe" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:var(--primary);"></i>Active Whitelisted IPs</h3>
                            <div style="display:flex; flex-direction:column; gap:0.5rem; max-height:350px; overflow-y:auto;">
                                <?php if (empty($ipsList)): ?>
                                    <p style="color:var(--text-muted); font-style:italic; font-size:0.85rem;">No IP Whitelists configured. System is open to all networks.</p>
                                <?php else: ?>
                                    <?php foreach ($ipsList as $ipRule): ?>
                                        <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.02); padding:0.5rem; border-radius:var(--radius-sm); border:1px solid rgba(255,255,255,0.05); font-size:0.85rem;">
                                            <div>
                                                <div style="font-weight:700; color:#fff;"><?= htmlspecialchars($ipRule['ip_address']) ?></div>
                                                <div style="font-size:0.75rem; color:var(--text-muted);"><?= htmlspecialchars($ipRule['description'] ?: 'No details') ?></div>
                                            </div>
                                            <form action="index.php?tab=security&sec_type=network" method="POST" style="margin:0;">
                                                <input type="hidden" name="action" value="delete_ip_whitelist">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                <input type="hidden" name="id" value="<?= $ipRule['id'] ?>">
                                                <button type="submit" class="btn btn-secondary" style="padding:0.25rem 0.5rem; font-size:0.75rem; color:var(--danger); border-color:rgba(239,68,68,0.2);">Remove</button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card glass-card">
                        <h3 class="card-title"><i data-lucide="history" style="vertical-align:middle; margin-right:0.5rem; width:18px;"></i>Employee Activity Trail</h3>
                        <div class="table-container" style="margin-top:1rem;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>Operator</th>
                                        <th>Action Taken</th>
                                        <th>Details</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activities as $act): ?>
                                        <tr>
                                            <td style="font-weight:600;"><?= htmlspecialchars($act['created_at']) ?></td>
                                            <td style="font-weight:700;"><?= htmlspecialchars($act['user_name'] ?: 'System/Client') ?></td>
                                            <td><span class="badge badge-progress"><?= htmlspecialchars($act['action']) ?></span></td>
                                            <td style="font-size:0.85rem; color:var(--text-muted);"><?= htmlspecialchars($act['details']) ?></td>
                                            <td style="font-family:monospace;"><?= htmlspecialchars($act['ip_address']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

            <!-- ================== ACCOUNTING TAB ================== -->
            <?php elseif ($activeTab === 'accounting' && RBAC::hasPermission($user['role'], 'manage_accounting')): 
                $clientOptions = Client::getClients();
                
                $accSubTab = trim($_GET['acc_sub'] ?? 'ledger');

                // Parse filters
                $invFilters = [
                    'client_id' => intval($_GET['inv_client_id'] ?? 0),
                    'status' => trim($_GET['inv_status'] ?? ''),
                    'start_date' => trim($_GET['inv_start_date'] ?? ''),
                    'end_date' => trim($_GET['inv_end_date'] ?? '')
                ];
                $expFilters = [
                    'category' => trim($_GET['exp_category'] ?? ''),
                    'start_date' => trim($_GET['exp_start_date'] ?? ''),
                    'end_date' => trim($_GET['exp_end_date'] ?? '')
                ];

                $invoiceList = Accounting::getInvoices($invFilters);
                $paymentsList = Accounting::getPayments();
                $expensesList = Accounting::getExpenses($expFilters);
                $finStats = Accounting::getFinancialStats();
                $servicesList = Accounting::getServices();
            ?>
                <!-- Accounting Stats -->
                <div class="accounting-stats" style="margin-bottom: 1.5rem;">
                    <div class="ac-stat-card invoiced glass-card">
                        <div>
                            <div style="font-size:0.8rem; color:var(--text-muted); text-transform:uppercase;">Total Invoiced</div>
                            <div class="ac-stat-value">₹<?= number_format($finStats['total_invoiced'], 2) ?></div>
                        </div>
                        <i data-lucide="file-text" style="color:var(--primary);"></i>
                    </div>
                    <div class="ac-stat-card collected glass-card">
                        <div>
                            <div style="font-size:0.8rem; color:var(--text-muted); text-transform:uppercase;">Collected Amount</div>
                            <div class="ac-stat-value">₹<?= number_format($finStats['total_collected'], 2) ?></div>
                        </div>
                        <i data-lucide="check-circle" style="color:var(--success);"></i>
                    </div>
                    <div class="ac-stat-card outstanding glass-card">
                        <div>
                            <div style="font-size:0.8rem; color:var(--text-muted); text-transform:uppercase;">Outstanding Bal</div>
                            <div class="ac-stat-value">₹<?= number_format($finStats['outstanding'], 2) ?></div>
                        </div>
                        <i data-lucide="clock" style="color:var(--warning);"></i>
                    </div>
                    <div class="ac-stat-card expenses glass-card">
                        <div>
                            <div style="font-size:0.8rem; color:var(--text-muted); text-transform:uppercase;">Approved Expenses</div>
                            <div class="ac-stat-value">₹<?= number_format($finStats['total_expenses'], 2) ?></div>
                        </div>
                        <i data-lucide="minus-circle" style="color:var(--danger);"></i>
                    </div>
                    <div class="ac-stat-card profit glass-card">
                        <div>
                            <div style="font-size:0.8rem; color:var(--text-muted); text-transform:uppercase;">Net income</div>
                            <div class="ac-stat-value" style="color: <?= $finStats['net_profit'] >= 0 ? '#c084fc' : 'var(--danger)' ?>;">₹<?= number_format($finStats['net_profit'], 2) ?></div>
                        </div>
                        <i data-lucide="trending-up" style="color:#a855f7;"></i>
                    </div>
                </div>

                <!-- Horizontal Sub Navigation Tabs -->
                <div style="display:flex; gap:0.5rem; margin-bottom:1.5rem; flex-wrap:wrap; border-bottom: 1px solid var(--border); padding-bottom:0.75rem;">
                    <a href="index.php?tab=accounting&acc_sub=ledger" class="btn <?= $accSubTab === 'ledger' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:0.85rem;"><i data-lucide="layers" style="width:14px; height:14px; vertical-align:middle; margin-right:4px;"></i> Billing & GST Invoices</a>
                    <a href="index.php?tab=accounting&acc_sub=designer" class="btn <?= $accSubTab === 'designer' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:0.85rem;"><i data-lucide="palette" style="width:14px; height:14px; vertical-align:middle; margin-right:4px;"></i> Invoice Designer</a>
                    <a href="index.php?tab=accounting&acc_sub=expenses" class="btn <?= $accSubTab === 'expenses' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:0.85rem;"><i data-lucide="check-square" style="width:14px; height:14px; vertical-align:middle; margin-right:4px;"></i> Expense Approvals Workflow</a>
                    <a href="index.php?tab=accounting&acc_sub=reconciliation" class="btn <?= $accSubTab === 'reconciliation' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:0.85rem;"><i data-lucide="refresh-cw" style="width:14px; height:14px; vertical-align:middle; margin-right:4px;"></i> Bank Reconciliation</a>
                    <a href="index.php?tab=accounting&acc_sub=statements" class="btn <?= $accSubTab === 'statements' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:0.85rem;"><i data-lucide="file-spreadsheet" style="width:14px; height:14px; vertical-align:middle; margin-right:4px;"></i> Financial Statements</a>
                </div>

                <!-- SUB TAB CONTENT 1: LEDGER -->
                <?php if ($accSubTab === 'ledger'): ?>
                    <div class="client-details-grid" style="margin-bottom:2rem;">
                        <!-- Billing controls -->
                        <div class="card glass-card" style="height:fit-content;">
                            <h3 class="card-title">Billing Operations</h3>
                            <div style="display:flex; flex-direction:column; gap:1rem; margin-top:1rem;">
                                <button class="btn btn-primary" style="width:100%; display:flex; justify-content:center; align-items:center; gap:0.5rem;" data-open-modal="add-invoice-modal">
                                    <i data-lucide="plus-circle" style="width:16px;"></i> Create GST Invoice
                                </button>
                                <button class="btn btn-secondary" style="width:100%; display:flex; justify-content:center; align-items:center; gap:0.5rem;" data-open-modal="add-expense-modal">
                                    <i data-lucide="minus-circle" style="width:16px;"></i> Log Firm Expense
                                </button>
                            </div>
                        </div>

                        <!-- Invoices Lists -->
                        <div class="card glass-card">
                            <h3 class="card-title">Active Invoice Ledger</h3>
                            <form method="GET" action="index.php" style="margin-top: 0.75rem; background-color: var(--bg-input); padding: 0.75rem; border-radius: var(--radius-md); display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: flex-end; box-shadow: var(--clay-shadow-input);">
                                <input type="hidden" name="tab" value="accounting">
                                <input type="hidden" name="acc_sub" value="ledger">
                                <div style="flex: 1; min-width: 120px;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted);">Client</label>
                                    <select name="inv_client_id" class="form-control" style="padding: 0.3rem; font-size: 0.8rem; height: auto;">
                                        <option value="">All Clients</option>
                                        <?php foreach ($clientOptions as $cl): ?>
                                            <option value="<?= $cl['id'] ?>" <?= $invFilters['client_id'] == $cl['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cl['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div style="flex: 1; min-width: 100px;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted);">Status</label>
                                    <select name="inv_status" class="form-control" style="padding: 0.3rem; font-size: 0.8rem; height: auto;">
                                        <option value="">All Statuses</option>
                                        <option value="unpaid" <?= $invFilters['status'] === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                                        <option value="paid" <?= $invFilters['status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                                        <option value="cancelled" <?= $invFilters['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </div>
                                <div style="flex: 1; min-width: 110px;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted);">Start Date</label>
                                    <input type="date" name="inv_start_date" class="form-control" value="<?= htmlspecialchars($invFilters['start_date']) ?>" style="padding: 0.3rem; font-size: 0.8rem; height: auto;">
                                </div>
                                <div style="flex: 1; min-width: 110px;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted);">End Date</label>
                                    <input type="date" name="inv_end_date" class="form-control" value="<?= htmlspecialchars($invFilters['end_date']) ?>" style="padding: 0.3rem; font-size: 0.8rem; height: auto;">
                                </div>
                                <button type="submit" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">Filter</button>
                                <a href="index.php?tab=accounting&acc_sub=ledger" class="btn btn-danger" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; text-decoration: none; text-align: center;">Reset</a>
                            </form>
                            <div class="table-container" style="margin-top:1rem;">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Invoice #</th>
                                            <th>Client</th>
                                            <th>Gross Amt</th>
                                            <th>Net Billed (GST Incl)</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($invoiceList)): ?>
                                            <tr><td colspan="7" style="text-align:center; color:var(--text-muted);">No invoices logged.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($invoiceList as $inv): ?>
                                                <tr>
                                                    <td style="font-weight:700;"><?= htmlspecialchars($inv['invoice_number']) ?></td>
                                                    <td><?= htmlspecialchars($inv['client_name']) ?></td>
                                                    <td>₹<?= number_format($inv['amount'], 2) ?></td>
                                                    <td style="font-weight:700; color:var(--primary);">₹<?= number_format($inv['net_amount'], 2) ?></td>
                                                    <td><?= htmlspecialchars($inv['issue_date']) ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= $inv['status'] === 'paid' ? 'completed' : ($inv['status'] === 'unpaid' && strtotime($inv['due_date']) < time() ? 'overdue' : 'progress') ?>">
                                                            <?= htmlspecialchars($inv['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div style="display:flex; gap:0.35rem;">
                                                            <a href="invoice_print.php?id=<?= $inv['id'] ?>" target="_blank" class="btn btn-secondary" style="padding:0.35rem 0.5rem; font-size:0.75rem; display:inline-flex; align-items:center; gap:0.25rem;">
                                                                <i data-lucide="eye" style="width:12px; height:12px;"></i> View & Print
                                                            </a>
                                                            <?php if ($inv['status'] !== 'paid'): ?>
                                                                <button class="btn btn-secondary" style="padding:0.35rem 0.5rem; font-size:0.75rem;" onclick="openRecordPayment(<?= $inv['id'] ?>, '<?= addslashes($inv['invoice_number']) ?>', <?= $inv['net_amount'] ?>)">
                                                                    Collect
                                                                </button>
                                                            <?php endif; ?>
                                                            <form action="index.php?tab=accounting" method="POST" onsubmit="return confirm('Delete this invoice?')" style="margin:0;">
                                                                <input type="hidden" name="action" value="delete_invoice">
                                                                <input type="hidden" name="id" value="<?= $inv['id'] ?>">
                                                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                                <button type="submit" class="btn btn-danger" style="padding:0.35rem 0.5rem; font-size:0.75rem;">
                                                                    Delete
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Collections History list -->
                    <div class="card glass-card" style="margin-bottom:2rem;">
                        <h3 class="card-title">Collections History Ledger</h3>
                        <div class="table-container" style="margin-top:1rem;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Receipt Date</th>
                                        <th>Invoice Refer</th>
                                        <th>Client Name</th>
                                        <th>Amount Received</th>
                                        <th>Payment Mode</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($paymentsList)): ?>
                                        <tr><td colspan="5" style="text-align:center; color:var(--text-muted);">No payment transactions receipt logged yet.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($paymentsList as $pay): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($pay['payment_date']) ?></td>
                                                <td style="font-weight:600;"><?= htmlspecialchars($pay['invoice_number']) ?></td>
                                                <td><?= htmlspecialchars($pay['client_name']) ?></td>
                                                <td style="font-weight:700; color:var(--success);">₹<?= number_format($pay['amount'], 2) ?></td>
                                                <td><span class="badge badge-progress"><?= htmlspecialchars($pay['payment_method']) ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <!-- SUB TAB CONTENT 2: INVOICE DESIGNER -->
                <?php elseif ($accSubTab === 'designer'): ?>
                    <div class="client-details-grid" style="margin-bottom:2rem;">
                        <!-- Configuration Card -->
                        <div class="card glass-card">
                            <h3 class="card-title"><i data-lucide="palette" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:var(--primary);"></i>Configure Default Layout</h3>
                            
                            <form id="invoice-designer-form" style="margin-top:1.5rem;">
                                <div class="form-group">
                                    <label class="form-label">Accent Theme Color</label>
                                    <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-top:0.25rem;">
                                        <label style="cursor:pointer; display:flex; align-items:center; gap:0.25rem;">
                                            <input type="radio" name="theme_color" value="#3b82f6" checked onchange="updateDesignerPreview()">
                                            <span style="display:inline-block; width:20px; height:20px; background:#3b82f6; border-radius:4px;"></span> Blue
                                        </label>
                                        <label style="cursor:pointer; display:flex; align-items:center; gap:0.25rem;">
                                            <input type="radio" name="theme_color" value="#10b981" onchange="updateDesignerPreview()">
                                            <span style="display:inline-block; width:20px; height:20px; background:#10b981; border-radius:4px;"></span> Emerald
                                        </label>
                                        <label style="cursor:pointer; display:flex; align-items:center; gap:0.25rem;">
                                            <input type="radio" name="theme_color" value="#6366f1" onchange="updateDesignerPreview()">
                                            <span style="display:inline-block; width:20px; height:20px; background:#6366f1; border-radius:4px;"></span> Indigo
                                        </label>
                                        <label style="cursor:pointer; display:flex; align-items:center; gap:0.25rem;">
                                            <input type="radio" name="theme_color" value="#ef4444" onchange="updateDesignerPreview()">
                                            <span style="display:inline-block; width:20px; height:20px; background:#ef4444; border-radius:4px;"></span> Crimson
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                                        <input type="checkbox" id="show_logo" checked onchange="updateDesignerPreview()"> Show Firm Brand Logo
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label for="terms" class="form-label">Terms & Conditions</label>
                                    <textarea id="terms" class="form-control" rows="3" oninput="updateDesignerPreview()">Payment is due within 15 days of invoice date. All late accounts accrue interest at 1.5% per month.</textarea>
                                </div>
                                <div class="form-group">
                                    <label for="footer_text" class="form-label">Footer Note / Message</label>
                                    <input type="text" id="footer_text" class="form-control" value="Thank you for choosing CA Associates LLP!" oninput="updateDesignerPreview()">
                                </div>
                                
                                <button type="button" class="btn btn-primary" onclick="alert('Default template configurations updated successfully!')" style="width:100%; margin-top:1rem; padding:0.75rem;">
                                    Save Default Template
                                </button>
                            </form>
                        </div>

                        <!-- Live Layout Simulator Preview -->
                        <div class="card glass-card" style="background:#ffffff; color:#0f172a; border-radius:var(--radius-lg); padding:2rem; box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);">
                            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #e2e8f0; padding-bottom:1rem; margin-bottom:1.5rem;">
                                <div id="preview-logo-box" style="font-weight:900; font-size:1.2rem; color:#3b82f6;">
                                    <span style="background:#3b82f6; color:#fff; width:25px; height:25px; display:inline-flex; align-items:center; justify-content:center; border-radius:4px; margin-right:4px;">CA</span> CA FIRM
                                </div>
                                <div style="text-align:right;">
                                    <div id="preview-title" style="font-weight:800; font-size:1.4rem; color:#3b82f6;">TAX INVOICE</div>
                                    <span style="font-size:0.8rem; color:#64748b; font-weight:700;">INV-2026-9999</span>
                                </div>
                            </div>
                            
                            <div style="display:flex; justify-content:space-between; font-size:0.8rem; margin-bottom:1.5rem;">
                                <div>
                                    <strong style="color:#64748b;">Billed By:</strong><br>
                                    CA Associates LLP<br>
                                    BKC Financial Hub, Mumbai
                                </div>
                                <div style="text-align:right;">
                                    <strong style="color:#64748b;">Billed To:</strong><br>
                                    Simulated Client Entity<br>
                                    GSTR: 27AABBC1234D1Z5
                                </div>
                            </div>

                            <table style="width:100%; border-collapse:collapse; margin-bottom:1.5rem; font-size:0.8rem;">
                                <tr style="background:#f8fafc; border-bottom:2px solid #e2e8f0; text-transform:uppercase; font-weight:700; color:#475569;">
                                    <th style="padding:0.5rem; text-align:left;">Item / Description</th>
                                    <th style="padding:0.5rem; text-align:right;">Amount</th>
                                </tr>
                                <tr style="border-bottom:1px solid #f1f5f9;">
                                    <td style="padding:0.5rem; font-weight:600;">Tax Consulting & GST Compliance Services</td>
                                    <td style="padding:0.5rem; text-align:right; font-weight:600;">₹10,000.00</td>
                                </tr>
                                <tr style="border-bottom:1px solid #f1f5f9;">
                                    <td style="padding:0.5rem; color:#64748b;">CGST (9%) & SGST (9%)</td>
                                    <td style="padding:0.5rem; text-align:right; color:#64748b;">₹1,800.00</td>
                                </tr>
                                <tr>
                                    <td style="padding:0.5rem; font-weight:800;">Net Billed Payable:</td>
                                    <td style="padding:0.5rem; text-align:right; font-weight:800; color:#3b82f6;" id="preview-payable-text">₹11,800.00</td>
                                </tr>
                            </table>

                            <div style="border-top:1px solid #e2e8f0; padding-top:1rem; font-size:0.7rem; color:#64748b;">
                                <strong>Terms:</strong> <span id="preview-terms">Payment is due within 15 days of invoice date. All late accounts accrue interest at 1.5% per month.</span>
                                <div id="preview-footer" style="text-align:center; font-weight:700; margin-top:1.5rem; color:#3b82f6;">Thank you for choosing CA Associates LLP!</div>
                            </div>
                        </div>
                    </div>

                    <script>
                        function updateDesignerPreview() {
                            const color = document.querySelector('input[name="theme_color"]:checked').value;
                            const showLogo = document.getElementById('show_logo').checked;
                            const terms = document.getElementById('terms').value;
                            const footer = document.getElementById('footer_text').value;

                            const logoBox = document.getElementById('preview-logo-box');
                            if (showLogo) {
                                logoBox.style.display = 'block';
                                logoBox.style.color = color;
                                logoBox.querySelector('span').style.backgroundColor = color;
                            } else {
                                logoBox.style.display = 'none';
                            }

                            document.getElementById('preview-title').style.color = color;
                            document.getElementById('preview-payable-text').style.color = color;
                            document.getElementById('preview-terms').textContent = terms;
                            document.getElementById('preview-footer').textContent = footer;
                            document.getElementById('preview-footer').style.color = color;

                            // Apply design configurations to hidden invoice creator element
                            const configObj = {
                                theme_color: color,
                                show_logo: showLogo,
                                terms: terms,
                                footer_text: footer
                            };
                            const designStr = JSON.stringify(configObj);
                            const modalDesignField = document.getElementById('inv-design-config');
                            if (modalDesignField) {
                                modalDesignField.value = designStr;
                            }
                        }
                    </script>

                <!-- SUB TAB CONTENT 3: EXPENSE APPROVALS WORKFLOW -->
                <?php elseif ($accSubTab === 'expenses'): ?>
                    <div class="card glass-card" style="margin-bottom:2rem;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                            <h3 class="card-title">Expense Log & Review Desk</h3>
                            <button class="btn btn-primary" style="font-size:0.85rem; padding:0.5rem 1rem;" data-open-modal="add-expense-modal">
                                <i data-lucide="plus" style="width:14px; height:14px; vertical-align:middle;"></i> Log New Expense
                            </button>
                        </div>

                        <form method="GET" action="index.php" style="margin-top: 0.75rem; background-color: var(--bg-input); padding: 0.75rem; border-radius: var(--radius-md); display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: flex-end; box-shadow: var(--clay-shadow-input); margin-bottom: 1.5rem;">
                            <input type="hidden" name="tab" value="accounting">
                            <input type="hidden" name="acc_sub" value="expenses">
                            <div style="flex: 1; min-width: 120px;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted);">Category</label>
                                <select name="exp_category" class="form-control" style="padding: 0.3rem; font-size: 0.8rem; height: auto;">
                                    <option value="">All Categories</option>
                                    <option value="Salaries" <?= $expFilters['category'] === 'Salaries' ? 'selected' : '' ?>>Salaries</option>
                                    <option value="Office Rent" <?= $expFilters['category'] === 'Office Rent' ? 'selected' : '' ?>>Office Rent</option>
                                    <option value="Software/Subscription" <?= $expFilters['category'] === 'Software/Subscription' ? 'selected' : '' ?>>Software/Subscription</option>
                                    <option value="Utilities" <?= $expFilters['category'] === 'Utilities' ? 'selected' : '' ?>>Utilities</option>
                                    <option value="Travel" <?= $expFilters['category'] === 'Travel' ? 'selected' : '' ?>>Travel</option>
                                    <option value="Miscellaneous" <?= $expFilters['category'] === 'Miscellaneous' ? 'selected' : '' ?>>Miscellaneous</option>
                                </select>
                            </div>
                            <div style="flex: 1; min-width: 110px;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted);">Start Date</label>
                                <input type="date" name="exp_start_date" class="form-control" value="<?= htmlspecialchars($expFilters['start_date']) ?>" style="padding: 0.3rem; font-size: 0.8rem; height: auto;">
                            </div>
                            <div style="flex: 1; min-width: 110px;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted);">End Date</label>
                                <input type="date" name="exp_end_date" class="form-control" value="<?= htmlspecialchars($expFilters['end_date']) ?>" style="padding: 0.3rem; font-size: 0.8rem; height: auto;">
                            </div>
                            <button type="submit" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">Filter</button>
                            <a href="index.php?tab=accounting&acc_sub=expenses" class="btn btn-danger" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; text-decoration: none; text-align: center;">Reset</a>
                        </form>

                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Category</th>
                                        <th>Amount</th>
                                        <th>Remarks</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($expensesList)): ?>
                                        <tr><td colspan="6" style="text-align:center; color:var(--text-muted);">No expenses logged.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($expensesList as $exp): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($exp['date']) ?></td>
                                                <td style="font-weight:600;"><?= htmlspecialchars($exp['category']) ?></td>
                                                <td style="font-weight:700; color:var(--danger);">₹<?= number_format($exp['amount'], 2) ?></td>
                                                <td style="color:var(--text-muted); font-size:0.85rem;"><?= htmlspecialchars($exp['description'] ?: 'No details') ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $exp['status'] === 'approved' ? 'completed' : ($exp['status'] === 'rejected' ? 'overdue' : 'progress') ?>">
                                                        <?= strtoupper($exp['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div style="display:flex; gap:0.25rem;">
                                                        <?php if ($exp['status'] === 'pending' && $isAdmin): ?>
                                                            <form action="index.php?tab=accounting&acc_sub=expenses" method="POST" style="margin:0;">
                                                                <input type="hidden" name="action" value="review_expense">
                                                                <input type="hidden" name="id" value="<?= $exp['id'] ?>">
                                                                <input type="hidden" name="status" value="approved">
                                                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                                <button type="submit" class="btn btn-secondary" style="padding:0.25rem 0.5rem; font-size:0.75rem; color:var(--success);">
                                                                    Approve
                                                                </button>
                                                            </form>
                                                            <form action="index.php?tab=accounting&acc_sub=expenses" method="POST" style="margin:0;">
                                                                <input type="hidden" name="action" value="review_expense">
                                                                <input type="hidden" name="id" value="<?= $exp['id'] ?>">
                                                                <input type="hidden" name="status" value="rejected">
                                                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                                <button type="submit" class="btn btn-secondary" style="padding:0.25rem 0.5rem; font-size:0.75rem; color:var(--danger);">
                                                                    Reject
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($isAdmin): ?>
                                                            <form action="index.php?tab=accounting&acc_sub=expenses" method="POST" onsubmit="return confirm('Delete this record?')" style="margin:0;">
                                                                <input type="hidden" name="action" value="delete_expense">
                                                                <input type="hidden" name="id" value="<?= $exp['id'] ?>">
                                                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                                <button type="submit" class="btn btn-danger" style="padding:0.25rem 0.5rem; font-size:0.75rem;">
                                                                    Delete
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            --
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <!-- SUB TAB CONTENT 4: BANK RECONCILIATION -->
                <?php elseif ($accSubTab === 'reconciliation'): ?>
                    <div class="client-details-grid" style="margin-bottom:2rem;">
                        <div class="card glass-card">
                            <h3 class="card-title"><i data-lucide="refresh-cw" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:var(--primary);"></i>Upload Bank Statement</h3>
                            <p style="font-size:0.8rem; color:var(--text-muted); margin-top:0.25rem; line-height:1.4;">
                                Upload a CSV bank statement to automatically match received credits with open client invoices. Invoices matching both the reference code and amount will be marked as paid.
                            </p>

                            <div style="background:var(--bg-input); padding:0.75rem; border-radius:var(--radius-sm); margin:1rem 0; font-size:0.75rem; border-left: 3px solid var(--primary);">
                                <strong>CSV Layout Format:</strong> Date, Reference Details, Credit Amount<br>
                                <code style="font-family:monospace; display:block; margin-top:0.25rem; color:var(--text-muted);">2026-07-06, "NEFT-INFLOW-INV-2026-001", 11800.00</code>
                            </div>

                            <form action="index.php?tab=accounting&acc_sub=reconciliation" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="reconcile_bank_statement">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <div class="form-group">
                                    <label class="form-label">Select statement file (.csv)</label>
                                    <input type="file" name="statement" accept=".csv" class="form-control" required style="padding:0.4rem;">
                                </div>
                                <button type="submit" class="btn btn-primary" style="width:100%; padding:0.65rem; font-size:0.85rem; margin-top:0.5rem;">
                                    Start Auto Matching
                                </button>
                            </form>
                        </div>

                        <!-- Manual Reconciliation helper / reference list -->
                        <div class="card glass-card">
                            <h3 class="card-title">Unpaid Billed Receivables Reference</h3>
                            <div class="table-container" style="margin-top:1rem; max-height: 250px; overflow-y:auto;">
                                <table class="data-table" style="font-size:0.8rem;">
                                    <thead>
                                        <tr>
                                            <th>Invoice #</th>
                                            <th>Client</th>
                                            <th>Net Billed</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $unpaidInvs = array_filter($invoiceList, function($i) { return $i['status'] === 'unpaid'; });
                                        if (empty($unpaidInvs)):
                                        ?>
                                            <tr><td colspan="3" style="text-align:center; color:var(--text-muted);">No unpaid invoices pending.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($unpaidInvs as $ui): ?>
                                                <tr>
                                                    <td style="font-weight:700;"><?= htmlspecialchars($ui['invoice_number']) ?></td>
                                                    <td><?= htmlspecialchars($ui['client_name']) ?></td>
                                                    <td style="font-weight:700; color:var(--warning);">₹<?= number_format($ui['net_amount'], 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                <!-- SUB TAB CONTENT 5: FINANCIAL STATEMENTS -->
                <?php elseif ($accSubTab === 'statements'): 
                    $accStart = trim($_GET['acc_start'] ?? date('Y-m-01'));
                    $accEnd = trim($_GET['acc_end'] ?? date('Y-m-t'));

                    $pl = Accounting::generateProfitAndLoss($accStart, $accEnd);
                    $bs = Accounting::generateBalanceSheet();
                    $cf = Accounting::generateCashFlow($accStart, $accEnd);
                ?>
                    <!-- Date Selector Form -->
                    <form method="GET" action="index.php" style="background-color: var(--bg-input); padding: 1rem; border-radius: var(--radius-md); display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; box-shadow: var(--clay-shadow-input); margin-bottom: 2rem;">
                        <input type="hidden" name="tab" value="accounting">
                        <input type="hidden" name="acc_sub" value="statements">
                        <div>
                            <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted);">Reporting Start Date</label>
                            <input type="date" name="acc_start" class="form-control" value="<?= htmlspecialchars($accStart) ?>" style="padding: 0.4rem; font-size: 0.85rem; width:150px;">
                        </div>
                        <div>
                            <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted);">Reporting End Date</label>
                            <input type="date" name="acc_end" class="form-control" value="<?= htmlspecialchars($accEnd) ?>" style="padding: 0.4rem; font-size: 0.85rem; width:150px;">
                        </div>
                        <button type="submit" class="btn btn-primary" style="padding:0.5rem 1rem; font-size:0.85rem;">Generate Statements</button>
                    </form>

                    <!-- Financial Sheets grid -->
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap:1.5rem; margin-bottom:2rem;">
                        <!-- Profit & Loss Statement -->
                        <div class="card glass-card">
                            <h3 class="card-title" style="border-bottom:1px solid var(--border); padding-bottom:0.5rem;">Profit & Loss Statement</h3>
                            <div style="margin-top:1rem; display:flex; flex-direction:column; gap:0.75rem; font-size:0.9rem;">
                                <div style="display:flex; justify-content:space-between; border-bottom:1px dashed var(--border); padding-bottom:0.25rem;">
                                    <span>Operating Revenue (Collected)</span>
                                    <strong>₹<?= number_format($pl['revenue'], 2) ?></strong>
                                </div>
                                <div style="display:flex; justify-content:space-between; border-bottom:1px dashed var(--border); padding-bottom:0.25rem; color:var(--danger);">
                                    <span>Operating Expenses</span>
                                    <span>- ₹<?= number_format($pl['expenses'], 2) ?></span>
                                </div>
                                <div style="display:flex; justify-content:space-between; border-bottom:1px dashed var(--border); padding-bottom:0.25rem; color:var(--danger);">
                                    <span>Employee Salaries</span>
                                    <span>- ₹<?= number_format($pl['salaries'], 2) ?></span>
                                </div>
                                <div style="display:flex; justify-content:space-between; border-top: 2px solid var(--border); padding-top:0.5rem; font-size:1.05rem;">
                                    <strong>Net Operating Profit / Loss</strong>
                                    <strong style="color:<?= $pl['net_income'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>;">₹<?= number_format($pl['net_income'], 2) ?></strong>
                                </div>
                            </div>
                        </div>

                        <!-- Balance Sheet -->
                        <div class="card glass-card">
                            <h3 class="card-title" style="border-bottom:1px solid var(--border); padding-bottom:0.5rem;">Balance Sheet Summary</h3>
                            <div style="margin-top:1rem; display:flex; flex-direction:column; gap:0.75rem; font-size:0.9rem;">
                                <div style="font-weight:700; color:var(--primary); font-size:0.8rem; text-transform:uppercase;">Assets</div>
                                <div style="display:flex; justify-content:space-between; border-bottom:1px dashed var(--border); padding-bottom:0.25rem; padding-left:0.5rem;">
                                    <span>Cash & Bank Balance</span>
                                    <span>₹<?= number_format($bs['bank_cash'], 2) ?></span>
                                </div>
                                <div style="display:flex; justify-content:space-between; border-bottom:1px dashed var(--border); padding-bottom:0.25rem; padding-left:0.5rem;">
                                    <span>Accounts Receivable (Unpaid Invs)</span>
                                    <span>₹<?= number_format($bs['accounts_receivable'], 2) ?></span>
                                </div>
                                <div style="display:flex; justify-content:space-between; border-top:1px solid var(--border); padding-top:0.25rem; font-weight:700;">
                                    <span>Total Assets</span>
                                    <span>₹<?= number_format($bs['total_assets'], 2) ?></span>
                                </div>

                                <div style="font-weight:700; color:var(--warning); font-size:0.8rem; text-transform:uppercase; margin-top:0.5rem;">Liabilities & Equity</div>
                                <div style="display:flex; justify-content:space-between; border-bottom:1px dashed var(--border); padding-bottom:0.25rem; padding-left:0.5rem;">
                                    <span>TDS Liabilities</span>
                                    <span>₹<?= number_format($bs['tds_liability'], 2) ?></span>
                                </div>
                                <div style="display:flex; justify-content:space-between; border-bottom:1px dashed var(--border); padding-bottom:0.25rem; padding-left:0.5rem;">
                                    <span>Retained Earnings</span>
                                    <span>₹<?= number_format($bs['retained_earnings'], 2) ?></span>
                                </div>
                                <div style="display:flex; justify-content:space-between; border-top:1px solid var(--border); padding-top:0.25rem; font-weight:700;">
                                    <span>Total Equity & Liabilities</span>
                                    <span>₹<?= number_format($bs['total_equity_liabilities'], 2) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Cash Flow Statement -->
                        <div class="card glass-card">
                            <h3 class="card-title" style="border-bottom:1px solid var(--border); padding-bottom:0.5rem;">Cash Flow Statement</h3>
                            <div style="margin-top:1rem; display:flex; flex-direction:column; gap:0.75rem; font-size:0.9rem;">
                                <div style="display:flex; justify-content:space-between; border-bottom:1px dashed var(--border); padding-bottom:0.25rem;">
                                    <span>Cash Inflows (Collections)</span>
                                    <strong style="color:var(--success);">+ ₹<?= number_format($cf['cash_inflows'], 2) ?></strong>
                                </div>
                                <div style="display:flex; justify-content:space-between; border-bottom:1px dashed var(--border); padding-bottom:0.25rem; color:var(--danger);">
                                    <span>Cash Outflows (Expenses)</span>
                                    <span>- ₹<?= number_format($cf['cash_outflows_expenses'], 2) ?></span>
                                </div>
                                <div style="display:flex; justify-content:space-between; border-bottom:1px dashed var(--border); padding-bottom:0.25rem; color:var(--danger);">
                                    <span>Cash Outflows (Salaries)</span>
                                    <span>- ₹<?= number_format($cf['cash_outflows_salaries'], 2) ?></span>
                                </div>
                                <div style="display:flex; justify-content:space-between; border-top:2px solid var(--border); padding-top:0.5rem; font-size:1.05rem;">
                                    <strong>Net Increase / Decrease in Cash</strong>
                                    <strong style="color:<?= $cf['net_cash_flow'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>;">₹<?= number_format($cf['net_cash_flow'], 2) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Add Invoice Modal -->
                <div class="modal-overlay" id="add-invoice-modal">
                    <div class="modal-overlay-content" style="max-width: 500px; width:90%; margin:auto;">
                        <div class="modal-container">
                            <div class="modal-header">
                                <h3 style="font-size:1.15rem; font-weight:700;">Create Tax / GST Invoice</h3>
                                <button class="modal-close" data-close-modal="add-invoice-modal">&times;</button>
                            </div>
                            <form action="index.php?tab=accounting" method="POST">
                                <div style="max-height: 420px; overflow-y:auto; padding-right:8px; margin-bottom:1rem;">
                                    <input type="hidden" name="action" value="add_invoice">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" id="inv-design-config" name="invoice_design" value="">

                                    <div class="form-group">
                                        <label for="inv-client" class="form-label">Select Client</label>
                                        <select id="inv-client" name="client_id" class="form-control" required>
                                            <option value="">Select client...</option>
                                            <?php foreach ($clientOptions as $cl): ?>
                                                <option value="<?= $cl['id'] ?>"><?= htmlspecialchars($cl['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="inv-num" class="form-label">Invoice Number</label>
                                        <input type="text" id="inv-num" name="invoice_number" class="form-control" required placeholder="e.g. INV-2026-001" value="<?= Accounting::getNextInvoiceNumber() ?>">
                                    </div>
                                    
                                     <!-- Multiple Services Manager -->
                                     <div style="background: rgba(255,255,255,0.02); padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid rgba(255,255,255,0.05); margin-bottom: 1rem;">
                                         <span class="form-label" style="font-weight:700; display:block; margin-bottom:0.5rem;"><i data-lucide="layers" style="width:14px; height:14px; vertical-align:middle; margin-right:4px; color:var(--primary);"></i> Invoice Line Items (Multiple Services)</span>
                                         
                                         <!-- Dropdown & Add Button -->
                                         <div style="display:flex; gap:0.5rem; margin-bottom:0.5rem;">
                                             <select id="inv-service-select" class="form-control" style="flex:1;">
                                                 <option value="">Select standard service...</option>
                                                 <?php foreach ($servicesList as $srv): ?>
                                                     <option value="<?= htmlspecialchars($srv['charge']) ?>" data-name="<?= htmlspecialchars($srv['name']) ?>"><?= htmlspecialchars($srv['name']) ?> (₹<?= number_format($srv['charge'], 0) ?>)</option>
                                                 <?php endforeach; ?>
                                             </select>
                                             <button type="button" class="btn btn-secondary" onclick="addServiceToInvoice()" style="padding:0.5rem 0.75rem; font-size:0.8rem; font-weight:600;">
                                                 + Add
                                             </button>
                                         </div>

                                         <!-- Custom Item input -->
                                         <div style="display:flex; gap:0.5rem; margin-bottom:0.75rem;">
                                             <input type="text" id="inv-custom-name" class="form-control" style="flex:2; font-size:0.8rem;" placeholder="Or type custom service...">
                                             <input type="number" id="inv-custom-charge" class="form-control" style="flex:1; font-size:0.8rem;" placeholder="Amount (₹)">
                                             <button type="button" class="btn btn-secondary" onclick="addCustomServiceToInvoice()" style="padding:0.5rem 0.75rem; font-size:0.8rem; font-weight:600;">
                                                 + Custom
                                             </button>
                                         </div>

                                         <!-- Selected Items Table -->
                                         <div style="max-height: 150px; overflow-y:auto; border-radius:var(--radius-sm); border:1px solid rgba(255,255,255,0.06); background: rgba(0,0,0,0.15);">
                                             <table style="width:100%; border-collapse:collapse; font-size:0.8rem; text-align:left;">
                                                 <thead>
                                                     <tr style="border-bottom:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.02);">
                                                         <th style="padding:0.4rem;">Service</th>
                                                         <th style="padding:0.4rem; text-align:right;">Charge</th>
                                                         <th style="padding:0.4rem; text-align:center; width:40px;">Action</th>
                                                     </tr>
                                                 </thead>
                                                 <tbody id="invoice-items-body">
                                                     <tr>
                                                         <td colspan="3" style="padding:0.5rem; text-align:center; color:var(--text-muted); font-style:italic;">No services added yet.</td>
                                                     </tr>
                                                 </tbody>
                                             </table>
                                         </div>
                                     </div>

                                     <div class="form-group">
                                         <label for="inv-amt" class="form-label">Invoiced Base Amount (Subtotal) (₹)</label>
                                         <input type="number" id="inv-amt" name="amount" step="0.01" min="0.01" class="form-control" required placeholder="0.00" readonly style="background-color: var(--bg-card); font-weight: 700;">
                                     </div>

                                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.5rem; background:var(--bg-input); padding:0.75rem; border-radius:var(--radius-md); margin-bottom:1rem; box-shadow: var(--clay-shadow-input);">
                                        <div class="form-group" style="margin-bottom:0.5rem;">
                                            <label for="inv-gst-type" class="form-label" style="font-size:0.75rem;">GST Type</label>
                                            <select id="inv-gst-type" class="form-control" style="padding:0.4rem;" onchange="calculateGstAndNet()">
                                                <option value="none">No GST</option>
                                                <option value="local">CGST + SGST (Local)</option>
                                                <option value="interstate">IGST (Interstate)</option>
                                            </select>
                                        </div>
                                        <div class="form-group" style="margin-bottom:0.5rem;">
                                            <label for="inv-gst-rate" class="form-label" style="font-size:0.75rem;">GST Rate (%)</label>
                                            <select id="inv-gst-rate" class="form-control" style="padding:0.4rem;" onchange="calculateGstAndNet()">
                                                <option value="0">0%</option>
                                                <option value="5">5%</option>
                                                <option value="12">12%</option>
                                                <option value="18" selected>18%</option>
                                                <option value="28">28%</option>
                                            </select>
                                        </div>
                                        <div class="form-group" style="margin-bottom:0.5rem;">
                                            <label for="inv-tds-rate" class="form-label" style="font-size:0.75rem;">TDS Rate (%)</label>
                                            <select id="inv-tds-rate" class="form-control" style="padding:0.4rem;" onchange="calculateGstAndNet()">
                                                <option value="0" selected>0% / None</option>
                                                <option value="1">1% (Sec 194C)</option>
                                                <option value="2">2% (Sec 194I)</option>
                                                <option value="10">10% (Sec 194J)</option>
                                            </select>
                                        </div>
                                        <div class="form-group" style="margin-bottom:0.5rem;">
                                            <label class="form-label" style="font-size:0.75rem;">TDS Amount (₹)</label>
                                            <input type="number" id="inv-tds" name="tds_amount" readonly class="form-control" value="0.00" style="padding:0.4rem; background-color:var(--bg-card);">
                                        </div>
                                        <div class="form-group" style="margin-bottom:0.5rem;">
                                            <label class="form-label" style="font-size:0.75rem;">CGST (₹)</label>
                                            <input type="number" id="inv-cgst" name="cgst" readonly class="form-control" value="0.00" style="padding:0.4rem; background-color:var(--bg-card);">
                                        </div>
                                        <div class="form-group" style="margin-bottom:0.5rem;">
                                            <label class="form-label" style="font-size:0.75rem;">SGST (₹)</label>
                                            <input type="number" id="inv-sgst" name="sgst" readonly class="form-control" value="0.00" style="padding:0.4rem; background-color:var(--bg-card);">
                                        </div>
                                        <div class="form-group" style="margin-bottom:0; grid-column: span 2;">
                                            <label class="form-label" style="font-size:0.75rem;">IGST (₹)</label>
                                            <input type="number" id="inv-igst" name="igst" readonly class="form-control" value="0.00" style="padding:0.4rem; background-color:var(--bg-card);">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Total Payable Net Amount (₹)</label>
                                        <input type="number" id="inv-net" readonly class="form-control" value="0.00" style="font-weight:700; color:var(--primary); background-color:var(--bg-card);">
                                    </div>

                                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                                        <div class="form-group">
                                            <label for="inv-issue" class="form-label">Issue Date</label>
                                            <input type="date" id="inv-issue" name="issue_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="inv-due" class="form-label">Due Date</label>
                                            <input type="date" id="inv-due" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="inv-desc" class="form-label">Invoice Description</label>
                                        <textarea id="inv-desc" name="description" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary" style="width:100%; padding:0.75rem;">Create Client GST Invoice</button>
                            </form>
                        </div>
                    </div>
                </div>

                <script>
                    let invoiceServices = [];

                    function addServiceToInvoice() {
                        const select = document.getElementById('inv-service-select');
                        const selectedOption = select.options[select.selectedIndex];
                        if (!select.value) return;

                        const name = selectedOption.getAttribute('data-name');
                        const charge = parseFloat(select.value);

                        invoiceServices.push({ name, charge });
                        renderInvoiceServices();
                        select.value = '';
                    }

                    function addCustomServiceToInvoice() {
                        const nameInput = document.getElementById('inv-custom-name');
                        const chargeInput = document.getElementById('inv-custom-charge');
                        const name = nameInput.value.trim();
                        const charge = parseFloat(chargeInput.value);

                        if (!name || isNaN(charge) || charge <= 0) {
                            alert("Please enter a valid service name and positive amount.");
                            return;
                        }

                        invoiceServices.push({ name, charge });
                        renderInvoiceServices();
                        nameInput.value = '';
                        chargeInput.value = '';
                    }

                    function removeInvoiceService(index) {
                        invoiceServices.splice(index, 1);
                        renderInvoiceServices();
                    }

                    function renderInvoiceServices() {
                        const tbody = document.getElementById('invoice-items-body');
                        const amtInput = document.getElementById('inv-amt');
                        const descInput = document.getElementById('inv-desc');

                        if (invoiceServices.length === 0) {
                            tbody.innerHTML = `<tr><td colspan="3" style="padding:0.5rem; text-align:center; color:var(--text-muted); font-style:italic;">No services added yet.</td></tr>`;
                            amtInput.value = '';
                            descInput.value = '';
                            calculateGstAndNet();
                            return;
                        }

                        let html = '';
                        let subtotal = 0;
                        let desc = '';

                        invoiceServices.forEach((item, index) => {
                            subtotal += item.charge;
                            desc += (desc ? "\n" : "") + `${item.name}: ₹${item.charge.toFixed(2)}`;
                            html += `<tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
                                <td style="padding:0.4rem; color:var(--text-main); font-weight:600;">${item.name}</td>
                                <td style="padding:0.4rem; text-align:right; font-weight:700; color:var(--primary);">₹${item.charge.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                                <td style="padding:0.4rem; text-align:center;">
                                    <button type="button" class="btn btn-danger" style="padding:0.15rem 0.35rem; font-size:0.75rem;" onclick="removeInvoiceService(${index})">&times;</button>
                                </td>
                            </tr>`;
                        });

                        tbody.innerHTML = html;
                        amtInput.value = subtotal.toFixed(2);
                        descInput.value = desc;
                        calculateGstAndNet();
                    }

                    // Reset invoice items array on modal open
                    document.addEventListener('click', function(e) {
                        const openBtn = e.target.closest('[data-open-modal="add-invoice-modal"]');
                        if (openBtn) {
                            invoiceServices = [];
                            renderInvoiceServices();
                        }
                    });

                    function calculateGstAndNet() {
                        const baseAmt = parseFloat(document.getElementById('inv-amt').value) || 0.0;
                        const gstType = document.getElementById('inv-gst-type').value;
                        const gstRate = parseFloat(document.getElementById('inv-gst-rate').value) || 0.0;
                        const tdsRate = parseFloat(document.getElementById('inv-tds-rate').value) || 0.0;

                        let cgst = 0, sgst = 0, igst = 0;
                        if (gstType === 'local') {
                            const halfRate = gstRate / 2;
                            cgst = baseAmt * (halfRate / 100);
                            sgst = baseAmt * (halfRate / 100);
                        } else if (gstType === 'interstate') {
                            igst = baseAmt * (gstRate / 100);
                        }

                        const tdsAmt = baseAmt * (tdsRate / 100);
                        const netAmt = baseAmt + cgst + sgst + igst - tdsAmt;

                        document.getElementById('inv-cgst').value = cgst.toFixed(2);
                        document.getElementById('inv-sgst').value = sgst.toFixed(2);
                        document.getElementById('inv-igst').value = igst.toFixed(2);
                        document.getElementById('inv-tds').value = tdsAmt.toFixed(2);
                        document.getElementById('inv-net').value = netAmt.toFixed(2);
                    }
                </script>

                <!-- Log Expense Modal -->
                <div class="modal-overlay" id="add-expense-modal">
                    <div class="modal-container">
                        <div class="modal-header">
                            <h3 style="font-size:1.15rem; font-weight:700;">Record Firm Expense</h3>
                            <button class="modal-close" data-close-modal="add-expense-modal">&times;</button>
                        </div>
                        <form action="index.php?tab=accounting&acc_sub=expenses" method="POST">
                            <input type="hidden" name="action" value="add_expense">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <div class="form-group">
                                <label for="exp-cat" class="form-label">Expense Category</label>
                                <select id="exp-cat" name="category" class="form-control">
                                    <option value="Salaries">Staff Salaries</option>
                                    <option value="Office Rent">Office Rent</option>
                                    <option value="Software/Subscription">Software & Subscriptions</option>
                                    <option value="Utilities">Electricity & Internet Utilities</option>
                                    <option value="Travel">Business Travel</option>
                                    <option value="Miscellaneous">Miscellaneous</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="exp-amt" class="form-label">Spent Amount (₹)</label>
                                <input type="number" id="exp-amt" name="amount" step="0.01" min="0.01" class="form-control" required placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label for="exp-date" class="form-label">Expense Date</label>
                                <input type="date" id="exp-date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="exp-desc" class="form-label">Description / Vendor Details</label>
                                <textarea id="exp-desc" name="description" class="form-control" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:0.5rem; padding:0.75rem;">Submit Expense Details</button>
                        </form>
                    </div>
                </div>

                <!-- Record Payment Modal -->
                <div class="modal-overlay" id="record-payment-modal">
                    <div class="modal-container">
                        <div class="modal-header">
                            <h3 style="font-size:1.15rem; font-weight:700;">Record Collection Payment</h3>
                            <button class="modal-close" data-close-modal="record-payment-modal">&times;</button>
                        </div>
                        <form action="index.php?tab=accounting&acc_sub=ledger" method="POST">
                            <input type="hidden" name="action" value="record_payment">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" id="pay-inv-id" name="invoice_id">
                            <div class="form-group">
                                <label class="form-label">Invoice Number</label>
                                <input type="text" id="pay-inv-num" class="form-control" readonly style="background-color:var(--bg-card);">
                            </div>
                            <div class="form-group">
                                <label for="pay-amt" class="form-label">Amount Collected (₹)</label>
                                <input type="number" id="pay-amt" name="amount" step="0.01" min="0.01" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="pay-date" class="form-label">Payment Date</label>
                                <input type="date" id="pay-date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="pay-method" class="form-label">Payment Mode</label>
                                <select id="pay-method" name="payment_method" class="form-control">
                                    <option value="Bank Transfer">Bank Transfer / IMPS / NEFT</option>
                                    <option value="UPI">UPI Payment</option>
                                    <option value="Cheque">Cheque Deposit</option>
                                    <option value="Cash">Cash payment</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:0.5rem; padding:0.75rem;">Record Payment Receipt</button>
                        </form>
                    </div>
                </div>

                <script>
                    function openRecordPayment(invId, invNum, invAmt) {
                        document.getElementById('pay-inv-id').value = invId;
                        document.getElementById('pay-inv-num').value = invNum;
                        document.getElementById('pay-amt').value = invAmt;
                        App.openModal('record-payment-modal');
                    }
                </script>

            <!-- ================== SERVICES CATALOG TAB ================== -->
            <?php elseif ($activeTab === 'services' && $isAdmin): 
                $servicesList = Accounting::getServices();
            ?>
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                    <div>
                        <h2 style="font-size:1.5rem; font-weight:800; letter-spacing:-0.03em;">Service Catalog Management</h2>
                        <p style="color:var(--text-muted); font-size:0.85rem;">Define service charges that dynamically populate invoice templates and billing forms.</p>
                    </div>
                    <button class="btn btn-primary" onclick="App.openModal('add-service-modal')">
                        <i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Add New Service
                    </button>
                </div>

                <div class="card glass-card" style="margin-top:1.5rem;">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Service ID</th>
                                    <th>Service Name</th>
                                    <th>Standard Charge (₹)</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($servicesList)): ?>
                                    <tr><td colspan="5" style="text-align:center; color:var(--text-muted); padding: 1.5rem 0;">No services registered yet. Add one to start.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($servicesList as $srv): ?>
                                        <tr>
                                            <td style="font-weight:700; color:var(--primary);">#<?= $srv['id'] ?></td>
                                            <td style="font-weight:700;"><?= htmlspecialchars($srv['name']) ?></td>
                                            <td style="font-weight:700; color:var(--success);">₹<?= number_format($srv['charge'], 2) ?></td>
                                            <td style="color:var(--text-muted); font-size:0.85rem; max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($srv['description'] ?: 'No description provided') ?></td>
                                            <td>
                                                <div style="display:flex; gap:0.5rem;">
                                                    <button class="btn btn-secondary" style="padding:0.35rem 0.5rem; font-size:0.75rem;" onclick="openEditService(<?= $srv['id'] ?>, '<?= addslashes($srv['name']) ?>', <?= $srv['charge'] ?>, '<?= addslashes($srv['description'] ?? '') ?>')">
                                                        Edit
                                                    </button>
                                                    <form action="index.php?tab=services" method="POST" onsubmit="return confirm('Are you sure you want to delete this service?')" style="margin:0;">
                                                        <input type="hidden" name="action" value="delete_service">
                                                        <input type="hidden" name="id" value="<?= $srv['id'] ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                        <button type="submit" class="btn btn-danger" style="padding:0.35rem 0.5rem; font-size:0.75rem;">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add Service Modal -->
                <div class="modal-overlay" id="add-service-modal">
                    <div class="modal-container" style="max-width:450px;">
                        <div class="modal-header">
                            <h3 style="font-size:1.15rem; font-weight:700;">Add Service Template</h3>
                            <button class="modal-close" data-close-modal="add-service-modal">&times;</button>
                        </div>
                        <form action="index.php?tab=services" method="POST" style="display:grid; gap:1rem;">
                            <input type="hidden" name="action" value="add_service">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                            <div class="form-group">
                                <label class="form-label">Service Name</label>
                                <input type="text" name="name" class="form-control" required placeholder="e.g. GST Monthly Return Filing">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Standard Charge Base Fee (₹)</label>
                                <input type="number" name="charge" step="0.01" min="0" class="form-control" required placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description (Optional)</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Enter service deliverables description..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary" style="width:100%; padding:0.75rem;">Create Service Template</button>
                        </form>
                    </div>
                </div>

                <!-- Edit Service Modal -->
                <div class="modal-overlay" id="edit-service-modal">
                    <div class="modal-container" style="max-width:450px;">
                        <div class="modal-header">
                            <h3 style="font-size:1.15rem; font-weight:700;">Edit Service Template</h3>
                            <button class="modal-close" data-close-modal="edit-service-modal">&times;</button>
                        </div>
                        <form action="index.php?tab=services" method="POST" style="display:grid; gap:1rem;">
                            <input type="hidden" name="action" value="edit_service">
                            <input type="hidden" id="edit-srv-id" name="id">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                            <div class="form-group">
                                <label class="form-label">Service Name</label>
                                <input type="text" id="edit-srv-name" name="name" class="form-control" required placeholder="e.g. GST Monthly Return Filing">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Standard Charge Base Fee (₹)</label>
                                <input type="number" id="edit-srv-charge" name="charge" step="0.01" min="0" class="form-control" required placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description (Optional)</label>
                                <textarea id="edit-srv-desc" name="description" class="form-control" rows="3" placeholder="Enter service deliverables description..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary" style="width:100%; padding:0.75rem;">Save Service Changes</button>
                        </form>
                    </div>
                </div>

                <script>
                    function openEditService(id, name, charge, desc) {
                        document.getElementById('edit-srv-id').value = id;
                        document.getElementById('edit-srv-name').value = name;
                        document.getElementById('edit-srv-charge').value = charge;
                        document.getElementById('edit-srv-desc').value = desc;
                        App.openModal('edit-service-modal');
                    }
                </script>

            <!-- ================== COMPLIANCE TEMPLATES TAB ================== -->
            <?php elseif ($activeTab === 'templates' && $isAdmin): 
                $tmplList = Task::getRecurringTemplates();
                $clientOptions = Client::getClients();
                $staffOptions = Auth::getStaffList();
            ?>
                <div style="display: flex; justify-content: flex-end;">
                    <button class="btn btn-primary" data-open-modal="add-tmpl-modal">
                        <i data-lucide="plus" style="width:16px;height:16px;"></i> Create Template
                    </button>
                </div>

                <div class="card glass-card" style="margin-top:1rem;">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Template Title</th>
                                    <th>Category</th>
                                    <th>Client / Entity</th>
                                    <th>Assigned Staff</th>
                                    <th>Frequency</th>
                                    <th>Next Spawn Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tmplList)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; color: var(--text-muted);">No recurring templates defined yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($tmplList as $tmpl): ?>
                                        <tr>
                                            <td style="font-weight: 700;"><?= htmlspecialchars($tmpl['title']) ?></td>
                                            <td><?= htmlspecialchars($tmpl['category']) ?></td>
                                            <td><?= htmlspecialchars($tmpl['client_name']) ?></td>
                                            <td><?= htmlspecialchars($tmpl['staff_name'] ?: 'Unassigned') ?></td>
                                            <td>
                                                <span class="badge badge-progress"><?= htmlspecialchars($tmpl['frequency']) ?></span>
                                            </td>
                                            <td style="font-weight: 600; color: var(--warning);"><?= htmlspecialchars($tmpl['next_spawn_date']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add Template Modal -->
                <div class="modal-overlay" id="add-tmpl-modal">
                    <div class="modal-container">
                        <div class="modal-header">
                            <h3 style="font-size: 1.15rem; font-weight: 700;">Create Recurring Template</h3>
                            <button class="modal-close" data-close-modal="add-tmpl-modal">&times;</button>
                        </div>
                        <form action="index.php?tab=templates" method="POST">
                            <input type="hidden" name="action" value="add_template">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <div class="form-group">
                                <label for="rt-client" class="form-label">Client / Firm</label>
                                <select id="rt-client" name="client_id" class="form-control" required>
                                    <option value="">Select client...</option>
                                    <?php foreach ($clientOptions as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="rt-assigned" class="form-label">Assign Spawned Tasks To</label>
                                <select id="rt-assigned" name="assigned_to" class="form-control">
                                    <option value="">Unassigned</option>
                                    <?php foreach ($staffOptions as $s): ?>
                                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="rt-title" class="form-label">Template Title</label>
                                <input type="text" id="rt-title" name="title" class="form-control" required placeholder="e.g. GSTR-1 GST filing">
                            </div>
                            <div class="form-group">
                                <label for="rt-desc" class="form-label">Description / Instructions</label>
                                <textarea id="rt-desc" name="description" class="form-control" rows="3"></textarea>
                            </div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label for="rt-cat" class="form-label">Category</label>
                                    <input type="text" id="rt-cat" name="category" class="form-control" required placeholder="GST, TDS, ROC">
                                </div>
                                <div class="form-group">
                                    <label for="rt-freq" class="form-label">Frequency</label>
                                    <select id="rt-freq" name="frequency" class="form-control">
                                        <option value="monthly">Monthly</option>
                                        <option value="quarterly">Quarterly</option>
                                        <option value="yearly">Yearly</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="rt-next" class="form-label">First Task Spawn Date</label>
                                <input type="date" id="rt-next" name="next_spawn_date" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem; padding: 0.75rem;">Create Template</button>
                        </form>
                    </div>
                </div>

            <!-- ================== DOCUMENT REQUESTS TAB ================== -->
            <?php elseif ($activeTab === 'requests' && $isAdmin): 
                $reqList = Document::getDocumentRequests();
                $clientOptions = Client::getClients();
            ?>
                <div style="display: flex; justify-content: flex-end;">
                    <button class="btn btn-primary" data-open-modal="add-req-modal">
                        <i data-lucide="plus" style="width:16px;height:16px;"></i> Request Document
                    </button>
                </div>

                <div class="card glass-card" style="margin-top:1rem;">
                    <h2 class="card-title">Pending & Uploaded Client Requests</h2>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Request Title</th>
                                    <th>Client / Firm</th>
                                    <th>Status</th>
                                    <th>Date Requested</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reqList)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: var(--text-muted);">No document requests created yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reqList as $req): ?>
                                        <tr>
                                            <td style="font-weight: 700;"><?= htmlspecialchars($req['title']) ?></td>
                                            <td><?= htmlspecialchars($req['client_name']) ?></td>
                                            <td>
                                                <span class="badge badge-<?= $req['status'] === 'uploaded' ? 'progress' : $req['status'] ?>">
                                                    <?= htmlspecialchars($req['status']) ?>
                                                </span>
                                            </td>
                                            <td style="color: var(--text-muted); font-size: 0.85rem;"><?= htmlspecialchars($req['created_at']) ?></td>
                                            <td>
                                                <div style="display: flex; gap: 0.5rem;">
                                                    <?php if ($req['status'] === 'uploaded'): ?>
                                                        <form action="index.php?tab=requests" method="POST" style="margin: 0;">
                                                            <input type="hidden" name="action" value="review_request">
                                                            <input type="hidden" name="id" value="<?= $req['id'] ?>">
                                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                            <button type="submit" class="btn btn-primary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                                                                Mark Reviewed
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add Document Request Modal -->
                <div class="modal-overlay" id="add-req-modal">
                    <div class="modal-container">
                        <div class="modal-header">
                            <h3 style="font-size: 1.15rem; font-weight: 700;">Request Document</h3>
                            <button class="modal-close" data-close-modal="add-req-modal">&times;</button>
                        </div>
                        <form action="index.php?tab=requests" method="POST">
                            <input type="hidden" name="action" value="add_request">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <div class="form-group">
                                <label for="req-client" class="form-label">Client</label>
                                <select id="req-client" name="client_id" class="form-control" required>
                                    <option value="">Select client...</option>
                                    <?php foreach ($clientOptions as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="req-title" class="form-label">Document Requested</label>
                                <input type="text" id="req-title" name="title" class="form-control" required placeholder="e.g. Bank Statements FY 2025-26">
                            </div>
                            <div class="form-group">
                                <label for="req-desc" class="form-label">Description / Instructions</label>
                                <textarea id="req-desc" name="description" class="form-control" rows="3" placeholder="Provide details on format or date ranges required..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem; padding: 0.75rem;">Send Request</button>
                        </form>
                    </div>
                </div>

            <!-- ================== WORK TIMESHEETS TAB ================== -->
            <?php elseif ($activeTab === 'logs'): 
                $staffOptions = Auth::getStaffList();
                $clientOptions = Client::getClients();
                $logSub = trim($_GET['log_sub'] ?? 'task_logs');
                
                $filters = [];
                if (!$isAdmin) {
                    $filters['user_id'] = $user['id'];
                } else {
                    $filters['user_id'] = intval($_GET['user_id'] ?? 0);
                }
                
                $startDate = trim($_GET['start_date'] ?? '');
                $endDate = trim($_GET['end_date'] ?? '');
                if (!empty($startDate)) $filters['start_date'] = $startDate;
                if (!empty($endDate)) $filters['end_date'] = $endDate;
                
                $logsList = Task::getWorkLogs($filters);
                $timesheetsList = OfficeSuite::getTimesheets($filters);
            ?>
                <!-- Logs sub-tab navigation -->
                <div style="display:flex; gap:1rem; border-bottom:1px solid var(--bg-card); margin-bottom:1.5rem;" class="no-print">
                    <a href="index.php?tab=logs&log_sub=task_logs" style="padding:0.75rem 1rem; font-weight:700; border-bottom:3px solid <?= ($logSub === 'task_logs') ? 'var(--primary)' : 'transparent' ?>; color: <?= ($logSub === 'task_logs') ? 'var(--primary)' : 'var(--text-muted)' ?>;">
                        Task Activity Logs
                    </a>
                    <a href="index.php?tab=logs&log_sub=hourly_timesheets" style="padding:0.75rem 1rem; font-weight:700; border-bottom:3px solid <?= ($logSub === 'hourly_timesheets') ? 'var(--primary)' : 'transparent' ?>; color: <?= ($logSub === 'hourly_timesheets') ? 'var(--primary)' : 'var(--text-muted)' ?>;">
                        Hourly Timesheets (Billing)
                    </a>
                </div>

                <?php if ($logSub === 'task_logs'): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
                        <form action="index.php?tab=logs" method="POST" style="margin: 0;">
                            <input type="hidden" name="action" value="export_logs">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="user_id" value="<?= $filters['user_id'] ?? 0 ?>">
                            <input type="hidden" name="start_date" value="<?= $startDate ?>">
                            <input type="hidden" name="end_date" value="<?= $endDate ?>">
                            <button type="submit" class="btn btn-primary">
                                <i data-lucide="download" style="width:16px;height:16px;"></i> Export CSV Timesheet
                            </button>
                        </form>

                        <form action="index.php" method="GET" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                            <input type="hidden" name="tab" value="logs">
                            <input type="hidden" name="log_sub" value="task_logs">
                            
                            <?php if ($isAdmin): ?>
                                <select name="user_id" class="form-control" style="width: 150px; padding: 0.5rem;" onchange="this.form.submit()">
                                    <option value="">Filter All Employees</option>
                                    <?php foreach ($staffOptions as $opt): ?>
                                        <option value="<?= $opt['id'] ?>" <?= (($filters['user_id'] ?? 0) == $opt['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($opt['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>

                            <input type="date" name="start_date" class="form-control" style="width: 140px; padding: 0.5rem;" value="<?= htmlspecialchars($startDate) ?>" onchange="this.form.submit()">
                            <input type="date" name="end_date" class="form-control" style="width: 140px; padding: 0.5rem;" value="<?= htmlspecialchars($endDate) ?>" onchange="this.form.submit()">
                            <a href="index.php?tab=logs&log_sub=task_logs" class="btn btn-secondary" style="padding: 0.5rem 1rem;">Clear</a>
                        </form>
                    </div>

                    <div class="card glass-card" style="margin-top:1rem;">
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Employee</th>
                                        <th>Client Entity</th>
                                        <th>Task Title</th>
                                        <th>Hours Worked</th>
                                        <th>Details / Activity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($logsList)): ?>
                                        <tr>
                                            <td colspan="6" style="text-align: center; color: var(--text-muted);">No activity hours logged matching selection.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($logsList as $l): ?>
                                            <tr>
                                                <td style="font-weight: 700;"><?= htmlspecialchars($l['log_date']) ?></td>
                                                <td><?= htmlspecialchars($l['staff_name']) ?></td>
                                                <td><?= htmlspecialchars($l['client_name']) ?></td>
                                                <td style="font-weight: 600;"><?= htmlspecialchars($l['task_title']) ?></td>
                                                <td style="font-weight: 700; color: var(--primary);"><?= htmlspecialchars($l['hours_spent']) ?></td>
                                                <td style="color: var(--text-muted); font-size: 0.9rem;"><?= htmlspecialchars($l['description']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Hourly Timesheets section -->
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
                        <button class="btn btn-primary" data-open-modal="add-timesheet-modal">
                            <i data-lucide="plus" style="width:16px;height:16px;"></i> Log Hourly Timesheet
                        </button>

                        <form action="index.php" method="GET" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                            <input type="hidden" name="tab" value="logs">
                            <input type="hidden" name="log_sub" value="hourly_timesheets">
                            
                            <?php if ($isAdmin): ?>
                                <select name="user_id" class="form-control" style="width: 150px; padding: 0.5rem;" onchange="this.form.submit()">
                                    <option value="">Filter All Employees</option>
                                    <?php foreach ($staffOptions as $opt): ?>
                                        <option value="<?= $opt['id'] ?>" <?= (($filters['user_id'] ?? 0) == $opt['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($opt['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                            <a href="index.php?tab=logs&log_sub=hourly_timesheets" class="btn btn-secondary" style="padding: 0.5rem 1rem;">Reset</a>
                        </form>
                    </div>

                    <div class="card glass-card" style="margin-top:1rem;">
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Employee</th>
                                        <th>Client</th>
                                        <th>Hours</th>
                                        <th>Activity Description</th>
                                        <th>Billing Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($timesheetsList)): ?>
                                        <tr>
                                            <td colspan="7" style="text-align: center; color: var(--text-muted);">No timesheet log entries registered.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($timesheetsList as $ts): ?>
                                            <tr>
                                                <td style="font-weight: 700;"><?= date('d M Y', strtotime($ts['date_logged'])) ?></td>
                                                <td><?= htmlspecialchars($ts['staff_name']) ?></td>
                                                <td><?= htmlspecialchars($ts['client_name']) ?></td>
                                                <td style="font-weight: 700; color: var(--primary);"><?= htmlspecialchars($ts['hours']) ?> hrs</td>
                                                <td style="font-size:0.85rem; color:var(--text-muted);"><?= htmlspecialchars($ts['description']) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $ts['billed_status'] === 'billed' ? 'completed' : 'overdue' ?>">
                                                        <?= htmlspecialchars($ts['billed_status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div style="display:flex; gap:0.25rem; align-items:center;">
                                                        <?php if ($ts['billed_status'] === 'pending' && $isAdmin): ?>
                                                            <button class="btn btn-primary" style="padding:0.25rem 0.5rem; font-size:0.75rem;" onclick="openBillTimesheetModal(<?= $ts['id'] ?>, <?= $ts['hours'] ?>)" title="Bill Timesheet">
                                                                Bill Hours
                                                            </button>
                                                        <?php endif; ?>
                                                        <form action="index.php?tab=logs&log_sub=hourly_timesheets" method="POST" style="margin:0;" onsubmit="return confirm('Delete timesheet entry?')">
                                                            <input type="hidden" name="action" value="delete_timesheet">
                                                            <input type="hidden" name="id" value="<?= $ts['id'] ?>">
                                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                            <button type="submit" class="btn btn-danger" style="padding:0.25rem 0.5rem;"><i data-lucide="trash" style="width:12px;height:12px;"></i></button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Add Timesheet Modal -->
                <div class="modal-overlay" id="add-timesheet-modal">
                    <div class="modal-container" style="max-width:450px;">
                        <div class="modal-header">
                            <h3 style="font-size:1.15rem; font-weight:700;">Log Hourly Timesheet</h3>
                            <button class="modal-close" data-close-modal="add-timesheet-modal">&times;</button>
                        </div>
                        <form action="index.php?tab=logs&log_sub=hourly_timesheets" method="POST" style="display:grid; gap:0.75rem;">
                            <input type="hidden" name="action" value="add_timesheet">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <div class="form-group">
                                <label class="form-label">Client Entity</label>
                                <select name="client_id" class="form-control" required>
                                    <option value="">Select client...</option>
                                    <?php foreach ($clientOptions as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem;">
                                <div class="form-group">
                                    <label class="form-label">Hours Worked</label>
                                    <input type="number" name="hours" class="form-control" required min="0.1" step="0.1" value="1.0">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Date Logged</label>
                                    <input type="date" name="date_logged" class="form-control" required value="<?= date('Y-m-d') ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Activity Description</label>
                                <textarea name="description" class="form-control" rows="3" required placeholder="Describe task worked on (e.g. GST GSTR-1 preparation, bank reconciliation)..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%; padding:0.6rem; margin-top:0.5rem;">Log Timesheet</button>
                        </form>
                    </div>
                </div>

                <!-- Bill Timesheet Modal -->
                <div class="modal-overlay" id="bill-timesheet-modal">
                    <div class="modal-container" style="max-width:400px;">
                        <div class="modal-header">
                            <h3 style="font-size:1.15rem; font-weight:700;">Bill Timesheet Hours</h3>
                            <button class="modal-close" data-close-modal="bill-timesheet-modal">&times;</button>
                        </div>
                        <form action="index.php?tab=logs&log_sub=hourly_timesheets" method="POST" style="display:grid; gap:0.75rem;">
                            <input type="hidden" name="action" value="bill_timesheet">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" id="bill-ts-id" name="id">
                            
                            <div class="form-group">
                                <label class="form-label">Total Hours to Bill</label>
                                <input type="text" id="bill-ts-hours" class="form-control" readonly>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Hourly Rate (INR)</label>
                                <input type="number" name="hourly_rate" class="form-control" required value="500" min="1">
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%; padding:0.6rem; margin-top:0.5rem;">Generate Bill Invoice</button>
                        </form>
                    </div>
                </div>

                <script>
                    function openBillTimesheetModal(id, hours) {
                        document.getElementById('bill-ts-id').value = id;
                        document.getElementById('bill-ts-hours').value = hours + " hours";
                        App.openModal('bill-timesheet-modal');
                    }
                </script>
            <!-- ================== REPORTS & ANALYTICS TAB ================== -->
            <?php elseif ($activeTab === 'reports' && $isAdmin): 
                $rType = trim($_GET['r_type'] ?? 'revenue');
                
                $filters = [
                    'start_date' => $_GET['r_start_date'] ?? date('Y-m-01'),
                    'end_date' => $_GET['r_end_date'] ?? date('Y-m-t'),
                    'client_id' => intval($_GET['r_client_id'] ?? 0)
                ];

                $clientOptions = Client::getClients();
                
                // Fetch reports based on type
                $reportData = [];
                if ($rType === 'revenue') {
                    $reportData = Report::getRevenueReport($filters);
                } elseif ($rType === 'client') {
                    $reportData = Report::getClientReport($filters);
                } elseif ($rType === 'employee') {
                    $reportData = Report::getEmployeeReport($filters);
                } elseif ($rType === 'task') {
                    $reportData = Report::getTaskReport($filters);
                } elseif ($rType === 'compliance') {
                    $reportData = Report::getComplianceReport($filters);
                } elseif ($rType === 'profit') {
                    $reportData = Report::getProfitReport($filters);
                }
            ?>
                <!-- Dynamic Filters Bar -->
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;" class="no-print">
                    <form action="index.php" method="GET" style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center; margin:0;">
                        <input type="hidden" name="tab" value="reports">
                        <input type="hidden" name="r_type" value="<?= htmlspecialchars($rType) ?>">

                        <label class="form-label" style="margin:0; font-size:0.8rem;">From:</label>
                        <input type="date" name="r_start_date" class="form-control" style="width:130px; padding:0.4rem;" value="<?= htmlspecialchars($filters['start_date']) ?>" onchange="this.form.submit()">

                        <label class="form-label" style="margin:0; font-size:0.8rem;">To:</label>
                        <input type="date" name="r_end_date" class="form-control" style="width:130px; padding:0.4rem;" value="<?= htmlspecialchars($filters['end_date']) ?>" onchange="this.form.submit()">

                        <?php if (in_array($rType, ['revenue', 'client'])): ?>
                            <select name="r_client_id" class="form-control" style="width:150px; padding:0.4rem;" onchange="this.form.submit()">
                                <option value="">All Clients</option>
                                <?php foreach ($clientOptions as $co): ?>
                                    <option value="<?= $co['id'] ?>" <?= $filters['client_id'] === intval($co['id']) ? 'selected' : '' ?>><?= htmlspecialchars($co['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>

                        <a href="index.php?tab=reports&r_type=<?= htmlspecialchars($rType) ?>" class="btn btn-secondary" style="padding:0.4rem 0.8rem;">Reset</a>
                    </form>

                    <!-- Export Actions -->
                    <div style="display:flex; gap:0.5rem;">
                        <form action="index.php?tab=reports" method="POST" style="margin:0;">
                            <input type="hidden" name="action" value="export_report_csv">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="report_type" value="<?= htmlspecialchars($rType) ?>">
                            <input type="hidden" name="start_date" value="<?= htmlspecialchars($filters['start_date']) ?>">
                            <input type="hidden" name="end_date" value="<?= htmlspecialchars($filters['end_date']) ?>">
                            <input type="hidden" name="client_id" value="<?= $filters['client_id'] ?>">
                            <button type="submit" class="btn btn-primary" style="padding:0.4rem 0.8rem;">
                                <i data-lucide="download" style="width:14px;height:14px;vertical-align:middle;margin-right:2px;"></i> Export Excel
                            </button>
                        </form>
                        <button type="button" class="btn btn-secondary" style="padding:0.4rem 0.8rem;" onclick="window.print()">
                            <i data-lucide="printer" style="width:14px;height:14px;vertical-align:middle;margin-right:2px;"></i> Print PDF
                        </button>
                        <button type="button" class="btn btn-secondary" style="padding:0.4rem 0.8rem; display:flex; align-items:center; gap:0.25rem;" onclick="generateAIReportSummary('<?= htmlspecialchars($rType) ?>')">
                            <i data-lucide="sparkles" style="width:14px;height:14px;color:var(--primary);"></i> AI Summary
                        </button>
                    </div>
                </div>

                <!-- Report Sub-Navigation Tabs -->
                <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-bottom:1.5rem; background:rgba(255,255,255,0.02); padding:0.5rem; border-radius:var(--radius-md); border:1px solid rgba(255,255,255,0.05);" class="no-print">
                    <?php 
                    $subReports = [
                        'revenue' => '💰 Revenue',
                        'client' => '👥 Client CRM',
                        'employee' => '👔 Employee Performance',
                        'task' => '📋 Tasks & Projects',
                        'compliance' => '📑 Statutory Compliance',
                        'profit' => '📈 Profit & Margins'
                    ];
                    foreach ($subReports as $type => $label) {
                        $isActive = ($rType === $type);
                        echo '<a href="index.php?tab=reports&r_type=' . $type . '&r_start_date=' . urlencode($filters['start_date']) . '&r_end_date=' . urlencode($filters['end_date']) . '" class="btn ' . ($isActive ? 'btn-primary' : 'btn-secondary') . '" style="font-size:0.8rem; border-radius:15px; padding:0.4rem 0.8rem;">' . $label . '</a>';
                    }
                    ?>
                </div>

                <!-- Print Header -->
                <div class="print-only" style="text-align:center; margin-bottom:2rem;">
                    <h1 style="color:#000; font-size:1.75rem; font-weight:800;">CA FIRM CRM REPORTS PORTAL</h1>
                    <p style="color:#555; font-size:0.9rem;">Generated: <?= date('d M Y h:i A') ?> | Filter Period: <?= $filters['start_date'] ?> to <?= $filters['end_date'] ?></p>
                    <hr style="border:1px solid #ddd; margin-top:1rem;">
                </div>

                <!-- 1. Revenue Report View -->
                <?php if ($rType === 'revenue'): ?>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
                        <div class="card glass-card" style="padding:1rem; text-align:center;">
                            <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted);">Total Billed</div>
                            <h3 style="font-size:1.5rem; font-weight:800; color:var(--primary); margin-top:0.25rem;">₹<?= number_format($reportData['summary']['billed'], 2) ?></h3>
                        </div>
                        <div class="card glass-card" style="padding:1rem; text-align:center;">
                            <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted);">Total Collected</div>
                            <h3 style="font-size:1.5rem; font-weight:800; color:var(--success); margin-top:0.25rem;">₹<?= number_format($reportData['summary']['collected'], 2) ?></h3>
                        </div>
                        <div class="card glass-card" style="padding:1rem; text-align:center;">
                            <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted);">Outstanding Receivable</div>
                            <h3 style="font-size:1.5rem; font-weight:800; color:var(--warning); margin-top:0.25rem;">₹<?= number_format($reportData['summary']['unpaid'], 2) ?></h3>
                        </div>
                    </div>

                    <div class="card glass-card">
                        <h3 class="card-title">Billed vs Collected Statement</h3>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Client Name</th>
                                        <th>Billed Amount</th>
                                        <th>Status</th>
                                        <th>Issue Date</th>
                                        <th>Due Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($reportData['data'])): ?>
                                        <tr><td colspan="6" style="text-align:center; color:var(--text-muted);">No records found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($reportData['data'] as $row): ?>
                                            <tr>
                                                <td style="font-weight:700;"><?= htmlspecialchars($row['invoice_number']) ?></td>
                                                <td><?= htmlspecialchars($row['client_name']) ?></td>
                                                <td style="font-weight:700;">₹<?= number_format($row['net_amount'], 2) ?></td>
                                                <td><span class="badge badge-<?= $row['status'] === 'paid' ? 'completed' : 'progress' ?>"><?= strtoupper($row['status']) ?></span></td>
                                                <td><?= htmlspecialchars($row['issue_date']) ?></td>
                                                <td><?= htmlspecialchars($row['due_date']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <!-- 2. Client CRM Report View -->
                <?php elseif ($rType === 'client'): ?>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
                        <div class="card glass-card" style="padding:1rem; text-align:center;">
                            <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted);">Total Active Clients</div>
                            <h3 style="font-size:1.5rem; font-weight:800; color:var(--primary); margin-top:0.25rem;"><?= $reportData['summary']['total_clients'] ?></h3>
                        </div>
                        <div class="card glass-card" style="padding:1rem; text-align:center;">
                            <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted);">Top Billed Account</div>
                            <h3 style="font-size:1.2rem; font-weight:800; color:var(--success); margin-top:0.4rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($reportData['summary']['top_billed_client']) ?></h3>
                        </div>
                    </div>

                    <div class="card glass-card">
                        <h3 class="card-title">Client Ledger Breakdown</h3>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Client Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Registered Date</th>
                                        <th>Total Billed</th>
                                        <th>Total Collected</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($reportData['data'])): ?>
                                        <tr><td colspan="6" style="text-align:center; color:var(--text-muted);">No records found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($reportData['data'] as $row): ?>
                                            <tr>
                                                <td style="font-weight:700;"><?= htmlspecialchars($row['name']) ?></td>
                                                <td><?= htmlspecialchars($row['email']) ?></td>
                                                <td><?= htmlspecialchars($row['phone'] ?: 'N/A') ?></td>
                                                <td><?= htmlspecialchars($row['created_at']) ?></td>
                                                <td style="font-weight:700; color:var(--primary);">₹<?= number_format($row['total_billed'], 2) ?></td>
                                                <td style="font-weight:700; color:var(--success);">₹<?= number_format($row['total_collected'], 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <!-- 3. Employee Performance Report View -->
                <?php elseif ($rType === 'employee'): ?>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
                        <div class="card glass-card" style="padding:1rem; text-align:center;">
                            <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted);">Total Employees</div>
                            <h3 style="font-size:1.5rem; font-weight:800; color:var(--primary); margin-top:0.25rem;"><?= $reportData['summary']['total_employees'] ?></h3>
                        </div>
                        <div class="card glass-card" style="padding:1rem; text-align:center;">
                            <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted);">Avg Productivity Rate</div>
                            <h3 style="font-size:1.5rem; font-weight:800; color:var(--success); margin-top:0.25rem;"><?= $reportData['summary']['avg_efficiency'] ?>%</h3>
                        </div>
                    </div>

                    <div class="card glass-card">
                        <h3 class="card-title">Employee Performance Metrics</h3>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Employee Name</th>
                                        <th>Role</th>
                                        <th>HR Details</th>
                                        <th>Tasks Completed</th>
                                        <th>Hours Logged</th>
                                        <th>Productivity Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reportData['data'] as $row): ?>
                                        <tr>
                                            <td style="font-weight:700;"><?= htmlspecialchars($row['name']) ?></td>
                                            <td><span class="badge badge-outline"><?= str_replace('_', ' ', $row['role']) ?></span></td>
                                            <td>
                                                <span style="font-weight:600;"><?= htmlspecialchars($row['designation'] ?: 'N/A') ?></span>
                                                <div style="font-size:0.75rem; color:var(--text-muted);"><?= htmlspecialchars($row['department'] ?: 'N/A') ?></div>
                                            </td>
                                            <td><strong><?= $row['completed_tasks'] ?></strong> / <?= $row['total_tasks'] ?></td>
                                            <td style="font-weight:700; color:var(--primary);"><?= $row['total_hours'] ?> hrs</td>
                                            <td>
                                                <div style="display:flex; align-items:center; gap:0.5rem; width:120px;">
                                                    <div class="progress-bar-container" style="flex:1; margin-top:0; height:8px;">
                                                        <div class="progress-bar-fill" style="width: <?= $row['efficiency'] ?>%; background-color: <?= $row['efficiency'] >= 80 ? 'var(--success)' : ($row['efficiency'] >= 50 ? 'var(--warning)' : 'var(--danger)') ?>;"></div>
                                                    </div>
                                                    <span style="font-weight:700; font-size:0.8rem;"><?= $row['efficiency'] ?>%</span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <!-- 4. Tasks & Projects Report View -->
                <?php elseif ($rType === 'task'): ?>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
                        <div class="card glass-card" style="padding:1rem; text-align:center;">
                            <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted);">Total Tasks</div>
                            <h3 style="font-size:1.5rem; font-weight:800; color:var(--primary); margin-top:0.25rem;"><?= $reportData['summary']['total_tasks'] ?></h3>
                        </div>
                        <div class="card glass-card" style="padding:1rem; text-align:center;">
                            <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted);">Completed Tasks</div>
                            <h3 style="font-size:1.5rem; font-weight:800; color:var(--success); margin-top:0.25rem;"><?= $reportData['summary']['completed'] ?></h3>
                        </div>
                        <div class="card glass-card" style="padding:1rem; text-align:center;">
                            <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted);">Active / In Progress</div>
                            <h3 style="font-size:1.5rem; font-weight:800; color:var(--warning); margin-top:0.25rem;"><?= $reportData['summary']['in_progress'] ?></h3>
                        </div>
                    </div>

                    <div class="card glass-card">
                        <h3 class="card-title">Task Checklist Details</h3>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Task Title</th>
                                        <th>Client Name</th>
                                        <th>Assigned To</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Due Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($reportData['data'])): ?>
                                        <tr><td colspan="6" style="text-align:center; color:var(--text-muted);">No records found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($reportData['data'] as $row): ?>
                                            <tr>
                                                <td style="font-weight:600;"><?= htmlspecialchars($row['title']) ?></td>
                                                <td><?= htmlspecialchars($row['client_name']) ?></td>
                                                <td><?= htmlspecialchars($row['staff_name'] ?: 'Unassigned') ?></td>
                                                <td><span class="badge badge-outline"><?= htmlspecialchars($row['category']) ?></span></td>
                                                <td><span class="badge badge-<?= $row['status'] === 'completed' ? 'completed' : 'progress' ?>"><?= strtoupper(str_replace('_', ' ', $row['status'])) ?></span></td>
                                                <td><?= htmlspecialchars($row['due_date']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <!-- 5. Compliance Report View -->
                <?php elseif ($rType === 'compliance'): ?>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
                        <div class="card glass-card" style="padding:1rem; text-align:center;">
                            <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted);">Total Filings</div>
                            <h3 style="font-size:1.5rem; font-weight:800; color:var(--primary); margin-top:0.25rem;"><?= $reportData['summary']['total_filings'] ?></h3>
                        </div>
                        <div class="card glass-card" style="padding:1rem; text-align:center;">
                            <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted);">Filed Returns</div>
                            <h3 style="font-size:1.5rem; font-weight:800; color:var(--success); margin-top:0.25rem;"><?= $reportData['summary']['filed'] ?></h3>
                        </div>
                        <div class="card glass-card" style="padding:1rem; text-align:center;">
                            <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted);">Overdue returns</div>
                            <h3 style="font-size:1.5rem; font-weight:800; color:var(--danger); margin-top:0.25rem;"><?= $reportData['summary']['overdue'] ?></h3>
                        </div>
                        <div class="card glass-card" style="padding:1rem; text-align:center;">
                            <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted);">Escalated Cases</div>
                            <h3 style="font-size:1.5rem; font-weight:800; color:var(--warning); margin-top:0.25rem;"><?= $reportData['summary']['escalated'] ?></h3>
                        </div>
                    </div>

                    <div class="card glass-card">
                        <h3 class="card-title">Compliance filings tracking</h3>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Filing Title</th>
                                        <th>Client Name</th>
                                        <th>Category</th>
                                        <th>Due Date</th>
                                        <th>Filing Date</th>
                                        <th>Status</th>
                                        <th>Escalation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($reportData['data'])): ?>
                                        <tr><td colspan="7" style="text-align:center; color:var(--text-muted);">No records found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($reportData['data'] as $row): ?>
                                            <tr>
                                                <td style="font-weight:700;"><?= htmlspecialchars($row['title']) ?></td>
                                                <td><?= htmlspecialchars($row['client_name']) ?></td>
                                                <td><span class="badge badge-outline"><?= htmlspecialchars($row['category']) ?></span></td>
                                                <td><?= htmlspecialchars($row['due_date']) ?></td>
                                                <td><?= htmlspecialchars($row['filing_date'] ?: 'N/A') ?></td>
                                                <td><span class="badge badge-<?= $row['status'] === 'filed' ? 'completed' : 'progress' ?>"><?= strtoupper($row['status']) ?></span></td>
                                                <td>
                                                    <?php if ($row['escalated']): ?>
                                                        <span class="badge badge-overdue" style="background-color:var(--danger); color:#fff;">ESCALATED</span>
                                                    <?php else: ?>
                                                        --
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <!-- 6. Profit & Margin Report View -->
                <?php elseif ($rType === 'profit'): ?>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
                        <div class="card glass-card" style="padding:1rem; text-align:center;">
                            <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted);">Invoiced Revenue</div>
                            <h3 style="font-size:1.5rem; font-weight:800; color:var(--primary); margin-top:0.25rem;">₹<?= number_format($reportData['summary']['revenue'], 2) ?></h3>
                        </div>
                        <div class="card glass-card" style="padding:1rem; text-align:center;">
                            <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted);">Total Expenses + Payroll</div>
                            <h3 style="font-size:1.5rem; font-weight:800; color:var(--danger); margin-top:0.25rem;">₹<?= number_format($reportData['summary']['total_expenses'], 2) ?></h3>
                        </div>
                        <div class="card glass-card" style="padding:1rem; text-align:center;">
                            <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted);">Net Profit / Surplus</div>
                            <h3 style="font-size:1.5rem; font-weight:800; color: <?= $reportData['summary']['net_profit'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>; margin-top:0.25rem;">₹<?= number_format($reportData['summary']['net_profit'], 2) ?></h3>
                        </div>
                        <div class="card glass-card" style="padding:1rem; text-align:center;">
                            <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted);">Profit Margin</div>
                            <h3 style="font-size:1.5rem; font-weight:800; color:var(--success); margin-top:0.25rem;"><?= $reportData['summary']['margin'] ?>%</h3>
                        </div>
                    </div>

                    <div class="card glass-card">
                        <h3 class="card-title">Profit & Loss Detailed Ledger</h3>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Accounting Entry Line Item</th>
                                        <th style="text-align:right;">Amount (INR)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr style="font-weight:700; color:var(--primary);">
                                        <td>Total Billed Invoiced Revenue (+)</td>
                                        <td style="text-align:right;">₹<?= number_format($reportData['summary']['revenue'], 2) ?></td>
                                    </tr>
                                    <tr style="color:var(--text-muted);">
                                        <td>Approved Operating Expenses (-)</td>
                                        <td style="text-align:right;">- ₹<?= number_format($reportData['summary']['custom_expenses'], 2) ?></td>
                                    </tr>
                                    <tr style="color:var(--text-muted);">
                                        <td>Active Employees Payroll Salaries (-)</td>
                                        <td style="text-align:right;">- ₹<?= number_format($reportData['summary']['payroll_salaries'], 2) ?></td>
                                    </tr>
                                    <tr style="font-weight:800; border-top:2px solid rgba(255,255,255,0.08); font-size:1rem; color: <?= $reportData['summary']['net_profit'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>;">
                                        <td>NET PROFIT / MARGIN SURPLUS</td>
                                        <td style="text-align:right;">₹<?= number_format($reportData['summary']['net_profit'], 2) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>


            <!-- ================== AUTOMATION HUB TAB ================== -->
            <?php elseif ($activeTab === 'automation' && $isAdmin): 
                $backupsDir = __DIR__ . '/uploads/backups/';
                $backupFiles = [];
                if (is_dir($backupsDir)) {
                    $backupFiles = array_filter(scandir($backupsDir), function($f) {
                        return strpos($f, 'db_backup_') === 0 && strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'sql';
                    });
                    rsort($backupFiles);
                }

                $reportsDir = __DIR__ . '/uploads/reports/';
                $scheduledReports = [];
                if (is_dir($reportsDir)) {
                    $scheduledReports = array_filter(scandir($reportsDir), function($f) {
                        return strpos($f, 'monthly_report_') === 0 && strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'json';
                    });
                    rsort($scheduledReports);
                }
            ?>
                <!-- Automation Hub tab layout -->
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;" class="no-print">
                    <h3 style="font-size:1.25rem; font-weight:700; margin:0;">System Automation Control Hub</h3>
                    <form action="index.php?tab=automation" method="POST" style="margin:0;">
                        <input type="hidden" name="action" value="run_cron_manually">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <button type="submit" class="btn btn-primary" style="display:flex; align-items:center; gap:0.5rem;">
                            <i data-lucide="play" style="width:16px;"></i> Trigger Cron Tasks Manually
                        </button>
                    </form>
                </div>

                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap:1.5rem;">
                    <!-- Database Backups Manager -->
                    <div class="card glass-card">
                        <h3 class="card-title"><i data-lucide="database" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:var(--primary);"></i>Database Backups</h3>
                        <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:1rem;">Automated SQL dumps are executed daily and stored securely. You can download copies below.</p>
                        
                        <div style="display:flex; flex-direction:column; gap:0.5rem; max-height:300px; overflow-y:auto;">
                            <?php if (empty($backupFiles)): ?>
                                <p style="color:var(--text-muted); font-style:italic; font-size:0.85rem;">No backup files created yet.</p>
                            <?php else: ?>
                                <?php foreach ($backupFiles as $bf): 
                                    $size = @filesize($backupsDir . $bf);
                                ?>
                                    <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.02); padding:0.5rem; border-radius:var(--radius-sm); border:1px solid rgba(255,255,255,0.05); font-size:0.85rem;">
                                        <div>
                                            <div style="font-weight:700;"><?= htmlspecialchars($bf) ?></div>
                                            <div style="font-size:0.75rem; color:var(--text-muted);"><?= Util::formatBytes($size) ?> | Created: <?= date('Y-m-d H:i A', @filemtime($backupsDir . $bf)) ?></div>
                                        </div>
                                        <a href="uploads/backups/<?= urlencode($bf) ?>" download class="btn btn-secondary" style="padding:0.25rem 0.5rem; font-size:0.75rem;">Download</a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Scheduled P&L Report Snapshots -->
                    <div class="card glass-card">
                        <h3 class="card-title"><i data-lucide="file-text" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:var(--success);"></i>Scheduled Reports Snapshots</h3>
                        <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:1rem;">Monthly operational reports compile billing, revenue, expenses, and margins snapshots.</p>

                        <div style="display:flex; flex-direction:column; gap:0.5rem; max-height:300px; overflow-y:auto;">
                            <?php if (empty($scheduledReports)): ?>
                                <p style="color:var(--text-muted); font-style:italic; font-size:0.85rem;">No monthly report snapshots available.</p>
                            <?php else: ?>
                                <?php foreach ($scheduledReports as $sr): 
                                    $data = json_decode(@file_get_contents($reportsDir . $sr), true);
                                ?>
                                    <div style="background:rgba(255,255,255,0.02); padding:0.75rem; border-radius:var(--radius-sm); border:1px solid rgba(255,255,255,0.05); font-size:0.85rem;">
                                        <div style="display:flex; justify-content:space-between; align-items:center; font-weight:700; color:var(--primary); margin-bottom:0.25rem;">
                                            <span>Period: <?= htmlspecialchars($data['period'] ?? 'N/A') ?></span>
                                            <a href="uploads/reports/<?= urlencode($sr) ?>" download style="font-size:0.75rem; text-decoration:underline;">Download JSON</a>
                                        </div>
                                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.25rem; font-size:0.75rem; color:var(--text-muted);">
                                            <span>Billed Rev: <strong>₹<?= number_format($data['billed_revenue'] ?? 0, 2) ?></strong></span>
                                            <span>Expenses: <strong>₹<?= number_format($data['total_expenses'] ?? 0, 2) ?></strong></span>
                                            <span>Net Profit: <strong style="color:var(--success);">₹<?= number_format($data['net_profit'] ?? 0, 2) ?></strong></span>
                                            <span>Margin: <strong><?= $data['profit_margin'] ?? 0 ?>%</strong></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>


            <!-- ================== SUPER ADMIN SAAS PORTAL TAB ================== -->
            <?php elseif ($activeTab === 'saas' && $user['role'] === 'super_admin'): 
                $tenants = Tenant::getTenants();
                $saasBills = Tenant::getBillingHistory();
                $allLogs = Security::getActivityLogs(100);
            ?>
                <!-- SaaS Portal Dashboard -->
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;" class="no-print">
                    <h3 style="font-size:1.25rem; font-weight:700; margin:0;">Super Admin SaaS Portal</h3>
                    <button class="btn btn-primary" data-open-modal="add-tenant-modal">
                        <i data-lucide="plus-circle" style="width:16px; margin-right:4px;"></i> Register Tenant Firm
                    </button>
                </div>

                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap:1.5rem; margin-bottom:1.5rem;">
                    <!-- Tenants List Card -->
                    <div class="card glass-card" style="grid-column: span 2;">
                        <h3 class="card-title"><i data-lucide="building" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:var(--primary);"></i>Registered Client Firms</h3>
                        <div class="table-container" style="margin-top:1rem;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Firm Name</th>
                                        <th>Active Plan</th>
                                        <th>Active Users</th>
                                        <th>Storage Used</th>
                                        <th>Filing Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tenants as $ten): 
                                        $tStats = Tenant::getTenantStats($ten['id']);
                                    ?>
                                        <tr>
                                            <td style="font-weight:700;"><?= htmlspecialchars($ten['name']) ?></td>
                                            <td><span class="badge badge-outline" style="text-transform:uppercase;"><?= htmlspecialchars($ten['plan_name']) ?></span></td>
                                            <td><strong><?= $tStats['users_active'] ?></strong> / <?= $ten['user_limit'] ?> employees</td>
                                            <td><strong><?= $tStats['storage_mb'] ?> MB</strong> / <?= $ten['storage_limit_mb'] ?> MB</td>
                                            <td>
                                                <span class="badge badge-completed" style="background-color: <?= $ten['status'] === 'active' ? 'var(--success)' : 'var(--danger)' ?>; color:#fff;">
                                                    <?= htmlspecialchars($ten['status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Plans Matrix Configuration summary -->
                    <div class="card glass-card">
                        <h3 class="card-title"><i data-lucide="sliders" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:var(--warning);"></i>Subscription Plans Config</h3>
                        <div style="display:flex; flex-direction:column; gap:0.75rem; margin-top:1rem; font-size:0.85rem;">
                            <div style="background:rgba(255,255,255,0.02); padding:0.5rem; border-radius:var(--radius-sm); border:1px solid rgba(255,255,255,0.05);">
                                <div style="font-weight:700; color:var(--primary); margin-bottom:0.25rem;">BASIC PLAN (₹1,500/mo)</div>
                                <div>Max Users: 5 | Max Storage: 1 GB (1024 MB)</div>
                            </div>
                            <div style="background:rgba(255,255,255,0.02); padding:0.5rem; border-radius:var(--radius-sm); border:1px solid rgba(255,255,255,0.05);">
                                <div style="font-weight:700; color:var(--success); margin-bottom:0.25rem;">PROFESSIONAL PLAN (₹5,000/mo)</div>
                                <div>Max Users: 15 | Max Storage: 5 GB (5120 MB)</div>
                            </div>
                            <div style="background:rgba(255,255,255,0.02); padding:0.5rem; border-radius:var(--radius-sm); border:1px solid rgba(255,255,255,0.05);">
                                <div style="font-weight:700; color:#a855f7; margin-bottom:0.25rem;">ENTERPRISE PLAN (₹15,000/mo)</div>
                                <div>Max Users: Unlimited | Max Storage: 100 GB</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SaaS Billing Ledger & Platform Activity Log -->
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap:1.5rem;">
                    <!-- Billing Ledger Card -->
                    <div class="card glass-card">
                        <h3 class="card-title"><i data-lucide="file-text" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:var(--primary);"></i>SaaS Billing & Invoices</h3>
                        <div class="table-container" style="margin-top:1rem;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Tenant Firm</th>
                                        <th>Amount</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($saasBills)): ?>
                                        <tr><td colspan="5" style="text-align:center; color:var(--text-muted);">No subscription billing invoices generated.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($saasBills as $bill): ?>
                                            <tr style="font-size:0.8rem;">
                                                <td style="font-weight:700;"><?= htmlspecialchars($bill['invoice_number']) ?></td>
                                                <td><?= htmlspecialchars($bill['tenant_name']) ?></td>
                                                <td><strong>₹<?= number_format($bill['amount'], 2) ?></strong></td>
                                                <td><?= htmlspecialchars($bill['due_date']) ?></td>
                                                <td>
                                                    <span class="badge badge-outline" style="font-size:0.7rem; color: <?= $bill['status'] === 'paid' ? 'var(--success)' : 'var(--danger)' ?>; border-color: <?= $bill['status'] === 'paid' ? 'var(--success)' : 'var(--danger)' ?>;">
                                                        <?= htmlspecialchars($bill['status']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Platform-wide Activity Logs Card -->
                    <div class="card glass-card">
                        <h3 class="card-title"><i data-lucide="history" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:var(--success);"></i>Global Activity Audits</h3>
                        <div style="display:flex; flex-direction:column; gap:0.5rem; max-height:300px; overflow-y:auto; margin-top:1rem; padding-right:0.25rem;">
                            <?php foreach ($allLogs as $log): ?>
                                <div style="background:rgba(255,255,255,0.02); padding:0.5rem; border-radius:var(--radius-sm); border:1px solid rgba(255,255,255,0.05); font-size:0.75rem;">
                                    <div style="display:flex; justify-content:space-between; font-weight:700; margin-bottom:0.15rem; color:#fff;">
                                        <span><?= htmlspecialchars($log['user_name'] ?: 'System') ?></span>
                                        <span style="color:var(--text-muted); font-weight:400;"><?= date('H:i A', strtotime($log['created_at'])) ?></span>
                                    </div>
                                    <div style="color:var(--text-muted);"><span class="badge badge-outline" style="font-size:0.6rem; padding:0 3px;"><?= htmlspecialchars($log['action']) ?></span> <?= htmlspecialchars($log['details']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Add Tenant Modal Overlay -->
                <div class="modal-overlay" id="add-tenant-modal">
                    <div class="modal-container" style="max-width:450px;">
                        <div class="modal-header">
                            <h3 style="font-size:1.15rem; font-weight:700;">Register Tenant Firm</h3>
                            <button class="modal-close" data-close-modal="add-tenant-modal">&times;</button>
                        </div>
                        <form action="index.php?tab=saas" method="POST" style="display:grid; gap:1rem;">
                            <input type="hidden" name="action" value="register_tenant">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                            <div class="form-group">
                                <label class="form-label">Firm/Company Name</label>
                                <input type="text" name="tenant_name" class="form-control" required placeholder="e.g. Sterling Audit Partners">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Choose Subscription Plan</label>
                                <select name="plan_name" class="form-control" required>
                                    <option value="basic">Basic (₹1,500/mo - 5 Users, 1GB storage)</option>
                                    <option value="professional">Professional (₹5,000/mo - 15 Users, 5GB storage)</option>
                                    <option value="enterprise">Enterprise (₹15,000/mo - Unlimited Users, 100GB storage)</option>
                                </select>
                            </div>

                    <button type="submit" class="btn btn-primary" style="width:100%; padding:0.75rem;">Register Firm Tenant</button>
                        </form>
                    </div>
                </div>

            <!-- ================== DSC & EXPIRIES TAB ================== -->
            <?php elseif ($activeTab === 'dsc'): 
                $dscTokens = OfficeSuite::getDSCTokens();
                $docExpiries = OfficeSuite::getDocumentExpiries();
                $clientOptions = Client::getClients();
            ?>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;" class="no-print">
                    <h3 style="font-size:1.25rem; font-weight:700; margin:0;">DSC & Document Expiries</h3>
                    <div style="display:flex; gap:0.5rem;">
                        <button class="btn btn-primary" data-open-modal="add-dsc-modal">
                            <i data-lucide="plus" style="width:16px; margin-right:4px;"></i> Register DSC Token
                        </button>
                        <button class="btn btn-secondary" data-open-modal="add-expiry-modal">
                            <i data-lucide="plus" style="width:16px; margin-right:4px;"></i> Add Doc Expiry
                        </button>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap:1.5rem;">
                    <!-- DSC Inventory -->
                    <div class="card glass-card">
                        <h3 class="card-title"><i data-lucide="key" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:var(--color-crm);"></i>Director DSC Inventory</h3>
                        <div class="table-container" style="margin-top:1rem;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Director</th>
                                        <th>Firm / Client</th>
                                        <th>Expiry Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($dscTokens)): ?>
                                        <tr><td colspan="5" style="text-align:center; color:var(--text-muted);">No DSC tokens registered.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($dscTokens as $tok): 
                                            $daysLeft = (strtotime($tok['expiry_date']) - time()) / 86400;
                                            $statusClass = 'completed';
                                            $statusText = 'Active';
                                            if ($daysLeft < 0) {
                                                $statusClass = 'overdue';
                                                $statusText = 'Expired';
                                            } elseif ($daysLeft <= 30) {
                                                $statusClass = 'progress';
                                                $statusText = 'Expiring Soon';
                                            }
                                        ?>
                                            <tr>
                                                <td style="font-weight:700;"><?= htmlspecialchars($tok['director_name']) ?></td>
                                                <td><?= htmlspecialchars($tok['client_name']) ?></td>
                                                <td><?= date('d M Y', strtotime($tok['expiry_date'])) ?></td>
                                                <td><span class="badge badge-<?= $statusClass ?>"><?= $statusText ?></span></td>
                                                <td>
                                                    <div style="display:flex; gap:0.25rem;">
                                                        <button class="btn btn-secondary" style="padding:0.25rem 0.5rem;" title="View Password & PIN" onclick="alert('Password Hint: <?= htmlspecialchars(addslashes($tok['password_hint'] ?: 'None')) ?>\nPIN Hint: <?= htmlspecialchars(addslashes($tok['pin_hint'] ?: 'None')) ?>')">
                                                            <i data-lucide="info" style="width:12px;height:12px;"></i>
                                                        </button>
                                                        <form action="index.php?tab=dsc" method="POST" style="margin:0;" onsubmit="return confirm('Delete token?')">
                                                            <input type="hidden" name="action" value="delete_dsc">
                                                            <input type="hidden" name="id" value="<?= $tok['id'] ?>">
                                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                            <button type="submit" class="btn btn-danger" style="padding:0.25rem 0.5rem;"><i data-lucide="trash" style="width:12px;height:12px;"></i></button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Other Documents Expiry Tracker -->
                    <div class="card glass-card">
                        <h3 class="card-title"><i data-lucide="file-text" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:var(--color-saas);"></i>Critical Document Expiries</h3>
                        <div class="table-container" style="margin-top:1rem;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Document Type</th>
                                        <th>Client / Firm</th>
                                        <th>Expiry Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($docExpiries)): ?>
                                        <tr><td colspan="5" style="text-align:center; color:var(--text-muted);">No document expiries tracked.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($docExpiries as $exp): 
                                            $daysLeft = (strtotime($exp['expiry_date']) - time()) / 86400;
                                            $statusClass = 'completed';
                                            $statusText = 'Active';
                                            if ($daysLeft < 0) {
                                                $statusClass = 'overdue';
                                                $statusText = 'Expired';
                                            } elseif ($daysLeft <= 30) {
                                                $statusClass = 'progress';
                                                $statusText = 'Expiring Soon';
                                            }
                                        ?>
                                            <tr>
                                                <td style="font-weight:700;"><?= htmlspecialchars($exp['doc_type']) ?></td>
                                                <td><?= htmlspecialchars($exp['client_name']) ?></td>
                                                <td><?= date('d M Y', strtotime($exp['expiry_date'])) ?></td>
                                                <td><span class="badge badge-<?= $statusClass ?>"><?= $statusText ?></span></td>
                                                <td>
                                                    <form action="index.php?tab=dsc" method="POST" style="margin:0;" onsubmit="return confirm('Delete expiry alert?')">
                                                        <input type="hidden" name="action" value="delete_expiry">
                                                        <input type="hidden" name="id" value="<?= $exp['id'] ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                        <button type="submit" class="btn btn-danger" style="padding:0.25rem 0.5rem;"><i data-lucide="trash" style="width:12px;height:12px;"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Add DSC Modal -->
                <div class="modal-overlay" id="add-dsc-modal">
                    <div class="modal-container" style="max-width:450px;">
                        <div class="modal-header">
                            <h3 style="font-size:1.15rem; font-weight:700;">Register Director DSC</h3>
                            <button class="modal-close" data-close-modal="add-dsc-modal">&times;</button>
                        </div>
                        <form action="index.php?tab=dsc" method="POST" style="display:grid; gap:0.75rem;">
                            <input type="hidden" name="action" value="add_dsc">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <div class="form-group">
                                <label class="form-label">Client Entity</label>
                                <select name="client_id" class="form-control" required>
                                    <option value="">Select client...</option>
                                    <?php foreach ($clientOptions as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Director Name</label>
                                <input type="text" name="director_name" class="form-control" required placeholder="e.g. Ramesh Kumar">
                            </div>
                            <div class="form-group">
                                <label class="form-label">DSC Expiry Date</label>
                                <input type="date" name="expiry_date" class="form-control" required>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem;">
                                <div class="form-group">
                                    <label class="form-label">Password Hint</label>
                                    <input type="text" name="password_hint" class="form-control" placeholder="Optional hint">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">PIN Hint</label>
                                    <input type="text" name="pin_hint" class="form-control" placeholder="Optional hint">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%; padding:0.6rem; margin-top:0.5rem;">Register DSC</button>
                        </form>
                    </div>
                </div>

                <!-- Add Doc Expiry Modal -->
                <div class="modal-overlay" id="add-expiry-modal">
                    <div class="modal-container" style="max-width:450px;">
                        <div class="modal-header">
                            <h3 style="font-size:1.15rem; font-weight:700;">Add Document Expiry</h3>
                            <button class="modal-close" data-close-modal="add-expiry-modal">&times;</button>
                        </div>
                        <form action="index.php?tab=dsc" method="POST" style="display:grid; gap:0.75rem;">
                            <input type="hidden" name="action" value="add_expiry">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <div class="form-group">
                                <label class="form-label">Client Entity</label>
                                <select name="client_id" class="form-control" required>
                                    <option value="">Select client...</option>
                                    <?php foreach ($clientOptions as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Document/License Type</label>
                                <input type="text" name="doc_type" class="form-control" required placeholder="e.g. Partnership Deed, IEC License, Shop Act">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" name="expiry_date" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%; padding:0.6rem; margin-top:0.5rem;">Add Expiry Tracker</button>
                        </form>
                    </div>
                </div>

            <!-- ================== TAX COMPUTATION TAB ================== -->
            <?php elseif ($activeTab === 'tax'): 
                $clientOptions = Client::getClients();
                $cId = intval($_GET['calc_client_id'] ?? 0);
                $computations = [];
                if ($cId > 0) {
                    $computations = OfficeSuite::getTaxComputations($cId);
                }
            ?>
                <div class="card glass-card">
                    <h3 class="card-title"><i data-lucide="calculator" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:var(--color-crm);"></i>Income Tax Computation (New vs Old Regime)</h3>
                    
                    <form action="index.php" method="GET" style="display:flex; gap:0.5rem; align-items:center; margin-bottom:1.5rem;" class="no-print">
                        <input type="hidden" name="tab" value="tax">
                        <select name="calc_client_id" class="form-control" style="width:250px; padding:0.5rem;" onchange="this.form.submit()">
                            <option value="">Select Client/Entity...</option>
                            <?php foreach ($clientOptions as $opt): ?>
                                <option value="<?= $opt['id'] ?>" <?= ($cId == $opt['id']) ? 'selected' : '' ?>><?= htmlspecialchars($opt['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary">Load History</button>
                    </form>

                    <?php if ($cId > 0): ?>
                        <div style="display:grid; grid-template-columns:1fr 1.2fr; gap:1.5rem;">
                            <!-- Calculator Form -->
                            <div>
                                <h4 style="font-weight:700; margin-bottom:0.75rem;">Income Details (FY 2025-26)</h4>
                                <form action="index.php?tab=tax&calc_client_id=<?= $cId ?>" method="POST" style="display:grid; gap:0.75rem;">
                                    <input type="hidden" name="action" value="add_tax_computation">
                                    <input type="hidden" name="client_id" value="<?= $cId ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    
                                    <div class="form-group">
                                        <label class="form-label">Financial Year</label>
                                        <select name="financial_year" class="form-control" required>
                                            <option value="2025-26">FY 2025-26 (AY 2026-27)</option>
                                            <option value="2024-25">FY 2024-25 (AY 2025-26)</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Gross Salary Income</label>
                                        <input type="number" name="gross_salary" class="form-control" value="0" step="any">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Income from House Property (Net)</label>
                                        <input type="number" name="house_property" class="form-control" value="0" step="any">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Capital Gains Income</label>
                                        <input type="number" name="cap_gains" class="form-control" value="0" step="any">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Profits/Gains of Business or Profession</label>
                                        <input type="number" name="business_income" class="form-control" value="0" step="any">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Income from Other Sources</label>
                                        <input type="number" name="other_sources" class="form-control" value="0" step="any">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Deductions (Section 80C, 80D etc. - Old Regime Only)</label>
                                        <input type="number" name="deductions_old" class="form-control" value="0" step="any">
                                    </div>
                                    <button type="submit" class="btn btn-primary" style="padding:0.75rem; margin-top:0.5rem;">Compute & Save Calculation</button>
                                </form>
                            </div>

                            <!-- Historical Computations -->
                            <div>
                                <h4 style="font-weight:700; margin-bottom:0.75rem;">Filing & Computation History</h4>
                                <div style="display:flex; flex-direction:column; gap:0.75rem; max-height:480px; overflow-y:auto; padding-right:0.25rem;">
                                    <?php if (empty($computations)): ?>
                                        <p style="color:var(--text-muted); font-style:italic;">No historical computations logged for this client.</p>
                                    <?php else: ?>
                                        <?php foreach ($computations as $comp): ?>
                                            <div class="card glass-card" style="padding:1rem;">
                                                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:0.5rem; margin-bottom:0.5rem;">
                                                    <span style="font-weight:700; color:var(--primary);">FY <?= htmlspecialchars($comp['financial_year']) ?></span>
                                                    <form action="index.php?tab=tax&calc_client_id=<?= $cId ?>" method="POST" style="margin:0;">
                                                        <input type="hidden" name="action" value="delete_tax_computation">
                                                        <input type="hidden" name="id" value="<?= $comp['id'] ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                        <button type="submit" class="btn btn-danger" style="padding:0.15rem 0.35rem; font-size:0.7rem;"><i data-lucide="trash" style="width:12px;height:12px;"></i></button>
                                                    </form>
                                                </div>
                                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem; font-size:0.8rem; line-height:1.5;">
                                                    <span>Gross Income: <strong>₹<?= number_format($comp['gross_salary']+$comp['house_property']+$comp['cap_gains']+$comp['business_income']+$comp['other_sources'], 2) ?></strong></span>
                                                    <span>Deductions (Old): <strong>₹<?= number_format($comp['deductions_old'], 2) ?></strong></span>
                                                    <span style="color:var(--text-muted);">Tax (Old Regime): <strong style="color:var(--text-main);">₹<?= number_format($comp['tax_old'], 2) ?></strong></span>
                                                    <span style="color:var(--text-muted);">Tax (New Regime): <strong style="color:var(--text-main);">₹<?= number_format($comp['tax_new'], 2) ?></strong></span>
                                                    <div style="grid-column: span 2; margin-top:0.25rem;">
                                                        <span class="badge badge-completed" style="font-size:0.75rem;">Preferred: <?= htmlspecialchars($comp['preferred_regime']) ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center; padding:3rem; color:var(--text-muted);">
                            <i data-lucide="calculator" style="width:3rem; height:3rem; opacity:0.3; margin-bottom:1rem;"></i>
                            <p>Please select a client from the dropdown above to load computation forms and history.</p>
                        </div>
                    <?php endif; ?>
                </div>

            <!-- ================== GSTR RECONCILER TAB ================== -->
            <?php elseif ($activeTab === 'gstr'): ?>
                <div class="card glass-card">
                    <h3 class="card-title"><i data-lucide="file-check-2" style="vertical-align:middle; margin-right:0.5rem; width:18px; color:var(--color-saas);"></i>GSTR-2B vs. Purchases Reconciliation</h3>
                    <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:1.5rem;">Upload the GSTR-2B JSON file and your client's Purchase Ledger (CSV format) to match Input Tax Credit (ITC) instantly. All parsing is done locally in your browser.</p>
                    
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem;" class="no-print">
                        <div class="card glass-card" style="padding:1.25rem; border:2px dashed rgba(255,255,255,0.08);">
                            <label class="form-label" style="font-weight:700;"><i data-lucide="file-json" style="vertical-align:middle; margin-right:0.25rem; width:16px;"></i> 1. Upload GSTR-2B (JSON)</label>
                            <input type="file" id="gstr2b-file" class="form-control" accept=".json" style="margin-top:0.5rem;">
                        </div>
                        <div class="card glass-card" style="padding:1.25rem; border:2px dashed rgba(255,255,255,0.08);">
                            <label class="form-label" style="font-weight:700;"><i data-lucide="file-spreadsheet" style="vertical-align:middle; margin-right:0.25rem; width:16px;"></i> 2. Upload Purchases Ledger (CSV)</label>
                            <input type="file" id="purchases-file" class="form-control" accept=".csv" style="margin-top:0.5rem;">
                            <div style="font-size:0.7rem; color:var(--text-muted); margin-top:0.5rem;">Required headers: <code>GSTIN, InvoiceNumber, InvoiceDate, InvoiceValue, CGST, SGST, IGST</code></div>
                        </div>
                    </div>
                    
                    <div style="text-align:center;" class="no-print">
                        <button type="button" class="btn btn-primary" style="padding:0.75rem 2rem; font-weight:700;" onclick="runGSTRReconciliation()">
                            <i data-lucide="sparkles" style="width:16px; margin-right:4px;"></i> Run Reconciliation Matching
                        </button>
                    </div>

                    <!-- Comparison Results Dashboard (hidden initially) -->
                    <div id="reconcile-dashboard" style="display:none; margin-top:2rem; flex-direction:column; gap:1.5rem;">
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem;">
                            <div class="stat-card gradient-emerald-teal">
                                <div class="stat-info">
                                    <span class="stat-label">Perfect Match</span>
                                    <span id="match-count" class="stat-value">0</span>
                                </div>
                                <div style="font-size:1.75rem;"><i data-lucide="check-circle-2"></i></div>
                            </div>
                            <div class="stat-card gradient-amber-orange">
                                <div class="stat-info">
                                    <span class="stat-label">Tax Mismatches</span>
                                    <span id="mismatch-count" class="stat-value">0</span>
                                </div>
                                <div style="font-size:1.75rem;"><i data-lucide="alert-circle"></i></div>
                            </div>
                            <div class="stat-card gradient-blue-indigo">
                                <div class="stat-info">
                                    <span class="stat-label">Missing in Ledger</span>
                                    <span id="missing-ledger-count" class="stat-value">0</span>
                                </div>
                                <div style="font-size:1.75rem;"><i data-lucide="file-question"></i></div>
                            </div>
                            <div class="stat-card gradient-red-rose">
                                <div class="stat-info">
                                    <span class="stat-label">Missing in GSTR-2B</span>
                                    <span id="missing-gstr-count" class="stat-value">0</span>
                                </div>
                                <div style="font-size:1.75rem;"><i data-lucide="x-circle"></i></div>
                            </div>
                        </div>

                        <div class="card glass-card">
                            <h4 style="font-weight:700;">Reconciliation Details Table</h4>
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Invoice No</th>
                                            <th>Vendor GSTIN</th>
                                            <th>GSTR-2B Value</th>
                                            <th>Ledger Value</th>
                                            <th>Status Details</th>
                                        </tr>
                                    </thead>
                                    <tbody id="reconcile-table-body">
                                        <!-- Javascript injected rows -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    function parseCSV(text) {
                        const lines = text.split('\n');
                        if (lines.length === 0) return [];
                        const headers = lines[0].split(',').map(h => h.trim().replace(/^["']|["']$/g, ''));
                        const result = [];
                        
                        for (let i = 1; i < lines.length; i++) {
                            if (!lines[i].trim()) continue;
                            const cols = lines[i].split(',').map(c => c.trim().replace(/^["']|["']$/g, ''));
                            const row = {};
                            headers.forEach((header, idx) => {
                                row[header] = cols[idx] || '';
                            });
                            result.push(row);
                        }
                        return result;
                    }

                    function runGSTRReconciliation() {
                        const gstrFile = document.getElementById('gstr2b-file').files[0];
                        const ledgerFile = document.getElementById('purchases-file').files[0];

                        if (!gstrFile || !ledgerFile) {
                            alert('Please upload both the GSTR-2B JSON file and the Purchase Ledger CSV file.');
                            return;
                        }

                        Promise.all([
                            gstrFile.text().then(t => JSON.parse(t)),
                            ledgerFile.text().then(t => parseCSV(t))
                        ]).then(([gstr, ledger]) => {
                            const gstrInvoices = {};
                            if (gstr.b2b) {
                                gstr.b2b.forEach(b2b => {
                                    const ctin = b2b.ctin;
                                    if (b2b.inv) {
                                        b2b.inv.forEach(inv => {
                                            const invNo = inv.inum;
                                            gstrInvoices[invNo] = {
                                                gstin: ctin,
                                                inum: invNo,
                                                val: parseFloat(inv.val || 0),
                                                cgst: parseFloat(inv.camt || 0),
                                                sgst: parseFloat(inv.samt || 0),
                                                igst: parseFloat(inv.iamt || 0),
                                                matched: false
                                            };
                                        });
                                    }
                                });
                            }

                            let perfectMatch = 0;
                            let mismatchVal = 0;
                            let missingLedger = 0;
                            let missingGstr = 0;
                            const tableRows = [];

                            ledger.forEach(row => {
                                const invNo = row.InvoiceNumber;
                                const gstin = row.GSTIN;
                                const val = parseFloat(row.InvoiceValue || 0);

                                if (invNo && gstrInvoices[invNo]) {
                                    gstrInvoices[invNo].matched = true;
                                    const gVal = gstrInvoices[invNo].val;
                                    if (Math.abs(gVal - val) < 2) {
                                        perfectMatch++;
                                        tableRows.push(`
                                            <tr>
                                                <td style="font-weight:700;">${invNo}</td>
                                                <td>${gstin}</td>
                                                <td>₹${gVal.toLocaleString('en-IN')}</td>
                                                <td>₹${val.toLocaleString('en-IN')}</td>
                                                <td><span class="badge badge-completed">Perfect Match</span></td>
                                            </tr>
                                        `);
                                    } else {
                                        mismatchVal++;
                                        tableRows.push(`
                                            <tr>
                                                <td style="font-weight:700;">${invNo}</td>
                                                <td>${gstin}</td>
                                                <td>₹${gVal.toLocaleString('en-IN')}</td>
                                                <td style="color:var(--warning); font-weight:700;">₹${val.toLocaleString('en-IN')}</td>
                                                <td><span class="badge badge-progress">Value Mismatch (Diff: ₹${Math.abs(gVal-val).toFixed(2)})</span></td>
                                            </tr>
                                        `);
                                    }
                                } else if (invNo) {
                                    missingGstr++;
                                    tableRows.push(`
                                        <tr>
                                            <td style="font-weight:700;">${invNo}</td>
                                            <td>${gstin}</td>
                                            <td>-</td>
                                            <td>₹${val.toLocaleString('en-IN')}</td>
                                            <td><span class="badge badge-overdue">Missing in GSTR-2B</span></td>
                                        </tr>
                                    `);
                                }
                            });

                            Object.keys(gstrInvoices).forEach(invNo => {
                                if (!gstrInvoices[invNo].matched) {
                                    missingLedger++;
                                    tableRows.push(`
                                        <tr>
                                            <td style="font-weight:700;">${invNo}</td>
                                            <td>${gstrInvoices[invNo].gstin}</td>
                                            <td>₹${gstrInvoices[invNo].val.toLocaleString('en-IN')}</td>
                                            <td>-</td>
                                            <td><span class="badge badge-outline" style="border-color:var(--primary); color:var(--primary);">Missing in Purchase Ledger</span></td>
                                        </tr>
                                    `);
                                }
                            });

                            document.getElementById('match-count').innerText = perfectMatch;
                            document.getElementById('mismatch-count').innerText = mismatchVal;
                            document.getElementById('missing-ledger-count').innerText = missingLedger;
                            document.getElementById('missing-gstr-count').innerText = missingGstr;
                            document.getElementById('reconcile-table-body').innerHTML = tableRows.join('');

                            document.getElementById('reconcile-dashboard').style.display = 'flex';
                            if (typeof lucide !== 'undefined') lucide.createIcons();
                        }).catch(err => {
                            console.error(err);
                            alert('Failed to run reconciliation. Please ensure your files are in the correct format.');
                        });
                    }
                </script>
            <?php endif; ?>
        </main>
    </div>

    <!-- Toast Notification Container -->
    <div id="toast-container" class="no-print"></div>



    <?php if (!empty($success)): ?>
        <script>window.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($success) ?>, 'success'));</script>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <script>window.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($error) ?>, 'danger'));</script>
    <?php endif; ?>

    <!-- AI Document Analysis Modal -->
    <div class="modal-overlay" id="ai-document-analysis-modal">
        <div class="modal-container" style="max-width:600px; width:95%;">
            <div class="modal-header">
                <h3 style="font-size:1.15rem; font-weight:700; display:flex; align-items:center; gap:0.5rem; color:var(--color-crm);">
                    <i data-lucide="sparkles" style="width:18px; height:18px;"></i> AI Document Parser Insights
                </h3>
                <button class="modal-close" data-close-modal="ai-document-analysis-modal">&times;</button>
            </div>
            <div id="ai-loading-state" style="text-align:center; padding:3rem 0;">
                <div style="width:3rem; height:3rem; border:4px solid var(--bg-card); border-top-color:var(--color-crm); border-radius:50%; animation:loadingSkeleton 1s linear infinite; margin:0 auto 1.5rem auto;"></div>
                <div style="font-weight:600; color:var(--text-main);">AI is analyzing the document...</div>
                <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.5rem;">Extracting structure, taxpayer names, tax identifiers, and warnings.</div>
            </div>
            <div id="ai-result-state" style="display:none; flex-direction:column; gap:1.25rem;">
                <div class="stat-card gradient-emerald-teal" style="padding: 1.25rem;">
                    <div>
                        <div style="font-size: 0.8rem; text-transform: uppercase; font-weight: 600; opacity: 0.8;">Document Type Identified</div>
                        <h2 id="ai-doc-type" style="font-size: 1.5rem; font-weight: 800; margin-top: 0.25rem;">-</h2>
                    </div>
                    <div style="font-size: 1.75rem;"><i data-lucide="file-check"></i></div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                    <div class="card glass-card" style="padding:1rem; gap:0.25rem;">
                        <span style="font-size:0.75rem; color:var(--text-muted); font-weight:600; text-transform:uppercase;">Taxpayer Name</span>
                        <span id="ai-doc-name" style="font-weight:700; font-size:0.95rem;">-</span>
                    </div>
                    <div class="card glass-card" style="padding:1rem; gap:0.25rem;">
                        <span style="font-size:0.75rem; color:var(--text-muted); font-weight:600; text-transform:uppercase;">Financial Year</span>
                        <span id="ai-doc-fy" style="font-weight:700; font-size:0.95rem;">-</span>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                    <div class="card glass-card" style="padding:1rem; gap:0.25rem;">
                        <span style="font-size:0.75rem; color:var(--text-muted); font-weight:600; text-transform:uppercase;">PAN Number</span>
                        <span id="ai-doc-pan" style="font-weight:700; color:var(--primary); font-size:0.95rem;">-</span>
                    </div>
                    <div class="card glass-card" style="padding:1rem; gap:0.25rem;">
                        <span style="font-size:0.75rem; color:var(--text-muted); font-weight:600; text-transform:uppercase;">GSTIN</span>
                        <span id="ai-doc-gstin" style="font-weight:700; color:var(--success); font-size:0.95rem;">-</span>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                    <div class="card glass-card" style="padding:1rem; gap:0.25rem;">
                        <span style="font-size:0.75rem; color:var(--text-muted); font-weight:600; text-transform:uppercase;">Total Amount</span>
                        <span id="ai-doc-amount" style="font-weight:700; font-size:0.95rem;">-</span>
                    </div>
                    <div class="card glass-card" style="padding:1rem; gap:0.25rem;">
                        <span style="font-size:0.75rem; color:var(--text-muted); font-weight:600; text-transform:uppercase;">Verification Status</span>
                        <span id="ai-doc-status" class="badge" style="width:fit-content; font-size:0.75rem; margin-top:0.15rem;">-</span>
                    </div>
                </div>

                <div class="card glass-card" style="padding:1rem; border-left:4px solid var(--danger);">
                    <div style="font-weight:700; font-size:0.85rem; color:var(--danger); display:flex; align-items:center; gap:0.35rem;">
                        <i data-lucide="alert-triangle" style="width:14px; height:14px;"></i> Warnings & Flags
                    </div>
                    <ul id="ai-doc-warnings" style="font-size:0.8rem; line-height:1.4; margin-top:0.5rem; padding-left:1.25rem; display:flex; flex-direction:column; gap:0.25rem;">
                        <!-- Warnings list -->
                    </ul>
                </div>

                <button type="button" class="btn btn-secondary" data-close-modal="ai-document-analysis-modal" style="padding:0.75rem; font-weight:600;">Close Insights</button>
            </div>
        </div>
    </div>

    <!-- PDF Preview Modal -->
    <div class="modal-overlay" id="pdf-preview-modal">
        <div class="modal-container" style="max-width:850px; width:95%;">
            <div class="modal-header">
                <h3 style="font-size:1.15rem; font-weight:700;" id="preview-file-title">Document Preview</h3>
                <div style="display:flex; align-items:center; gap:1rem;">
                    <label style="display:inline-flex; align-items:center; gap:0.25rem; font-size:0.85rem; cursor:pointer; color:var(--primary); font-weight:bold;">
                        <input type="checkbox" id="toggle-watermark-cb" onchange="toggleWatermarkOverlay(this.checked)"> Add Watermark
                    </label>
                    <button class="modal-close" data-close-modal="pdf-preview-modal">&times;</button>
                </div>
            </div>
            <div style="position:relative; width:100%; height:550px; background:#222; border-radius:var(--radius-sm); overflow:hidden;">
                <!-- Watermark overlay -->
                <div id="preview-watermark-layer" style="display:none; position:absolute; top:0; left:0; width:100%; height:100%; pointer-events:none; z-index:1000; justify-content:center; align-items:center; transform:rotate(-30deg); opacity:0.25; font-size:3.5rem; font-weight:900; color:#ef4444; text-transform:uppercase; white-space:nowrap; user-select:none;">
                    CONFIDENTIAL - CA ASSOCIATES
                </div>
                <iframe id="preview-pdf-iframe" src="" style="width:100%; height:100%; border:none; background:#fff;"></iframe>
            </div>
        </div>
    </div>

    <!-- Digital Signature Modal -->
    <div class="modal-overlay" id="doc-signature-modal">
        <div class="modal-container" style="max-width:500px;">
            <div class="modal-header">
                <h3 style="font-size:1.15rem; font-weight:700;">Digital Signature Pad</h3>
                <button class="modal-close" data-close-modal="doc-signature-modal">&times;</button>
            </div>
            <form action="index.php?tab=clients&client_id=<?= $clientId ?>&sub=<?= urlencode($subTab) ?>&folder=<?= urlencode($currentFolder) ?>" method="POST">
                <input type="hidden" name="action" value="sign_document">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" id="sign-doc-id" name="id">

                <div class="form-group">
                    <label class="form-label">Draw Signature below</label>
                    <div style="border: 2px dashed var(--primary); background:#1a1a1a; border-radius:var(--radius-md); overflow:hidden; position:relative; height:180px;">
                        <canvas id="signature-canvas" width="460" height="176" style="cursor:crosshair; width:100%; height:100%; display:block;"></canvas>
                    </div>
                    <button type="button" class="btn btn-secondary" style="margin-top:0.5rem; padding:0.25rem 0.5rem; font-size:0.75rem;" onclick="clearSignatureCanvas()">Clear Pad</button>
                </div>

                <div class="form-group">
                    <label class="form-label" for="sign-name">Type Full Name for Verification</label>
                    <input type="text" id="sign-name" name="signed_by" class="form-control" placeholder="e.g. CA Amit Kumar" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%; padding:0.75rem;">Apply Verified Digital Signature</button>
            </form>
        </div>
    </div>

    <!-- Floating AI Chat Assistant -->
    <div id="ai-chat-bubble" class="no-print" style="position:fixed; bottom:5rem; right:1.5rem; width:50px; height:50px; background:var(--primary); border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:0 10px 15px rgba(0,0,0,0.3); z-index:99999;" onclick="toggleAIChatWindow()">
        <i data-lucide="message-square" style="color:#fff; width:24px; height:24px;"></i>
    </div>
    <div id="ai-chat-window" class="no-print" style="display:none; position:fixed; bottom:9rem; right:1.5rem; width:320px; height:380px; background:var(--bg-surface); border:1px solid rgba(255,255,255,0.08); border-radius:var(--radius-lg); box-shadow:var(--clay-shadow-card); z-index:99999; overflow:hidden; flex-direction:column;">
        <div style="background:var(--primary); padding:0.75rem 1rem; color:#fff; display:flex; justify-content:space-between; align-items:center; font-weight:bold; font-size:0.9rem;">
            <span style="display:flex; align-items:center; gap:4px;"><i data-lucide="cpu" style="width:16px; height:16px;"></i>CA AI Assistant</span>
            <button onclick="toggleAIChatWindow()" style="background:transparent; border:none; color:#fff; cursor:pointer; font-size:1.15rem; font-weight:bold;">&times;</button>
        </div>
        <div id="ai-chat-messages" style="flex:1; overflow-y:auto; padding:0.75rem; display:flex; flex-direction:column; gap:0.5rem; font-size:0.8rem; line-height:1.4;">
            <div style="background:rgba(255,255,255,0.03); padding:0.5rem; border-radius:var(--radius-sm); align-self:flex-start; color:var(--text-muted);">Hello! I am your AI tax & workflow assistant. Ask me anything about due dates, backups, or system features!</div>
        </div>
        <div style="padding:0.5rem; border-top:1px solid rgba(255,255,255,0.05); display:flex; gap:0.5rem; background:rgba(0,0,0,0.1);">
            <input type="text" id="ai-chat-input" class="form-control" style="font-size:0.8rem; padding:0.35rem 0.5rem;" placeholder="Ask AI Assistant..." onkeydown="if(event.key==='Enter')sendAIChatPrompt()">
            <button onclick="sendAIChatPrompt()" class="btn btn-primary" style="padding:0.35rem 0.65rem; font-size:0.8rem;"><i data-lucide="send" style="width:14px; height:14px;"></i></button>
        </div>
    </div>

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // PDF Preview
        function openPdfPreview(filePath, fileName) {
            document.getElementById('preview-file-title').innerText = "Previewing: " + fileName;
            document.getElementById('preview-pdf-iframe').src = filePath;
            document.getElementById('toggle-watermark-cb').checked = false;
            document.getElementById('preview-watermark-layer').style.display = 'none';
            App.openModal('pdf-preview-modal');
        }

        function toggleWatermarkOverlay(checked) {
            document.getElementById('preview-watermark-layer').style.display = checked ? 'flex' : 'none';
        }

        // Signature Pad
        let sigCanvas = null;
        let sigCtx = null;
        let isDrawing = false;

        function openSignaturePad(docId, docName) {
            document.getElementById('sign-doc-id').value = docId;
            App.openModal('doc-signature-modal');
            setTimeout(() => {
                sigCanvas = document.getElementById('signature-canvas');
                if (sigCanvas) {
                    sigCtx = sigCanvas.getContext('2d');
                    clearSignatureCanvas();

                    // Mouse Events
                    sigCanvas.onmousedown = function(e) {
                        isDrawing = true;
                        sigCtx.beginPath();
                        const r = sigCanvas.getBoundingClientRect();
                        sigCtx.moveTo(e.clientX - r.left, e.clientY - r.top);
                    };
                    sigCanvas.onmousemove = function(e) {
                        if (!isDrawing) return;
                        sigCtx.lineWidth = 3;
                        sigCtx.lineCap = 'round';
                        sigCtx.strokeStyle = '#3b82f6';
                        const r = sigCanvas.getBoundingClientRect();
                        sigCtx.lineTo(e.clientX - r.left, e.clientY - r.top);
                        sigCtx.stroke();
                    };
                    sigCanvas.onmouseup = function() { isDrawing = false; };
                    sigCanvas.onmouseleave = function() { isDrawing = false; };

                    // Touch Events
                    sigCanvas.ontouchstart = function(e) {
                        isDrawing = true;
                        sigCtx.beginPath();
                        const touch = e.touches[0];
                        const r = sigCanvas.getBoundingClientRect();
                        sigCtx.moveTo(touch.clientX - r.left, touch.clientY - r.top);
                    };
                    sigCanvas.ontouchmove = function(e) {
                        if (!isDrawing) return;
                        const touch = e.touches[0];
                        const r = sigCanvas.getBoundingClientRect();
                        sigCtx.lineWidth = 3;
                        sigCtx.lineCap = 'round';
                        sigCtx.strokeStyle = '#3b82f6';
                        sigCtx.lineTo(touch.clientX - r.left, touch.clientY - r.top);
                        sigCtx.stroke();
                    };
                    sigCanvas.ontouchend = function() { isDrawing = false; };
                }
            }, 300);
        }

        function clearSignatureCanvas() {
            if (sigCanvas && sigCtx) {
                sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
            }
        }

        // Drag & Drop Upload
        document.addEventListener('DOMContentLoaded', () => {
            const dropArea = document.getElementById('drag-drop-area');
            if (dropArea) {
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropArea.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                    }, false);
                });

                ['dragenter', 'dragover'].forEach(eventName => {
                    dropArea.addEventListener(eventName, () => dropArea.classList.add('highlight'), false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    dropArea.addEventListener(eventName, () => dropArea.classList.remove('highlight'), false);
                });

                dropArea.addEventListener('drop', (e) => {
                    let dt = e.dataTransfer;
                    let files = dt.files;
                    if (files.length) {
                        document.getElementById('drag-drop-file-input').files = files;
                        document.getElementById('drag-drop-status').innerText = 'Selected via Drag-and-Drop: ' + files[0].name;
                    }
                }, false);
            }
        });

        // AI Dynamic Checklist Helper
        function suggestAISubtasks() {
            const titleInput = document.getElementById('t-title');
            const descTextarea = document.getElementById('t-desc');
            if (!titleInput || !titleInput.value.trim()) {
                alert('Please enter a Task Title first.');
                return;
            }
            
            const btn = document.querySelector('button[onclick="suggestAISubtasks()"]');
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="animate-spin" style="width:12px; height:12px;"></i> Generating...';
            if (typeof lucide !== 'undefined') lucide.createIcons();

            fetch('index.php?action=ai_query&type=subtasks&title=' + encodeURIComponent(titleInput.value.trim()))
                .then(res => res.json())
                .then(data => {
                    if (data.subtasks) {
                        const existingVal = descTextarea.value.trim();
                        descTextarea.value = (existingVal ? existingVal + "\n\n" : "") + "AI Checklist:\n" + data.subtasks;
                    } else {
                        alert('Could not generate subtasks from AI.');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Error calling AI service.');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
        }

        // AI Document Parser Helper
        function getMimeType(fileName) {
            const ext = fileName.split('.').pop().toLowerCase();
            if (ext === 'pdf') return 'application/pdf';
            if (ext === 'jpg' || ext === 'jpeg') return 'image/jpeg';
            if (ext === 'png') return 'image/png';
            if (ext === 'webp') return 'image/webp';
            return 'application/octet-stream';
        }

        function analyzeDocumentWithAI(filePath, fileName) {
            const mime = getMimeType(fileName);
            
            // Open modal
            const modal = document.getElementById('ai-document-analysis-modal');
            if (!modal) return;
            
            document.getElementById('ai-loading-state').style.display = 'block';
            document.getElementById('ai-result-state').style.display = 'none';
            App.openModal('ai-document-analysis-modal');
            
            fetch('index.php?action=ai_query&type=parse_doc&file_path=' + encodeURIComponent(filePath) + '&mime_type=' + encodeURIComponent(mime))
                .then(res => res.json())
                .then(res => {
                    document.getElementById('ai-loading-state').style.display = 'none';
                    if (res.success && res.data) {
                        const d = res.data;
                        document.getElementById('ai-doc-type').innerText = d.DocumentType || 'Unknown';
                        document.getElementById('ai-doc-name').innerText = d.TaxpayerName || 'Not Found';
                        document.getElementById('ai-doc-fy').innerText = d.FinancialYear || 'Not Found';
                        document.getElementById('ai-doc-pan').innerText = d.PAN || 'N/A';
                        document.getElementById('ai-doc-gstin').innerText = d.GSTIN || 'N/A';
                        document.getElementById('ai-doc-amount').innerText = d.TotalAmount ? '₹' + Number(d.TotalAmount).toLocaleString('en-IN') : 'N/A';
                        
                        const statusBadge = document.getElementById('ai-doc-status');
                        statusBadge.innerText = d.VerificationStatus || 'Suspect';
                        statusBadge.className = 'badge badge-' + ((d.VerificationStatus === 'Valid') ? 'completed' : 'danger');

                        const warningsList = document.getElementById('ai-doc-warnings');
                        warningsList.innerHTML = '';
                        if (d.Warnings && d.Warnings.length > 0) {
                            d.Warnings.forEach(w => {
                                const li = document.createElement('li');
                                li.innerText = w;
                                warningsList.appendChild(li);
                            });
                        } else {
                            const li = document.createElement('li');
                            li.innerText = 'No anomalies detected.';
                            li.style.listStyle = 'none';
                            li.style.color = 'var(--text-muted)';
                            warningsList.appendChild(li);
                        }

                        document.getElementById('ai-result-state').style.display = 'flex';
                    } else {
                        alert(res.message || 'AI failed to analyze document.');
                        App.closeModal('ai-document-analysis-modal');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Error calling AI document parser.');
                    App.closeModal('ai-document-analysis-modal');
                })
                .finally(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
        }
    </script>
    <script src="js/main.js?v=<?= filemtime(__DIR__ . '/js/main.js') ?>"></script>
</body>
</html>
