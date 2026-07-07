<?php
// src/Controller/TaskController.php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Task.php';

class TaskController extends BaseController {
    public function handlePost() {
        $action = $_POST['action'] ?? '';
        $this->validateCSRF();

        switch ($action) {
            case 'add_task':
                $this->requirePermission('manage_tasks');
                $clientId = intval($_POST['client_id'] ?? 0);
                $assignedTo = intval($_POST['assigned_to'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $priority = trim($_POST['priority'] ?? 'medium');
                $category = trim($_POST['category'] ?? '');
                $dueDate = trim($_POST['due_date'] ?? '');
                $fy = trim($_POST['financial_year'] ?? '');
                $ay = trim($_POST['assessment_year'] ?? '');
                $period = trim($_POST['periodicity'] ?? '');
                $fees = $_POST['estimated_fees'] !== '' ? floatval($_POST['estimated_fees']) : null;
                if ($clientId > 0 && !empty($title) && !empty($category)) {
                    $res = Task::createTask($clientId, $assignedTo, $title, $description, $priority, $category, $dueDate, $fy, $ay, $period, $fees);
                    if (isset($res['success'])) {
                        Security::logActivity('add_task', "Created task: $title");
                        return ["success" => "Task created successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to create task."];
                }
                return ["error" => "Client, Title, and Category are required."];

            case 'edit_task':
                $this->requirePermission('manage_tasks');
                $id = intval($_POST['id'] ?? 0);
                $clientId = intval($_POST['client_id'] ?? 0);
                $assignedTo = intval($_POST['assigned_to'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $status = trim($_POST['status'] ?? 'pending');
                $priority = trim($_POST['priority'] ?? 'medium');
                $category = trim($_POST['category'] ?? '');
                $dueDate = trim($_POST['due_date'] ?? '');
                $fy = trim($_POST['financial_year'] ?? '');
                $ay = trim($_POST['assessment_year'] ?? '');
                $period = trim($_POST['periodicity'] ?? '');
                $fees = $_POST['estimated_fees'] !== '' ? floatval($_POST['estimated_fees']) : null;
                if ($id > 0 && $clientId > 0 && !empty($title) && !empty($category)) {
                    $res = Task::updateTask($id, $clientId, $assignedTo, $title, $description, $status, $priority, $category, $dueDate, $fy, $ay, $period, $fees);
                    if (isset($res['success'])) {
                        Security::logActivity('edit_task', "Updated task ID $id");
                        return ["success" => "Task updated successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to update task."];
                }
                return ["error" => "Required task parameters missing."];

            case 'delete_task':
                $this->requirePermission('manage_tasks');
                $id = intval($_POST['id'] ?? 0);
                if ($id > 0) {
                    $res = Task::deleteTask($id);
                    if (isset($res['success'])) {
                        Security::logActivity('delete_task', "Deleted task ID $id");
                        return ["success" => "Task deleted successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to delete task."];
                }
                return ["error" => "Invalid ID."];

            case 'update_task_status':
                $id = intval($_POST['id'] ?? 0);
                $status = trim($_POST['status'] ?? '');
                if ($id > 0 && !empty($status)) {
                    $task = Task::getTask($id);
                    if ($this->isAdmin || ($task && $task['assigned_to_user_id'] == $this->user['id'])) {
                        $res = Task::updateStatus($id, $status);
                        if (isset($res['success'])) {
                            Security::logActivity('update_task_status', "Updated task ID $id status to $status");
                            return ["success" => "Task status updated."];
                        }
                        return ["error" => $res['error'] ?? "Failed to update status."];
                    }
                    return ["error" => "Unauthorized action."];
                }
                return ["error" => "Required parameters missing."];

            case 'log_work':
                $taskId = intval($_POST['task_id'] ?? 0);
                $description = trim($_POST['description'] ?? '');
                $hours = floatval($_POST['hours'] ?? 0.0);
                $date = trim($_POST['log_date'] ?? date('Y-m-d'));
                if ($taskId > 0 && !empty($description) && $hours > 0) {
                    $res = Task::logWork($taskId, $this->user['id'], $description, $hours, $date);
                    if (isset($res['success'])) {
                        Security::logActivity('log_work', "Logged $hours hours for task ID $taskId");
                        return ["success" => "Work hours logged successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to log work."];
                }
                return ["error" => "Task, description, and positive hours are required."];

            case 'add_template':
                $this->requirePermission('manage_compliance');
                $clientId = intval($_POST['client_id'] ?? 0);
                $assignedTo = intval($_POST['assigned_to'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $priority = trim($_POST['priority'] ?? 'medium');
                $category = trim($_POST['category'] ?? '');
                $frequency = trim($_POST['frequency'] ?? 'monthly');
                $nextDate = trim($_POST['next_spawn_date'] ?? '');
                if ($clientId > 0 && !empty($title) && !empty($category) && !empty($nextDate)) {
                    $res = Task::createRecurringTemplate($clientId, $assignedTo, $title, $description, $priority, $category, $frequency, $nextDate);
                    if (isset($res['success'])) {
                        Security::logActivity('add_template', "Created compliance template $title");
                        return ["success" => "Recurring task template created successfully."];
                    }
                    return ["error" => $res['error'] ?? "Failed to create template."];
                }
                return ["error" => "Client, Title, Category, and Next Spawn Date are required."];

            case 'spawn_tasks':
                $this->requirePermission('manage_compliance');
                $spawned = Task::spawnRecurringTasks();
                Security::logActivity('spawn_tasks', "Triggered template spawn ($spawned tasks)");
                return ["success" => "$spawned tasks spawned successfully from active recurring templates."];
        }
        return null;
    }
}
