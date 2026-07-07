<?php
// src/Controller/CommunicationController.php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Communication.php';

class CommunicationController extends BaseController {
    public function handlePost() {
        $action = $_POST['action'] ?? '';
        $this->validateCSRF();

        switch ($action) {
            case 'send_chat_message':
                $to = intval($_POST['receiver_id'] ?? 0);
                $msg = trim($_POST['message_text'] ?? '');
                if ($to > 0 && !empty($msg)) {
                    $res = Communication::sendMessage($this->user['id'], $to, $msg);
                    if (isset($res['success'])) {
                        // Redirect to the chat tab with the selected user
                        $this->redirect("index.php?tab=chat&chat_with=$to");
                    }
                    return ["error" => $res['error'] ?? "Failed to send message."];
                }
                return ["error" => "Recipient and message text are required."];

            case 'add_announcement':
                $this->requirePermission('manage_staff');
                $title = trim($_POST['title'] ?? '');
                $content = trim($_POST['content'] ?? '');
                if (!empty($title) && !empty($content)) {
                    $res = Communication::createAnnouncement($title, $content, $this->user['id']);
                    if (isset($res['success'])) {
                        Security::logActivity('post_announcement', "Posted announcement: $title");
                        return ["success" => "Announcement published successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to create announcement."];
                }
                return ["error" => "Title and content are required."];

            case 'delete_announcement':
                $this->requirePermission('manage_staff');
                $id = intval($_POST['id'] ?? 0);
                if ($id > 0) {
                    $res = Communication::deleteAnnouncement($id);
                    if (isset($res['success'])) {
                        Security::logActivity('delete_announcement', "Deleted announcement ID $id");
                        return ["success" => "Announcement deleted successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to delete announcement."];
                }
                return ["error" => "Invalid ID."];

            case 'update_permissions':
                $this->requirePermission('edit_roles');
                $targetRole = trim($_POST['target_role'] ?? '');
                $perms = $_POST['perms'] ?? [];
                if (in_array($targetRole, ['admin_manager', 'staff'])) {
                    $res = RBAC::updateRolePermissions($targetRole, $perms);
                    if (isset($res['success'])) {
                        Security::logActivity('update_permissions', "Modified permissions for role: $targetRole");
                        return ["success" => "Permissions for $targetRole updated successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to update permissions."];
                }
                return ["error" => "Invalid target role."];
            case 'update_email_template':
                $this->requirePermission('manage_staff');
                $id = intval($_POST['template_id'] ?? 0);
                $subject = trim($_POST['subject'] ?? '');
                $body = trim($_POST['body'] ?? '');
                if ($id > 0 && !empty($subject) && !empty($body)) {
                    $res = Communication::updateEmailTemplate($id, $subject, $body);
                    if (isset($res['success'])) {
                        Security::logActivity('update_email_template', "Updated email template ID $id");
                        return ["success" => "Email template updated successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to update email template."];
                }
                return ["error" => "Template ID, subject, and body are required."];

            case 'mark_notifications_read':
                $res = Communication::markNotificationsAsRead($this->user['id']);
                if (isset($res['success'])) {
                    return ["success" => "All notifications marked as read."];
                }
                return ["error" => $res['error'] ?? "Failed to clear notifications."];
        }
        return null;
    }
}
