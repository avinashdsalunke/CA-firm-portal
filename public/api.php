<?php
// public/api.php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Client.php';
require_once __DIR__ . '/../src/Task.php';
require_once __DIR__ . '/../src/Document.php';
require_once __DIR__ . '/../src/Accounting.php';
require_once __DIR__ . '/../src/Compliance.php';
require_once __DIR__ . '/../src/Report.php';
require_once __DIR__ . '/../src/HRMS.php';

// Resolve Route
$route = $_GET['route'] ?? '';
if (empty($route) && isset($_SERVER['PATH_INFO'])) {
    $route = trim($_SERVER['PATH_INFO'], '/');
}
$route = strtolower(trim($route));

// JSON Request Payload Reader
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// Bypass Auth for login
if ($route === 'login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["error" => "Method Not Allowed. Use POST."]);
        exit;
    }
    
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(["error" => "Missing email or password."]);
        exit;
    }

    $res = Auth::login($email, $password);
    if (isset($res['success'])) {
        // Find registered session token
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT session_token FROM user_sessions WHERE user_id = :id ORDER BY last_active DESC LIMIT 1");
        $stmt->execute(['id' => $res['user']['id']]);
        $sess = $stmt->fetch();

        echo json_encode([
            "success" => true,
            "token" => $sess['session_token'] ?? null,
            "user" => [
                "id" => $res['user']['id'],
                "name" => $res['user']['name'],
                "email" => $res['user']['email'],
                "role" => $res['user']['role']
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(["error" => $res['error'] ?? "Invalid credentials."]);
    }
    exit;
}

// Token Verification Guard for all other endpoints
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
$token = '';
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}

if (empty($token)) {
    $token = $_GET['token'] ?? '';
}

if (empty($token) || !Security::isSessionValid($token)) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized access. Valid Bearer Token required."]);
    exit;
}

// Find User by Session Token
$db = Database::getConnection();
$stmtUser = $db->prepare("
    SELECT u.* 
    FROM user_sessions us 
    JOIN users u ON us.user_id = u.id 
    WHERE us.session_token = :token 
    LIMIT 1
");
$stmtUser->execute(['token' => $token]);
$currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$currentUser) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid session context."]);
    exit;
}

// ROUTER ENDPOINTS DISPATCH
switch ($route) {
    case 'client':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            echo json_encode(Client::getClients());
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($input['name'] ?? '');
            $email = trim($input['email'] ?? '');
            $phone = trim($input['phone'] ?? '');
            
            if (empty($name) || empty($email)) {
                http_response_code(400);
                echo json_encode(["error" => "Missing client name or email."]);
                exit;
            }
            $res = Client::createClient($name, $email, $phone);
            echo json_encode($res);
        }
        break;

    case 'task':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            echo json_encode(Task::getTasks());
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $clientId = intval($input['client_id'] ?? 0);
            $title = trim($input['title'] ?? '');
            $category = trim($input['category'] ?? '');
            $dueDate = trim($input['due_date'] ?? '');
            $assignedTo = intval($input['assigned_to_user_id'] ?? 0);

            if (empty($title) || $clientId <= 0) {
                http_response_code(400);
                echo json_encode(["error" => "Missing task title or client_id."]);
                exit;
            }
            $res = Task::createTask($clientId, $title, $category, $dueDate, $assignedTo);
            echo json_encode($res);
        }
        break;

    case 'hrms':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            echo json_encode(HRMS::getAttendanceList(date('Y-m-d')));
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = trim($input['action'] ?? '');
            if ($action === 'clock_in') {
                $res = HRMS::clockIn($currentUser['id']);
                echo json_encode($res);
            } elseif ($action === 'clock_out') {
                $res = HRMS::clockOut($currentUser['id']);
                echo json_encode($res);
            } else {
                http_response_code(400);
                echo json_encode(["error" => "Action must be clock_in or clock_out."]);
            }
        }
        break;

    case 'accounting':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            echo json_encode(Accounting::getInvoices());
        } else {
            http_response_code(405);
            echo json_encode(["error" => "GET only."]);
        }
        break;

    case 'report':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $filters = [
                'start_date' => date('Y-m-01'),
                'end_date' => date('Y-m-t')
            ];
            echo json_encode([
                "revenue" => Report::getRevenueReport($filters),
                "profit" => Report::getProfitReport($filters)
            ]);
        } else {
            http_response_code(405);
            echo json_encode(["error" => "GET only."]);
        }
        break;

    case 'document':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $clientId = intval($_GET['client_id'] ?? 0);
            if ($clientId <= 0) {
                http_response_code(400);
                echo json_encode(["error" => "Missing client_id parameter."]);
                exit;
            }
            echo json_encode(Document::getDocumentsForClient($clientId));
        } else {
            http_response_code(405);
            echo json_encode(["error" => "GET only via API for document listings."]);
        }
        break;

    case 'compliance':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            echo json_encode(Compliance::getCompliances());
        } else {
            http_response_code(405);
            echo json_encode(["error" => "GET only."]);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["error" => "Endpoint not found."]);
        break;
}
