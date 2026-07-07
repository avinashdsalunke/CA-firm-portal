<?php
// src/Controller/BaseController.php

require_once __DIR__ . '/../Util.php';
require_once __DIR__ . '/../RBAC.php';
require_once __DIR__ . '/../Security.php';

class BaseController {
    protected $user;
    protected $isAdmin;

    public function __construct() {
        $this->user = Auth::getCurrentUser();
        $this->isAdmin = $this->user && ($this->user['role'] === 'super_admin' || $this->user['role'] === 'admin_manager');
    }

    protected function validateCSRF() {
        $token = $_POST['csrf_token'] ?? '';
        if (!Util::validateCSRF($token)) {
            throw new Exception("CSRF verification failed.");
        }
    }

    protected function requirePermission($permission) {
        RBAC::requirePermission($permission);
    }

    protected function sendJSON($data, $statusCode = 200) {
        Util::sendJSON($data, $statusCode);
    }

    protected function redirect($url) {
        Util::redirect($url);
    }
}
