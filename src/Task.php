<?php
// src/Task.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Client.php';

class Task {
    /**
     * Get tasks with filter parameters
     */
    public static function getTasks($filters = []) {
        $db = Database::getConnection();
        $sql = "
            SELECT t.*, c.name as client_name, u.name as staff_name 
            FROM tasks t 
            JOIN clients c ON t.client_id = c.id 
            LEFT JOIN users u ON t.assigned_to_user_id = u.id 
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND t.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['priority'])) {
            $sql .= " AND t.priority = :priority";
            $params['priority'] = $filters['priority'];
        }
        if (!empty($filters['category'])) {
            $sql .= " AND t.category = :category";
            $params['category'] = $filters['category'];
        }
        if (!empty($filters['assigned_to'])) {
            $sql .= " AND t.assigned_to_user_id = :assigned_to";
            $params['assigned_to'] = $filters['assigned_to'];
        }
        if (!empty($filters['client_id'])) {
            $sql .= " AND t.client_id = :client_id";
            $params['client_id'] = $filters['client_id'];
        }
        if (isset($filters['due_today']) && $filters['due_today'] === true) {
            $sql .= " AND t.due_date = CURRENT_DATE()";
        }
        if (isset($filters['overdue']) && $filters['overdue'] === true) {
            $sql .= " AND t.due_date < CURRENT_DATE() AND t.status != 'completed'";
        }

        $sql .= " ORDER BY t.due_date ASC, t.priority DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get single task
     */
    public static function getTask($id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT t.*, c.name as client_name, u.name as staff_name 
            FROM tasks t 
            JOIN clients c ON t.client_id = c.id 
            LEFT JOIN users u ON t.assigned_to_user_id = u.id 
            WHERE t.id = :id LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Create a task
     */
    public static function createTask($clientId, $assignedTo, $title, $description, $priority, $category, $dueDate, $financial_year = null, $assessment_year = null, $periodicity = null, $estimated_fees = null) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                INSERT INTO tasks (client_id, assigned_to_user_id, title, description, priority, category, due_date, status, financial_year, assessment_year, periodicity, estimated_fees) 
                VALUES (:client_id, :assigned_to_user_id, :title, :description, :priority, :category, :due_date, 'pending', :financial_year, :assessment_year, :periodicity, :estimated_fees)
            ");
            $stmt->execute([
                'client_id' => $clientId,
                'assigned_to_user_id' => $assignedTo ?: null,
                'title' => $title,
                'description' => $description,
                'priority' => $priority,
                'category' => $category,
                'due_date' => $dueDate ?: null,
                'financial_year' => $financial_year ?: null,
                'assessment_year' => $assessment_year ?: null,
                'periodicity' => $periodicity ?: null,
                'estimated_fees' => $estimated_fees !== null ? floatval($estimated_fees) : null
            ]);
            $taskId = $db->lastInsertId();

            Client::addTimelineEvent($clientId, null, 'task_created', "Task '$title' created and assigned.");
            return ["success" => true, "id" => $taskId];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Update a task
     */
    public static function updateTask($id, $clientId, $assignedTo, $title, $description, $status, $priority, $category, $dueDate, $financial_year = null, $assessment_year = null, $periodicity = null, $estimated_fees = null) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                UPDATE tasks 
                SET client_id = :client_id, assigned_to_user_id = :assigned_to_user_id, title = :title, 
                    description = :description, status = :status, priority = :priority, category = :category, due_date = :due_date,
                    financial_year = :financial_year, assessment_year = :assessment_year, periodicity = :periodicity, estimated_fees = :estimated_fees
                WHERE id = :id
            ");
            $stmt->execute([
                'client_id' => $clientId,
                'assigned_to_user_id' => $assignedTo ?: null,
                'title' => $title,
                'description' => $description,
                'status' => $status,
                'priority' => $priority,
                'category' => $category,
                'due_date' => $dueDate ?: null,
                'financial_year' => $financial_year ?: null,
                'assessment_year' => $assessment_year ?: null,
                'periodicity' => $periodicity ?: null,
                'estimated_fees' => $estimated_fees !== null ? floatval($estimated_fees) : null,
                'id' => $id
            ]);

            Client::addTimelineEvent($clientId, null, 'task_updated', "Task '$title' updated (Status: $status).");
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Delete task
     */
    public static function deleteTask($id) {
        $db = Database::getConnection();
        try {
            $task = self::getTask($id);
            if ($task) {
                $stmt = $db->prepare("DELETE FROM tasks WHERE id = :id");
                $stmt->execute(['id' => $id]);
                Client::addTimelineEvent($task['client_id'], null, 'task_deleted', "Task '{$task['title']}' deleted.");
            }
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Update only status (by staff)
     */
    public static function updateStatus($id, $status) {
        $db = Database::getConnection();
        try {
            $task = self::getTask($id);
            if (!$task) return ["error" => "Task not found."];

            $stmt = $db->prepare("UPDATE tasks SET status = :status WHERE id = :id");
            $stmt->execute(['status' => $status, 'id' => $id]);

            Client::addTimelineEvent($task['client_id'], null, 'task_status_changed', "Task '{$task['title']}' status updated to $status.");
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Submit work log for task
     */
    public static function logWork($taskId, $userId, $description, $hoursSpent, $logDate) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                INSERT INTO work_logs (task_id, user_id, description, hours_spent, log_date) 
                VALUES (:task_id, :user_id, :description, :hours_spent, :log_date)
            ");
            $stmt->execute([
                'task_id' => $taskId,
                'user_id' => $userId,
                'description' => $description,
                'hours_spent' => $hoursSpent,
                'log_date' => $logDate
            ]);
            
            $task = self::getTask($taskId);
            if ($task) {
                Client::addTimelineEvent($task['client_id'], $userId, 'work_logged', "Logged $hoursSpent hours on task '{$task['title']}'.");
            }
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Get work logs with details
     */
    public static function getWorkLogs($filters = []) {
        $db = Database::getConnection();
        $sql = "
            SELECT wl.*, t.title as task_title, u.name as staff_name, c.name as client_name
            FROM work_logs wl
            JOIN tasks t ON wl.task_id = t.id
            JOIN users u ON wl.user_id = u.id
            JOIN clients c ON t.client_id = c.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['user_id'])) {
            $sql .= " AND wl.user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }
        if (!empty($filters['task_id'])) {
            $sql .= " AND wl.task_id = :task_id";
            $params['task_id'] = $filters['task_id'];
        }
        if (!empty($filters['start_date'])) {
            $sql .= " AND wl.log_date >= :start_date";
            $params['start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND wl.log_date <= :end_date";
            $params['end_date'] = $filters['end_date'];
        }

        $sql .= " ORDER BY wl.log_date DESC, wl.id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Create Recurring Task Template
     */
    public static function createRecurringTemplate($clientId, $assignedTo, $title, $description, $priority, $category, $frequency, $nextSpawnDate) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                INSERT INTO recurring_templates (client_id, assigned_to_user_id, title, description, priority, category, frequency, next_spawn_date) 
                VALUES (:client_id, :assigned_to_user_id, :title, :description, :priority, :category, :frequency, :next_spawn_date)
            ");
            $stmt->execute([
                'client_id' => $clientId,
                'assigned_to_user_id' => $assignedTo ?: null,
                'title' => $title,
                'description' => $description,
                'priority' => $priority,
                'category' => $category,
                'frequency' => $frequency,
                'next_spawn_date' => $nextSpawnDate
            ]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Get Recurring Templates
     */
    public static function getRecurringTemplates() {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT rt.*, c.name as client_name, u.name as staff_name 
            FROM recurring_templates rt 
            JOIN clients c ON rt.client_id = c.id 
            LEFT JOIN users u ON rt.assigned_to_user_id = u.id 
            ORDER BY rt.next_spawn_date ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Spawn Tasks from Recurring Templates
     */
    public static function spawnRecurringTasks() {
        $db = Database::getConnection();
        
        // Find templates due for spawning today or earlier
        $stmt = $db->query("SELECT * FROM recurring_templates WHERE next_spawn_date <= CURRENT_DATE()");
        $templates = $stmt->fetchAll();
        $spawnedCount = 0;

        foreach ($templates as $tmpl) {
            $db->beginTransaction();
            try {
                // Spawn task
                // Target due date is next spawn date
                $stmtSpawn = $db->prepare("
                    INSERT INTO tasks (client_id, assigned_to_user_id, title, description, priority, category, due_date, status) 
                    VALUES (:client_id, :assigned_to_user_id, :title, :description, :priority, :category, :due_date, 'pending')
                ");
                $stmtSpawn->execute([
                    'client_id' => $tmpl['client_id'],
                    'assigned_to_user_id' => $tmpl['assigned_to_user_id'],
                    'title' => $tmpl['title'] . " - " . date('M Y'),
                    'description' => "Automatically generated compliance task: " . $tmpl['description'],
                    'priority' => $tmpl['priority'],
                    'category' => $tmpl['category'],
                    'due_date' => $tmpl['next_spawn_date']
                ]);

                // Calculate next spawn date
                $interval = '+1 month';
                if ($tmpl['frequency'] === 'quarterly') {
                    $interval = '+3 months';
                } elseif ($tmpl['frequency'] === 'yearly') {
                    $interval = '+1 year';
                }
                $nextSpawn = date('Y-m-d', strtotime($tmpl['next_spawn_date'] . ' ' . $interval));

                // Update template next spawn date
                $stmtUpdate = $db->prepare("UPDATE recurring_templates SET next_spawn_date = :next_spawn WHERE id = :id");
                $stmtUpdate->execute([
                    'next_spawn' => $nextSpawn,
                    'id' => $tmpl['id']
                ]);

                Client::addTimelineEvent($tmpl['client_id'], null, 'task_spawned', "Compliance task spawned from template: " . $tmpl['title']);
                $db->commit();
                $spawnedCount++;
            } catch (Exception $e) {
                $db->rollBack();
            }
        }
        return $spawnedCount;
    }

    /**
     * Get Dashboard Stats for Admins/Managers
     */
    public static function getAdminStats() {
        $db = Database::getConnection();
        
        $clients = $db->query("SELECT COUNT(*) FROM clients")->fetchColumn();
        $staff = $db->query("SELECT COUNT(*) FROM users WHERE role = 'staff'")->fetchColumn();
        $tasks = $db->query("SELECT COUNT(*) FROM tasks WHERE status != 'completed'")->fetchColumn();
        
        $overdue = $db->query("SELECT COUNT(*) FROM tasks WHERE due_date < CURRENT_DATE() AND status != 'completed'")->fetchColumn();
        $dueToday = $db->query("SELECT COUNT(*) FROM tasks WHERE due_date = CURRENT_DATE() AND status != 'completed'")->fetchColumn();
        
        $totalHours = $db->query("SELECT SUM(hours_spent) FROM work_logs")->fetchColumn();
        $totalHours = $totalHours ? round($totalHours, 1) : 0;

        return [
            'clients' => $clients,
            'staff' => $staff,
            'tasks' => $tasks,
            'overdue' => $overdue,
            'due_today' => $dueToday,
            'total_hours' => $totalHours
        ];
    }

    /**
     * Get Dashboard Stats for specific staff
     */
    public static function getStaffStats($userId) {
        $db = Database::getConnection();
        
        $stmtPending = $db->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to_user_id = :user_id AND status != 'completed'");
        $stmtPending->execute(['user_id' => $userId]);
        $pending = $stmtPending->fetchColumn();

        $stmtOverdue = $db->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to_user_id = :user_id AND due_date < CURRENT_DATE() AND status != 'completed'");
        $stmtOverdue->execute(['user_id' => $userId]);
        $overdue = $stmtOverdue->fetchColumn();

        $stmtTodayHours = $db->prepare("SELECT SUM(hours_spent) FROM work_logs WHERE user_id = :user_id AND log_date = CURRENT_DATE()");
        $stmtTodayHours->execute(['user_id' => $userId]);
        $todayHours = $stmtTodayHours->fetchColumn();
        $todayHours = $todayHours ? round($todayHours, 1) : 0;

        return [
            'pending_tasks' => $pending,
            'overdue_tasks' => $overdue,
            'today_hours' => $todayHours
        ];
    }
}
