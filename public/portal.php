<?php
// public/portal.php

require_once __DIR__ . '/../src/Client.php';
require_once __DIR__ . '/../src/Document.php';
require_once __DIR__ . '/../src/Util.php';
require_once __DIR__ . '/../src/Compliance.php';
require_once __DIR__ . '/../src/Accounting.php';

Util::startSession();
Util::setSecurityHeaders();

$token = trim($_GET['token'] ?? $_POST['token'] ?? $_SESSION['client_portal_token'] ?? '');
$client = Client::findClientByPortalToken($token);

$loginError = null;

// Handle Portal Login Form Submission
if (!$client && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'portal_login') {
    $loginToken = trim($_POST['portal_token'] ?? '');
    $client = Client::findClientByPortalToken($loginToken);
    if ($client) {
        $_SESSION['client_portal_token'] = $loginToken;
        $token = $loginToken;
        header("Location: portal.php");
        exit;
    } else {
        $loginError = "Invalid secure token. Contact your administrator to request a new key.";
    }
}

// Handle Logout
if ($client && isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['client_portal_token']);
    header("Location: portal.php");
    exit;
}

// If no client is resolved, render Login Screen
if (!$client) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Client Portal Login - Secure Session</title>
        <link rel="stylesheet" href="css/style.css">
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
            .login-container {
                width: 100%;
                max-width: 420px;
                padding: 1.5rem;
            }
            .glass-card {
                background: rgba(30, 41, 59, 0.4);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border: 1px solid rgba(255, 255, 255, 0.08);
                box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
                border-radius: var(--radius-lg);
                padding: 2.25rem;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="glass-card">
                <div style="text-align: center; margin-bottom: 2rem;">
                    <div style="font-size: 0.75rem; font-weight: 800; color: var(--primary); letter-spacing: 0.1em; text-transform: uppercase;">Secure Vault Access</div>
                    <h1 style="font-size: 1.75rem; font-weight: 800; margin-top: 0.5rem; letter-spacing: -0.03em; color:#fff;">CA Client Portal</h1>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.5rem;">Enter your unique portal security token to establish a session.</p>
                </div>

                <?php if ($loginError): ?>
                    <div style="background-color: var(--danger-glow); color: var(--danger); padding: 0.75rem 1rem; border-radius: var(--radius-md); font-size: 0.85rem; font-weight: 600; margin-bottom: 1.5rem; border: 1px solid rgba(239, 68, 68, 0.2);">
                        <?= htmlspecialchars($loginError) ?>
                    </div>
                <?php endif; ?>

                <form action="portal.php" method="POST">
                    <input type="hidden" name="action" value="portal_login">
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label for="portal_token" class="form-label" style="font-weight: 600;">Secure Portal Token</label>
                        <input type="password" id="portal_token" name="portal_token" class="form-control" placeholder="Paste your generated token key here" required style="letter-spacing: 0.05em; font-family: monospace;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem; font-weight: 700;">Establish Secure Connection</button>
                </form>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$error = null;
$success = null;

// Handle Document Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $requestId = !empty($_POST['request_id']) ? intval($_POST['request_id']) : null;
    $res = Document::uploadDocument($client['id'], $_FILES['document'], 'client', $requestId);
    if (isset($res['success'])) {
        $success = "Document uploaded successfully.";
    } else {
        $error = $res['error'] ?? "Failed to upload document.";
    }
}

// Handle Compliance Response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_compliance_response') {
    $compId = intval($_POST['compliance_id'] ?? 0);
    $clientResponse = trim($_POST['client_response'] ?? '');
    
    if ($compId > 0 && !empty($clientResponse)) {
        $comp = Compliance::getCompliance($compId);
        if ($comp && $comp['client_id'] == $client['id']) {
            $res = Compliance::updateClientResponse($compId, $clientResponse);
            if (isset($res['success'])) {
                $success = "Your response has been submitted successfully.";
            } else {
                $error = $res['error'] ?? "Failed to submit response.";
            }
        } else {
            $error = "Unauthorized attempt to respond to another client's compliance.";
        }
    } else {
        $error = "Please enter a valid response.";
    }
}

// Fetch lists
$requests = Document::getDocumentRequests(['client_id' => $client['id']]);
$documents = array_filter(Document::getDocuments($client['id']), function($d) {
    return $d['sharing_scope'] === 'client_shared';
});
$compliances = Compliance::getCompliances(['client_id' => $client['id']]);
$invoices = Accounting::getInvoices(['client_id' => $client['id']]);

$pendingOrOverdueCount = 0;
$overdueCount = 0;
foreach ($compliances as $c) {
    if ($c['status'] === 'pending') {
        $pendingOrOverdueCount++;
        if (strtotime($c['due_date']) < time()) {
            $overdueCount++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Portal - <?= htmlspecialchars($client['name']) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            display: block;
            background-color: var(--bg-base);
        }
        .portal-header {
            background-color: var(--bg-surface);
            padding: 1.5rem 2rem;
            box-shadow: var(--clay-shadow-flat);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom-left-radius: var(--radius-lg);
            border-bottom-right-radius: var(--radius-lg);
            margin-bottom: 2rem;
        }
        .portal-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 1rem 3rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        .portal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
    </style>
</head>
<body>
    <header class="portal-header">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--primary); letter-spacing: -0.03em;">CA CLIENT PORTAL</h1>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Secure workspace for <strong><?= htmlspecialchars($client['name']) ?></strong></p>
        </div>
        <div style="text-align: right; display:flex; align-items:center; gap:1rem;">
            <span class="badge badge-completed">Secure Session Active</span>
            <a href="portal.php?action=logout" class="btn btn-secondary" style="font-size:0.8rem; padding:0.4rem 0.8rem;">Logout</a>
        </div>
    </header>

    <main class="portal-container">
        <?php if ($success): ?>
            <div style="background-color: var(--success-glow); color: var(--success); padding: 0.75rem 1rem; border-radius: var(--radius-md); font-size: 0.9rem; font-weight: 600;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background-color: var(--danger-glow); color: var(--danger); padding: 0.75rem 1rem; border-radius: var(--radius-md); font-size: 0.9rem; font-weight: 600;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($pendingOrOverdueCount > 0): ?>
            <div style="background-color: var(--danger-glow); color: var(--danger); padding: 1rem; border-radius: var(--radius-md); font-size: 0.95rem; font-weight: 600; box-shadow: var(--clay-shadow-flat); display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
                <i data-lucide="alert-circle" style="width: 20px; height: 20px;"></i>
                <span>
                    Attention: You have <strong><?= $pendingOrOverdueCount ?></strong> pending compliance filing requirement(s)
                    <?php if ($overdueCount > 0): ?>
                        (including <strong><?= $overdueCount ?></strong> OVERDUE)
                    <?php endif; ?>
                    requiring your review/comments. Please check the filings table below.
                </span>
            </div>
        <?php endif; ?>

        <div class="portal-grid">
            <!-- Active Document Requests -->
            <div class="card">
                <h2 class="card-title">Pending Document Requests</h2>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0.5rem;">Please upload the requested files. Our team will review them promptly.</p>
                
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php 
                    $pendingRequests = array_filter($requests, function($r) { return $r['status'] === 'pending'; });
                    if (empty($pendingRequests)): 
                    ?>
                        <p style="color: var(--text-muted); font-style: italic; font-size: 0.9rem;">No pending document requests at this time.</p>
                    <?php else: ?>
                        <?php foreach ($pendingRequests as $req): ?>
                            <div style="background-color: var(--bg-input); padding: 1rem; border-radius: var(--radius-md); box-shadow: var(--clay-shadow-input); display: flex; flex-direction: column; gap: 0.5rem;">
                                <div>
                                    <h3 style="font-size: 0.95rem; font-weight: 700;"><?= htmlspecialchars($req['title']) ?></h3>
                                    <?php if ($req['description']): ?>
                                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;"><?= htmlspecialchars($req['description']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <form action="portal.php" method="POST" enctype="multipart/form-data" style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem;">
                                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                    <input type="file" name="document" required style="font-size: 0.8rem; color: var(--text-muted);">
                                    <button type="submit" class="btn btn-primary" style="padding: 0.45rem 1rem; font-size: 0.8rem;">Upload</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Upload Custom Document -->
            <div class="card">
                <h2 class="card-title">Upload General Document</h2>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0.5rem;">Need to share another file with us? Upload it here directly.</p>
                <form action="portal.php" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 1rem;">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <div class="form-group">
                        <label class="form-label">Select File (PDF, DOCX, XLSX, images max 10MB)</label>
                        <input type="file" name="document" class="form-control" required style="padding: 0.5rem;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="padding: 0.75rem;">Upload Document</button>
                </form>
            </div>
        </div>

        <!-- Statutory Compliance Filings -->
        <div class="card" style="margin-bottom: 2rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem;">
                <h2 class="card-title" style="margin-bottom:0; display:flex; align-items:center; gap:0.5rem;">
                    <i data-lucide="award" style="color:var(--primary); width:20px; height:20px;"></i>
                    Statutory Compliance Filings
                </h2>
                <span class="badge badge-outline" style="font-weight:600;">Action Required</span>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Return Title</th>
                            <th>Category</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>My Response / Comments</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($compliances)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted);">No compliance filing requirements found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($compliances as $c): 
                                $isOverdue = ($c['status'] === 'pending' && strtotime($c['due_date']) < time());
                                $badgeClass = $c['status'] === 'filed' ? 'badge-completed' : ($isOverdue ? 'badge-overdue' : 'badge-progress');
                                $statusLabel = $c['status'] === 'filed' ? 'Filed' : ($isOverdue ? 'Overdue' : 'Pending');
                            ?>
                                <tr>
                                    <td style="font-weight: 700;">
                                        <?= htmlspecialchars($c['title']) ?>
                                        <?php if (!empty($c['notes'])): ?>
                                            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 400; margin-top: 0.25rem;">
                                                <strong>Notes:</strong> <?= htmlspecialchars($c['notes']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge badge-outline"><?= htmlspecialchars($c['category']) ?></span></td>
                                    <td style="font-weight: 600; color: <?= $isOverdue ? 'var(--danger)' : 'inherit' ?>;">
                                        <?= htmlspecialchars($c['due_date']) ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= htmlspecialchars($statusLabel) ?>
                                        </span>
                                    </td>
                                    <td style="max-width: 300px; font-size: 0.85rem;">
                                        <?php if (!empty($c['client_response'])): ?>
                                            <div style="background-color: var(--bg-input); padding: 0.5rem; border-radius: var(--radius-sm); border-left: 3px solid var(--primary);">
                                                <?= htmlspecialchars($c['client_response']) ?>
                                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem; font-style: italic;">
                                                    Submitted: <?= date('d M Y, h:i A', strtotime($c['client_responded_at'])) ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-style: italic;">No response submitted yet.</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($c['status'] !== 'filed'): ?>
                                            <button class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;" onclick="openRespondModal(<?= $c['id'] ?>, '<?= addslashes($c['title']) ?>', '<?= addslashes($c['client_response'] ?? '') ?>')">
                                                <?= empty($c['client_response']) ? 'Submit Response' : 'Update Response' ?>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;" disabled>
                                                Filed
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Shared Documents List -->
        <div class="card" style="margin-bottom: 2rem;">
            <h2 class="card-title">Shared Documents</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Request Type</th>
                            <th>Uploaded By</th>
                            <th>Uploaded On</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($documents)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted);">No documents uploaded yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($documents as $doc): ?>
                                <tr>
                                    <td style="font-weight: 600;"><?= htmlspecialchars($doc['file_name']) ?></td>
                                    <td><?= htmlspecialchars($doc['request_title'] ?: 'General Document') ?></td>
                                    <td>
                                        <span class="badge <?= $doc['uploaded_by'] === 'client' ? 'badge-progress' : 'badge-completed' ?>">
                                            <?= htmlspecialchars($doc['uploaded_by']) ?>
                                        </span>
                                    </td>
                                    <td style="color: var(--text-muted); font-size: 0.85rem;"><?= htmlspecialchars($doc['created_at']) ?></td>
                                    <td>
                                        <a href="<?= htmlspecialchars($doc['file_path']) ?>" download class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                            Download
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Billing & Payment History -->
        <div class="card">
            <h2 class="card-title"><i data-lucide="credit-card" style="vertical-align:middle; margin-right:0.5rem; width:20px; color:var(--primary);"></i>Billing & Payment History</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Invoice Number</th>
                            <th>Service Detail</th>
                            <th>Billed Amount</th>
                            <th>Due Date</th>
                            <th>Payment Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($invoices)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted);">No billing transactions recorded.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($invoices as $inv): ?>
                                <tr>
                                    <td style="font-weight:700;"><?= htmlspecialchars($inv['invoice_number']) ?></td>
                                    <td><?= htmlspecialchars($inv['service_name'] ?? 'Professional Consulting Fees') ?></td>
                                    <td style="font-weight:700;">₹<?= number_format($inv['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($inv['due_date']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $inv['status'] === 'paid' ? 'completed' : 'progress' ?>">
                                            <?= strtoupper($inv['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:0.5rem;">
                                            <a href="invoice_print.php?id=<?= $inv['id'] ?>" target="_blank" class="btn btn-secondary" style="padding:0.35rem 0.5rem; font-size:0.75rem; display:inline-flex; align-items:center; gap:0.25rem;">
                                                <i data-lucide="eye" style="width:12px; height:12px;"></i> View & Print
                                            </a>
                                            <?php if ($inv['status'] === 'unpaid'): ?>
                                                <a href="payment_checkout.php?id=<?= $inv['id'] ?>" class="btn btn-primary" style="padding:0.35rem 0.5rem; font-size:0.75rem; display:inline-flex; align-items:center; gap:0.25rem;">
                                                    <i data-lucide="credit-card" style="width:12px; height:12px;"></i> Pay
                                                </a>
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
    </main>

    <!-- Respond Modal -->
    <div class="modal-overlay" id="respond-compliance-modal">
        <div class="modal-container" style="max-width: 500px;">
            <div class="modal-header">
                <h3 style="font-size:1.15rem; font-weight:700;">Submit Compliance Response</h3>
                <button class="modal-close" data-close-modal="respond-compliance-modal">&times;</button>
            </div>
            <form action="portal.php" method="POST">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <input type="hidden" name="action" value="submit_compliance_response">
                <input type="hidden" id="resp-compliance-id" name="compliance_id">
                
                <div class="form-group">
                    <label class="form-label">Return Detail</label>
                    <input type="text" id="resp-compliance-title" readonly class="form-control" style="background-color:var(--bg-input);">
                </div>
                
                <div class="form-group">
                    <label for="resp-client-text" class="form-label">Your Response / Status Updates / Comments</label>
                    <textarea id="resp-client-text" name="client_response" class="form-control" rows="4" placeholder="e.g. All documents have been uploaded / Please check details / Submitted documents to CA staff." required></textarea>
                </div>
                
                <div style="display:flex; gap:0.5rem; justify-content:flex-end; margin-top:1rem;">
                    <button type="button" class="btn btn-secondary" data-close-modal="respond-compliance-modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Response</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        function openRespondModal(id, title, existingResponse) {
            document.getElementById('resp-compliance-id').value = id;
            document.getElementById('resp-compliance-title').value = title;
            document.getElementById('resp-client-text').value = existingResponse;
            App.openModal('respond-compliance-modal');
        }
    </script>
</body>
</html>
