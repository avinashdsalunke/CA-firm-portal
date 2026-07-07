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
    <title>Login - AL-Hussain & Associates</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-primary: #0B2B5C; /* Deep Navy Blue */
            --brand-accent: #A22D30;  /* Crimson Red */
            --brand-success: #2C8A4A; /* Forest Green */
            --brand-bg: #F4F6F9;
        }

        body {
            font-family: 'Outfit', 'Inter', sans-serif;
            background: var(--brand-bg);
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        /* Top and Bottom Decorative Crimson Bands as in the reference image */
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: var(--brand-accent);
            z-index: 10;
        }
        body::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: var(--brand-accent);
            z-index: 10;
        }

        .login-container {
            display: flex;
            width: 950px;
            max-width: 90%;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
            overflow: hidden;
            border: 1px solid rgba(11, 43, 92, 0.08);
            min-height: 550px;
        }

        /* Branding Panel (Left Side) */
        .brand-panel {
            flex: 1.1;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #ffffff;
            position: relative;
            border-right: 1px solid rgba(11, 43, 92, 0.05);
        }

        .brand-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .brand-title-block {
            text-align: left;
        }

        .brand-name {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--brand-primary);
            margin: 0;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        .brand-subtitle {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--brand-primary);
            opacity: 0.85;
            margin: 0.25rem 0 0 0;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .brand-address {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--brand-accent);
            margin: 0.2rem 0 0 0;
        }

        /* Stylized CA Logo SVG (Matches top-right checkmark theme) */
        .ca-logo-svg {
            width: 80px;
            height: 60px;
        }

        /* Flat Modern SVG Illustration (Recreating the corporate office vector) */
        .illustration-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 2.5rem 0;
        }

        .illustration-container svg {
            width: 100%;
            max-width: 320px;
            height: auto;
        }

        /* Footer Credits */
        .brand-footer {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        /* Login Form Panel (Right Side) */
        .login-panel {
            flex: 0.9;
            padding: 3.5rem 3rem;
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-form-title {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--brand-primary);
            margin: 0 0 0.5rem 0;
            letter-spacing: -0.02em;
        }

        .login-form-desc {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin: 0 0 2rem 0;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--brand-primary);
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .form-control {
            border: 1.5px solid rgba(11, 43, 92, 0.15);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            font-family: inherit;
            color: var(--brand-primary);
            background: #ffffff;
            transition: all 0.25s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px rgba(11, 43, 92, 0.08);
        }

        .btn-submit {
            background: var(--brand-primary);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 0.85rem;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 4px 12px rgba(11, 43, 92, 0.15);
            width: 100%;
            margin-top: 0.75rem;
        }

        .btn-submit:hover {
            background: var(--brand-accent);
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(162, 45, 48, 0.2);
        }

        .error-banner {
            background: rgba(162, 45, 48, 0.06);
            color: var(--brand-accent);
            border-left: 4px solid var(--brand-accent);
            padding: 0.75rem 1rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .credentials-tip {
            text-align: center;
            background: rgba(44, 138, 74, 0.05);
            color: var(--brand-success);
            padding: 0.65rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            border: 1px dashed rgba(44, 138, 74, 0.2);
            margin-top: 1.5rem;
        }

        /* Responsive Breakpoints */
        @media (max-width: 850px) {
            .login-container {
                flex-direction: column;
                max-width: 480px;
            }
            .brand-panel {
                border-right: none;
                border-bottom: 1px solid rgba(11, 43, 92, 0.05);
                padding: 2rem;
            }
            .login-panel {
                padding: 2.5rem 2rem;
            }
            .illustration-container {
                display: none; /* Hide illustration on mobile to save vertical space */
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Brand Information Panel (Matches client theme image) -->
        <div class="brand-panel">
            <div class="brand-header">
                <div class="brand-title-block">
                    <h1 class="brand-name">AL-Hussain & Associates</h1>
                    <div class="brand-subtitle">Chartered Accountants</div>
                    <div class="brand-address">B-17, Varanasi (U.P.)</div>
                </div>
                <!-- CA Custom Stylized Logo -->
                <svg class="ca-logo-svg" viewBox="0 0 100 70" xmlns="http://www.w3.org/2000/svg">
                    <!-- Letter 'C' in Dark Navy -->
                    <path d="M40,20 C25,20 20,30 20,40 C20,50 25,60 40,60" fill="none" stroke="#0B2B5C" stroke-width="8" stroke-linecap="round" />
                    <!-- Letter 'A' in Dark Navy -->
                    <path d="M55,60 L70,20 L85,60" fill="none" stroke="#0B2B5C" stroke-width="8" stroke-linecap="round" stroke-linejoin="round" />
                    <!-- Checkmark (Tick) in green crossing the 'A' -->
                    <path d="M60,42 L72,55 L95,25" fill="none" stroke="#2C8A4A" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>

            <!-- Professional Office Work Illustration -->
            <div class="illustration-container">
                <svg viewBox="0 0 300 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Office Desk -->
                    <line x1="50" y1="130" x2="200" y2="130" stroke="#E2E8F0" stroke-width="4" />
                    <line x1="120" y1="130" x2="150" y2="180" stroke="#CBD5E1" stroke-width="3" />
                    <line x1="180" y1="130" x2="150" y2="180" stroke="#CBD5E1" stroke-width="3" />
                    
                    <!-- Office Chair -->
                    <rect x="70" y="100" width="30" height="25" rx="5" fill="#E2E8F0" />
                    <line x1="85" y1="125" x2="85" y2="160" stroke="#94A3B8" stroke-width="4" />
                    <line x1="70" y1="160" x2="100" y2="160" stroke="#94A3B8" stroke-width="4" />
                    
                    <!-- Male character -->
                    <!-- Body (Navy Jacket) -->
                    <path d="M140,180 L140,120 C140,115 155,115 155,120 L160,180 Z" fill="#0B2B5C" />
                    <!-- Trousers (Brown/Rust) -->
                    <path d="M140,180 L148,220 L153,220 L160,180 Z" fill="#A22D30" />
                    <!-- Head -->
                    <circle cx="150" cy="100" r="10" fill="#E0A96D" />
                    <path d="M145,103 C145,105 155,105 155,103 Z" fill="#475569" /> <!-- Beard -->
                    
                    <!-- Female character -->
                    <!-- Body (Dark Dress) -->
                    <path d="M210,180 L212,125 C212,120 228,120 228,125 L230,180 Z" fill="#0F172A" />
                    <!-- Head -->
                    <circle cx="220" cy="105" r="9" fill="#E0A96D" />
                    <!-- Hair -->
                    <path d="M210,105 C210,95 230,95 230,105 L232,120 L208,120 Z" fill="#475569" />
                    
                    <!-- Laptop (Floating / Held) -->
                    <path d="M165,120 L185,120 L190,132 L160,132 Z" fill="#CBD5E1" />
                    <line x1="165" y1="132" x2="185" y2="132" stroke="#475569" stroke-width="2" />
                </svg>
            </div>

            <div class="brand-footer">
                AL-Hussain & Associates &copy; <?= date('Y') ?>. All rights reserved.
            </div>
        </div>

        <!-- Secure Credentials Login Panel -->
        <div class="login-panel">
            <h2 class="login-form-title">Secure Login</h2>
            <p class="login-form-desc">Provide authentication keys to enter CRM portal.</p>

            <?php if ($error): ?>
                <div class="error-banner">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" style="display: flex; flex-direction: column; gap: 0.5rem;">
                <div class="form-group">
                    <label for="email" class="form-label">Email Address / User ID</label>
                    <input type="text" id="email" name="email" class="form-control" placeholder="test or admin@example.com" required autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                </div>

                <button type="submit" class="btn-submit">
                    Sign In to Portal
                </button>
            </form>

            <div class="credentials-tip">
                Demo Access: <strong>test / test</strong>
            </div>
        </div>
    </div>
    <script src="js/main.js"></script>
</body>
</html>
