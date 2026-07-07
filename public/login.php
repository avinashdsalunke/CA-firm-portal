<?php
// public/login.php

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Util.php';
require_once __DIR__ . '/../src/Security.php';

Util::startSession();
Util::setSecurityHeaders();

// Enforce Whitelist IP Restriction
if (!Security::isIPWhitelisted(Security::getIP())) {
    http_response_code(403);
    die("<!DOCTYPE html><html><head><title>Access Denied</title><link rel='stylesheet' href='css/style.css'></head><body style='display:flex;align-items:center;justify-content:center;height:100vh;background-color:var(--bg-base);'><div class='card' style='max-width:400px;text-align:center;'><h1 style='color:var(--danger);'>Access Denied</h1><p style='color:var(--text-muted);margin-top:0.5rem;'>Your IP address (" . htmlspecialchars(Security::getIP()) . ") is not authorized to access this firm network.</p></div></body></html>");
}

if (Auth::isLoggedIn()) {
    Util::redirect('index.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $result = Auth::login($email, $password);
        if (isset($result['success'])) {
            Util::redirect('index.php');
        } elseif (isset($result['2fa_required'])) {
            Util::redirect('login_2fa.php');
        } else {
            $error = $result['error'] ?? "Login failed.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CA Firm CRM</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card" style="box-shadow: var(--clay-shadow-card);">
            <div style="text-align: center;">
                <h1 style="font-size: 1.75rem; font-weight: 800; color: var(--primary); letter-spacing: -0.03em; margin-bottom: 0.5rem;">CA FIRM CRM</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Chartered Accountant Practice Management</p>
            </div>
            
            <?php if ($error): ?>
                <div style="background-color: var(--danger-glow); color: var(--danger); padding: 0.75rem 1rem; border-radius: var(--radius-md); font-size: 0.85rem; font-weight: 600; text-align: center;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="form-group">
                    <label for="email" class="form-label">Email or ID</label>
                    <input type="text" id="email" name="email" class="form-control" placeholder="test or admin@example.com" required autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                </div>

                <button type="submit" class="btn btn-primary" style="padding: 0.85rem; margin-top: 0.5rem; font-size: 1rem; width: 100%;">
                    Sign In
                </button>
            </form>
            
            <div style="text-align: center; font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem;">
                Demo Credentials: <strong>test / test</strong>
            </div>
        </div>
    </div>
    <script src="js/main.js"></script>
</body>
</html>
