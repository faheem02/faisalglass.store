<?php
/**
 * Save Supplier Manual Ledger Entry (Debit / Credit)
 * Faysal Glass And Aluminium Centre
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please login.']);
    exit();
}

include_once('../includes/database.php');
include_once('../includes/txt.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$user_id = $_SESSION['user_id'] ?? 0;
$supplier_id = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
$entry_date = isset($_POST['entry_date']) && !empty($_POST['entry_date']) ? mysqli_real_escape_string($conn, trim($_POST['entry_date'])) : date('Y-m-d');
$entry_type = isset($_POST['entry_type']) ? strtolower(trim($_POST['entry_type'])) : '';
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
$payment_method = isset($_POST['payment_method']) ? strtolower(trim($_POST['payment_method'])) : 'adjustment';
$bank_account_id = ($payment_method === 'bank' && isset($_POST['bank_account_id'])) ? intval($_POST['bank_account_id']) : NULL;
$reference_no = isset($_POST['reference_no']) ? mysqli_real_escape_string($conn, trim($_POST['reference_no'])) : '';
$remarks = isset($_POST['remarks']) ? mysqli_real_escape_string($conn, trim($_POST['remarks'])) : '';

// Validation
if ($supplier_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid supplier selected.']);
    exit();
}

if (!in_array($entry_type, ['debit', 'credit'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid entry type. Must be Debit or Credit.']);
    exit();
}

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Amount must be greater than zero.']);
    exit();
}

if (empty($remarks)) {
    echo json_encode(['success' => false, 'message' => 'Description / Remarks are required.']);
    exit();
}

if ($payment_method === 'bank' && empty($bank_account_id)) {
    echo json_encode(['success' => false, 'message' => 'Please select a bank account.']);
    exit();
}

// Fetch supplier
$sup_query = "SELECT * FROM suppliers WHERE id = $supplier_id";
$sup_res = mysqli_query($conn, $sup_query);
if (!$sup_res || mysqli_num_rows($sup_res) === 0) {
    echo json_encode(['success' => false, 'message' => 'Supplier not found.']);
    exit();
}
$supplier = mysqli_fetch_assoc($sup_res);
$current_balance = floatval($supplier['current_balance']);

// Start Database Transaction
mysqli_begin_transaction($conn);

try {
    // For supplier (Liability / Payable):
    // Credit increases payable (we owe supplier more)
    // Debit decreases payable (we owe supplier less / payment made)
    if ($entry_type === 'credit') {
        $debit = 0;
        $credit = $amount;
        $new_balance = $current_balance + $amount;
        $type_tag = 'Manual Credit';
    } else {
        $debit = $amount;
        $credit = 0;
        $new_balance = $current_balance - $amount;
        $type_tag = 'Manual Debit';
    }

    $description = "[$type_tag] $remarks";
    if (!empty($reference_no)) {
        $description .= " (Ref: $reference_no)";
    }

    // 1. Insert into supplier_ledger
    $ledger_query = "INSERT INTO supplier_ledger (date, supplier_id, reference_type, reference_id, description, debit, credit, balance, created_at) 
                     VALUES ('$entry_date', $supplier_id, 'ADJUSTMENT', 0, '$description', $debit, $credit, $new_balance, NOW())";
    if (!mysqli_query($conn, $ledger_query)) {
        throw new Exception("Failed to insert supplier ledger record: " . mysqli_error($conn));
    }
    $ledger_entry_id = mysqli_insert_id($conn);

    // 2. Update supplier current_balance
    $update_supplier = "UPDATE suppliers SET current_balance = $new_balance WHERE id = $supplier_id";
    if (!mysqli_query($conn, $update_supplier)) {
        throw new Exception("Failed to update supplier balance: " . mysqli_error($conn));
    }

    // 3. Optional Cash / Bank Book handling
    if ($payment_method === 'cash') {
        $cash_bal_q = mysqli_query($conn, "SELECT balance FROM cash_book ORDER BY id DESC LIMIT 1");
        $cash_balance = 0;
        if ($cash_bal_q && mysqli_num_rows($cash_bal_q) > 0) {
            $cash_balance = floatval(mysqli_fetch_assoc($cash_bal_q)['balance']);
        }

        if ($entry_type === 'debit') {
            // Supplier debit with cash = Payment made to supplier (Cash OUT / Credit)
            $new_cash_balance = $cash_balance - $amount;
            $cash_desc = "Payment made to {$supplier['supplier_name']} (Manual Debit) - $remarks";
            $cash_sql = "INSERT INTO cash_book (date, reference_type, reference_id, description, debit, credit, balance, created_at) 
                         VALUES ('$entry_date', 'SUPPLIER_MANUAL', $ledger_entry_id, '$cash_desc', 0, $amount, $new_cash_balance, NOW())";
        } else {
            // Supplier credit with cash = Cash received/refund from supplier (Cash IN / Debit)
            $new_cash_balance = $cash_balance + $amount;
            $cash_desc = "Refund/Cash received from {$supplier['supplier_name']} (Manual Credit) - $remarks";
            $cash_sql = "INSERT INTO cash_book (date, reference_type, reference_id, description, debit, credit, balance, created_at) 
                         VALUES ('$entry_date', 'SUPPLIER_MANUAL', $ledger_entry_id, '$cash_desc', $amount, 0, $new_cash_balance, NOW())";
        }

        if (!mysqli_query($conn, $cash_sql)) {
            throw new Exception("Failed to update cash book: " . mysqli_error($conn));
        }
    } elseif ($payment_method === 'bank') {
        $bank_q = mysqli_query($conn, "SELECT * FROM bank_accounts WHERE id = $bank_account_id");
        if (!$bank_q || mysqli_num_rows($bank_q) === 0) {
            throw new Exception("Selected bank account not found.");
        }
        $bank_acc = mysqli_fetch_assoc($bank_q);
        $bank_curr_bal = floatval($bank_acc['current_balance']);

        if ($entry_type === 'debit') {
            // Supplier debit with bank = Payment to supplier from bank (Outflow / Credit)
            $new_bank_balance = $bank_curr_bal - $amount;
            $bank_desc = "Bank payment to {$supplier['supplier_name']} (Manual Debit) - $remarks";
            $bank_book_sql = "INSERT INTO bank_book (date, bank_account_id, reference_type, reference_id, description, debit, credit, balance, created_at) 
                              VALUES ('$entry_date', $bank_account_id, 'SUPPLIER_MANUAL', $ledger_entry_id, '$bank_desc', 0, $amount, $new_bank_balance, NOW())";
        } else {
            // Supplier credit with bank = Received from supplier into bank (Inflow / Debit)
            $new_bank_balance = $bank_curr_bal + $amount;
            $bank_desc = "Bank received from {$supplier['supplier_name']} (Manual Credit) - $remarks";
            $bank_book_sql = "INSERT INTO bank_book (date, bank_account_id, reference_type, reference_id, description, debit, credit, balance, created_at) 
                              VALUES ('$entry_date', $bank_account_id, 'SUPPLIER_MANUAL', $ledger_entry_id, '$bank_desc', $amount, 0, $new_bank_balance, NOW())";
        }

        if (!mysqli_query($conn, $bank_book_sql)) {
            throw new Exception("Failed to update bank book: " . mysqli_error($conn));
        }

        $update_bank = "UPDATE bank_accounts SET current_balance = $new_bank_balance WHERE id = $bank_account_id";
        if (!mysqli_query($conn, $update_bank)) {
            throw new Exception("Failed to update bank account balance: " . mysqli_error($conn));
        }
    }

    mysqli_commit($conn);

    echo json_encode([
        'success' => true,
        'message' => 'Manual ' . ucfirst($entry_type) . ' entry recorded successfully!',
        'new_balance' => $new_balance,
        'formatted_balance' => formatCurrency($new_balance)
    ]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
