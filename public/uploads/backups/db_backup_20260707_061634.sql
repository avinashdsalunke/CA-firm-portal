-- CA CRM Database Backup --
-- Generated: 2026-07-07 06:16:34 --

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `accounting_expenses`;
CREATE TABLE `accounting_expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` date NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'approved',
  `approved_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `accounting_expenses` (`id`, `category`, `amount`, `date`, `description`, `created_at`, `status`, `approved_by`) VALUES ('1', 'Utilities', '2000.00', '2026-07-05', 'mahaviratan', '2026-07-05 20:20:36', 'approved', NULL);
INSERT INTO `accounting_expenses` (`id`, `category`, `amount`, `date`, `description`, `created_at`, `status`, `approved_by`) VALUES ('2', 'Salaries', '15000.00', '2026-07-05', 'avinash staff1 salary paid', '2026-07-05 20:21:12', 'approved', NULL);

DROP TABLE IF EXISTS `accounting_invoices`;
CREATE TABLE `accounting_invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('unpaid','paid','overdue','cancelled') NOT NULL DEFAULT 'unpaid',
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cgst` decimal(15,2) DEFAULT 0.00,
  `sgst` decimal(15,2) DEFAULT 0.00,
  `igst` decimal(15,2) DEFAULT 0.00,
  `tds_amount` decimal(15,2) DEFAULT 0.00,
  `net_amount` decimal(15,2) DEFAULT 0.00,
  `invoice_design` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `client_id` (`client_id`),
  KEY `idx_invoices_status_due` (`status`,`due_date`),
  CONSTRAINT `accounting_invoices_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `accounting_invoices` (`id`, `client_id`, `invoice_number`, `amount`, `status`, `issue_date`, `due_date`, `description`, `created_at`, `updated_at`, `cgst`, `sgst`, `igst`, `tds_amount`, `net_amount`, `invoice_design`) VALUES ('1', '1', 'INV-2026-09', '15000.00', 'paid', '2026-07-05', '2026-08-04', 'GST ,ROC,TDs', '2026-07-05 20:19:27', '2026-07-05 20:19:47', '0.00', '0.00', '0.00', '0.00', '0.00', NULL);
INSERT INTO `accounting_invoices` (`id`, `client_id`, `invoice_number`, `amount`, `status`, `issue_date`, `due_date`, `description`, `created_at`, `updated_at`, `cgst`, `sgst`, `igst`, `tds_amount`, `net_amount`, `invoice_design`) VALUES ('2', '1', 'INV-202607-001', '5000.00', 'unpaid', '2026-07-07', '2026-07-22', 'CA retainer professional services fee for July 2026', '2026-07-07 09:44:42', '2026-07-07 09:44:42', '450.00', '450.00', '0.00', '0.00', '5900.00', NULL);
INSERT INTO `accounting_invoices` (`id`, `client_id`, `invoice_number`, `amount`, `status`, `issue_date`, `due_date`, `description`, `created_at`, `updated_at`, `cgst`, `sgst`, `igst`, `tds_amount`, `net_amount`, `invoice_design`) VALUES ('3', '2', 'INV-202607-002', '5000.00', 'unpaid', '2026-07-07', '2026-07-22', 'CA retainer professional services fee for July 2026', '2026-07-07 09:44:42', '2026-07-07 09:44:42', '450.00', '450.00', '0.00', '0.00', '5900.00', NULL);

DROP TABLE IF EXISTS `accounting_payments`;
CREATE TABLE `accounting_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`),
  CONSTRAINT `accounting_payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `accounting_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `accounting_payments` (`id`, `invoice_id`, `amount`, `payment_date`, `payment_method`, `created_at`) VALUES ('1', '1', '15000.00', '2026-07-05', 'Bank Transfer', '2026-07-05 20:19:47');

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('1', NULL, 'test_action', 'Running verification tests', '127.0.0.1', '2026-07-05 18:11:52');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('2', NULL, 'test_action', 'Running verification tests', '127.0.0.1', '2026-07-05 18:14:26');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('3', '1', 'update_employee', 'Updated HR details for user ID 3', '::1', '2026-07-05 19:59:20');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('4', '1', 'add_task', 'Created task: gst tr', '::1', '2026-07-05 20:01:56');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('5', '4', 'log_work', 'Logged 12 hours for task ID 3', '::1', '2026-07-05 20:03:02');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('6', '4', 'update_task_status', 'Updated task ID 3 status to completed', '::1', '2026-07-05 20:03:06');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('7', '4', 'update_task_status', 'Updated task ID 3 status to in_progress', '::1', '2026-07-05 20:03:22');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('8', '4', 'clock_in', 'Employee clocked in at 16:33:48', '::1', '2026-07-05 20:03:48');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('9', '4', 'clock_out', 'Employee clocked out at 16:33:54', '::1', '2026-07-05 20:03:54');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('10', '3', 'clock_in', 'Employee clocked in at 16:35:16', '::1', '2026-07-05 20:05:16');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('11', '3', 'clock_out', 'Employee clocked out at 16:35:19', '::1', '2026-07-05 20:05:19');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('12', '4', 'request_leave', 'Requested sick leave from 2026-07-06 to 2026-07-06', '::1', '2026-07-05 20:07:59');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('13', '1', 'review_leave', 'Leave request ID 1 reviewed as: approved', '::1', '2026-07-05 20:08:24');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('14', '1', 'document_request', 'Requested document \'send new document\' from client 1', '::1', '2026-07-05 20:10:39');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('15', '1', 'edit_task', 'Updated task ID 3', '::1', '2026-07-05 20:18:26');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('16', '1', 'add_invoice', 'Generated invoice INV-2026-09', '::1', '2026-07-05 20:19:28');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('17', '1', 'record_payment', 'Recorded collection of ₹15000 for Invoice ID 1', '::1', '2026-07-05 20:19:47');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('18', '1', 'add_expense', 'Logged firm expense of ₹2000 for Utilities', '::1', '2026-07-05 20:20:36');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('19', '1', 'add_expense', 'Logged firm expense of ₹15000 for Salaries', '::1', '2026-07-05 20:21:12');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('20', '1', 'post_announcement', 'Posted announcement: public holiday', '::1', '2026-07-05 21:28:40');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('21', '1', 'update_employee', 'Updated HR details and salary structure for user ID 1', '::1', '2026-07-05 22:02:00');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('22', '1', 'update_employee', 'Updated HR details and salary structure for user ID 3', '::1', '2026-07-05 22:02:48');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('23', '1', 'update_employee', 'Updated HR details and salary structure for user ID 4', '::1', '2026-07-05 22:03:30');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('24', '1', 'update_employee', 'Updated HR details and salary structure for user ID 5', '::1', '2026-07-05 22:04:19');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('25', '1', 'generate_salary_slip', 'Generated salary slip for employee ID 4 for month 2026-07', '::1', '2026-07-05 22:11:51');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('26', '1', 'add_compliance', 'Created compliance task \'gst\' for client ID 2', '::1', '2026-07-06 14:56:59');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('27', '1', 'generate_token', 'Generated portal token for client ID 2', '::1', '2026-07-06 14:58:09');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('28', '1', 'add_compliance', 'Created compliance task \'gst\' for client ID 2', '::1', '2026-07-06 22:58:08');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('29', '1', 'clock_in', 'Employee clocked in at 19:59:22', '::1', '2026-07-06 23:29:22');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES ('30', '1', 'clock_out', 'Employee clocked out at 19:59:25', '::1', '2026-07-06 23:29:25');

DROP TABLE IF EXISTS `announcements`;
CREATE TABLE `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `announcements` (`id`, `title`, `content`, `created_by`, `created_at`) VALUES ('1', 'public holiday', '15 aug 2026', '1', '2026-07-05 21:28:40');

DROP TABLE IF EXISTS `api_tokens`;
CREATE TABLE `api_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `api_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `attendance`;
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `status` enum('present','absent','on_leave') NOT NULL DEFAULT 'present',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_date_unique` (`user_id`,`date`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `record_id` int(11) NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `automation_queue`;
CREATE TABLE `automation_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(100) NOT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `scheduled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sent_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `automation_queue` (`id`, `event_type`, `recipient_email`, `subject`, `body`, `status`, `scheduled_at`, `sent_at`) VALUES ('1', 'compliance_created', 'rajratn@gmail.com', 'New Compliance Scheduled: GST Filing Q1 2026', 'Dear rajratn inductry,\n\nA new compliance task \'GST Filing Q1 2026\' (Category: GST Return) has been scheduled. It is due on 2026-06-30.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-06 14:56:21', '2026-07-06 14:56:21');
INSERT INTO `automation_queue` (`id`, `event_type`, `recipient_email`, `subject`, `body`, `status`, `scheduled_at`, `sent_at`) VALUES ('2', 'compliance_overdue', 'rajratn@gmail.com', 'OVERDUE Tax Compliance Action Required: GST Filing Q1 2026', 'Dear rajratn inductry,\n\nThis is an urgent notice that your compliance task \'GST Filing Q1 2026\' (Due: 2026-06-30) is OVERDUE. Please log in to your portal immediately to review and submit your response.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-06 14:56:21', '2026-07-06 14:56:21');
INSERT INTO `automation_queue` (`id`, `event_type`, `recipient_email`, `subject`, `body`, `status`, `scheduled_at`, `sent_at`) VALUES ('3', 'compliance_created', 'ramraj@email.com', 'New Compliance Scheduled: gst', 'Dear ramraj,\n\nA new compliance task \'gst\' (Category: GST Return) has been scheduled. It is due on 2026-07-06.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-06 14:56:59', '2026-07-07 00:45:46');
INSERT INTO `automation_queue` (`id`, `event_type`, `recipient_email`, `subject`, `body`, `status`, `scheduled_at`, `sent_at`) VALUES ('4', 'compliance_created', 'ramraj@email.com', 'New Compliance Scheduled: gst', 'Dear ramraj,\n\nA new compliance task \'gst\' (Category: TDS Return) has been scheduled. It is due on 2026-07-07.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-06 22:58:08', '2026-07-07 00:45:46');
INSERT INTO `automation_queue` (`id`, `event_type`, `recipient_email`, `subject`, `body`, `status`, `scheduled_at`, `sent_at`) VALUES ('5', 'compliance_created', 'rajratn@gmail.com', 'New Compliance Scheduled: GST Return Filing - Jul 2026', 'Dear rajratn inductry,\n\nA new compliance task \'GST Return Filing - Jul 2026\' (Category: GST Return) has been scheduled. It is due on 2026-07-20.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-07 00:45:46', '2026-07-07 00:45:46');
INSERT INTO `automation_queue` (`id`, `event_type`, `recipient_email`, `subject`, `body`, `status`, `scheduled_at`, `sent_at`) VALUES ('6', 'compliance_created', 'rajratn@gmail.com', 'New Compliance Scheduled: TDS Return Filing - Q3 2026', 'Dear rajratn inductry,\n\nA new compliance task \'TDS Return Filing - Q3 2026\' (Category: TDS Return) has been scheduled. It is due on 2026-07-31.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-07 00:45:46', '2026-07-07 00:45:46');
INSERT INTO `automation_queue` (`id`, `event_type`, `recipient_email`, `subject`, `body`, `status`, `scheduled_at`, `sent_at`) VALUES ('7', 'compliance_created', 'rajratn@gmail.com', 'New Compliance Scheduled: Annual ROC Filing - 2026', 'Dear rajratn inductry,\n\nA new compliance task \'Annual ROC Filing - 2026\' (Category: ROC) has been scheduled. It is due on 2026-11-30.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-07 00:45:46', '2026-07-07 00:45:46');
INSERT INTO `automation_queue` (`id`, `event_type`, `recipient_email`, `subject`, `body`, `status`, `scheduled_at`, `sent_at`) VALUES ('8', 'compliance_created', 'rajratn@gmail.com', 'New Compliance Scheduled: Income Tax Return (ITR) Filing - AY 2026-2027', 'Dear rajratn inductry,\n\nA new compliance task \'Income Tax Return (ITR) Filing - AY 2026-2027\' (Category: ITR) has been scheduled. It is due on 2026-07-31.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-07 00:45:46', '2026-07-07 00:45:46');
INSERT INTO `automation_queue` (`id`, `event_type`, `recipient_email`, `subject`, `body`, `status`, `scheduled_at`, `sent_at`) VALUES ('9', 'compliance_created', 'ramraj@email.com', 'New Compliance Scheduled: GST Return Filing - Jul 2026', 'Dear ramraj,\n\nA new compliance task \'GST Return Filing - Jul 2026\' (Category: GST Return) has been scheduled. It is due on 2026-07-20.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-07 00:45:46', '2026-07-07 00:45:46');
INSERT INTO `automation_queue` (`id`, `event_type`, `recipient_email`, `subject`, `body`, `status`, `scheduled_at`, `sent_at`) VALUES ('10', 'compliance_created', 'ramraj@email.com', 'New Compliance Scheduled: TDS Return Filing - Q3 2026', 'Dear ramraj,\n\nA new compliance task \'TDS Return Filing - Q3 2026\' (Category: TDS Return) has been scheduled. It is due on 2026-07-31.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-07 00:45:46', '2026-07-07 00:45:46');
INSERT INTO `automation_queue` (`id`, `event_type`, `recipient_email`, `subject`, `body`, `status`, `scheduled_at`, `sent_at`) VALUES ('11', 'compliance_created', 'ramraj@email.com', 'New Compliance Scheduled: Annual ROC Filing - 2026', 'Dear ramraj,\n\nA new compliance task \'Annual ROC Filing - 2026\' (Category: ROC) has been scheduled. It is due on 2026-11-30.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-07 00:45:46', '2026-07-07 00:45:46');
INSERT INTO `automation_queue` (`id`, `event_type`, `recipient_email`, `subject`, `body`, `status`, `scheduled_at`, `sent_at`) VALUES ('12', 'compliance_created', 'ramraj@email.com', 'New Compliance Scheduled: Income Tax Return (ITR) Filing - AY 2026-2027', 'Dear ramraj,\n\nA new compliance task \'Income Tax Return (ITR) Filing - AY 2026-2027\' (Category: ITR) has been scheduled. It is due on 2026-07-31.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-07 00:45:46', '2026-07-07 00:45:46');
INSERT INTO `automation_queue` (`id`, `event_type`, `recipient_email`, `subject`, `body`, `status`, `scheduled_at`, `sent_at`) VALUES ('13', 'compliance_reminder', 'ramraj@email.com', 'Tax Return Compliance Reminder: gst', 'Dear ramraj,\n\nThis is an automated reminder that your return gst is due on 2026-07-06. Please ensure all files and comments are uploaded to the portal.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-07 00:45:46', '2026-07-07 00:45:46');
INSERT INTO `automation_queue` (`id`, `event_type`, `recipient_email`, `subject`, `body`, `status`, `scheduled_at`, `sent_at`) VALUES ('14', 'compliance_reminder', 'ramraj@email.com', 'Tax Return Compliance Reminder: gst', 'Dear ramraj,\n\nThis is an automated reminder that your return gst is due on 2026-07-07. Please ensure all files and comments are uploaded to the portal.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-07 00:45:46', '2026-07-07 00:45:46');
INSERT INTO `automation_queue` (`id`, `event_type`, `recipient_email`, `subject`, `body`, `status`, `scheduled_at`, `sent_at`) VALUES ('15', 'compliance_overdue', 'ramraj@email.com', 'OVERDUE Tax Compliance Action Required: gst', 'Dear ramraj,\n\nThis is an urgent notice that your compliance task \'gst\' (Due: 2026-07-06) is OVERDUE. Please log in to your portal immediately to review and submit your response.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-07 00:45:46', '2026-07-07 00:45:46');

DROP TABLE IF EXISTS `client_compliance_configs`;
CREATE TABLE `client_compliance_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `auto_gst` tinyint(1) DEFAULT 0,
  `auto_tds` tinyint(1) DEFAULT 0,
  `auto_roc` tinyint(1) DEFAULT 0,
  `auto_itr` tinyint(1) DEFAULT 0,
  `remind_email` tinyint(1) DEFAULT 1,
  `remind_sms` tinyint(1) DEFAULT 0,
  `escalation_days` int(11) DEFAULT 5,
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_id` (`client_id`),
  CONSTRAINT `client_compliance_configs_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `client_compliance_configs` (`id`, `client_id`, `auto_gst`, `auto_tds`, `auto_roc`, `auto_itr`, `remind_email`, `remind_sms`, `escalation_days`) VALUES ('1', '1', '1', '1', '1', '1', '1', '1', '5');
INSERT INTO `client_compliance_configs` (`id`, `client_id`, `auto_gst`, `auto_tds`, `auto_roc`, `auto_itr`, `remind_email`, `remind_sms`, `escalation_days`) VALUES ('2', '2', '1', '1', '1', '1', '1', '1', '5');

DROP TABLE IF EXISTS `client_notes`;
CREATE TABLE `client_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `client_notes_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_notes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `client_timeline`;
CREATE TABLE `client_timeline` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `event_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `client_timeline_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_timeline_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('1', '1', NULL, 'client_created', 'Client account created for rajratn inductry.', '2026-07-04 22:11:04');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('2', '1', NULL, 'token_generated', 'Secure portal access token generated (valid for 7 days).', '2026-07-04 22:11:11');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('3', '1', NULL, 'task_created', 'Task \'gst\' created and assigned.', '2026-07-04 22:12:05');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('4', '1', '1', 'work_logged', 'Logged 3 hours on task \'gst\'.', '2026-07-04 22:12:42');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('5', '1', NULL, 'document_requested', 'Document requested: \'statement\'.', '2026-07-04 22:14:07');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('6', '1', NULL, 'document_uploaded', 'Document \'shopping.jpg\' uploaded by client.', '2026-07-04 22:50:43');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('7', '1', NULL, 'document_uploaded', 'Document \'shopping.jpg\' uploaded by client.', '2026-07-04 22:50:56');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('8', '1', NULL, 'request_status_changed', 'Document request \'statement\' updated to: reviewed.', '2026-07-04 22:51:30');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('9', '1', NULL, 'task_spawned', 'Compliance task spawned from template: test', '2026-07-04 22:52:35');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('10', '1', NULL, 'task_deleted', 'Task \'test - Jul 2026\' deleted.', '2026-07-04 22:54:16');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('11', '1', NULL, 'task_updated', 'Task \'gst\' updated (Status: completed).', '2026-07-04 22:57:56');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('12', '1', NULL, 'task_updated', 'Task \'gst\' updated (Status: in_progress).', '2026-07-04 23:57:08');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('13', '1', NULL, 'task_updated', 'Task \'gst\' updated (Status: completed).', '2026-07-05 02:17:52');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('14', '2', NULL, 'client_created', 'Client account created for ramraj.', '2026-07-05 02:20:49');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('15', '2', NULL, 'task_updated', 'Task \'gst\' updated (Status: pending).', '2026-07-05 02:21:44');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('16', '2', NULL, 'task_updated', 'Task \'gst\' updated (Status: completed).', '2026-07-05 11:25:06');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('17', '1', NULL, 'task_created', 'Task \'gst tr\' created and assigned.', '2026-07-05 20:01:56');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('18', '1', '4', 'work_logged', 'Logged 12 hours on task \'gst tr\'.', '2026-07-05 20:03:02');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('19', '1', NULL, 'task_status_changed', 'Task \'gst tr\' status updated to completed.', '2026-07-05 20:03:06');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('20', '1', NULL, 'task_status_changed', 'Task \'gst tr\' status updated to in_progress.', '2026-07-05 20:03:22');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('21', '1', NULL, 'document_requested', 'Document requested: \'send new document\'.', '2026-07-05 20:10:39');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('22', '1', NULL, 'task_updated', 'Task \'gst tr\' updated (Status: completed).', '2026-07-05 20:18:26');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('23', '1', NULL, 'invoice_created', 'Invoice #INV-2026-09 for amount 15000 created.', '2026-07-05 20:19:27');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('24', '1', NULL, 'payment_recorded', 'Payment of 15000 received for Invoice #INV-2026-09.', '2026-07-05 20:19:47');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('25', '1', NULL, 'compliance_created', 'Compliance task \'GST Filing Q1 2026\' added (Due: 2026-06-30).', '2026-07-06 14:56:21');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('26', '1', NULL, 'compliance_responded', 'Client responded to compliance \'GST Filing Q1 2026\': \'All invoices uploaded to Document Center.\'', '2026-07-06 14:56:21');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('27', '1', NULL, 'compliance_filed', 'Compliance return filed: \'GST Filing Q1 2026\' (Ack: ACK-987654321-GST).', '2026-07-06 14:56:21');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('28', '1', NULL, 'compliance_deleted', 'Compliance return \'GST Filing Q1 2026\' deleted.', '2026-07-06 14:56:21');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('29', '2', NULL, 'compliance_created', 'Compliance task \'gst\' added (Due: 2026-07-06).', '2026-07-06 14:56:59');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('30', '2', NULL, 'token_generated', 'Secure portal access token generated (valid for 7 days).', '2026-07-06 14:58:09');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('31', '2', NULL, 'compliance_created', 'Compliance task \'gst\' added (Due: 2026-07-07).', '2026-07-06 22:58:08');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('32', '1', NULL, 'compliance_created', 'Compliance task \'GST Return Filing - Jul 2026\' added (Due: 2026-07-20).', '2026-07-07 00:45:46');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('33', '1', NULL, 'compliance_created', 'Compliance task \'TDS Return Filing - Q3 2026\' added (Due: 2026-07-31).', '2026-07-07 00:45:46');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('34', '1', NULL, 'compliance_created', 'Compliance task \'Annual ROC Filing - 2026\' added (Due: 2026-11-30).', '2026-07-07 00:45:46');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('35', '1', NULL, 'compliance_created', 'Compliance task \'Income Tax Return (ITR) Filing - AY 2026-2027\' added (Due: 2026-07-31).', '2026-07-07 00:45:46');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('36', '2', NULL, 'compliance_created', 'Compliance task \'GST Return Filing - Jul 2026\' added (Due: 2026-07-20).', '2026-07-07 00:45:46');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('37', '2', NULL, 'compliance_created', 'Compliance task \'TDS Return Filing - Q3 2026\' added (Due: 2026-07-31).', '2026-07-07 00:45:46');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('38', '2', NULL, 'compliance_created', 'Compliance task \'Annual ROC Filing - 2026\' added (Due: 2026-11-30).', '2026-07-07 00:45:46');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('39', '2', NULL, 'compliance_created', 'Compliance task \'Income Tax Return (ITR) Filing - AY 2026-2027\' added (Due: 2026-07-31).', '2026-07-07 00:45:46');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('40', '1', NULL, 'document_uploaded', 'Document \'gst_return_fy26.pdf\' uploaded to folder \'/GST\' (Version: v1) by test_user.', '2026-07-07 00:51:18');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('41', '1', NULL, 'document_uploaded', 'Document \'gst_return_fy26.pdf\' uploaded to folder \'/GST\' (Version: v2) by test_user.', '2026-07-07 00:51:18');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('42', '1', NULL, 'document_signed', 'Document \'gst_return_fy26.pdf\' was digitally signed by CA Amit Kumar.', '2026-07-07 00:51:18');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('43', '1', NULL, 'document_uploaded', 'Document \'gst_return_fy26.pdf\' uploaded to folder \'/GST\' (Version: v3) by test_user.', '2026-07-07 00:51:35');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('44', '1', NULL, 'document_uploaded', 'Document \'gst_return_fy26.pdf\' uploaded to folder \'/GST\' (Version: v4) by test_user.', '2026-07-07 00:51:35');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('45', '1', NULL, 'document_signed', 'Document \'gst_return_fy26.pdf\' was digitally signed by CA Amit Kumar.', '2026-07-07 00:51:35');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('46', '1', NULL, 'meeting_scheduled', 'Scheduled meeting \'Virtual Audit Alignment\' on 2026-07-07 06:12:48.', '2026-07-07 09:42:48');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('47', '1', NULL, 'invoice_created', 'Invoice #INV-202607-001 for amount 5000 (Net: 5900) created.', '2026-07-07 09:44:42');
INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES ('48', '2', NULL, 'invoice_created', 'Invoice #INV-202607-002 for amount 5000 (Net: 5900) created.', '2026-07-07 09:44:42');

DROP TABLE IF EXISTS `clients`;
CREATE TABLE `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `portal_token` varchar(255) DEFAULT NULL,
  `portal_token_expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `portal_token` (`portal_token`),
  KEY `idx_clients_deleted_at` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `clients` (`id`, `name`, `email`, `phone`, `portal_token`, `portal_token_expires_at`, `created_at`, `updated_at`, `deleted_at`) VALUES ('1', 'rajratn inductry', 'rajratn@gmail.com', '7676767676', '0de5a802eb74247882b7b09a673e9907957865db2f36faa1a65e9d230bcb895f', '2026-07-11 18:41:11', '2026-07-04 22:11:04', '2026-07-04 22:11:11', NULL);
INSERT INTO `clients` (`id`, `name`, `email`, `phone`, `portal_token`, `portal_token_expires_at`, `created_at`, `updated_at`, `deleted_at`) VALUES ('2', 'ramraj', 'ramraj@email.com', '9898989898', 'c1d2367088ab0bb424d135b8be767660802b4d9866d5a82ffa39678d2530e590', '2026-07-13 11:28:09', '2026-07-05 02:20:49', '2026-07-06 14:58:09', NULL);

DROP TABLE IF EXISTS `compliances`;
CREATE TABLE `compliances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `due_date` date NOT NULL,
  `filing_date` date DEFAULT NULL,
  `acknowledgement_number` varchar(100) DEFAULT NULL,
  `status` enum('pending','filed','overdue') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `client_response` text DEFAULT NULL,
  `client_responded_at` timestamp NULL DEFAULT NULL,
  `email_reminders_sent` int(11) DEFAULT 0,
  `sms_reminders_sent` int(11) DEFAULT 0,
  `escalated` tinyint(1) DEFAULT 0,
  `escalated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `idx_compliances_status_due` (`status`,`due_date`),
  CONSTRAINT `compliances_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `compliances` (`id`, `client_id`, `title`, `category`, `due_date`, `filing_date`, `acknowledgement_number`, `status`, `notes`, `created_at`, `updated_at`, `client_response`, `client_responded_at`, `email_reminders_sent`, `sms_reminders_sent`, `escalated`, `escalated_at`) VALUES ('2', '2', 'gst', 'GST Return', '2026-07-06', NULL, NULL, 'pending', 'test', '2026-07-06 14:56:59', '2026-07-07 00:45:46', NULL, NULL, '1', '1', '0', NULL);
INSERT INTO `compliances` (`id`, `client_id`, `title`, `category`, `due_date`, `filing_date`, `acknowledgement_number`, `status`, `notes`, `created_at`, `updated_at`, `client_response`, `client_responded_at`, `email_reminders_sent`, `sms_reminders_sent`, `escalated`, `escalated_at`) VALUES ('3', '2', 'gst', 'TDS Return', '2026-07-07', NULL, NULL, 'pending', 'new', '2026-07-06 22:58:08', '2026-07-07 00:45:46', NULL, NULL, '1', '1', '0', NULL);
INSERT INTO `compliances` (`id`, `client_id`, `title`, `category`, `due_date`, `filing_date`, `acknowledgement_number`, `status`, `notes`, `created_at`, `updated_at`, `client_response`, `client_responded_at`, `email_reminders_sent`, `sms_reminders_sent`, `escalated`, `escalated_at`) VALUES ('4', '1', 'GST Return Filing - Jul 2026', 'GST Return', '2026-07-20', NULL, NULL, 'pending', 'Automatically generated monthly GST return task.', '2026-07-07 00:45:46', '2026-07-07 00:45:46', NULL, NULL, '0', '0', '0', NULL);
INSERT INTO `compliances` (`id`, `client_id`, `title`, `category`, `due_date`, `filing_date`, `acknowledgement_number`, `status`, `notes`, `created_at`, `updated_at`, `client_response`, `client_responded_at`, `email_reminders_sent`, `sms_reminders_sent`, `escalated`, `escalated_at`) VALUES ('5', '1', 'TDS Return Filing - Q3 2026', 'TDS Return', '2026-07-31', NULL, NULL, 'pending', 'Automatically generated quarterly TDS return task.', '2026-07-07 00:45:46', '2026-07-07 00:45:46', NULL, NULL, '0', '0', '0', NULL);
INSERT INTO `compliances` (`id`, `client_id`, `title`, `category`, `due_date`, `filing_date`, `acknowledgement_number`, `status`, `notes`, `created_at`, `updated_at`, `client_response`, `client_responded_at`, `email_reminders_sent`, `sms_reminders_sent`, `escalated`, `escalated_at`) VALUES ('6', '1', 'Annual ROC Filing - 2026', 'ROC', '2026-11-30', NULL, NULL, 'pending', 'Automatically generated annual ROC return task.', '2026-07-07 00:45:46', '2026-07-07 00:45:46', NULL, NULL, '0', '0', '0', NULL);
INSERT INTO `compliances` (`id`, `client_id`, `title`, `category`, `due_date`, `filing_date`, `acknowledgement_number`, `status`, `notes`, `created_at`, `updated_at`, `client_response`, `client_responded_at`, `email_reminders_sent`, `sms_reminders_sent`, `escalated`, `escalated_at`) VALUES ('7', '1', 'Income Tax Return (ITR) Filing - AY 2026-2027', 'ITR', '2026-07-31', NULL, NULL, 'pending', 'Automatically generated annual Income Tax Return task.', '2026-07-07 00:45:46', '2026-07-07 00:45:46', NULL, NULL, '0', '0', '0', NULL);
INSERT INTO `compliances` (`id`, `client_id`, `title`, `category`, `due_date`, `filing_date`, `acknowledgement_number`, `status`, `notes`, `created_at`, `updated_at`, `client_response`, `client_responded_at`, `email_reminders_sent`, `sms_reminders_sent`, `escalated`, `escalated_at`) VALUES ('8', '2', 'GST Return Filing - Jul 2026', 'GST Return', '2026-07-20', NULL, NULL, 'pending', 'Automatically generated monthly GST return task.', '2026-07-07 00:45:46', '2026-07-07 00:45:46', NULL, NULL, '0', '0', '0', NULL);
INSERT INTO `compliances` (`id`, `client_id`, `title`, `category`, `due_date`, `filing_date`, `acknowledgement_number`, `status`, `notes`, `created_at`, `updated_at`, `client_response`, `client_responded_at`, `email_reminders_sent`, `sms_reminders_sent`, `escalated`, `escalated_at`) VALUES ('9', '2', 'TDS Return Filing - Q3 2026', 'TDS Return', '2026-07-31', NULL, NULL, 'pending', 'Automatically generated quarterly TDS return task.', '2026-07-07 00:45:46', '2026-07-07 00:45:46', NULL, NULL, '0', '0', '0', NULL);
INSERT INTO `compliances` (`id`, `client_id`, `title`, `category`, `due_date`, `filing_date`, `acknowledgement_number`, `status`, `notes`, `created_at`, `updated_at`, `client_response`, `client_responded_at`, `email_reminders_sent`, `sms_reminders_sent`, `escalated`, `escalated_at`) VALUES ('10', '2', 'Annual ROC Filing - 2026', 'ROC', '2026-11-30', NULL, NULL, 'pending', 'Automatically generated annual ROC return task.', '2026-07-07 00:45:46', '2026-07-07 00:45:46', NULL, NULL, '0', '0', '0', NULL);
INSERT INTO `compliances` (`id`, `client_id`, `title`, `category`, `due_date`, `filing_date`, `acknowledgement_number`, `status`, `notes`, `created_at`, `updated_at`, `client_response`, `client_responded_at`, `email_reminders_sent`, `sms_reminders_sent`, `escalated`, `escalated_at`) VALUES ('11', '2', 'Income Tax Return (ITR) Filing - AY 2026-2027', 'ITR', '2026-07-31', NULL, NULL, 'pending', 'Automatically generated annual Income Tax Return task.', '2026-07-07 00:45:46', '2026-07-07 00:45:46', NULL, NULL, '0', '0', '0', NULL);

DROP TABLE IF EXISTS `document_requests`;
CREATE TABLE `document_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','uploaded','reviewed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `document_requests_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `document_requests` (`id`, `client_id`, `title`, `description`, `status`, `created_at`, `updated_at`) VALUES ('1', '1', 'statement', 'new', 'reviewed', '2026-07-04 22:14:07', '2026-07-04 22:51:30');
INSERT INTO `document_requests` (`id`, `client_id`, `title`, `description`, `status`, `created_at`, `updated_at`) VALUES ('2', '1', 'send new document', 'trial', 'pending', '2026-07-05 20:10:39', '2026-07-05 20:10:39');

DROP TABLE IF EXISTS `documents`;
CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `category` varchar(100) DEFAULT 'General',
  `document_request_id` int(11) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `version` int(11) DEFAULT 1,
  `description` text DEFAULT NULL,
  `uploaded_by` enum('client','staff') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `folder` varchar(255) DEFAULT '/',
  `parent_document_id` int(11) DEFAULT NULL,
  `signature_status` enum('unsigned','signed') DEFAULT 'unsigned',
  `signed_by` varchar(255) DEFAULT NULL,
  `signed_at` timestamp NULL DEFAULT NULL,
  `ocr_text` text DEFAULT NULL,
  `sharing_scope` enum('internal_only','client_shared') DEFAULT 'client_shared',
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `document_request_id` (`document_request_id`),
  FULLTEXT KEY `idx_documents_ocr` (`ocr_text`),
  CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`document_request_id`) REFERENCES `document_requests` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `documents` (`id`, `client_id`, `category`, `document_request_id`, `file_name`, `file_path`, `file_size`, `version`, `description`, `uploaded_by`, `created_at`, `folder`, `parent_document_id`, `signature_status`, `signed_by`, `signed_at`, `ocr_text`, `sharing_scope`) VALUES ('1', '1', 'General', '1', 'shopping.jpg', 'uploads/8aeb684729624a9e8868d5e40b9802dd.jpg', '19246', '1', NULL, 'client', '2026-07-04 22:50:43', '/', NULL, 'unsigned', NULL, NULL, NULL, 'client_shared');
INSERT INTO `documents` (`id`, `client_id`, `category`, `document_request_id`, `file_name`, `file_path`, `file_size`, `version`, `description`, `uploaded_by`, `created_at`, `folder`, `parent_document_id`, `signature_status`, `signed_by`, `signed_at`, `ocr_text`, `sharing_scope`) VALUES ('2', '1', 'General', NULL, 'shopping.jpg', 'uploads/af01863820dd13ef4d8cc9eb2ed4e220.jpg', '19246', '1', NULL, 'client', '2026-07-04 22:50:56', '/', NULL, 'unsigned', NULL, NULL, NULL, 'client_shared');
INSERT INTO `documents` (`id`, `client_id`, `category`, `document_request_id`, `file_name`, `file_path`, `file_size`, `version`, `description`, `uploaded_by`, `created_at`, `folder`, `parent_document_id`, `signature_status`, `signed_by`, `signed_at`, `ocr_text`, `sharing_scope`) VALUES ('3', '1', 'General', NULL, 'gst_return_fy26.pdf', 'uploads/b78b4872ae1bfc4b70bdd8b153795ffb.pdf', '1024', '1', NULL, '', '2026-07-07 00:51:18', '/GST', NULL, 'unsigned', NULL, NULL, 'Document: gst_return_fy26.pdf, Uploaded by: test_user, GST GSTR filing tax returns CGST SGST IGST ledger', 'client_shared');
INSERT INTO `documents` (`id`, `client_id`, `category`, `document_request_id`, `file_name`, `file_path`, `file_size`, `version`, `description`, `uploaded_by`, `created_at`, `folder`, `parent_document_id`, `signature_status`, `signed_by`, `signed_at`, `ocr_text`, `sharing_scope`) VALUES ('4', '1', 'General', NULL, 'gst_return_fy26.pdf', 'uploads/262f01acd80482a4951354c4e89f562c.pdf', '1024', '2', NULL, '', '2026-07-07 00:51:18', '/GST', '3', 'signed', 'CA Amit Kumar', '2026-07-07 00:51:18', 'Document: gst_return_fy26.pdf, Uploaded by: test_user, GST GSTR filing tax returns CGST SGST IGST ledger', 'client_shared');
INSERT INTO `documents` (`id`, `client_id`, `category`, `document_request_id`, `file_name`, `file_path`, `file_size`, `version`, `description`, `uploaded_by`, `created_at`, `folder`, `parent_document_id`, `signature_status`, `signed_by`, `signed_at`, `ocr_text`, `sharing_scope`) VALUES ('5', '1', 'General', NULL, 'gst_return_fy26.pdf', 'uploads/1f3d074320b6fb612437e82cf989c6e1.pdf', '1024', '3', NULL, '', '2026-07-07 00:51:35', '/GST', '3', 'unsigned', NULL, NULL, 'Document: gst_return_fy26.pdf, Uploaded by: test_user, GST GSTR filing tax returns CGST SGST IGST ledger', 'client_shared');
INSERT INTO `documents` (`id`, `client_id`, `category`, `document_request_id`, `file_name`, `file_path`, `file_size`, `version`, `description`, `uploaded_by`, `created_at`, `folder`, `parent_document_id`, `signature_status`, `signed_by`, `signed_at`, `ocr_text`, `sharing_scope`) VALUES ('6', '1', 'General', NULL, 'gst_return_fy26.pdf', 'uploads/e844525b88679c9c610f399f54a5de91.pdf', '1024', '4', NULL, '', '2026-07-07 00:51:35', '/GST', '3', 'signed', 'CA Amit Kumar', '2026-07-07 00:51:35', 'Document: gst_return_fy26.pdf, Uploaded by: test_user, GST GSTR filing tax returns CGST SGST IGST ledger', 'client_shared');

DROP TABLE IF EXISTS `email_logs`;
CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'sent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `email_logs_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `email_templates`;
CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_name` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_name` (`template_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `email_templates` (`id`, `template_name`, `subject`, `body`, `updated_at`) VALUES ('1', 'compliance_due_reminder', 'Statutory Compliance Due Date Reminder', 'Dear Client,\n\nThis is to remind you that your statutory filing for {filing_title} is due on {due_date}.\n\nPlease upload the required documents as soon as possible via the client portal.\n\nBest Regards,\nCA Associates Team', '2026-07-07 09:39:04');
INSERT INTO `email_templates` (`id`, `template_name`, `subject`, `body`, `updated_at`) VALUES ('2', 'invoice_outstanding', 'Invoice Outstanding Notice - CA Associates', 'Dear Client,\n\nInvoice {invoice_number} for the amount of ₹{amount} issued on {issue_date} remains unpaid.\n\nPlease clear the balance of ₹{net_amount} by the due date of {due_date}.\n\nBest Regards,\nCA Associates Finance Team', '2026-07-07 09:39:04');
INSERT INTO `email_templates` (`id`, `template_name`, `subject`, `body`, `updated_at`) VALUES ('3', 'client_portal_welcome', 'URGENT: Welcome to CA Firm Client Portal', 'Dear {client_name},\n\nYour secure client portal session has been set up.\n\nYou can access your portal vault at any time using your personal access token: {portal_token}\n\nBest Regards,\nCA Associates Support Team', '2026-07-07 09:42:48');

DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `department` varchar(100) NOT NULL,
  `designation` varchar(100) NOT NULL,
  `joining_date` date NOT NULL,
  `salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `basic` decimal(10,2) NOT NULL DEFAULT 0.00,
  `hra` decimal(10,2) NOT NULL DEFAULT 0.00,
  `conveyance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `allowance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `pf` decimal(10,2) NOT NULL DEFAULT 0.00,
  `pt` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tds` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shift` varchar(50) DEFAULT 'General',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `employees` (`id`, `user_id`, `department`, `designation`, `joining_date`, `salary`, `status`, `basic`, `hra`, `conveyance`, `allowance`, `pf`, `pt`, `tds`, `shift`) VALUES ('1', '3', 'employee1', 'STC', '2026-07-05', '15000.00', 'active', '10000.00', '200.00', '200.00', '200.00', '2000.00', '200.00', '200.00', 'General');
INSERT INTO `employees` (`id`, `user_id`, `department`, `designation`, `joining_date`, `salary`, `status`, `basic`, `hra`, `conveyance`, `allowance`, `pf`, `pt`, `tds`, `shift`) VALUES ('2', '2', 'Taxation', 'Tax Lead Specialist', '2026-01-15', '60000.00', 'active', '35000.00', '15000.00', '3000.00', '7000.00', '1800.00', '200.00', '3000.00', 'General');
INSERT INTO `employees` (`id`, `user_id`, `department`, `designation`, `joining_date`, `salary`, `status`, `basic`, `hra`, `conveyance`, `allowance`, `pf`, `pt`, `tds`, `shift`) VALUES ('3', '1', 'SA', 'head', '2026-07-05', '50000.00', 'active', '40000.00', '10000.00', '200.00', '200.00', '2500.00', '2000.00', '200.00', 'General');
INSERT INTO `employees` (`id`, `user_id`, `department`, `designation`, `joining_date`, `salary`, `status`, `basic`, `hra`, `conveyance`, `allowance`, `pf`, `pt`, `tds`, `shift`) VALUES ('4', '4', 'st2', 'STC', '2026-07-05', '20000.00', 'active', '15000.00', '2000.00', '200.00', '200.00', '2000.00', '200.00', '200.00', 'General');
INSERT INTO `employees` (`id`, `user_id`, `department`, `designation`, `joining_date`, `salary`, `status`, `basic`, `hra`, `conveyance`, `allowance`, `pf`, `pt`, `tds`, `shift`) VALUES ('5', '5', 'SA', 'STC', '2026-07-05', '15000.00', 'active', '10000.00', '2000.00', '200.00', '200.00', '2000.00', '200.00', '200.00', 'General');

DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `invoice_number` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('unpaid','paid','overdue','cancelled') DEFAULT 'unpaid',
  `due_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `leads`;
CREATE TABLE `leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `source` varchar(100) DEFAULT 'Direct',
  `status` enum('new','contacted','qualified','disqualified') NOT NULL DEFAULT 'new',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `leave_requests`;
CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `leave_type` enum('casual','sick','earned') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `comments` text DEFAULT NULL,
  `workflow_step` varchar(100) DEFAULT 'approved_by_admin',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_requests_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `leave_requests` (`id`, `user_id`, `leave_type`, `start_date`, `end_date`, `status`, `reason`, `approved_by`, `created_at`, `comments`, `workflow_step`) VALUES ('1', '4', 'sick', '2026-07-06', '2026-07-06', 'approved', 'fever', '1', '2026-07-05 20:07:59', NULL, 'approved_by_admin');

DROP TABLE IF EXISTS `leaves`;
CREATE TABLE `leaves` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `leave_type` enum('sick','casual','earned','unpaid') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `leaves_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `login_logs`;
CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `email_attempted` varchar(255) NOT NULL,
  `ip_address` varchar(50) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `status` enum('success','failed') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('1', '1', 'test@example.com', '127.0.0.1', 'Unknown', 'success', '2026-07-05 18:11:52');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('2', '1', 'test@example.com', '127.0.0.1', 'Unknown', 'success', '2026-07-05 18:14:26');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('3', '6', 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 18:28:41');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('4', '6', 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 18:28:41');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('5', '6', 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 18:28:41');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('6', '6', 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 18:28:41');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('7', '6', 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 18:28:41');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('8', NULL, 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 18:28:41');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('9', '7', 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 18:29:14');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('10', '7', 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 18:29:14');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('11', '7', 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 18:29:14');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('12', '7', 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 18:29:14');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('13', '7', 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 18:29:14');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('14', '8', 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 18:29:32');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('15', '8', 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 18:29:32');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('16', '8', 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 18:29:32');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('17', '8', 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 18:29:32');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('18', '8', 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 18:29:32');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('19', '9', 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 18:29:54');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('20', '9', 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 18:29:54');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('21', '9', 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 18:29:54');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('22', '9', 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 18:29:54');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('23', '9', 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 18:29:54');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('24', '1', 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 19:58:22');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('25', '1', 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 20:02:14');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('26', '4', 'staff2@example.test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 20:02:45');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('27', '3', 'staff1@example.test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 20:04:42');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('28', '1', 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 20:05:38');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('29', '4', 'staff2@example.test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 20:07:25');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('30', '1', 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 20:08:15');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('31', '4', 'staff2@example.test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 20:08:49');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('32', '1', 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 20:09:09');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('33', '1', 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 20:50:48');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('34', '1', 'test', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 21:15:30');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('35', '1', 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 21:19:05');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('36', '1', 'test', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 21:19:48');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('37', '1', 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 22:00:30');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('38', '4', 'staff2@example.test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 22:08:36');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('39', '1', 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 22:11:30');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('40', '4', 'staff2@example.test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 22:12:24');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('41', '1', 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-06 14:26:12');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('42', '1', 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-06 14:56:37');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('43', NULL, 'ramraj@email.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'failed', '2026-07-06 14:57:23');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('44', NULL, 'ramraj@email.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'failed', '2026-07-06 14:57:35');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('45', '1', 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-06 14:57:59');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('46', '1', 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-06 23:20:15');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('47', '3', 'staff1@example.test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-07 00:19:13');
INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES ('48', '1', 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-07 00:20:42');

DROP TABLE IF EXISTS `meetings`;
CREATE TABLE `meetings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `meeting_date` datetime NOT NULL,
  `status` enum('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `video_link` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `meetings_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `meetings` (`id`, `client_id`, `title`, `description`, `meeting_date`, `status`, `created_at`, `video_link`) VALUES ('1', '1', 'Virtual Audit Alignment', 'Online walkthrough', '2026-07-07 06:12:48', 'scheduled', '2026-07-07 09:42:48', 'https://meet.google.com/abc-defg-hij');

DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message_text` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `message_text`, `is_read`, `created_at`) VALUES ('1', '1', '2', 'Test check-in greeting!', '0', '2026-07-05 18:11:52');
INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `message_text`, `is_read`, `created_at`) VALUES ('2', '1', '2', 'Test check-in greeting!', '0', '2026-07-05 18:14:26');
INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `message_text`, `is_read`, `created_at`) VALUES ('3', '4', '3', 'hi', '1', '2026-07-05 20:04:12');

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `is_read`, `created_at`) VALUES ('1', '1', 'Task Deadline Warning', 'Task GSTR filing is overdue.', '1', '2026-07-07 09:42:48');

DROP TABLE IF EXISTS `opportunities`;
CREATE TABLE `opportunities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stage` enum('discovery','proposal','negotiation','won','lost') NOT NULL DEFAULT 'discovery',
  `probability` int(11) NOT NULL DEFAULT 10,
  `close_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `lead_id` (`lead_id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `opportunities_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `opportunities_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`id`, `name`, `description`, `created_at`) VALUES ('1', 'manage_settings', 'Modify system settings, configuration', '2026-07-05 10:58:51');
INSERT INTO `permissions` (`id`, `name`, `description`, `created_at`) VALUES ('2', 'manage_staff', 'Add, edit, or delete staff members', '2026-07-05 10:58:51');
INSERT INTO `permissions` (`id`, `name`, `description`, `created_at`) VALUES ('3', 'manage_clients', 'Add, edit, or delete clients', '2026-07-05 10:58:51');
INSERT INTO `permissions` (`id`, `name`, `description`, `created_at`) VALUES ('4', 'manage_tasks', 'Assign, update, and manage all tasks', '2026-07-05 10:58:51');
INSERT INTO `permissions` (`id`, `name`, `description`, `created_at`) VALUES ('5', 'view_all_tasks', 'View tasks assigned to any staff', '2026-07-05 10:58:51');
INSERT INTO `permissions` (`id`, `name`, `description`, `created_at`) VALUES ('6', 'log_work', 'Create work logs on tasks', '2026-07-05 10:58:51');
INSERT INTO `permissions` (`id`, `name`, `description`, `created_at`) VALUES ('7', 'view_audit_logs', 'Access system logs and security reports', '2026-07-05 10:58:51');

DROP TABLE IF EXISTS `recurring_templates`;
CREATE TABLE `recurring_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `assigned_to_user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `category` varchar(100) NOT NULL,
  `frequency` enum('monthly','quarterly','yearly') NOT NULL DEFAULT 'monthly',
  `next_spawn_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `assigned_to_user_id` (`assigned_to_user_id`),
  CONSTRAINT `recurring_templates_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recurring_templates_ibfk_2` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `recurring_templates` (`id`, `client_id`, `assigned_to_user_id`, `title`, `description`, `priority`, `category`, `frequency`, `next_spawn_date`, `created_at`, `updated_at`) VALUES ('1', '1', '2', 'test', 'new test', 'medium', 'TDS', 'monthly', '2026-08-01', '2026-07-04 22:13:41', '2026-07-04 22:52:35');

DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions` (
  `role` enum('super_admin','admin_manager','staff') NOT NULL,
  `permission` varchar(100) NOT NULL,
  PRIMARY KEY (`role`,`permission`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `role_permissions` (`role`, `permission`) VALUES ('super_admin', 'edit_roles');
INSERT INTO `role_permissions` (`role`, `permission`) VALUES ('super_admin', 'manage_accounting');
INSERT INTO `role_permissions` (`role`, `permission`) VALUES ('super_admin', 'manage_clients');
INSERT INTO `role_permissions` (`role`, `permission`) VALUES ('super_admin', 'manage_compliance');
INSERT INTO `role_permissions` (`role`, `permission`) VALUES ('super_admin', 'manage_hrms');
INSERT INTO `role_permissions` (`role`, `permission`) VALUES ('super_admin', 'manage_staff');
INSERT INTO `role_permissions` (`role`, `permission`) VALUES ('super_admin', 'manage_tasks');
INSERT INTO `role_permissions` (`role`, `permission`) VALUES ('super_admin', 'view_reports');
INSERT INTO `role_permissions` (`role`, `permission`) VALUES ('super_admin', 'view_security_logs');
INSERT INTO `role_permissions` (`role`, `permission`) VALUES ('admin_manager', 'manage_accounting');
INSERT INTO `role_permissions` (`role`, `permission`) VALUES ('admin_manager', 'manage_clients');
INSERT INTO `role_permissions` (`role`, `permission`) VALUES ('admin_manager', 'manage_compliance');
INSERT INTO `role_permissions` (`role`, `permission`) VALUES ('admin_manager', 'manage_hrms');
INSERT INTO `role_permissions` (`role`, `permission`) VALUES ('admin_manager', 'manage_tasks');
INSERT INTO `role_permissions` (`role`, `permission`) VALUES ('admin_manager', 'view_reports');
INSERT INTO `role_permissions` (`role`, `permission`) VALUES ('staff', 'manage_compliance');
INSERT INTO `role_permissions` (`role`, `permission`) VALUES ('staff', 'manage_tasks');

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `name`, `description`, `created_at`) VALUES ('1', 'super_admin', 'Full access to all modules and configurations', '2026-07-05 10:58:51');
INSERT INTO `roles` (`id`, `name`, `description`, `created_at`) VALUES ('2', 'admin_manager', 'Manage client CRM, tasks, and document centers', '2026-07-05 10:58:51');
INSERT INTO `roles` (`id`, `name`, `description`, `created_at`) VALUES ('3', 'staff', 'Access to own tasks, daily work logging, and client documents', '2026-07-05 10:58:51');

DROP TABLE IF EXISTS `salary_slips`;
CREATE TABLE `salary_slips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `month` varchar(7) NOT NULL,
  `basic` decimal(10,2) NOT NULL DEFAULT 0.00,
  `hra` decimal(10,2) NOT NULL DEFAULT 0.00,
  `conveyance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `allowance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `pf` decimal(10,2) NOT NULL DEFAULT 0.00,
  `pt` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tds` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('unpaid','paid') NOT NULL DEFAULT 'unpaid',
  `paid_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `emp_month_unique` (`employee_id`,`month`),
  CONSTRAINT `salary_slips_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `salary_slips` (`id`, `employee_id`, `month`, `basic`, `hra`, `conveyance`, `allowance`, `pf`, `pt`, `tds`, `net_salary`, `status`, `paid_date`, `created_at`) VALUES ('4', '2', '2026-07', '30000.00', '12000.00', '2000.00', '5000.00', '1800.00', '200.00', '1000.00', '46000.00', 'paid', '2026-07-05', '2026-07-05 22:10:51');
INSERT INTO `salary_slips` (`id`, `employee_id`, `month`, `basic`, `hra`, `conveyance`, `allowance`, `pf`, `pt`, `tds`, `net_salary`, `status`, `paid_date`, `created_at`) VALUES ('5', '4', '2026-07', '967.74', '129.03', '12.90', '12.90', '2000.00', '200.00', '200.00', '-1277.43', 'unpaid', NULL, '2026-07-05 22:11:51');

DROP TABLE IF EXISTS `services`;
CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `charge` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `services` (`id`, `name`, `charge`, `description`) VALUES ('1', 'GST New Registration', '3000.00', 'Complete GST registration including ARN generation');
INSERT INTO `services` (`id`, `name`, `charge`, `description`) VALUES ('2', 'Monthly GSTR-3B Filing', '1500.00', 'Preparation and filing of GSTR-3B monthly returns');
INSERT INTO `services` (`id`, `name`, `charge`, `description`) VALUES ('3', 'ITR-1 Consultation & Filing', '2500.00', 'Income tax returns filing for salaried individuals');
INSERT INTO `services` (`id`, `name`, `charge`, `description`) VALUES ('4', 'Corporate Tax Audit Return', '15000.00', 'Detailed audit of accounts and form 3CD filing');
INSERT INTO `services` (`id`, `name`, `charge`, `description`) VALUES ('5', 'ROC Annual Filing Form AOC-4', '8000.00', 'Filing of annual financial statements of private limited company');

DROP TABLE IF EXISTS `sms_logs`;
CREATE TABLE `sms_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `phone_number` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'sent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `sms_logs_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sms_logs` (`id`, `client_id`, `phone_number`, `message`, `status`, `created_at`) VALUES ('1', '2', '9898989898', 'CA CRM Reminder: Your return \'gst\' is due on 2026-07-06. Please submit documents ASAP.', 'sent', '2026-07-07 00:45:46');
INSERT INTO `sms_logs` (`id`, `client_id`, `phone_number`, `message`, `status`, `created_at`) VALUES ('2', '2', '9898989898', 'CA CRM Reminder: Your return \'gst\' is due on 2026-07-07. Please submit documents ASAP.', 'sent', '2026-07-07 00:45:46');

DROP TABLE IF EXISTS `tasks`;
CREATE TABLE `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `assigned_to_user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','in_progress','completed','overdue') NOT NULL DEFAULT 'pending',
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `category` varchar(100) NOT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `assigned_to_user_id` (`assigned_to_user_id`),
  KEY `idx_tasks_status` (`status`),
  KEY `idx_tasks_due_date` (`due_date`),
  KEY `idx_tasks_status_due` (`status`,`due_date`),
  CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tasks` (`id`, `client_id`, `assigned_to_user_id`, `title`, `description`, `status`, `priority`, `category`, `due_date`, `created_at`, `updated_at`, `deleted_at`) VALUES ('1', '2', '3', 'gst', 'new gst month june 2025', 'completed', 'medium', 'TDS', '2026-07-05', '2026-07-04 22:12:05', '2026-07-05 11:25:06', NULL);
INSERT INTO `tasks` (`id`, `client_id`, `assigned_to_user_id`, `title`, `description`, `status`, `priority`, `category`, `due_date`, `created_at`, `updated_at`, `deleted_at`) VALUES ('3', '1', '4', 'gst tr', 'new', 'completed', 'medium', 'ROC', '2026-07-11', '2026-07-05 20:01:56', '2026-07-05 20:18:26', NULL);

DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE `user_roles` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES ('1', '1');
INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES ('2', '2');
INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES ('3', '3');
INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES ('4', '3');
INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES ('5', '3');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `pin_hash` varchar(255) NOT NULL,
  `role` enum('super_admin','admin_manager','staff') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `api_token` varchar(255) DEFAULT NULL,
  `login_failures` int(11) DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `pin_hash`, `role`, `created_at`, `updated_at`, `deleted_at`, `api_token`, `login_failures`, `locked_until`) VALUES ('1', 'Super Admin', 'test', '$2y$10$gVXLDAnyatPtcC.xmCC/TOhZfAtKRvRWxMK.6fRp2ATu22N3/nE4W', '$2a$10$ZG0gPL79cC2xxcTl5iQrleKsBJMhlIDbSHFa2SpaspnwNmnFzIRKe', 'super_admin', '2026-07-04 20:18:30', '2026-07-05 18:17:04', NULL, 'ecfba0d1b348452cfe1f4857b6568dde8f5c55981b11b3348bf4ceadba1d1995', '0', NULL);
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `pin_hash`, `role`, `created_at`, `updated_at`, `deleted_at`, `api_token`, `login_failures`, `locked_until`) VALUES ('2', 'Admin Manager', 'manager@example.test', '$2y$10$gVXLDAnyatPtcC.xmCC/TOhZfAtKRvRWxMK.6fRp2ATu22N3/nE4W', '$2a$10$oh.8G7U1p.ZDnhQsREoN9O6evqFeWuobNW.bPlO09ks.fKrD8DMFy', 'admin_manager', '2026-07-04 20:18:30', '2026-07-05 18:17:04', NULL, 'f872625be22985007f807c21176df43b5c867fea26b02d044c672c9c60cce94c', '0', NULL);
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `pin_hash`, `role`, `created_at`, `updated_at`, `deleted_at`, `api_token`, `login_failures`, `locked_until`) VALUES ('3', 'Staff 1', 'staff1@example.test', '$2y$10$gVXLDAnyatPtcC.xmCC/TOhZfAtKRvRWxMK.6fRp2ATu22N3/nE4W', '$2a$10$ZG0gPL79cC2xxcTl5iQrleKsBJMhlIDbSHFa2SpaspnwNmnFzIRKe', 'staff', '2026-07-04 20:18:30', '2026-07-05 18:17:04', NULL, 'b14f590a60bcac722658ff8db4dea7e77bd91b3257e1a6ce4c8ea27ec22b4c6c', '0', NULL);
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `pin_hash`, `role`, `created_at`, `updated_at`, `deleted_at`, `api_token`, `login_failures`, `locked_until`) VALUES ('4', 'Staff 2', 'staff2@example.test', '$2y$10$gVXLDAnyatPtcC.xmCC/TOhZfAtKRvRWxMK.6fRp2ATu22N3/nE4W', '$2a$10$fG4Xfdr3vHVIuF35b0nofeFIv1KloqpFvsoGxyFVc3L69h8xKyaMC', 'staff', '2026-07-04 20:18:30', '2026-07-05 18:17:04', NULL, '60fc899b76eabab5673631631e13578640e4e154bd7fc9b22506d450a3473172', '0', NULL);
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `pin_hash`, `role`, `created_at`, `updated_at`, `deleted_at`, `api_token`, `login_failures`, `locked_until`) VALUES ('5', 'Staff 3', 'staff3@example.test', '$2y$10$gVXLDAnyatPtcC.xmCC/TOhZfAtKRvRWxMK.6fRp2ATu22N3/nE4W', '$2a$10$FhTIUOrgafECh/Om47Hnm.fV0WrsAULEYjk4PBGJPpKNDd1l/hE/S', 'staff', '2026-07-04 20:18:30', '2026-07-05 18:17:04', NULL, '4b7283a43579ed5204992dffb444caa7c918adc404522a3b8c873c6e3eabf44e', '0', NULL);

DROP TABLE IF EXISTS `whatsapp_logs`;
CREATE TABLE `whatsapp_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `sender` varchar(50) NOT NULL DEFAULT 'staff',
  `message` text NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'sent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `whatsapp_logs_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `work_logs`;
CREATE TABLE `work_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `hours_spent` decimal(5,2) NOT NULL,
  `log_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `work_logs_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `work_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `work_logs` (`id`, `task_id`, `user_id`, `description`, `hours_spent`, `log_date`, `created_at`) VALUES ('1', '1', '1', 'all done', '3.00', '2026-07-04', '2026-07-04 22:12:42');
INSERT INTO `work_logs` (`id`, `task_id`, `user_id`, `description`, `hours_spent`, `log_date`, `created_at`) VALUES ('2', '3', '4', 'new', '12.00', '2026-07-05', '2026-07-05 20:03:02');

SET FOREIGN_KEY_CHECKS=1;
