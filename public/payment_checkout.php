<?php
// public/payment_checkout.php

require_once __DIR__ . '/../src/Accounting.php';
require_once __DIR__ . '/../src/Util.php';

Util::startSession();

$invoiceId = intval($_GET['id'] ?? 0);
$invoice = Accounting::getInvoice($invoiceId);

if (!$invoice) {
    die("Invoice not found.");
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simulate credit card checkout
    $cardNumber = trim($_POST['card_number'] ?? '');
    $expiry = trim($_POST['expiry'] ?? '');
    $cvv = trim($_POST['cvv'] ?? '');
    
    if (strlen(str_replace(' ', '', $cardNumber)) === 16 && !empty($expiry) && strlen($cvv) === 3) {
        $res = Accounting::recordPayment($invoiceId, $invoice['net_amount'], date('Y-m-d'), 'Credit Card (Online)');
        if (isset($res['success'])) {
            $success = "Payment checkout completed successfully! Transaction Ref: TXN-" . rand(100000, 999999);
            // Redirect back to invoice print/detail page after 2 seconds
            header("Refresh: 2.5; URL=invoice_print.php?id=" . $invoiceId);
        } else {
            $error = $res['error'] ?? "Failed to process transaction.";
        }
    } else {
        $error = "Payment failed. Please check credit card inputs and try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Checkout Gateway - Invoice #<?= htmlspecialchars($invoice['invoice_number']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            font-family: 'Inter', sans-serif;
        }
        .checkout-container {
            width: 100%;
            max-width: 440px;
            padding: 1.5rem;
        }
        .checkout-card {
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 2.25rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
        }
        .form-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .form-control {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            color: #fff;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.2s;
        }
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
        }
        .pay-btn {
            background-color: #10b981;
            color: #fff;
            border: none;
            width: 100%;
            padding: 0.85rem;
            font-weight: 700;
            font-size: 0.95rem;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
            transition: all 0.2s;
        }
        .pay-btn:hover {
            background-color: #059669;
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <div class="checkout-card">
            <div style="text-align: center; margin-bottom: 2rem;">
                <div style="font-size: 0.7rem; font-weight: 800; color: #10b981; letter-spacing: 0.15em; text-transform: uppercase;">Secure Payment Terminal</div>
                <h1 style="font-size: 1.5rem; font-weight: 800; margin-top: 0.5rem; color:#fff;">Checkout Billing</h1>
                <p style="color: #94a3b8; font-size: 0.85rem; margin-top: 0.25rem;">Invoice <strong><?= htmlspecialchars($invoice['invoice_number']) ?></strong></p>
            </div>

            <div style="background: rgba(16, 185, 129, 0.1); border:1px solid rgba(16, 185, 129, 0.2); padding:1rem; border-radius:8px; display:flex; justify-content:space-between; align-items:center; margin-bottom: 1.5rem;">
                <span style="font-size:0.85rem; color:#a7f3d0;">Total Net Amount Due:</span>
                <span style="font-size:1.4rem; font-weight:800; color:#10b981;">₹<?= number_format($invoice['net_amount'], 2) ?></span>
            </div>

            <?php if ($success): ?>
                <div style="background-color: rgba(16, 185, 129, 0.15); color: #10b981; padding: 0.75rem 1rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600; margin-bottom: 1.5rem; border: 1px solid rgba(16, 185, 129, 0.2);">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div style="background-color: rgba(239, 68, 68, 0.15); color: #ef4444; padding: 0.75rem 1rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600; margin-bottom: 1.5rem; border: 1px solid rgba(239, 68, 68, 0.2);">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
                <form action="payment_checkout.php?id=<?= $invoiceId ?>" method="POST">
                    <div class="form-group">
                        <label class="form-label">Cardholder Name</label>
                        <input type="text" class="form-control" placeholder="e.g. John Doe" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Credit Card Number</label>
                        <input type="text" name="card_number" class="form-control" placeholder="xxxx xxxx xxxx xxxx" maxlength="19" required oninput="this.value = this.value.replace(/[^\d]/g, '').replace(/(.{4})/g, '$1 ').trim()">
                    </div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label class="form-label">Expiry MM/YY</label>
                            <input type="text" name="expiry" class="form-control" placeholder="MM/YY" maxlength="5" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">CVV</label>
                            <input type="password" name="cvv" class="form-control" placeholder="***" maxlength="3" required>
                        </div>
                    </div>
                    <button type="submit" class="pay-btn" style="margin-top:1rem;">
                        Authorize Payment & Checkout
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
