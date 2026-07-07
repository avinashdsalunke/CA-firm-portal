<?php
// src/Controller/CRMController.php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../CRM.php';

class CRMController extends BaseController {
    public function handlePost() {
        $action = $_POST['action'] ?? '';
        $this->validateCSRF();

        switch ($action) {
            case 'add_lead':
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $source = trim($_POST['source'] ?? 'Direct');
                $notes = trim($_POST['notes'] ?? '');

                if (!empty($name) && !empty($email)) {
                    $res = CRM::createLead($name, $email, $phone, $source, $notes);
                    if (isset($res['success'])) {
                        Security::logActivity('add_lead', "Created lead: $name");
                        return ["success" => "Lead created successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to create lead."];
                }
                return ["error" => "Name and Email are required."];

            case 'update_lead_status':
                $leadId = intval($_POST['lead_id'] ?? 0);
                $status = trim($_POST['status'] ?? '');
                if ($leadId > 0 && in_array($status, ['new', 'contacted', 'qualified', 'disqualified'])) {
                    $res = CRM::updateLeadStatus($leadId, $status);
                    if (isset($res['success'])) {
                        Security::logActivity('update_lead_status', "Lead ID $leadId updated to $status");
                        return ["success" => "Lead status updated."];
                    }
                    return ["error" => $res['error'] ?? "Failed to update lead."];
                }
                return ["error" => "Invalid parameters."];

            case 'delete_lead':
                $leadId = intval($_POST['lead_id'] ?? 0);
                if ($leadId > 0) {
                    $res = CRM::deleteLead($leadId);
                    if (isset($res['success'])) {
                        Security::logActivity('delete_lead', "Deleted lead ID $leadId");
                        return ["success" => "Lead deleted."];
                    }
                    return ["error" => $res['error'] ?? "Failed to delete lead."];
                }
                return ["error" => "Invalid ID."];

            case 'add_opportunity':
                $title = trim($_POST['title'] ?? '');
                $value = floatval($_POST['value'] ?? 0.00);
                $stage = trim($_POST['stage'] ?? 'discovery');
                $probability = intval($_POST['probability'] ?? 10);
                $closeDate = trim($_POST['close_date'] ?? '');
                $leadId = !empty($_POST['lead_id']) ? intval($_POST['lead_id']) : null;
                $clientId = !empty($_POST['client_id']) ? intval($_POST['client_id']) : null;

                if (!empty($title) && $value >= 0) {
                    $res = CRM::createOpportunity($title, $value, $stage, $probability, $closeDate, $leadId, $clientId);
                    if (isset($res['success'])) {
                        Security::logActivity('add_opportunity', "Created opportunity: $title");
                        return ["success" => "Opportunity created."];
                    }
                    return ["error" => $res['error'] ?? "Failed to create opportunity."];
                }
                return ["error" => "Title and positive value required."];

            case 'edit_opportunity_stage':
                $oppId = intval($_POST['opportunity_id'] ?? 0);
                $stage = trim($_POST['stage'] ?? '');
                if ($oppId > 0 && in_array($stage, ['discovery', 'proposal', 'negotiation', 'won', 'lost'])) {
                    $res = CRM::updateOpportunityStage($oppId, $stage);
                    if (isset($res['success'])) {
                        Security::logActivity('edit_opportunity_stage', "Opportunity ID $oppId updated to $stage");
                        return ["success" => "Opportunity stage updated."];
                    }
                    return ["error" => $res['error'] ?? "Failed to update opportunity."];
                }
                return ["error" => "Invalid parameters."];

            case 'add_note':
                $clientId = intval($_POST['client_id'] ?? 0);
                $content = trim($_POST['content'] ?? '');
                if ($clientId > 0 && !empty($content)) {
                    $res = CRM::addClientNote($clientId, $this->user['id'], $content);
                    if (isset($res['success'])) {
                        Security::logActivity('add_note', "Added note for client ID $clientId");
                        return ["success" => "Note added successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to add note."];
                }
                return ["error" => "Content and Client ID required."];

            case 'delete_note':
                $noteId = intval($_POST['note_id'] ?? 0);
                if ($noteId > 0) {
                    $res = CRM::deleteClientNote($noteId);
                    if (isset($res['success'])) {
                        Security::logActivity('delete_note', "Deleted client note ID $noteId");
                        return ["success" => "Note deleted."];
                    }
                    return ["error" => $res['error'] ?? "Failed to delete note."];
                }
                return ["error" => "Invalid note ID."];

            case 'schedule_meeting':
                $clientId = intval($_POST['client_id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $desc = trim($_POST['description'] ?? '');
                $meetDate = trim($_POST['meeting_date'] ?? '');
                $videoLink = trim($_POST['video_link'] ?? '');

                if ($clientId > 0 && !empty($title) && !empty($meetDate)) {
                    $res = CRM::scheduleMeeting($clientId, $title, $desc, $meetDate, $videoLink ?: null);
                    if (isset($res['success'])) {
                        Security::logActivity('schedule_meeting', "Scheduled meeting: $title");
                        return ["success" => "Meeting scheduled successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to schedule meeting."];
                }
                return ["error" => "Client, Title, and Meeting Date are required."];

            case 'update_meeting_status':
                $meetId = intval($_POST['meeting_id'] ?? 0);
                $status = trim($_POST['status'] ?? '');
                if ($meetId > 0 && in_array($status, ['scheduled', 'completed', 'cancelled'])) {
                    $res = CRM::updateMeetingStatus($meetId, $status);
                    if (isset($res['success'])) {
                        Security::logActivity('update_meeting_status', "Meeting ID $meetId updated to $status");
                        return ["success" => "Meeting status updated."];
                    }
                    return ["error" => $res['error'] ?? "Failed to update meeting."];
                }
                return ["error" => "Invalid parameters."];

            case 'send_whatsapp':
                $clientId = intval($_POST['client_id'] ?? 0);
                $message = trim($_POST['message'] ?? '');
                if ($clientId > 0 && !empty($message)) {
                    $res = CRM::logWhatsApp($clientId, $message, 'staff', 'sent');
                    if (isset($res['success'])) {
                        Security::logActivity('send_whatsapp', "WhatsApp sent to client ID $clientId: $message");
                        return ["success" => "Simulated WhatsApp message logged successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to log message."];
                }
                return ["error" => "Client ID and Message are required."];

            case 'send_email':
                $clientId = intval($_POST['client_id'] ?? 0);
                $subject = trim($_POST['subject'] ?? '');
                $body = trim($_POST['body'] ?? '');
                if ($clientId > 0 && !empty($subject) && !empty($body)) {
                    $res = CRM::logEmail($clientId, $subject, $body, 'sent');
                    if (isset($res['success'])) {
                        Security::logActivity('send_email', "Email logged to client ID $clientId: $subject");
                        return ["success" => "Simulated Email tracked and logged successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to log email."];
                }
                return ["error" => "Client, Subject, and Body are required."];
        }

        return null;
    }
}
