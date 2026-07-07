<?php
// public/invoice_print.php

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Accounting.php';
require_once __DIR__ . '/../src/Util.php';

Util::startSession();

// Access Guard: must be logged in or have portal token match
$user = Auth::getCurrentUser();
$invoiceId = intval($_GET['id'] ?? 0);
$invoice = Accounting::getInvoice($invoiceId);

if (!$invoice) {
    die("Invoice not found.");
}

// Security: Employees / Admins or Client match portal token
$authorized = false;
if ($user) {
    $authorized = true; // Staff/Admin access
} elseif (!empty($_SESSION['client_portal_token'])) {
    require_once __DIR__ . '/../src/Client.php';
    $client = Client::findClientByPortalToken($_SESSION['client_portal_token']);
    if ($client && $client['id'] == $invoice['client_id']) {
        $authorized = true;
    }
}

if (!$authorized) {
    die("Unauthorized access to this invoice.");
}

// Parse Design Config (Color template, Custom text, etc.)
$design = [
    'theme_color' => '#3b82f6', // Default blue
    'footer_text' => 'Thank you for your business!',
    'terms' => 'Payment is due within the stipulated period. Late payments are subject to interest rates.',
    'show_logo' => true
];

if (!empty($invoice['invoice_design'])) {
    $parsed = json_decode($invoice['invoice_design'], true);
    if (is_array($parsed)) {
        $design = array_merge($design, $parsed);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice - <?= htmlspecialchars($invoice['invoice_number']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f8fafc;
            color: #0f172a;
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 2rem;
            display: flex;
            justify-content: center;
        }
        .invoice-card {
            background: #ffffff;
            width: 100%;
            max-width: 800px;
            padding: 3rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
        }
        .header-bar {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .invoice-title {
            font-size: 2rem;
            font-weight: 800;
            color: <?= htmlspecialchars($design['theme_color']) ?>;
            letter-spacing: -0.04em;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .section-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 0.5rem;
        }
        .address-box {
            font-size: 0.9rem;
            line-height: 1.5;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }
        .details-table th {
            background-color: #f8fafc;
            padding: 0.75rem 1rem;
            border-bottom: 2px solid #e2e8f0;
            font-weight: 700;
            font-size: 0.8rem;
            color: #475569;
            text-transform: uppercase;
            text-align: left;
        }
        .details-table td {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
        }
        .totals-box {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.5rem;
            font-size: 0.9rem;
            margin-bottom: 2rem;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            width: 250px;
        }
        .grand-total {
            font-size: 1.2rem;
            font-weight: 800;
            color: #0f172a;
            border-top: 2px solid #e2e8f0;
            padding-top: 0.5rem;
        }
        .btn-print {
            background-color: <?= htmlspecialchars($design['theme_color']) ?>;
            color: #ffffff;
            border: none;
            padding: 0.65rem 1.25rem;
            font-weight: 700;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .badge-status {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }
            .invoice-card {
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
    <div class="invoice-card">
        <div class="no-print" style="display:flex; justify-content:space-between; margin-bottom: 1.5rem;">
            <button onclick="window.print()" class="btn-print">
                Print Invoice
            </button>
            <?php if ($invoice['status'] === 'unpaid'): ?>
                <a href="payment_checkout.php?id=<?= $invoice['id'] ?>" class="btn-print" style="text-decoration:none; background-color:#10b981;">
                    Pay Online (Gateway)
                </a>
            <?php endif; ?>
        </div>

        <div class="header-bar">
            <div>
                <?php if ($design['show_logo']): ?>
                    <div style="font-weight: 900; font-size: 1.5rem; color: <?= htmlspecialchars($design['theme_color']) ?>; letter-spacing: -0.05em; display:flex; align-items:center; gap:0.25rem;">
                        <span style="background:<?= htmlspecialchars($design['theme_color']) ?>; color:#fff; width:30px; height:30px; display:inline-flex; align-items:center; justify-content:center; border-radius:6px; font-size:1rem; font-weight:900;">CA</span>
                        CA FIRM SERVICES
                    </div>
                <?php else: ?>
                    <div style="font-weight: 800; font-size: 1.25rem; color:#475569;">TAX INVOICE</div>
                <?php endif; ?>
                <div style="color: #64748b; font-size: 0.8rem; margin-top: 0.25rem;">GSTIN: 27AAAAA1111A1Z1</div>
            </div>
            <div style="text-align: right;">
                <div class="invoice-title">TAX INVOICE</div>
                <div style="font-weight: 700; font-size: 0.95rem; margin-top: 0.25rem;"><?= htmlspecialchars($invoice['invoice_number']) ?></div>
            </div>
        </div>

        <div class="meta-grid">
            <div>
                <div class="section-title">Billed By</div>
                <div class="address-box">
                    <strong>CA Associates LLP</strong><br>
                    404 Finance Tower, Bandra Kurla Complex<br>
                    Mumbai, MH - 400051<br>
                    Email: billing@cafirm.com
                </div>
            </div>
            <div style="text-align: right;">
                <div class="section-title">Billed To</div>
                <div class="address-box">
                    <strong><?= htmlspecialchars($invoice['client_name']) ?></strong><br>
                    Phone: <?= htmlspecialchars($invoice['client_phone'] ?: 'N/A') ?><br>
                    Email: <?= htmlspecialchars($invoice['client_email'] ?: 'N/A') ?><br>
                    Status: <span class="badge-status" style="background-color: <?= $invoice['status'] === 'paid' ? '#dcfce7; color: #16a34a;' : '#fee2e2; color: #ef4444;' ?>"><?= strtoupper($invoice['status']) ?></span>
                </div>
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; margin-bottom: 2rem; border-top:1px solid #f1f5f9; padding-top:1rem; font-size:0.85rem;">
            <div>
                <strong>Issue Date:</strong> <?= htmlspecialchars($invoice['issue_date']) ?>
            </div>
            <div>
                <strong>Due Date:</strong> <?= htmlspecialchars($invoice['due_date']) ?>
            </div>
        </div>

        <table class="details-table">
            <thead>
                <tr>
                    <th>Service Description</th>
                    <th style="text-align: right; width: 150px;">Taxable Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-weight: 600;">
                        <?= htmlspecialchars($invoice['description'] ?: 'Professional Services Billed') ?>
                    </td>
                    <td style="text-align: right; font-weight: 600;">
                        ₹<?= number_format($invoice['amount'], 2) ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="totals-box">
            <div class="totals-row">
                <span style="color:#64748b;">Subtotal (Taxable):</span>
                <strong>₹<?= number_format($invoice['amount'], 2) ?></strong>
            </div>
            
            <?php if (floatval($invoice['cgst']) > 0): ?>
                <div class="totals-row">
                    <span style="color:#64748b;">CGST:</span>
                    <span>₹<?= number_format($invoice['cgst'], 2) ?></span>
                </div>
            <?php endif; ?>

            <?php if (floatval($invoice['sgst']) > 0): ?>
                <div class="totals-row">
                    <span style="color:#64748b;">SGST:</span>
                    <span>₹<?= number_format($invoice['sgst'], 2) ?></span>
                </div>
            <?php endif; ?>

            <?php if (floatval($invoice['igst']) > 0): ?>
                <div class="totals-row">
                    <span style="color:#64748b;">IGST:</span>
                    <span>₹<?= number_format($invoice['igst'], 2) ?></span>
                </div>
            <?php endif; ?>

            <?php if (floatval($invoice['tds_amount']) > 0): ?>
                <div class="totals-row" style="color: #ef4444;">
                    <span>TDS Deducted:</span>
                    <span>- ₹<?= number_format($invoice['tds_amount'], 2) ?></span>
                </div>
            <?php endif; ?>

            <div class="totals-row grand-total">
                <span>Net Total Payable:</span>
                <span>₹<?= number_format($invoice['net_amount'], 2) ?></span>
            </div>
        </div>

        <div style="border-top: 1px solid #e2e8f0; padding-top:1.5rem; margin-top: 2rem; font-size: 0.8rem; color: #64748b; line-height: 1.6;">
            <div style="font-weight: 700; margin-bottom: 0.25rem; text-transform: uppercase;">Terms & Conditions</div>
            <div><?= htmlspecialchars($design['terms']) ?></div>
            
            <div style="text-align: center; margin-top: 2.5rem; font-style: italic; font-weight: 600; color: <?= htmlspecialchars($design['theme_color']) ?>;">
                <?= htmlspecialchars($design['footer_text']) ?>
            </div>
        </div>
    </div>
</body>
</html>
