<?php
// src/Document.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Client.php';
require_once __DIR__ . '/Util.php';

class Document {
    /**
     * Create document request
     */
    public static function createDocumentRequest($clientId, $title, $description = null) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("INSERT INTO document_requests (client_id, title, description, status) VALUES (:client_id, :title, :description, 'pending')");
            $stmt->execute([
                'client_id' => $clientId,
                'title' => $title,
                'description' => $description
            ]);
            
            Client::addTimelineEvent($clientId, null, 'document_requested', "Document requested: '$title'.");
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Get document requests
     */
    public static function getDocumentRequests($filters = []) {
        $db = Database::getConnection();
        $sql = "
            SELECT dr.*, c.name as client_name 
            FROM document_requests dr 
            JOIN clients c ON dr.client_id = c.id 
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['client_id'])) {
            $sql .= " AND dr.client_id = :client_id";
            $params['client_id'] = $filters['client_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND dr.status = :status";
            $params['status'] = $filters['status'];
        }

        $sql .= " ORDER BY dr.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Update request status
     */
    public static function updateRequestStatus($requestId, $status) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("UPDATE document_requests SET status = :status WHERE id = :id");
            $stmt->execute(['status' => $status, 'id' => $requestId]);
            
            // Get client id for timeline logging
            $stmtGet = $db->prepare("SELECT client_id, title FROM document_requests WHERE id = :id LIMIT 1");
            $stmtGet->execute(['id' => $requestId]);
            $req = $stmtGet->fetch();
            if ($req) {
                Client::addTimelineEvent($req['client_id'], null, 'request_status_changed', "Document request '{$req['title']}' updated to: $status.");
            }
            
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Upload a document with versioning, folders, scope, and simulated OCR indexing
     */
    public static function uploadDocument($clientId, $file, $uploadedBy, $requestId = null, $folder = '/', $sharingScope = 'client_shared') {
        // Validate file upload using Util helper
        $validationResult = Util::validateUpload($file);
        if ($validationResult !== true) {
            return ["error" => $validationResult];
        }

        require_once __DIR__ . '/Tenant.php';
        if (!Tenant::validateStorageLimit(1, $file['size'])) {
            return ["error" => "Tenant storage quota exceeded. Please upgrade your subscription plan."];
        }

        $uploadDir = dirname(__DIR__) . '/public/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique name to prevent collisions
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $uniqueName = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetPath = $uploadDir . $uniqueName;

        if (move_uploaded_file($file['tmp_name'], $targetPath) || (php_sapi_name() === 'cli' && copy($file['tmp_name'], $targetPath))) {
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                Util::compressImage($targetPath, $targetPath, 75);
            }
            $db = Database::getConnection();
            $db->beginTransaction();
            try {
                // Check if file already exists in this folder for version control
                $stmtExist = $db->prepare("
                    SELECT id, version, parent_document_id 
                    FROM documents 
                    WHERE client_id = :client_id 
                      AND file_name = :file_name 
                      AND folder = :folder 
                    ORDER BY version DESC LIMIT 1
                ");
                $stmtExist->execute([
                    'client_id' => $clientId,
                    'file_name' => $file['name'],
                    'folder' => $folder
                ]);
                $existingDoc = $stmtExist->fetch();

                $version = 1;
                $parentId = null;

                if ($existingDoc) {
                    $version = intval($existingDoc['version']) + 1;
                    $parentId = $existingDoc['parent_document_id'] ?: $existingDoc['id'];
                }

                // Simple mock OCR text extraction
                $ocrKeywords = ["Document: " . $file['name'], "Uploaded by: " . $uploadedBy];
                if (stripos($file['name'], 'gst') !== false) {
                    $ocrKeywords[] = "GST GSTR filing tax returns CGST SGST IGST ledger";
                }
                if (stripos($file['name'], 'tds') !== false) {
                    $ocrKeywords[] = "TDS income tax deduction withholding return";
                }
                if (stripos($file['name'], 'invoice') !== false || stripos($file['name'], 'bill') !== false) {
                    $ocrKeywords[] = "billing invoice revenue receipt payment transaction amount";
                }
                if (stripos($file['name'], 'salary') !== false || stripos($file['name'], 'slip') !== false) {
                    $ocrKeywords[] = "salary slip pay roll staff employee basic salary allowance";
                }
                $ocrText = implode(", ", $ocrKeywords);

                // Insert into documents table
                $stmt = $db->prepare("
                    INSERT INTO documents (client_id, document_request_id, file_name, file_path, file_size, uploaded_by, folder, version, parent_document_id, ocr_text, sharing_scope) 
                    VALUES (:client_id, :document_request_id, :file_name, :file_path, :file_size, :uploaded_by, :folder, :version, :parent_document_id, :ocr_text, :sharing_scope)
                ");
                $stmt->execute([
                    'client_id' => $clientId,
                    'document_request_id' => $requestId ?: null,
                    'file_name' => $file['name'],
                    'file_path' => 'uploads/' . $uniqueName, // Relative path inside public folder
                    'file_size' => $file['size'],
                    'uploaded_by' => $uploadedBy,
                    'folder' => $folder,
                    'version' => $version,
                    'parent_document_id' => $parentId,
                    'ocr_text' => $ocrText,
                    'sharing_scope' => $sharingScope
                ]);

                // If associated with request, update request status to uploaded
                if ($requestId) {
                    $stmtReq = $db->prepare("UPDATE document_requests SET status = 'uploaded' WHERE id = :id");
                    $stmtReq->execute(['id' => $requestId]);
                }

                Client::addTimelineEvent($clientId, null, 'document_uploaded', "Document '{$file['name']}' uploaded to folder '$folder' (Version: v$version) by $uploadedBy.");
                $db->commit();
                return ["success" => true];
            } catch (Exception $e) {
                $db->rollBack();
                if (file_exists($targetPath)) {
                    unlink($targetPath);
                }
                return ["error" => "Database save failed: " . $e->getMessage()];
            }
        }
        return ["error" => "Failed to move uploaded file."];
    }

    /**
     * Get documents uploaded for a client with folder & search filters
     */
    public static function getDocuments($clientId, $folder = null, $searchQuery = null) {
        $db = Database::getConnection();
        
        $sql = "
            SELECT d.*, dr.title as request_title 
            FROM documents d 
            LEFT JOIN document_requests dr ON d.document_request_id = dr.id 
            WHERE d.client_id = :client_id
        ";
        $params = ['client_id' => $clientId];

        if ($folder !== null) {
            $sql .= " AND d.folder = :folder";
            $params['folder'] = $folder;
        }

        if (!empty($searchQuery)) {
            $sql .= " AND (d.file_name LIKE :search1 OR d.ocr_text LIKE :search2)";
            $params['search1'] = '%' . $searchQuery . '%';
            $params['search2'] = '%' . $searchQuery . '%';
        }

        $sql .= " ORDER BY d.file_name ASC, d.version DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Digitally sign a document
     */
    public static function signDocument($id, $signedBy) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("SELECT client_id, file_name FROM documents WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $doc = $stmt->fetch();

            if (!$doc) {
                return ["error" => "Document record not found."];
            }

            $stmtSign = $db->prepare("
                UPDATE documents 
                SET signature_status = 'signed', signed_by = :signed_by, signed_at = CURRENT_TIMESTAMP 
                WHERE id = :id
            ");
            $stmtSign->execute([
                'signed_by' => $signedBy,
                'id' => $id
            ]);

            Client::addTimelineEvent($doc['client_id'], null, 'document_signed', "Document '{$doc['file_name']}' was digitally signed by $signedBy.");
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Delete document
     */
    public static function deleteDocument($id) {
        $db = Database::getConnection();
        try {
            // Find document path
            $stmt = $db->prepare("SELECT client_id, file_name, file_path FROM documents WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $doc = $stmt->fetch();

            if ($doc) {
                $filePath = dirname(__DIR__) . '/public/' . $doc['file_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }

                $stmtDel = $db->prepare("DELETE FROM documents WHERE id = :id");
                $stmtDel->execute(['id' => $id]);
                
                Client::addTimelineEvent($doc['client_id'], null, 'document_deleted', "Document '{$doc['file_name']}' deleted.");
            }
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }
}
