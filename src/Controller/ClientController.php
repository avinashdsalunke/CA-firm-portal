<?php
// src/Controller/ClientController.php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Client.php';
require_once __DIR__ . '/../Document.php';

class ClientController extends BaseController {
    public function handlePost() {
        $action = $_POST['action'] ?? '';
        $this->validateCSRF();

        switch ($action) {
            case 'add_client':
                $this->requirePermission('manage_clients');
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                if (!empty($name) && !empty($email)) {
                    $res = Client::createClient($name, $email, $phone);
                    if (isset($res['success'])) {
                        Security::logActivity('add_client', "Created client $name");
                        return ["success" => "Client created successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to create client."];
                }
                return ["error" => "Name and Email are required."];

            case 'edit_client':
                $this->requirePermission('manage_clients');
                $id = intval($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                if ($id > 0 && !empty($name) && !empty($email)) {
                    $res = Client::updateClient($id, $name, $email, $phone);
                    if (isset($res['success'])) {
                        Security::logActivity('edit_client', "Updated client details for ID $id");
                        return ["success" => "Client updated successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to update client."];
                }
                return ["error" => "Invalid inputs."];

            case 'delete_client':
                $this->requirePermission('manage_clients');
                $id = intval($_POST['id'] ?? 0);
                if ($id > 0) {
                    $res = Client::deleteClient($id);
                    if (isset($res['success'])) {
                        Security::logActivity('delete_client', "Deleted client ID $id");
                        return ["success" => "Client deleted successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to delete client."];
                }
                return ["error" => "Invalid ID."];

            case 'generate_token':
                $this->requirePermission('manage_clients');
                $id = intval($_POST['id'] ?? 0);
                if ($id > 0) {
                    $res = Client::generatePortalToken($id);
                    if (isset($res['success'])) {
                        Security::logActivity('generate_token', "Generated portal token for client ID $id");
                        return ["success" => "Portal link generated successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to generate link."];
                }
                return ["error" => "Invalid ID."];

            case 'add_request':
                $this->requirePermission('manage_clients');
                $clientId = intval($_POST['client_id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $desc = trim($_POST['description'] ?? '');
                if ($clientId > 0 && !empty($title)) {
                    $res = Document::createDocumentRequest($clientId, $title, $desc);
                    if (isset($res['success'])) {
                        Security::logActivity('document_request', "Requested document '$title' from client $clientId");
                        return ["success" => "Document request created and sent to client portal."];
                    }
                    return ["error" => $res['error'] ?? "Failed to request document."];
                }
                return ["error" => "Client and Request Title are required."];

            case 'review_request':
                $this->requirePermission('manage_clients');
                $id = intval($_POST['id'] ?? 0);
                if ($id > 0) {
                    $res = Document::updateRequestStatus($id, 'reviewed');
                    if (isset($res['success'])) {
                        Security::logActivity('review_request', "Reviewed document request ID $id");
                        return ["success" => "Document request marked as reviewed."];
                    }
                    return ["error" => $res['error'] ?? "Failed to mark as reviewed."];
                }
                return ["error" => "Invalid ID."];

            case 'delete_document':
                $this->requirePermission('manage_clients');
                $id = intval($_POST['id'] ?? 0);
                if ($id > 0) {
                    $res = Document::deleteDocument($id);
                    if (isset($res['success'])) {
                        Security::logActivity('delete_document', "Deleted document ID $id");
                        return ["success" => "Document deleted."];
                    }
                    return ["error" => $res['error'] ?? "Failed to delete document."];
                }
                return ["error" => "Invalid ID."];

            case 'staff_upload_document':
                $clientId = intval($_POST['client_id'] ?? 0);
                $folder = trim($_POST['folder'] ?? '/');
                $sharingScope = trim($_POST['sharing_scope'] ?? 'client_shared');
                $requestId = intval($_POST['document_request_id'] ?? 0);

                if ($clientId > 0 && isset($_FILES['document'])) {
                    $res = Document::uploadDocument($clientId, $_FILES['document'], 'staff', $requestId ?: null, $folder, $sharingScope);
                    if (isset($res['success'])) {
                        Security::logActivity('upload_document', "Uploaded document for client ID $clientId to folder $folder");
                        return ["success" => "Document uploaded successfully to client folder $folder."];
                    }
                    return ["error" => $res['error'] ?? "Failed to upload."];
                }
                return ["error" => "Missing client file upload parameter."];

            case 'sign_document':
                $id = intval($_POST['id'] ?? 0);
                $signedBy = trim($_POST['signed_by'] ?? '');
                if ($id > 0 && !empty($signedBy)) {
                    $res = Document::signDocument($id, $signedBy);
                    if (isset($res['success'])) {
                        Security::logActivity('sign_document', "Signed document ID $id by $signedBy");
                        return ["success" => "Document digitally signed successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to sign document."];
                }
                return ["error" => "Document ID and Signature Name are required."];
        }
        return null;
    }
}
