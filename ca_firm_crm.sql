-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 07, 2026 at 09:42 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ca_firm_crm`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounting_expenses`
--

CREATE TABLE `accounting_expenses` (
  `id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` date NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'approved',
  `approved_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accounting_expenses`
--

INSERT INTO `accounting_expenses` (`id`, `category`, `amount`, `date`, `description`, `created_at`, `status`, `approved_by`) VALUES
(3, 'Office Rent', 15000.00, '2026-07-07', 'june 2026', '2026-07-07 10:34:45', 'approved', 1);

-- --------------------------------------------------------

--
-- Table structure for table `accounting_invoices`
--

CREATE TABLE `accounting_invoices` (
  `id` int(11) NOT NULL,
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
  `invoice_design` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accounting_invoices`
--

INSERT INTO `accounting_invoices` (`id`, `client_id`, `invoice_number`, `amount`, `status`, `issue_date`, `due_date`, `description`, `created_at`, `updated_at`, `cgst`, `sgst`, `igst`, `tds_amount`, `net_amount`, `invoice_design`) VALUES
(4, 3, 'INV-2026-09', 3000.00, 'paid', '2026-07-07', '2026-08-06', '', '2026-07-07 10:11:46', '2026-07-07 12:24:57', 270.00, 270.00, 0.00, 0.00, 3540.00, ''),
(5, 3, 'INV-2026-10', 3000.00, 'paid', '2026-07-07', '2026-08-06', '', '2026-07-07 10:33:22', '2026-07-07 12:24:50', 270.00, 270.00, 0.00, 0.00, 3540.00, ''),
(6, 3, 'INV-2026-11', 15000.00, 'paid', '2026-07-07', '2026-08-06', '', '2026-07-07 12:25:32', '2026-07-07 12:25:40', 0.00, 0.00, 0.00, 0.00, 15000.00, '');

-- --------------------------------------------------------

--
-- Table structure for table `accounting_payments`
--

CREATE TABLE `accounting_payments` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accounting_payments`
--

INSERT INTO `accounting_payments` (`id`, `invoice_id`, `amount`, `payment_date`, `payment_method`, `created_at`) VALUES
(2, 5, 3540.00, '2026-07-07', 'Bank Transfer', '2026-07-07 12:24:50'),
(3, 4, 3540.00, '2026-07-07', 'Bank Transfer', '2026-07-07 12:24:57'),
(5, 6, 15000.00, '2026-07-07', 'Bank Transfer', '2026-07-07 12:25:40');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, NULL, 'test_action', 'Running verification tests', '127.0.0.1', '2026-07-05 12:41:52'),
(2, NULL, 'test_action', 'Running verification tests', '127.0.0.1', '2026-07-05 12:44:26'),
(3, 1, 'update_employee', 'Updated HR details for user ID 3', '::1', '2026-07-05 14:29:20'),
(4, 1, 'add_task', 'Created task: gst tr', '::1', '2026-07-05 14:31:56'),
(5, 4, 'log_work', 'Logged 12 hours for task ID 3', '::1', '2026-07-05 14:33:02'),
(6, 4, 'update_task_status', 'Updated task ID 3 status to completed', '::1', '2026-07-05 14:33:06'),
(7, 4, 'update_task_status', 'Updated task ID 3 status to in_progress', '::1', '2026-07-05 14:33:22'),
(8, 4, 'clock_in', 'Employee clocked in at 16:33:48', '::1', '2026-07-05 14:33:48'),
(9, 4, 'clock_out', 'Employee clocked out at 16:33:54', '::1', '2026-07-05 14:33:54'),
(10, 3, 'clock_in', 'Employee clocked in at 16:35:16', '::1', '2026-07-05 14:35:16'),
(11, 3, 'clock_out', 'Employee clocked out at 16:35:19', '::1', '2026-07-05 14:35:19'),
(12, 4, 'request_leave', 'Requested sick leave from 2026-07-06 to 2026-07-06', '::1', '2026-07-05 14:37:59'),
(13, 1, 'review_leave', 'Leave request ID 1 reviewed as: approved', '::1', '2026-07-05 14:38:24'),
(14, 1, 'document_request', 'Requested document \'send new document\' from client 1', '::1', '2026-07-05 14:40:39'),
(15, 1, 'edit_task', 'Updated task ID 3', '::1', '2026-07-05 14:48:26'),
(16, 1, 'add_invoice', 'Generated invoice INV-2026-09', '::1', '2026-07-05 14:49:28'),
(17, 1, 'record_payment', 'Recorded collection of ₹15000 for Invoice ID 1', '::1', '2026-07-05 14:49:47'),
(18, 1, 'add_expense', 'Logged firm expense of ₹2000 for Utilities', '::1', '2026-07-05 14:50:36'),
(19, 1, 'add_expense', 'Logged firm expense of ₹15000 for Salaries', '::1', '2026-07-05 14:51:12'),
(20, 1, 'post_announcement', 'Posted announcement: public holiday', '::1', '2026-07-05 15:58:40'),
(21, 1, 'update_employee', 'Updated HR details and salary structure for user ID 1', '::1', '2026-07-05 16:32:00'),
(22, 1, 'update_employee', 'Updated HR details and salary structure for user ID 3', '::1', '2026-07-05 16:32:48'),
(23, 1, 'update_employee', 'Updated HR details and salary structure for user ID 4', '::1', '2026-07-05 16:33:30'),
(24, 1, 'update_employee', 'Updated HR details and salary structure for user ID 5', '::1', '2026-07-05 16:34:19'),
(25, 1, 'generate_salary_slip', 'Generated salary slip for employee ID 4 for month 2026-07', '::1', '2026-07-05 16:41:51'),
(26, 1, 'add_compliance', 'Created compliance task \'gst\' for client ID 2', '::1', '2026-07-06 09:26:59'),
(27, 1, 'generate_token', 'Generated portal token for client ID 2', '::1', '2026-07-06 09:28:09'),
(28, 1, 'add_compliance', 'Created compliance task \'gst\' for client ID 2', '::1', '2026-07-06 17:28:08'),
(29, 1, 'clock_in', 'Employee clocked in at 19:59:22', '::1', '2026-07-06 17:59:22'),
(30, 1, 'clock_out', 'Employee clocked out at 19:59:25', '::1', '2026-07-06 17:59:25'),
(31, 1, 'clock_in', 'Employee clocked in at 11:10:55', '::1', '2026-07-07 09:10:55'),
(32, 1, 'delete_client', 'Deleted client ID 1', '::1', '2026-07-07 09:11:23'),
(33, 1, 'delete_client', 'Deleted client ID 2', '::1', '2026-07-07 09:11:26'),
(34, 1, 'add_client', 'Created client raviraj industry', '::1', '2026-07-07 09:14:47'),
(35, 1, 'add_lead', 'Created lead: rahul', '::1', '2026-07-07 09:19:17'),
(36, 1, 'add_task', 'Created task: GST', '::1', '2026-07-07 09:20:39'),
(37, 1, 'add_compliance', 'Created compliance task \'tds\' for client ID 3', '::1', '2026-07-07 09:21:34'),
(38, 3, 'log_work', 'Logged 5 hours for task ID 4', '::1', '2026-07-07 09:23:43'),
(39, 3, 'update_task_status', 'Updated task ID 4 status to completed', '::1', '2026-07-07 09:23:52'),
(40, 3, 'record_filing', 'Recorded filing for compliance ID 12. Ack: ACN12589', '::1', '2026-07-07 09:24:21'),
(41, 3, 'clock_in', 'Employee clocked in at 11:24:49', '::1', '2026-07-07 09:24:49'),
(42, 1, 'assign_shift', 'Assigned shift \'General Shift (09:00 AM - 06:00 PM)\' for user ID 3 on 2026-07-08', '::1', '2026-07-07 10:09:40'),
(43, 1, 'add_invoice', 'Generated invoice INV-2026-09', '::1', '2026-07-07 10:11:46'),
(44, 1, 'generate_token', 'Generated portal token for client ID 3', '::1', '2026-07-07 10:15:04'),
(45, 1, 'generate_token', 'Generated portal token for client ID 3', '::1', '2026-07-07 10:20:43'),
(46, 1, 'generate_token', 'Generated portal token for client ID 3', '::1', '2026-07-07 10:21:39'),
(47, 1, 'generate_token', 'Generated portal token for client ID 3', '::1', '2026-07-07 10:22:13'),
(48, 1, 'add_invoice', 'Generated invoice INV-2026-10', '::1', '2026-07-07 10:33:22'),
(49, 1, 'add_expense', 'Logged firm expense of ₹15000 for Office Rent (Status: approved)', '::1', '2026-07-07 10:34:45'),
(50, 1, 'record_payment', 'Recorded collection of ₹3540 for Invoice ID 5', '::1', '2026-07-07 12:24:50'),
(51, 1, 'record_payment', 'Recorded collection of ₹3540 for Invoice ID 4', '::1', '2026-07-07 12:24:57'),
(52, 1, 'record_payment', 'Recorded collection of ₹3540 for Invoice ID 4', '::1', '2026-07-07 12:25:03'),
(53, 1, 'add_invoice', 'Generated invoice INV-2026-11', '::1', '2026-07-07 12:25:32'),
(54, 1, 'record_payment', 'Recorded collection of ₹15000 for Invoice ID 6', '::1', '2026-07-07 12:25:40'),
(55, 1, 'record_payment', 'Recorded collection of ₹15000 for Invoice ID 6', '::1', '2026-07-07 12:25:46'),
(56, 1, 'add_client', 'Created client kiran industry', '::1', '2026-07-07 12:26:24'),
(57, 1, 'document_request', 'Requested document \'bank statement\' from client 4', '::1', '2026-07-07 12:27:13'),
(58, 1, 'add_task', 'Created task: ITR', '::1', '2026-07-07 12:31:56'),
(59, 1, 'assign_shift', 'Assigned shift \'Night Shift (10:00 PM - 06:00 AM)\' for user ID 1 on 2026-07-07', '::1', '2026-07-07 12:32:36'),
(60, 1, 'add_compliance', 'Created compliance task \'TDS\' for client ID 4', '::1', '2026-07-07 12:35:46'),
(61, 1, 'add_task', 'Created task: TDS', '::1', '2026-07-07 12:45:07'),
(62, 1, 'add_compliance', 'Created compliance task \'rco\' for client ID 3', '::1', '2026-07-07 12:46:22'),
(63, 1, 'request_leave', 'Requested sick leave from 2026-07-11 to 2026-07-12', '::1', '2026-07-07 13:04:39'),
(64, 4, 'log_work', 'Logged 5 hours for task ID 5', '::1', '2026-07-07 15:42:47'),
(65, 4, 'update_task_status', 'Updated task ID 5 status to completed', '::1', '2026-07-07 15:42:50'),
(66, 4, 'record_filing', 'Recorded filing for compliance ID 14. Ack: ARN2342', '::1', '2026-07-07 15:43:17'),
(67, 4, 'record_filing', 'Recorded filing for compliance ID 13. Ack: ARN23429', '::1', '2026-07-07 15:43:30'),
(68, 1, 'clock_out', 'Employee clocked out at 17:45:27', '::1', '2026-07-07 15:45:27'),
(69, 1, 'add_service', 'Added new service: GST Audit Filings', '::1', '2026-07-07 15:57:02'),
(70, 1, 'add_compliance', 'Created compliance task \'gst\' for client ID 3', '::1', '2026-07-07 19:24:44'),
(71, 1, 'delete_compliance', 'Deleted compliance ID 15', '::1', '2026-07-07 19:25:09'),
(72, 1, 'add_compliance', 'Created compliance task \'gst2\' for client ID 3', '::1', '2026-07-07 19:25:38'),
(73, 1, 'generate_token', 'Generated portal token for client ID 4', '::1', '2026-07-07 19:31:56'),
(74, 3, 'clock_out', 'Employee clocked out at 21:35:10', '::1', '2026-07-07 19:35:10');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `created_by`, `created_at`) VALUES
(1, 'public holiday', '15 aug 2026', 1, '2026-07-05 15:58:40');

-- --------------------------------------------------------

--
-- Table structure for table `api_tokens`
--

CREATE TABLE `api_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `status` enum('present','absent','on_leave') NOT NULL DEFAULT 'present'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `date`, `check_in`, `check_out`, `status`) VALUES
(34, 1, '2026-07-07', '11:10:55', '17:45:27', 'present'),
(35, 3, '2026-07-07', '11:24:49', '21:35:10', 'present');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `record_id` int(11) NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `automation_queue`
--

CREATE TABLE `automation_queue` (
  `id` int(11) NOT NULL,
  `event_type` varchar(100) NOT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `scheduled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sent_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `automation_queue`
--

INSERT INTO `automation_queue` (`id`, `event_type`, `recipient_email`, `subject`, `body`, `status`, `scheduled_at`, `sent_at`) VALUES
(1, 'compliance_created', 'rajratn@gmail.com', 'New Compliance Scheduled: GST Filing Q1 2026', 'Dear rajratn inductry,\n\nA new compliance task \'GST Filing Q1 2026\' (Category: GST Return) has been scheduled. It is due on 2026-06-30.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-06 09:26:21', '2026-07-06 09:26:21'),
(2, 'compliance_overdue', 'rajratn@gmail.com', 'OVERDUE Tax Compliance Action Required: GST Filing Q1 2026', 'Dear rajratn inductry,\n\nThis is an urgent notice that your compliance task \'GST Filing Q1 2026\' (Due: 2026-06-30) is OVERDUE. Please log in to your portal immediately to review and submit your response.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-06 09:26:21', '2026-07-06 09:26:21'),
(3, 'compliance_created', 'ramraj@email.com', 'New Compliance Scheduled: gst', 'Dear ramraj,\n\nA new compliance task \'gst\' (Category: GST Return) has been scheduled. It is due on 2026-07-06.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-06 09:26:59', '2026-07-06 19:15:46'),
(4, 'compliance_created', 'ramraj@email.com', 'New Compliance Scheduled: gst', 'Dear ramraj,\n\nA new compliance task \'gst\' (Category: TDS Return) has been scheduled. It is due on 2026-07-07.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-06 17:28:08', '2026-07-06 19:15:46'),
(5, 'compliance_created', 'rajratn@gmail.com', 'New Compliance Scheduled: GST Return Filing - Jul 2026', 'Dear rajratn inductry,\n\nA new compliance task \'GST Return Filing - Jul 2026\' (Category: GST Return) has been scheduled. It is due on 2026-07-20.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-06 19:15:46', '2026-07-06 19:15:46'),
(6, 'compliance_created', 'rajratn@gmail.com', 'New Compliance Scheduled: TDS Return Filing - Q3 2026', 'Dear rajratn inductry,\n\nA new compliance task \'TDS Return Filing - Q3 2026\' (Category: TDS Return) has been scheduled. It is due on 2026-07-31.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-06 19:15:46', '2026-07-06 19:15:46'),
(7, 'compliance_created', 'rajratn@gmail.com', 'New Compliance Scheduled: Annual ROC Filing - 2026', 'Dear rajratn inductry,\n\nA new compliance task \'Annual ROC Filing - 2026\' (Category: ROC) has been scheduled. It is due on 2026-11-30.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-06 19:15:46', '2026-07-06 19:15:46'),
(8, 'compliance_created', 'rajratn@gmail.com', 'New Compliance Scheduled: Income Tax Return (ITR) Filing - AY 2026-2027', 'Dear rajratn inductry,\n\nA new compliance task \'Income Tax Return (ITR) Filing - AY 2026-2027\' (Category: ITR) has been scheduled. It is due on 2026-07-31.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-06 19:15:46', '2026-07-06 19:15:46'),
(9, 'compliance_created', 'ramraj@email.com', 'New Compliance Scheduled: GST Return Filing - Jul 2026', 'Dear ramraj,\n\nA new compliance task \'GST Return Filing - Jul 2026\' (Category: GST Return) has been scheduled. It is due on 2026-07-20.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-06 19:15:46', '2026-07-06 19:15:46'),
(10, 'compliance_created', 'ramraj@email.com', 'New Compliance Scheduled: TDS Return Filing - Q3 2026', 'Dear ramraj,\n\nA new compliance task \'TDS Return Filing - Q3 2026\' (Category: TDS Return) has been scheduled. It is due on 2026-07-31.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-06 19:15:46', '2026-07-06 19:15:46'),
(11, 'compliance_created', 'ramraj@email.com', 'New Compliance Scheduled: Annual ROC Filing - 2026', 'Dear ramraj,\n\nA new compliance task \'Annual ROC Filing - 2026\' (Category: ROC) has been scheduled. It is due on 2026-11-30.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-06 19:15:46', '2026-07-06 19:15:46'),
(12, 'compliance_created', 'ramraj@email.com', 'New Compliance Scheduled: Income Tax Return (ITR) Filing - AY 2026-2027', 'Dear ramraj,\n\nA new compliance task \'Income Tax Return (ITR) Filing - AY 2026-2027\' (Category: ITR) has been scheduled. It is due on 2026-07-31.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-06 19:15:46', '2026-07-06 19:15:46'),
(13, 'compliance_reminder', 'ramraj@email.com', 'Tax Return Compliance Reminder: gst', 'Dear ramraj,\n\nThis is an automated reminder that your return gst is due on 2026-07-06. Please ensure all files and comments are uploaded to the portal.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-06 19:15:46', '2026-07-06 19:15:46'),
(14, 'compliance_reminder', 'ramraj@email.com', 'Tax Return Compliance Reminder: gst', 'Dear ramraj,\n\nThis is an automated reminder that your return gst is due on 2026-07-07. Please ensure all files and comments are uploaded to the portal.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-06 19:15:46', '2026-07-06 19:15:46'),
(15, 'compliance_overdue', 'ramraj@email.com', 'OVERDUE Tax Compliance Action Required: gst', 'Dear ramraj,\n\nThis is an urgent notice that your compliance task \'gst\' (Due: 2026-07-06) is OVERDUE. Please log in to your portal immediately to review and submit your response.\n\nBest Regards,\nCA CRM Team.', 'sent', '2026-07-06 19:15:46', '2026-07-06 19:15:46'),
(16, 'compliance_created', 'avinashsalunkehoh@gmail.com', 'New Compliance Scheduled: tds', 'Dear raviraj industry,\n\nA new compliance task \'tds\' (Category: TDS Return) has been scheduled. It is due on 2026-07-08.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'pending', '2026-07-07 09:21:34', NULL),
(17, 'compliance_created', 'kiran@gmail.com', 'New Compliance Scheduled: TDS', 'Dear kiran industry,\n\nA new compliance task \'TDS\' (Category: TDS Return) has been scheduled. It is due on 2026-07-07.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'pending', '2026-07-07 12:35:46', NULL),
(18, 'compliance_created', 'avinashsalunkehoh@gmail.com', 'New Compliance Scheduled: rco', 'Dear raviraj industry,\n\nA new compliance task \'rco\' (Category: ROC) has been scheduled. It is due on 2026-07-07.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'pending', '2026-07-07 12:46:22', NULL),
(19, 'compliance_created', 'avinashsalunkehoh@gmail.com', 'New Compliance Scheduled: gst', 'Dear raviraj industry,\n\nA new compliance task \'gst\' (Category: GST Return) has been scheduled. It is due on 2026-07-08.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'pending', '2026-07-07 19:24:44', NULL),
(20, 'compliance_created', 'avinashsalunkehoh@gmail.com', 'New Compliance Scheduled: gst2', 'Dear raviraj industry,\n\nA new compliance task \'gst2\' (Category: GST Return) has been scheduled. It is due on 2026-07-08.\n\nPlease log in to your portal to review and provide your response/comments.\n\nBest Regards,\nCA CRM Team.', 'pending', '2026-07-07 19:25:38', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `portal_token` varchar(255) DEFAULT NULL,
  `portal_token_expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `encrypted_tax_data` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `name`, `email`, `phone`, `portal_token`, `portal_token_expires_at`, `created_at`, `updated_at`, `deleted_at`, `encrypted_tax_data`) VALUES
(3, 'raviraj industry', 'avinashsalunkehoh@gmail.com', '8830666253', '0101387134099bfa131fd3bd24eca1ec253efb9d1f268117ac86c24222b9f9d5', '2026-07-14 06:52:13', '2026-07-07 09:14:47', '2026-07-07 10:22:13', NULL, NULL),
(4, 'kiran industry', 'kiran@gmail.com', '6565656565', 'acd36f7f9bb15aa658009b434a4422a1eb27bc1de1d6ba7815023d82613b9b62', '2026-07-14 16:01:56', '2026-07-07 12:26:24', '2026-07-07 19:31:56', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `client_compliance_configs`
--

CREATE TABLE `client_compliance_configs` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `auto_gst` tinyint(1) DEFAULT 0,
  `auto_tds` tinyint(1) DEFAULT 0,
  `auto_roc` tinyint(1) DEFAULT 0,
  `auto_itr` tinyint(1) DEFAULT 0,
  `remind_email` tinyint(1) DEFAULT 1,
  `remind_sms` tinyint(1) DEFAULT 0,
  `escalation_days` int(11) DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_notes`
--

CREATE TABLE `client_notes` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_timeline`
--

CREATE TABLE `client_timeline` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `event_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `client_timeline`
--

INSERT INTO `client_timeline` (`id`, `client_id`, `user_id`, `event_type`, `description`, `created_at`) VALUES
(49, 3, NULL, 'client_created', 'Client account created for raviraj industry.', '2026-07-07 09:14:47'),
(50, 3, NULL, 'task_created', 'Task \'GST\' created and assigned.', '2026-07-07 09:20:39'),
(51, 3, NULL, 'compliance_created', 'Compliance task \'tds\' added (Due: 2026-07-08).', '2026-07-07 09:21:34'),
(52, 3, 3, 'work_logged', 'Logged 5 hours on task \'GST\'.', '2026-07-07 09:23:43'),
(53, 3, NULL, 'task_status_changed', 'Task \'GST\' status updated to completed.', '2026-07-07 09:23:52'),
(54, 3, NULL, 'compliance_filed', 'Compliance return filed: \'tds\' (Ack: ACN12589).', '2026-07-07 09:24:21'),
(55, 3, NULL, 'invoice_created', 'Invoice #INV-2026-09 for amount 3000 (Net: 3540) created.', '2026-07-07 10:11:46'),
(56, 3, NULL, 'token_generated', 'Secure portal access token generated (valid for 7 days).', '2026-07-07 10:15:04'),
(57, 3, NULL, 'token_generated', 'Secure portal access token generated (valid for 7 days).', '2026-07-07 10:20:43'),
(58, 3, NULL, 'token_generated', 'Secure portal access token generated (valid for 7 days).', '2026-07-07 10:21:39'),
(59, 3, NULL, 'token_generated', 'Secure portal access token generated (valid for 7 days).', '2026-07-07 10:22:13'),
(60, 3, NULL, 'invoice_created', 'Invoice #INV-2026-10 for amount 3000 (Net: 3540) created.', '2026-07-07 10:33:22'),
(61, 3, NULL, 'payment_recorded', 'Payment of 3540 received for Invoice #INV-2026-10.', '2026-07-07 12:24:50'),
(62, 3, NULL, 'payment_recorded', 'Payment of 3540 received for Invoice #INV-2026-09.', '2026-07-07 12:24:57'),
(63, 3, NULL, 'payment_recorded', 'Payment of 3540 received for Invoice #INV-2026-09.', '2026-07-07 12:25:03'),
(64, 3, NULL, 'invoice_created', 'Invoice #INV-2026-11 for amount 15000 (Net: 15000) created.', '2026-07-07 12:25:32'),
(65, 3, NULL, 'payment_recorded', 'Payment of 15000 received for Invoice #INV-2026-11.', '2026-07-07 12:25:40'),
(66, 3, NULL, 'payment_recorded', 'Payment of 15000 received for Invoice #INV-2026-11.', '2026-07-07 12:25:46'),
(67, 4, NULL, 'client_created', 'Client account created for kiran industry.', '2026-07-07 12:26:24'),
(68, 4, NULL, 'document_requested', 'Document requested: \'bank statement\'.', '2026-07-07 12:27:13'),
(69, 4, NULL, 'task_created', 'Task \'ITR\' created and assigned.', '2026-07-07 12:31:56'),
(70, 4, NULL, 'compliance_created', 'Compliance task \'TDS\' added (Due: 2026-07-07).', '2026-07-07 12:35:46'),
(71, 4, NULL, 'task_created', 'Task \'TDS\' created and assigned.', '2026-07-07 12:45:07'),
(72, 3, NULL, 'compliance_created', 'Compliance task \'rco\' added (Due: 2026-07-07).', '2026-07-07 12:46:22'),
(73, 4, 4, 'work_logged', 'Logged 5 hours on task \'ITR\'.', '2026-07-07 15:42:47'),
(74, 4, NULL, 'task_status_changed', 'Task \'ITR\' status updated to completed.', '2026-07-07 15:42:50'),
(75, 3, NULL, 'compliance_filed', 'Compliance return filed: \'rco\' (Ack: ARN2342).', '2026-07-07 15:43:17'),
(76, 4, NULL, 'compliance_filed', 'Compliance return filed: \'TDS\' (Ack: ARN23429).', '2026-07-07 15:43:30'),
(77, 3, NULL, 'compliance_created', 'Compliance task \'gst\' added (Due: 2026-07-08).', '2026-07-07 19:24:44'),
(78, 3, NULL, 'compliance_deleted', 'Compliance return \'gst\' deleted.', '2026-07-07 19:25:09'),
(79, 3, NULL, 'compliance_created', 'Compliance task \'gst2\' added (Due: 2026-07-08).', '2026-07-07 19:25:38'),
(80, 4, NULL, 'token_generated', 'Secure portal access token generated (valid for 7 days).', '2026-07-07 19:31:56');

-- --------------------------------------------------------

--
-- Table structure for table `compliances`
--

CREATE TABLE `compliances` (
  `id` int(11) NOT NULL,
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
  `escalated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `compliances`
--

INSERT INTO `compliances` (`id`, `client_id`, `title`, `category`, `due_date`, `filing_date`, `acknowledgement_number`, `status`, `notes`, `created_at`, `updated_at`, `client_response`, `client_responded_at`, `email_reminders_sent`, `sms_reminders_sent`, `escalated`, `escalated_at`) VALUES
(12, 3, 'tds', 'TDS Return', '2026-07-08', '2026-07-07', 'ACN12589', 'filed', 'tested', '2026-07-07 09:21:34', '2026-07-07 09:24:21', NULL, NULL, 0, 0, 0, NULL),
(13, 4, 'TDS', 'TDS Return', '2026-07-07', '2026-07-07', 'ARN23429', 'filed', 'tested', '2026-07-07 12:35:46', '2026-07-07 15:43:30', NULL, NULL, 0, 0, 0, NULL),
(14, 3, 'rco', 'ROC', '2026-07-07', '2026-07-07', 'ARN2342', 'filed', 'done', '2026-07-07 12:46:22', '2026-07-07 15:43:17', NULL, NULL, 0, 0, 0, NULL),
(16, 3, 'gst2', 'GST Return', '2026-07-08', NULL, NULL, 'pending', 'temp', '2026-07-07 19:25:38', '2026-07-07 19:25:38', NULL, NULL, 0, 0, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
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
  `sharing_scope` enum('internal_only','client_shared') DEFAULT 'client_shared'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_requests`
--

CREATE TABLE `document_requests` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','uploaded','reviewed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_requests`
--

INSERT INTO `document_requests` (`id`, `client_id`, `title`, `description`, `status`, `created_at`, `updated_at`) VALUES
(3, 4, 'bank statement', 'please provide the june 2026 doc', 'pending', '2026-07-07 12:27:13', '2026-07-07 12:27:13');

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'sent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_templates`
--

CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_templates`
--

INSERT INTO `email_templates` (`id`, `template_name`, `subject`, `body`, `updated_at`) VALUES
(1, 'compliance_due_reminder', 'Statutory Compliance Due Date Reminder', 'Dear Client,\n\nThis is to remind you that your statutory filing for {filing_title} is due on {due_date}.\n\nPlease upload the required documents as soon as possible via the client portal.\n\nBest Regards,\nCA Associates Team', '2026-07-07 04:09:04'),
(2, 'invoice_outstanding', 'Invoice Outstanding Notice - CA Associates', 'Dear Client,\n\nInvoice {invoice_number} for the amount of ₹{amount} issued on {issue_date} remains unpaid.\n\nPlease clear the balance of ₹{net_amount} by the due date of {due_date}.\n\nBest Regards,\nCA Associates Finance Team', '2026-07-07 04:09:04'),
(3, 'client_portal_welcome', 'URGENT: Welcome to CA Firm Client Portal', 'Dear {client_name},\n\nYour secure client portal session has been set up.\n\nYou can access your portal vault at any time using your personal access token: {portal_token}\n\nBest Regards,\nCA Associates Support Team', '2026-07-07 04:12:48');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
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
  `shift` varchar(50) DEFAULT 'General'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `user_id`, `department`, `designation`, `joining_date`, `salary`, `status`, `basic`, `hra`, `conveyance`, `allowance`, `pf`, `pt`, `tds`, `shift`) VALUES
(1, 3, 'employee1', 'STC', '2026-07-05', 15000.00, 'active', 10000.00, 200.00, 200.00, 200.00, 2000.00, 200.00, 200.00, 'General'),
(2, 2, 'Taxation', 'Tax Lead Specialist', '2026-01-15', 60000.00, 'active', 35000.00, 15000.00, 3000.00, 7000.00, 1800.00, 200.00, 3000.00, 'General'),
(3, 1, 'SA', 'head', '2026-07-05', 50000.00, 'active', 40000.00, 10000.00, 200.00, 200.00, 2500.00, 2000.00, 200.00, 'General'),
(4, 4, 'st2', 'STC', '2026-07-05', 20000.00, 'active', 15000.00, 2000.00, 200.00, 200.00, 2000.00, 200.00, 200.00, 'General'),
(5, 5, 'SA', 'STC', '2026-07-05', 15000.00, 'active', 10000.00, 2000.00, 200.00, 200.00, 2000.00, 200.00, 200.00, 'General');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `invoice_number` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('unpaid','paid','overdue','cancelled') DEFAULT 'unpaid',
  `due_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ip_restrictions`
--

CREATE TABLE `ip_restrictions` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

CREATE TABLE `leads` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `source` varchar(100) DEFAULT 'Direct',
  `status` enum('new','contacted','qualified','disqualified') NOT NULL DEFAULT 'new',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leads`
--

INSERT INTO `leads` (`id`, `name`, `email`, `phone`, `source`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'rahul', 'rahul@gmail.com', '9595959595', 'Referral', 'new', 'gst file', '2026-07-07 09:19:17', '2026-07-07 09:19:17');

-- --------------------------------------------------------

--
-- Table structure for table `leaves`
--

CREATE TABLE `leaves` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `leave_type` enum('sick','casual','earned','unpaid') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `leave_type` enum('casual','sick','earned') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `comments` text DEFAULT NULL,
  `workflow_step` varchar(100) DEFAULT 'approved_by_admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`id`, `user_id`, `leave_type`, `start_date`, `end_date`, `status`, `reason`, `approved_by`, `created_at`, `comments`, `workflow_step`) VALUES
(1, 4, 'sick', '2026-07-06', '2026-07-06', 'approved', 'fever', 1, '2026-07-05 14:37:59', NULL, 'approved_by_admin'),
(2, 1, 'sick', '2026-07-11', '2026-07-12', 'pending', 'breee', NULL, '2026-07-07 13:04:39', NULL, 'approved_by_admin');

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email_attempted` varchar(255) NOT NULL,
  `ip_address` varchar(50) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `status` enum('success','failed') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`id`, `user_id`, `email_attempted`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES
(1, 1, 'test@example.com', '127.0.0.1', 'Unknown', 'success', '2026-07-05 12:41:52'),
(2, 1, 'test@example.com', '127.0.0.1', 'Unknown', 'success', '2026-07-05 12:44:26'),
(3, 6, 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 12:58:41'),
(4, 6, 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 12:58:41'),
(5, 6, 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 12:58:41'),
(6, 6, 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 12:58:41'),
(7, 6, 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 12:58:41'),
(8, NULL, 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 12:58:41'),
(9, 7, 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 12:59:14'),
(10, 7, 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 12:59:14'),
(11, 7, 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 12:59:14'),
(12, 7, 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 12:59:14'),
(13, 7, 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 12:59:14'),
(14, 8, 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 12:59:32'),
(15, 8, 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 12:59:32'),
(16, 8, 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 12:59:32'),
(17, 8, 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 12:59:32'),
(18, 8, 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 12:59:32'),
(19, 9, 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 12:59:54'),
(20, 9, 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 12:59:54'),
(21, 9, 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 12:59:54'),
(22, 9, 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 12:59:54'),
(23, 9, 'lockout_test@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-05 12:59:54'),
(24, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 14:28:22'),
(25, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 14:32:14'),
(26, 4, 'staff2@example.test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 14:32:45'),
(27, 3, 'staff1@example.test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 14:34:42'),
(28, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 14:35:38'),
(29, 4, 'staff2@example.test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 14:37:25'),
(30, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 14:38:15'),
(31, 4, 'staff2@example.test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 14:38:49'),
(32, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 14:39:09'),
(33, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 15:20:48'),
(34, 1, 'test', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 15:45:30'),
(35, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 15:49:05'),
(36, 1, 'test', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 15:49:48'),
(37, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 16:30:30'),
(38, 4, 'staff2@example.test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 16:38:36'),
(39, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 16:41:30'),
(40, 4, 'staff2@example.test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-05 16:42:24'),
(41, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-06 08:56:12'),
(42, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-06 09:26:37'),
(43, NULL, 'ramraj@email.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'failed', '2026-07-06 09:27:23'),
(44, NULL, 'ramraj@email.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'failed', '2026-07-06 09:27:35'),
(45, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-06 09:27:59'),
(46, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-06 17:50:15'),
(47, 3, 'staff1@example.test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-06 18:49:13'),
(48, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-06 18:50:42'),
(49, NULL, 'admin@example.com', '127.0.0.1', 'Unknown', 'failed', '2026-07-07 04:31:41'),
(50, 1, 'test', '127.0.0.1', 'Unknown', 'success', '2026-07-07 04:31:41'),
(51, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-07 09:10:07'),
(52, 3, 'staff1@example.test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'success', '2026-07-07 09:23:09'),
(53, 3, 'staff1@example.test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'success', '2026-07-07 10:08:10'),
(54, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-07 12:24:24'),
(55, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-07 13:07:51'),
(56, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-07 13:13:22'),
(57, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-07 13:19:28'),
(58, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-07 13:48:54'),
(59, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-07 14:04:27'),
(60, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-07 14:08:41'),
(61, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-07 15:27:23'),
(62, 4, 'staff2@example.test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-07 15:42:30'),
(63, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-07 15:43:47'),
(64, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-07 15:55:26'),
(65, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-07 16:01:20'),
(66, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-07 16:03:02'),
(67, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-07 16:32:32'),
(68, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-07 17:33:35'),
(69, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-07 17:41:35'),
(70, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-07 17:59:19'),
(71, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-07 19:09:17'),
(72, 3, 'staff1@example.test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-07 19:34:48'),
(73, 1, 'test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-07-07 19:35:53');

-- --------------------------------------------------------

--
-- Table structure for table `meetings`
--

CREATE TABLE `meetings` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `meeting_date` datetime NOT NULL,
  `status` enum('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `video_link` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message_text` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `message_text`, `is_read`, `created_at`) VALUES
(1, 1, 2, 'Test check-in greeting!', 0, '2026-07-05 12:41:52'),
(2, 1, 2, 'Test check-in greeting!', 0, '2026-07-05 12:44:26'),
(3, 4, 3, 'hi', 1, '2026-07-05 14:34:12');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 'Task Deadline Warning', 'Task GSTR filing is overdue.', 1, '2026-07-07 04:12:48');

-- --------------------------------------------------------

--
-- Table structure for table `opportunities`
--

CREATE TABLE `opportunities` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stage` enum('discovery','proposal','negotiation','won','lost') NOT NULL DEFAULT 'discovery',
  `probability` int(11) NOT NULL DEFAULT 10,
  `close_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'manage_settings', 'Modify system settings, configuration', '2026-07-05 05:28:51'),
(2, 'manage_staff', 'Add, edit, or delete staff members', '2026-07-05 05:28:51'),
(3, 'manage_clients', 'Add, edit, or delete clients', '2026-07-05 05:28:51'),
(4, 'manage_tasks', 'Assign, update, and manage all tasks', '2026-07-05 05:28:51'),
(5, 'view_all_tasks', 'View tasks assigned to any staff', '2026-07-05 05:28:51'),
(6, 'log_work', 'Create work logs on tasks', '2026-07-05 05:28:51'),
(7, 'view_audit_logs', 'Access system logs and security reports', '2026-07-05 05:28:51');

-- --------------------------------------------------------

--
-- Table structure for table `recurring_templates`
--

CREATE TABLE `recurring_templates` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `assigned_to_user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `category` varchar(100) NOT NULL,
  `frequency` enum('monthly','quarterly','yearly') NOT NULL DEFAULT 'monthly',
  `next_spawn_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'super_admin', 'Full access to all modules and configurations', '2026-07-05 05:28:51'),
(2, 'admin_manager', 'Manage client CRM, tasks, and document centers', '2026-07-05 05:28:51'),
(3, 'staff', 'Access to own tasks, daily work logging, and client documents', '2026-07-05 05:28:51');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role` enum('super_admin','admin_manager','staff') NOT NULL,
  `permission` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role`, `permission`) VALUES
('super_admin', 'edit_roles'),
('super_admin', 'manage_accounting'),
('super_admin', 'manage_clients'),
('super_admin', 'manage_compliance'),
('super_admin', 'manage_hrms'),
('super_admin', 'manage_staff'),
('super_admin', 'manage_tasks'),
('super_admin', 'view_reports'),
('super_admin', 'view_security_logs'),
('admin_manager', 'manage_accounting'),
('admin_manager', 'manage_clients'),
('admin_manager', 'manage_compliance'),
('admin_manager', 'manage_hrms'),
('admin_manager', 'manage_tasks'),
('admin_manager', 'view_reports'),
('staff', 'manage_compliance'),
('staff', 'manage_tasks');

-- --------------------------------------------------------

--
-- Table structure for table `salary_slips`
--

CREATE TABLE `salary_slips` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `salary_slips`
--

INSERT INTO `salary_slips` (`id`, `employee_id`, `month`, `basic`, `hra`, `conveyance`, `allowance`, `pf`, `pt`, `tds`, `net_salary`, `status`, `paid_date`, `created_at`) VALUES
(4, 2, '2026-07', 30000.00, 12000.00, 2000.00, 5000.00, 1800.00, 200.00, 1000.00, 46000.00, 'paid', '2026-07-05', '2026-07-05 16:40:51'),
(5, 4, '2026-07', 967.74, 129.03, 12.90, 12.90, 2000.00, 200.00, 200.00, -1277.43, 'unpaid', NULL, '2026-07-05 16:41:51');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `charge` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `charge`, `description`) VALUES
(1, 'GST New Registration', 3000.00, 'Complete GST registration including ARN generation'),
(2, 'Monthly GSTR-3B Filing', 1500.00, 'Preparation and filing of GSTR-3B monthly returns'),
(3, 'ITR-1 Consultation & Filing', 2500.00, 'Income tax returns filing for salaried individuals'),
(4, 'Corporate Tax Audit Return', 15000.00, 'Detailed audit of accounts and form 3CD filing'),
(5, 'ROC Annual Filing Form AOC-4', 8000.00, 'Filing of annual financial statements of private limited company'),
(6, 'GST Audit Filings', 4500.00, 'Annual GST reconciliation and audit reporting');

-- --------------------------------------------------------

--
-- Table structure for table `shift_assignments`
--

CREATE TABLE `shift_assignments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `shift_timing` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shift_assignments`
--

INSERT INTO `shift_assignments` (`id`, `user_id`, `date`, `shift_timing`, `created_at`) VALUES
(1, 3, '2026-07-07', 'Night Shift (10:00 PM - 06:00 AM)', '2026-07-07 10:06:41'),
(2, 3, '2026-07-08', 'General Shift (09:00 AM - 06:00 PM)', '2026-07-07 10:09:40'),
(3, 1, '2026-07-07', 'Night Shift (10:00 PM - 06:00 AM)', '2026-07-07 12:32:36');

-- --------------------------------------------------------

--
-- Table structure for table `sms_logs`
--

CREATE TABLE `sms_logs` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `phone_number` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'sent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
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
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `client_id`, `assigned_to_user_id`, `title`, `description`, `status`, `priority`, `category`, `due_date`, `created_at`, `updated_at`, `deleted_at`) VALUES
(4, 3, 3, 'GST', 'new GST augst', 'completed', 'medium', 'gst,tds', '2026-07-08', '2026-07-07 09:20:39', '2026-07-07 09:23:52', NULL),
(5, 4, 4, 'ITR', 'file ITR in sapt 2026', 'completed', 'medium', 'ITR', '2026-09-01', '2026-07-07 12:31:55', '2026-07-07 15:42:50', NULL),
(6, 4, 3, 'TDS', 'fixed', 'pending', 'medium', 'TDS', '2026-07-07', '2026-07-07 12:45:07', '2026-07-07 12:45:07', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tenants`
--

CREATE TABLE `tenants` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `plan_name` varchar(50) DEFAULT 'basic',
  `user_limit` int(11) DEFAULT 5,
  `storage_limit_mb` int(11) DEFAULT 1024,
  `status` varchar(50) DEFAULT 'active',
  `billing_due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tenants`
--

INSERT INTO `tenants` (`id`, `name`, `plan_name`, `user_limit`, `storage_limit_mb`, `status`, `billing_due_date`, `created_at`) VALUES
(1, 'Primary CA Firm Ltd', 'professional', 15, 5120, 'active', '2026-08-06', '2026-07-07 04:33:08'),
(2, 'Alpha Audit Firm 24', 'basic', 5, 1024, 'active', '2026-08-06', '2026-07-07 04:36:27'),
(3, 'Alpha Audit Firm 40', 'basic', 5, 1024, 'active', '2026-08-06', '2026-07-07 04:37:13');

-- --------------------------------------------------------

--
-- Table structure for table `tenant_billing`
--

CREATE TABLE `tenant_billing` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT 'unpaid',
  `due_date` date NOT NULL,
  `invoice_number` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tenant_billing`
--

INSERT INTO `tenant_billing` (`id`, `tenant_id`, `amount`, `status`, `due_date`, `invoice_number`, `created_at`) VALUES
(1, 2, 1500.00, 'unpaid', '2026-08-06', 'SUB-20260707-002-92', '2026-07-07 04:36:27'),
(2, 3, 1500.00, 'unpaid', '2026-08-06', 'SUB-20260707-003-51', '2026-07-07 04:37:13');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
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
  `two_fa_enabled` tinyint(1) DEFAULT 0,
  `two_fa_code` varchar(10) DEFAULT NULL,
  `two_fa_expires_at` datetime DEFAULT NULL,
  `tenant_id` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `pin_hash`, `role`, `created_at`, `updated_at`, `deleted_at`, `api_token`, `login_failures`, `locked_until`, `two_fa_enabled`, `two_fa_code`, `two_fa_expires_at`, `tenant_id`) VALUES
(1, 'Super Admin', 'test', '$2y$10$gVXLDAnyatPtcC.xmCC/TOhZfAtKRvRWxMK.6fRp2ATu22N3/nE4W', '$2a$10$ZG0gPL79cC2xxcTl5iQrleKsBJMhlIDbSHFa2SpaspnwNmnFzIRKe', 'super_admin', '2026-07-04 14:48:30', '2026-07-05 12:47:04', NULL, 'ecfba0d1b348452cfe1f4857b6568dde8f5c55981b11b3348bf4ceadba1d1995', 0, NULL, 0, NULL, NULL, 1),
(2, 'Admin Manager', 'manager@example.test', '$2y$10$gVXLDAnyatPtcC.xmCC/TOhZfAtKRvRWxMK.6fRp2ATu22N3/nE4W', '$2a$10$oh.8G7U1p.ZDnhQsREoN9O6evqFeWuobNW.bPlO09ks.fKrD8DMFy', 'admin_manager', '2026-07-04 14:48:30', '2026-07-05 12:47:04', NULL, 'f872625be22985007f807c21176df43b5c867fea26b02d044c672c9c60cce94c', 0, NULL, 0, NULL, NULL, 1),
(3, 'Staff 1', 'staff1@example.test', '$2y$10$gVXLDAnyatPtcC.xmCC/TOhZfAtKRvRWxMK.6fRp2ATu22N3/nE4W', '$2a$10$ZG0gPL79cC2xxcTl5iQrleKsBJMhlIDbSHFa2SpaspnwNmnFzIRKe', 'staff', '2026-07-04 14:48:30', '2026-07-05 12:47:04', NULL, 'b14f590a60bcac722658ff8db4dea7e77bd91b3257e1a6ce4c8ea27ec22b4c6c', 0, NULL, 0, NULL, NULL, 1),
(4, 'Staff 2', 'staff2@example.test', '$2y$10$gVXLDAnyatPtcC.xmCC/TOhZfAtKRvRWxMK.6fRp2ATu22N3/nE4W', '$2a$10$fG4Xfdr3vHVIuF35b0nofeFIv1KloqpFvsoGxyFVc3L69h8xKyaMC', 'staff', '2026-07-04 14:48:30', '2026-07-05 12:47:04', NULL, '60fc899b76eabab5673631631e13578640e4e154bd7fc9b22506d450a3473172', 0, NULL, 0, NULL, NULL, 1),
(5, 'Staff 3', 'staff3@example.test', '$2y$10$gVXLDAnyatPtcC.xmCC/TOhZfAtKRvRWxMK.6fRp2ATu22N3/nE4W', '$2a$10$FhTIUOrgafECh/Om47Hnm.fV0WrsAULEYjk4PBGJPpKNDd1l/hE/S', 'staff', '2026-07-04 14:48:30', '2026-07-05 12:47:04', NULL, '4b7283a43579ed5204992dffb444caa7c918adc404522a3b8c873c6e3eabf44e', 0, NULL, 0, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 3),
(5, 3);

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(50) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `last_active` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_token`, `ip_address`, `user_agent`, `last_active`) VALUES
(1, 1, 'dbedd6cf1741b4cc42729053652d00081c841490211d03e16d683decc76384c9', '127.0.0.1', 'Unknown', '2026-07-07 04:21:33'),
(3, 1, '9c6ef6c732037ee8d71678779ce667f11083d5668caf2dbd298f5a299bc01508', '127.0.0.1', 'Unknown', '2026-07-07 04:31:41'),
(4, 1, '5452bac26c3493dc3e98473b6a5af0dcc17fd8c68bafe7d339855426ef25b8bb', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-07 09:10:07'),
(5, 3, 'ffa1cbb30b65c6ca6e0e6c7f2bc2c583fa48a682e47f02a66e745e3bc7f8627e', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '2026-07-07 09:23:09'),
(6, 3, 'ce53504da3d7b92cdea65537be9576bff446e55dcefde2842fbd0e7d4f3f9387', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '2026-07-07 10:08:10'),
(7, 1, '05f54b2ebf2c11ea5cb0e5bc9aaf2b91c6ad3d8f9b78690ae81aac835f32c476', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-07 12:24:24'),
(8, 1, '1fe595fc8ec515d079403f30f307223737b8210195a9676dae86702e68803941', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-07 13:07:51'),
(9, 1, '2f222cf61efe5126f4f3c248c26cdea3fd3edeec69b1f3d269a711e7a0e578fc', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-07 13:13:22'),
(10, 1, '2e30cb527dd5500fb199c79db803938b5f9dd393ebe5db7035af367a4c465f43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-07 13:19:28'),
(11, 1, '4635d46a31c36f6a65386447e1cb7f8de910df6d780d02b8813285afec5550e2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-07 13:48:54'),
(12, 1, '30879dd16f22fd5598413ea8aa5f1c4143596d71dc906e393e01ecacebab901c', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-07 14:04:27'),
(13, 1, '0b35f8a8a825dfeb086af2ca61812f70a7539f3c2b52b60468a048994ef8681b', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-07 14:08:41'),
(14, 4, 'b285158d3e796f4d9b6b202839aa30123430e02d9b27b9a9769f85288ee2873f', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-07 15:42:30'),
(15, 1, 'a3075300fe4de7ba503166421b199bd5698adb5f9e251c384bacc66ea86c0658', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-07 15:43:47'),
(16, 1, 'd7ed1f4ecacd616148124d3a1e471beea89776d9ea76e0192f060215766e71b8', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-07 16:01:20'),
(17, 1, '741c1ccfc55e01d95b3650a8dd5f22379b7d8ca4ea76cfc7a6657c8f848d196c', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-07 16:32:32'),
(18, 1, 'a4cbd2a652d0afb0be37537ed910d1593d1a6731ad82ed53789824e325a3f43f', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-07 17:33:35'),
(19, 1, 'acc511c19cafdfa67bc48922b09984cb89663eb2dc1a68986ee7f53d62cc1242', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-07 17:41:35'),
(20, 1, 'd787abe1bfd447e98afdef92a1a575b8e4b0a0444a4fd761ad5f65446b3dc784', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-07 17:59:19'),
(21, 1, '59b73058900c5deb9aa1fa61e30baafab5432a0a9bc7032013cd1de96c2bb6fb', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-07 19:09:17'),
(22, 3, '016a721ece033d5e4533fb2f7964061f07c0658d112c1a842074abd5e86ce6fc', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-07 19:34:48'),
(23, 1, '8239a0cd7d23a0ee24fb7b215b70ed5c4842e86c638d31fdea5ec99003bc2416', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-07 19:35:53');

-- --------------------------------------------------------

--
-- Table structure for table `whatsapp_logs`
--

CREATE TABLE `whatsapp_logs` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `sender` varchar(50) NOT NULL DEFAULT 'staff',
  `message` text NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'sent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `work_logs`
--

CREATE TABLE `work_logs` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `hours_spent` decimal(5,2) NOT NULL,
  `log_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `work_logs`
--

INSERT INTO `work_logs` (`id`, `task_id`, `user_id`, `description`, `hours_spent`, `log_date`, `created_at`) VALUES
(3, 4, 3, 'done', 5.00, '2026-07-07', '2026-07-07 09:23:43'),
(4, 5, 4, 'comlpleted', 5.00, '2026-07-07', '2026-07-07 15:42:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounting_expenses`
--
ALTER TABLE `accounting_expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `accounting_invoices`
--
ALTER TABLE `accounting_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `idx_invoices_status_due` (`status`,`due_date`);

--
-- Indexes for table `accounting_payments`
--
ALTER TABLE `accounting_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `api_tokens`
--
ALTER TABLE `api_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_date_unique` (`user_id`,`date`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `automation_queue`
--
ALTER TABLE `automation_queue`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `portal_token` (`portal_token`),
  ADD KEY `idx_clients_deleted_at` (`deleted_at`),
  ADD KEY `idx_clients_name` (`name`);

--
-- Indexes for table `client_compliance_configs`
--
ALTER TABLE `client_compliance_configs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `client_id` (`client_id`);

--
-- Indexes for table `client_notes`
--
ALTER TABLE `client_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `client_timeline`
--
ALTER TABLE `client_timeline`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `compliances`
--
ALTER TABLE `compliances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `idx_compliances_status_due` (`status`,`due_date`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `document_request_id` (`document_request_id`),
  ADD KEY `idx_documents_client` (`client_id`);
ALTER TABLE `documents` ADD FULLTEXT KEY `idx_documents_ocr` (`ocr_text`);

--
-- Indexes for table `document_requests`
--
ALTER TABLE `document_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `email_templates`
--
ALTER TABLE `email_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `template_name` (`template_name`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `ip_restrictions`
--
ALTER TABLE `ip_restrictions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ip_address` (`ip_address`);

--
-- Indexes for table `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leaves`
--
ALTER TABLE `leaves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `meetings`
--
ALTER TABLE `meetings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `opportunities`
--
ALTER TABLE `opportunities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lead_id` (`lead_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `recurring_templates`
--
ALTER TABLE `recurring_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `assigned_to_user_id` (`assigned_to_user_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role`,`permission`);

--
-- Indexes for table `salary_slips`
--
ALTER TABLE `salary_slips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `emp_month_unique` (`employee_id`,`month`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shift_assignments`
--
ALTER TABLE `shift_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_date` (`user_id`,`date`);

--
-- Indexes for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `assigned_to_user_id` (`assigned_to_user_id`),
  ADD KEY `idx_tasks_status` (`status`),
  ADD KEY `idx_tasks_due_date` (`due_date`),
  ADD KEY `idx_tasks_status_due` (`status`,`due_date`),
  ADD KEY `idx_tasks_client` (`client_id`);

--
-- Indexes for table `tenants`
--
ALTER TABLE `tenants`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tenant_billing`
--
ALTER TABLE `tenant_billing`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `tenant_id` (`tenant_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_tenant` (`tenant_id`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `whatsapp_logs`
--
ALTER TABLE `whatsapp_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `work_logs`
--
ALTER TABLE `work_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounting_expenses`
--
ALTER TABLE `accounting_expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `accounting_invoices`
--
ALTER TABLE `accounting_invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `accounting_payments`
--
ALTER TABLE `accounting_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `api_tokens`
--
ALTER TABLE `api_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `automation_queue`
--
ALTER TABLE `automation_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `client_compliance_configs`
--
ALTER TABLE `client_compliance_configs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `client_notes`
--
ALTER TABLE `client_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `client_timeline`
--
ALTER TABLE `client_timeline`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `compliances`
--
ALTER TABLE `compliances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `document_requests`
--
ALTER TABLE `document_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_templates`
--
ALTER TABLE `email_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ip_restrictions`
--
ALTER TABLE `ip_restrictions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `leaves`
--
ALTER TABLE `leaves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `meetings`
--
ALTER TABLE `meetings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `opportunities`
--
ALTER TABLE `opportunities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `recurring_templates`
--
ALTER TABLE `recurring_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `salary_slips`
--
ALTER TABLE `salary_slips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `shift_assignments`
--
ALTER TABLE `shift_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sms_logs`
--
ALTER TABLE `sms_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tenants`
--
ALTER TABLE `tenants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tenant_billing`
--
ALTER TABLE `tenant_billing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `whatsapp_logs`
--
ALTER TABLE `whatsapp_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `work_logs`
--
ALTER TABLE `work_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounting_invoices`
--
ALTER TABLE `accounting_invoices`
  ADD CONSTRAINT `accounting_invoices_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `accounting_payments`
--
ALTER TABLE `accounting_payments`
  ADD CONSTRAINT `accounting_payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `accounting_invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `api_tokens`
--
ALTER TABLE `api_tokens`
  ADD CONSTRAINT `api_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `client_compliance_configs`
--
ALTER TABLE `client_compliance_configs`
  ADD CONSTRAINT `client_compliance_configs_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `client_notes`
--
ALTER TABLE `client_notes`
  ADD CONSTRAINT `client_notes_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_notes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `client_timeline`
--
ALTER TABLE `client_timeline`
  ADD CONSTRAINT `client_timeline_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_timeline_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `compliances`
--
ALTER TABLE `compliances`
  ADD CONSTRAINT `compliances_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`document_request_id`) REFERENCES `document_requests` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `document_requests`
--
ALTER TABLE `document_requests`
  ADD CONSTRAINT `document_requests_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD CONSTRAINT `email_logs_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leaves`
--
ALTER TABLE `leaves`
  ADD CONSTRAINT `leaves_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_requests_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `meetings`
--
ALTER TABLE `meetings`
  ADD CONSTRAINT `meetings_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `opportunities`
--
ALTER TABLE `opportunities`
  ADD CONSTRAINT `opportunities_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `opportunities_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `recurring_templates`
--
ALTER TABLE `recurring_templates`
  ADD CONSTRAINT `recurring_templates_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recurring_templates_ibfk_2` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `salary_slips`
--
ALTER TABLE `salary_slips`
  ADD CONSTRAINT `salary_slips_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shift_assignments`
--
ALTER TABLE `shift_assignments`
  ADD CONSTRAINT `shift_assignments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD CONSTRAINT `sms_logs_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tenant_billing`
--
ALTER TABLE `tenant_billing`
  ADD CONSTRAINT `tenant_billing_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `whatsapp_logs`
--
ALTER TABLE `whatsapp_logs`
  ADD CONSTRAINT `whatsapp_logs_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `work_logs`
--
ALTER TABLE `work_logs`
  ADD CONSTRAINT `work_logs_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `work_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
