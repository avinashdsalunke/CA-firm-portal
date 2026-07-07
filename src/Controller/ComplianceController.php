<?php
// src/Controller/ComplianceController.php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Compliance.php';

class ComplianceController extends BaseController {
    public function handlePost() {
        $action = $_POST['action'] ?? '';
        $this->validateCSRF();

        switch ($action) {
            case 'add_compliance':
                $this->requirePermission('manage_compliance');
                $clientId = intval($_POST['client_id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $category = trim($_POST['category'] ?? '');
                $dueDate = trim($_POST['due_date'] ?? '');
                $notes = trim($_POST['notes'] ?? '');

                if ($clientId > 0 && !empty($title) && !empty($dueDate)) {
                    $res = Compliance::createCompliance($clientId, $title, $category, $dueDate, $notes);
                    if (isset($res['success'])) {
                        Security::logActivity('add_compliance', "Created compliance task '$title' for client ID $clientId");
                        return ["success" => "Compliance task created successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to create compliance task."];
                }
                return ["error" => "Client, Title and Due Date are required fields."];

            case 'record_filing':
                $this->requirePermission('manage_compliance');
                $id = intval($_POST['id'] ?? 0);
                $filingDate = trim($_POST['filing_date'] ?? '');
                $ackNumber = trim($_POST['acknowledgement_number'] ?? '');
                $notes = trim($_POST['notes'] ?? '');

                if ($id > 0 && !empty($filingDate) && !empty($ackNumber)) {
                    $res = Compliance::recordFiling($id, $filingDate, $ackNumber, $notes);
                    if (isset($res['success'])) {
                        Security::logActivity('record_filing', "Recorded filing for compliance ID $id. Ack: $ackNumber");
                        return ["success" => "Filing recorded successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to record filing."];
                }
                return ["error" => "Filing Date and Acknowledgement Number are required."];

            case 'delete_compliance':
                $this->requirePermission('manage_compliance');
                $id = intval($_POST['id'] ?? 0);
                if ($id > 0) {
                    $res = Compliance::deleteCompliance($id);
                    if (isset($res['success'])) {
                        Security::logActivity('delete_compliance', "Deleted compliance ID $id");
                        return ["success" => "Compliance task deleted successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to delete compliance."];
                }
                return ["error" => "Invalid compliance ID."];

            case 'update_compliance_config':
                $this->requirePermission('manage_compliance');
                $clientId = intval($_POST['client_id'] ?? 0);
                if ($clientId > 0) {
                    $res = Compliance::updateClientConfig($clientId, $_POST);
                    if (isset($res['success'])) {
                        Security::logActivity('update_compliance_config', "Updated automated compliance config for client ID $clientId");
                        return ["success" => "Compliance configuration updated successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to update config."];
                }
                return ["error" => "Invalid Client ID."];

            case 'trigger_automation_run':
                $this->requirePermission('manage_compliance');
                $res = Compliance::runComplianceAutomation();
                Security::logActivity('trigger_compliance_automation', "Manually triggered compliance automation checks");
                return ["success" => "Compliance automation run complete! Generated " . $res['generated'] . " filings, triggered " . $res['reminders'] . " reminders, and escalated " . $res['escalations'] . " items."];

            default:
                return ["error" => "Unknown compliance action."];
        }
    }
}
