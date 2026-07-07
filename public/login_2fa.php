<?php
// public/login_2fa.php

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Util.php';

Util::startSession();
Util::setSecurityHeaders();

$tempUserId = $_SESSION['temp_2fa_user_id'] ?? 0;
if ($tempUserId <= 0) {
    Util::redirect('login.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    if (empty($code)) {
        $error = "Please enter the 6-digit verification code.";
    } else {
        if (Auth::verify2FA($tempUserId, $code)) {
            unset($_SESSION['temp_2fa_user_id']);
            Util::redirect('index.php');
        } else {
            $error = "Invalid or expired verification code. Please check your email inbox.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA Verification - CA Firm CRM</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: var(--bg-base);
            margin: 0;
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body>
    <div class="login-wrapper" style="width: 100%; max-width: 400px; padding: 1rem;">
        <div class="login-card" style="box-shadow: var(--clay-shadow-card); background: var(--bg-surface); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid rgba(255,255,255,0.08);">
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--primary); margin-bottom: 0.5rem;">Two-Factor Authentication</h1>
                <p style="color: var(--text-muted); font-size: 0.85rem;">Enter the 6-digit verification code sent to your registered email address.</p>
            </div>
            
            <?php if ($error): ?>
                <div style="background-color: var(--danger-glow); color: var(--danger); padding: 0.75rem 1rem; border-radius: var(--radius-md); font-size: 0.85rem; font-weight: 600; text-align: center; margin-bottom: 1rem; border: 1px solid rgba(239, 68, 68, 0.2);">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="login_2fa.php" method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="form-group">
                    <label for="code" class="form-label" style="font-weight:600;">Verification Code</label>
                    <input type="text" id="code" name="code" class="form-control" placeholder="123456" required autocomplete="off" style="text-align:center; font-size:1.5rem; letter-spacing:0.2em; font-family:monospace; padding:0.5rem;">
                </div>

                <button type="submit" class="btn btn-primary" style="padding: 0.85rem; font-weight: 700; width: 100%;">
                    Verify Code
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="login.php" style="color: var(--text-muted); font-size: 0.85rem; text-decoration: underline;">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
