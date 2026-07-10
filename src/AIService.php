<?php
// src/AIService.php

require_once __DIR__ . '/Util.php';

class AIService {
    /**
     * Call Google Gemini API
     */
    private static function callGeminiAPI($prompt, $systemInstruction = null) {
        $apiKey = Util::getEnv('GEMINI_API_KEY');
        if (empty($apiKey)) {
            return null;
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . urlencode($apiKey);

        if ($systemInstruction) {
            $prompt = "System Instruction: " . $systemInstruction . "\n\nUser Request: " . $prompt;
        }

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => 1024,
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            curl_close($ch);
            return null;
        }
        curl_close($ch);

        $data = json_decode($response, true);
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return trim($data['candidates'][0]['content']['parts'][0]['text']);
        }

        return null;
    }

    /**
     * Parse User Chat Prompts
     */
    public static function chatResponse($prompt) {
        $apiKey = Util::getEnv('GEMINI_API_KEY');
        if (!empty($apiKey)) {
            $systemInstruction = "You are a certified Chartered Accountant (CA) firm assistant. Answer user queries professionally and concisely in English. Help with Income Tax, GST, Audits, Compliances, and system settings.";
            $response = self::callGeminiAPI($prompt, $systemInstruction);
            if ($response) {
                return $response;
            }
        }

        $p = strtolower(trim($prompt));

        if (strpos($p, 'due date') !== false || strpos($p, 'compliance') !== false || strpos($p, 'gstr') !== false) {
            return "Here are the key statutory tax deadlines:\n- GSTR-1: 11th of every succeeding month.\n- GSTR-3B: 20th of every succeeding month.\n- Income Tax Return (Individual): 31st July.\n- Corporate Audit Returns: 30th September.";
        }

        if (strpos($p, 'backup') !== false || strpos($p, 'db') !== false) {
            return "To execute database backups, navigate to the [Automation Hub](index.php?tab=automation). Backups are saved in `public/uploads/backups/` and can be manually triggered there.";
        }

        if (strpos($p, 'invoice') !== false || strpos($p, 'bill') !== false) {
            return "You can generate custom billing invoices in the [Accounting](index.php?tab=accounting) tab. Automated retainer monthly invoices are created on the 1st of each month via the cron engine.";
        }

        if (strpos($p, 'security') !== false || strpos($p, '2fa') !== false) {
            return "Account safety features are under the [Security](index.php?tab=security) tab. There you can configure 2FA, view active logged-in device sessions, and whitelist IP addresses.";
        }

        return "I am your CA Firm AI Assistant. You can ask me about:\n- Tax due dates (GSTR-1, ITR etc.)\n- Database backups and crons\n- Invoicing and billing\n- Account security settings and 2FA";
    }

    /**
     * Generate templated email drafts
     */
    public static function generateEmailDraft($clientName, $topic) {
        $clientName = htmlspecialchars($clientName);
        if (strpos(strtolower($topic), 'invoice') !== false || strpos(strtolower($topic), 'outstanding') !== false) {
            return "Dear $clientName,\n\nWe hope you are doing well.\n\nThis is a friendly alert that invoice #INV-XXXX is currently outstanding. Kindly arrange for the settlement of the dues at your earliest convenience.\n\nBest Regards,\nCA Accounts Desk";
        }
        return "Dear $clientName,\n\nThis is to notify you regarding your upcoming return filing obligations. Please ensure all requested invoices and statements are uploaded to the document vault.\n\nBest Regards,\nCA CRM Support Office";
    }

    /**
     * Suggest checklists for common tasks
     */
    public static function suggestSubtasks($taskTitle) {
        $apiKey = Util::getEnv('GEMINI_API_KEY');
        if (!empty($apiKey)) {
            $prompt = "For the Chartered Accountant (CA) firm CRM task titled \"$taskTitle\", generate a list of 3 to 5 clear, professional subtasks/checklists. Return the result format strictly as markdown bullet points starting with - [ ], one per line. Do not write any introduction or conclusion.";
            $response = self::callGeminiAPI($prompt, "You are a professional Chartered Accountant project manager.");
            if ($response) {
                return $response;
            }
        }

        $t = strtolower($taskTitle);
        if (strpos($t, 'audit') !== false) {
            return "- [ ] Fetch bank statements and ledgers\n- [ ] Reconcile cash and purchase transactions\n- [ ] Verify fixed asset purchases\n- [ ] Prepare draft Balance Sheet and P&L statements";
        }
        if (strpos($t, 'gst') !== false || strpos($t, 'gstr') !== false) {
            return "- [ ] Export sales register\n- [ ] Match input tax credit (ITC) against GSTR-2B\n- [ ] Reconcile differences in sales declarations\n- [ ] File return and record ARN details";
        }
        return "- [ ] Collect initial client files\n- [ ] Review documentation checklist\n- [ ] Prepare calculation worksheet\n- [ ] Finalize filing submission draft";
    }

    /**
     * Summarize P&L report data
     */
    public static function summarizeReport($data) {
        $billed = floatval($data['billed_revenue'] ?? 0);
        $collected = floatval($data['collected_revenue'] ?? 0);
        $expenses = floatval($data['total_expenses'] ?? 0);
        $profit = floatval($data['net_profit'] ?? 0);
        $margin = floatval($data['profit_margin'] ?? 0);

        $summary = "### AI Executive Financial Summary\n";
        $summary .= "- **Revenue Analysis**: The firm billed a total of ₹" . number_format($billed, 2) . " and collected ₹" . number_format($collected, 2) . " over this period.\n";
        $summary .= "- **Outflow**: Operating expenses and payroll payroll total ₹" . number_format($expenses, 2) . ".\n";
        $summary .= "- **Net Results**: The net profit is ₹" . number_format($profit, 2) . " with an operating profit margin of **$margin%**.\n";

        if ($profit > 0) {
            $summary .= "- **AI Diagnostic**: The practice is running profitably. To further optimize liquidity, verify outstanding unpaid billing invoices.";
        } else {
            $summary .= "- **AI Diagnostic**: High operating costs detected. Review payroll salary distributions and cut discretionary admin overheads.";
        }

        return $summary;
    }

    /**
     * Generate dynamic compliance reminders
     */
    public static function generateComplianceReminder($clientName, $filingName, $dueDate) {
        return "Dear " . htmlspecialchars($clientName) . ", this is a reminder that your filing for " . htmlspecialchars($filingName) . " is due on " . htmlspecialchars($dueDate) . ". Please submit documents.";
    }

    /**
     * Analyze dashboard indicators to suggest insights
     */
    public static function generateDashboardInsights($kpi) {
        $outstanding = floatval($kpi['outstanding'] ?? 0);
        $insights = [];

        if ($outstanding > 20000) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Liquidity Warning',
                'desc' => "Unpaid receivables are high (₹" . number_format($outstanding, 2) . "). Trigger email outstanding reminders."
            ];
        }

        $insights[] = [
            'type' => 'info',
            'title' => 'Filing Deadline Reminder',
            'desc' => "GSTR-1 filing dates are approaching. Ensure GST sales registers are exported soon."
        ];

        return $insights;
    }

    /**
     * Parse document files using Gemini API
     */
    public static function parseUploadedDocument($filePath, $mimeType) {
        $apiKey = Util::getEnv('GEMINI_API_KEY');
        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'Gemini API key is not configured.'
            ];
        }

        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'message' => 'File not found.'
            ];
        }

        $fileData = base64_encode(file_get_contents($filePath));
        
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . urlencode($apiKey);

        $prompt = "You are a professional Chartered Accountant (CA) Document Parser. Analyze this document and extract the following details in raw JSON format. Return ONLY the JSON object. Do not include markdown code block backticks (like ```json).
Fields to extract:
- DocumentType: String (e.g. GST Invoice, Form 16, Bank Statement, PAN Card, Other)
- TaxpayerName: String
- PAN: String (format ABCDE1234F, leave null if not found)
- GSTIN: String (15-character format, leave null if not found)
- TotalAmount: Float/Double (leave null if not found)
- FinancialYear: String (e.g. 2025-26, leave null if not found)
- VerificationStatus: String ('Valid' if document looks genuine and not modified, else 'Suspect')
- Warnings: Array of Strings (list any discrepancies, e.g. dates not matching, missing signature, arithmetic errors)";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'inlineData' => [
                                'mimeType' => $mimeType,
                                'data' => $fileData
                            ]
                        ],
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'responseMimeType' => 'application/json'
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $err = curl_error($ch);
            curl_close($ch);
            return [
                'success' => false,
                'message' => 'Curl error: ' . $err
            ];
        }
        curl_close($ch);

        $data = json_decode($response, true);
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $text = trim($data['candidates'][0]['content']['parts'][0]['text']);
            $jsonData = json_decode($text, true);
            if ($jsonData) {
                return [
                    'success' => true,
                    'data' => $jsonData
                ];
            }
        }

        return [
            'success' => false,
            'message' => 'AI could not read or structure the document.'
        ];
    }
}
