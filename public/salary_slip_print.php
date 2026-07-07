<?php
// public/salary_slip_print.php

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/HRMS.php';
require_once __DIR__ . '/../src/Util.php';

Util::startSession();

// Access Guard: must be logged in
Auth::requireLogin();

$user = Auth::getCurrentUser();
$isAdmin = ($user['role'] === 'super_admin' || $user['role'] === 'admin_manager');

$slipId = intval($_GET['id'] ?? 0);
$slip = HRMS::getSalarySlip($slipId);

if (!$slip) {
    die("Salary slip not found.");
}

// Security: Employees can only view their own slips
if (!$isAdmin && $slip['employee_id'] !== $user['id']) {
    die("Access denied. Unauthorized to view this salary slip.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Slip - <?= htmlspecialchars($slip['employee_name']) ?> - <?= htmlspecialchars($slip['month']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f1f5f9;
            color: #0f172a;
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 2rem;
            display: flex;
            justify-content: center;
        }
        .slip-container {
            background: #ffffff;
            width: 100%;
            max-width: 800px;
            padding: 3rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
        }
        .slip-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .slip-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: #3b82f6;
            letter-spacing: -0.03em;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .meta-group {
            font-size: 0.9rem;
        }
        .meta-label {
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            margin-bottom: 0.25rem;
        }
        .meta-value {
            font-weight: 700;
            color: #0f172a;
        }
        .breakdown-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }
        .breakdown-table th {
            background-color: #f8fafc;
            text-align: left;
            padding: 0.75rem 1rem;
            border-bottom: 2px solid #e2e8f0;
            font-weight: 700;
            font-size: 0.85rem;
            color: #475569;
            text-transform: uppercase;
        }
        .breakdown-table td {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
        }
        .net-payout-box {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 1.5rem;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
        }
        .no-print-btn {
            background-color: #3b82f6;
            color: #ffffff;
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 700;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.4);
            transition: all 0.2s;
        }
        .no-print-btn:hover {
            background-color: #2563eb;
        }
        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }
            .slip-container {
                box-shadow: none;
                border: none;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="slip-container">
        <div class="no-print" style="text-align: right; margin-bottom: 1.5rem;">
            <button onclick="window.print()" class="no-print-btn">
                Print / Save PDF
            </button>
        </div>

        <div class="slip-header">
            <div>
                <div class="slip-title">CA FIRM ERP SERVICES</div>
                <div style="color: #64748b; font-size: 0.85rem; margin-top: 0.25rem;">Corporate Headquarters | Staff Payroll Division</div>
            </div>
            <div style="text-align: right;">
                <div style="font-weight: 800; font-size: 1.25rem; text-transform: uppercase;">Pay Slip</div>
                <div style="color: #3b82f6; font-weight: 700; margin-top: 0.25rem;"><?= htmlspecialchars($slip['month']) ?></div>
            </div>
        </div>

        <div class="meta-grid">
            <div>
                <div class="meta-group" style="margin-bottom: 0.75rem;">
                    <div class="meta-label">Employee Name</div>
                    <div class="meta-value"><?= htmlspecialchars($slip['employee_name']) ?></div>
                </div>
                <div class="meta-group" style="margin-bottom: 0.75rem;">
                    <div class="meta-label">Department / Assignment</div>
                    <div class="meta-value"><?= htmlspecialchars($slip['department'] ?: 'Consulting') ?></div>
                </div>
                <div class="meta-group">
                    <div class="meta-label">Date of Joining</div>
                    <div class="meta-value"><?= htmlspecialchars($slip['joining_date'] ?: 'N/A') ?></div>
                </div>
            </div>
            <div style="text-align: right;">
                <div class="meta-group" style="margin-bottom: 0.75rem;">
                    <div class="meta-label">Salary Slip ID</div>
                    <div class="meta-value">#SLIP-<?= $slip['id'] ?></div>
                </div>
                <div class="meta-group" style="margin-bottom: 0.75rem;">
                    <div class="meta-label">Designation Role</div>
                    <div class="meta-value"><?= htmlspecialchars($slip['designation'] ?: 'Associate') ?></div>
                </div>
                <div class="meta-group">
                    <div class="meta-label">Payment Status</div>
                    <div class="meta-value" style="color: <?= $slip['status'] === 'paid' ? '#16a34a' : '#ea580c' ?>;">
                        <?= strtoupper($slip['status']) ?>
                    </div>
                </div>
            </div>
        </div>

        <table class="breakdown-table">
            <thead>
                <tr>
                    <th>Earnings (Allowances)</th>
                    <th style="text-align: right;">Amount</th>
                    <th>Deductions (Taxes)</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Basic Pay Scale</td>
                    <td style="text-align: right; font-weight: 600;">₹<?= number_format($slip['basic'], 2) ?></td>
                    <td>Provident Fund (PF)</td>
                    <td style="text-align: right; font-weight: 600; color: #dc2626;">₹<?= number_format($slip['pf'], 2) ?></td>
                </tr>
                <tr>
                    <td>House Rent Allowance (HRA)</td>
                    <td style="text-align: right; font-weight: 600;">₹<?= number_format($slip['hra'], 2) ?></td>
                    <td>Professional Tax (PT)</td>
                    <td style="text-align: right; font-weight: 600; color: #dc2626;">₹<?= number_format($slip['pt'], 2) ?></td>
                </tr>
                <tr>
                    <td>Conveyance Reimbursements</td>
                    <td style="text-align: right; font-weight: 600;">₹<?= number_format($slip['conveyance'], 2) ?></td>
                    <td>TDS Deductions</td>
                    <td style="text-align: right; font-weight: 600; color: #dc2626;">₹<?= number_format($slip['tds'], 2) ?></td>
                </tr>
                <tr>
                    <td>Special Incentives & Allowances</td>
                    <td style="text-align: right; font-weight: 600;">₹<?= number_format($slip['allowance'], 2) ?></td>
                    <td>--</td>
                    <td style="text-align: right; font-weight: 600;">₹0.00</td>
                </tr>
                <?php
                    $gross = $slip['basic'] + $slip['hra'] + $slip['conveyance'] + $slip['allowance'];
                    $deductions = $slip['pf'] + $slip['pt'] + $slip['tds'];
                ?>
                <tr style="background-color: #f8fafc; font-weight: 700;">
                    <td>Gross Earnings</td>
                    <td style="text-align: right;">₹<?= number_format($gross, 2) ?></td>
                    <td>Total Deductions</td>
                    <td style="text-align: right; color: #dc2626;">- ₹<?= number_format($deductions, 2) ?></td>
                </tr>
            </tbody>
        </table>

        <div class="net-payout-box">
            <div>
                <div style="font-size: 0.8rem; font-weight: 800; color: #1e3a8a; text-transform: uppercase; letter-spacing: 0.05em;">Net Monthly Payout</div>
                <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.15rem;">Billed Net Salary directly dispatched to salary account</div>
            </div>
            <div style="font-size: 1.75rem; font-weight: 800; color: #1e3a8a;">
                ₹<?= number_format($slip['net_salary'], 2) ?>
            </div>
        </div>

        <div style="margin-top: 4rem; display: grid; grid-template-columns: 1fr 1fr; font-size: 0.85rem; color: #64748b;">
            <div>
                <div style="height: 50px;"></div>
                <div style="border-top: 1px solid #cbd5e1; width: 200px; padding-top: 0.5rem; font-weight: 600;">Employee Signature</div>
            </div>
            <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end;">
                <div style="height: 50px;"></div>
                <div style="border-top: 1px solid #cbd5e1; width: 200px; padding-top: 0.5rem; font-weight: 600; text-align: center;">Authorized Signatory</div>
            </div>
        </div>
    </div>
</body>
</html>
