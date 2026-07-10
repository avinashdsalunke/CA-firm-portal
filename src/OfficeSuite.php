<?php
// src/OfficeSuite.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Client.php';

class OfficeSuite {
    
    // ==========================================
    // DSC TOKENS METHODS
    // ==========================================
    
    public static function getDSCTokens($clientId = null) {
        $db = Database::getConnection();
        $sql = "SELECT d.*, c.name as client_name FROM dsc_tokens d JOIN clients c ON d.client_id = c.id";
        $params = [];
        if ($clientId) {
            $sql .= " WHERE d.client_id = :client_id";
            $params['client_id'] = $clientId;
        }
        $sql .= " ORDER BY d.expiry_date ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function addDSCToken($clientId, $directorName, $expiryDate, $passwordHint = null, $pinHint = null) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("INSERT INTO dsc_tokens (client_id, director_name, expiry_date, password_hint, pin_hint) VALUES (:client_id, :director_name, :expiry_date, :password_hint, :pin_hint)");
            $stmt->execute([
                'client_id' => $clientId,
                'director_name' => $directorName,
                'expiry_date' => $expiryDate,
                'password_hint' => $passwordHint,
                'pin_hint' => $pinHint
            ]);
            Client::addTimelineEvent($clientId, null, 'dsc_token_added', "Added DSC Token for Director: $directorName (Expiry: $expiryDate).");
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => $e->getMessage()];
        }
    }

    public static function deleteDSCToken($id) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("DELETE FROM dsc_tokens WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => $e->getMessage()];
        }
    }

    // ==========================================
    // DOCUMENT EXPIRIES METHODS
    // ==========================================

    public static function getDocumentExpiries($clientId = null) {
        $db = Database::getConnection();
        $sql = "SELECT de.*, c.name as client_name FROM document_expiries de JOIN clients c ON de.client_id = c.id";
        $params = [];
        if ($clientId) {
            $sql .= " WHERE de.client_id = :client_id";
            $params['client_id'] = $clientId;
        }
        $sql .= " ORDER BY de.expiry_date ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function addDocumentExpiry($clientId, $docType, $expiryDate) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("INSERT INTO document_expiries (client_id, doc_type, expiry_date) VALUES (:client_id, :doc_type, :expiry_date)");
            $stmt->execute([
                'client_id' => $clientId,
                'doc_type' => $docType,
                'expiry_date' => $expiryDate
            ]);
            Client::addTimelineEvent($clientId, null, 'doc_expiry_added', "Added expiry date for $docType (Expiry: $expiryDate).");
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => $e->getMessage()];
        }
    }

    public static function deleteDocumentExpiry($id) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("DELETE FROM document_expiries WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => $e->getMessage()];
        }
    }

    // ==========================================
    // TIMESHEETS METHODS
    // ==========================================

    public static function getTimesheets($filters = []) {
        $db = Database::getConnection();
        $sql = "SELECT t.*, u.name as staff_name, c.name as client_name 
                FROM timesheets t 
                JOIN users u ON t.user_id = u.id 
                JOIN clients c ON t.client_id = c.id 
                WHERE 1=1";
        $params = [];
        if (!empty($filters['user_id'])) {
            $sql .= " AND t.user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }
        if (!empty($filters['client_id'])) {
            $sql .= " AND t.client_id = :client_id";
            $params['client_id'] = $filters['client_id'];
        }
        if (!empty($filters['billed_status'])) {
            $sql .= " AND t.billed_status = :billed_status";
            $params['billed_status'] = $filters['billed_status'];
        }
        $sql .= " ORDER BY t.date_logged DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function addTimesheet($userId, $clientId, $hours, $description, $dateLogged) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("INSERT INTO timesheets (user_id, client_id, hours, description, date_logged) VALUES (:user_id, :client_id, :hours, :description, :date_logged)");
            $stmt->execute([
                'user_id' => $userId,
                'client_id' => $clientId,
                'hours' => $hours,
                'description' => $description,
                'date_logged' => $dateLogged
            ]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => $e->getMessage()];
        }
    }

    public static function deleteTimesheet($id) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("DELETE FROM timesheets WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => $e->getMessage()];
        }
    }

    public static function billTimesheet($id, $invoiceId) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("UPDATE timesheets SET billed_status = 'billed', invoice_id = :invoice_id WHERE id = :id");
            $stmt->execute(['invoice_id' => $invoiceId, 'id' => $id]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => $e->getMessage()];
        }
    }

    // ==========================================
    // TAX COMPUTATIONS METHODS
    // ==========================================

    public static function getTaxComputations($clientId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM tax_computations WHERE client_id = :client_id ORDER BY financial_year DESC");
        $stmt->execute(['client_id' => $clientId]);
        return $stmt->fetchAll();
    }

    public static function addTaxComputation($clientId, $fy, $grossSalary, $houseProperty, $capGains, $businessIncome, $otherSources, $deductionsOld, $taxOld, $taxNew, $preferredRegime) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("INSERT INTO tax_computations (client_id, financial_year, gross_salary, house_property, cap_gains, business_income, other_sources, deductions_old, tax_old, tax_new, preferred_regime) 
                                 VALUES (:client_id, :financial_year, :gross_salary, :house_property, :cap_gains, :business_income, :other_sources, :deductions_old, :tax_old, :tax_new, :preferred_regime)");
            $stmt->execute([
                'client_id' => $clientId,
                'financial_year' => $fy,
                'gross_salary' => $grossSalary,
                'house_property' => $houseProperty,
                'cap_gains' => $capGains,
                'business_income' => $businessIncome,
                'other_sources' => $otherSources,
                'deductions_old' => $deductionsOld,
                'tax_old' => $taxOld,
                'tax_new' => $taxNew,
                'preferred_regime' => $preferredRegime
            ]);
            Client::addTimelineEvent($clientId, null, 'tax_computed', "Saved ITR tax computation details for FY $fy.");
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => $e->getMessage()];
        }
    }

    public static function deleteTaxComputation($id) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("DELETE FROM tax_computations WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => $e->getMessage()];
        }
    }

    // Tax Engine: Computes both New and Old Regimes side-by-side (FY 2025-26 slabs)
    public static function calculateTaxRegimes($grossSalary, $houseProperty, $capGains, $businessIncome, $otherSources, $deductionsOld) {
        $grossTotal = $grossSalary + $houseProperty + $capGains + $businessIncome + $otherSources;
        
        // Old Regime: Subtract Deductions (capped at grossTotal)
        $taxableOld = max(0, $grossTotal - $deductionsOld);
        $taxOld = self::computeOldTax($taxableOld);

        // New Regime: Flat Standard Deduction for Salary (if any)
        // Capped standard deduction: 75,000 for FY 2025-26
        $standardDeductionNew = ($grossSalary > 75000) ? 75000 : $grossSalary;
        $taxableNew = max(0, $grossTotal - $standardDeductionNew);
        $taxNew = self::computeNewTax($taxableNew);

        return [
            'gross_total' => $grossTotal,
            'taxable_old' => $taxableOld,
            'tax_old' => $taxOld,
            'taxable_new' => $taxableNew,
            'tax_new' => $taxNew,
            'suggested' => ($taxOld <= $taxNew) ? 'Old Regime' : 'New Regime',
            'savings' => abs($taxOld - $taxNew)
        ];
    }

    private static function computeOldTax($income) {
        // Old Tax Slab slabs:
        // Up to 2.5L: Nil
        // 2.5L to 5L: 5% (Rebate u/s 87A up to 5L)
        // 5L to 10L: 20%
        // Above 10L: 30%
        $tax = 0;
        if ($income <= 250000) {
            return 0;
        }

        if ($income <= 500000) {
            $tax = ($income - 250000) * 0.05;
            // Rebate Section 87A up to 5L old regime: Nil tax
            return 0;
        }

        // Above 5L, standard slabs apply
        if ($income > 1000000) {
            $tax += ($income - 1000000) * 0.30;
            $tax += 500000 * 0.20; // 5L to 10L
            $tax += 250000 * 0.05; // 2.5L to 5L
        } else {
            $tax += ($income - 500000) * 0.20;
            $tax += 250000 * 0.05;
        }

        // Add Health & Education Cess (4%)
        return $tax * 1.04;
    }

    private static function computeNewTax($income) {
        // New Tax Slab slabs (FY 2025-26):
        // Up to 4,00,000: Nil
        // 4,00,000 to 8,00,000: 5% (Rebate u/s 87A up to 12,00,000 for salaried / 7,00,000 for others - let's standardise rebate up to 7L)
        // 8,00,000 to 12,00,000: 10%
        // 12,00,000 to 16,00,000: 15%
        // Above 16,00,000: 20%
        if ($income <= 400000) {
            return 0;
        }

        // Standard rebate u/s 87A (if taxable income <= 7,00,000, tax is 0)
        if ($income <= 700000) {
            return 0;
        }

        $tax = 0;
        if ($income > 1600000) {
            $tax += ($income - 1600000) * 0.20;
            $tax += 400000 * 0.15; // 12L to 16L
            $tax += 400000 * 0.10; // 8L to 12L
            $tax += 400000 * 0.05; // 4L to 8L
        } elseif ($income > 1200000) {
            $tax += ($income - 1200000) * 0.15;
            $tax += 400000 * 0.10;
            $tax += 400000 * 0.05;
        } elseif ($income > 800000) {
            $tax += ($income - 800000) * 0.10;
            $tax += 400000 * 0.05;
        } else {
            $tax += ($income - 400000) * 0.05;
        }

        return $tax * 1.04; // Cess 4%
    }
}
