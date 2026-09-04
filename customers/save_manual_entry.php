<?php
/**
 * Save Customer Manual Ledger Entry (Debit / Credit)
 * Faysal Glass And Aluminium Centre
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
while (ob_get_level()) { ob_end_clean(); }
ob_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please login.']);
    exit();
}

include_once(__DIR__ . '/../includes/database.php');
include_once(__DIR__ . '/../includes/txt.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$user_id = $_SESSION['user_id'] ?? 0;
$customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
$entry_date = isset($_POST['entry_date']) && !empty($_POST['entry_date']) ? mysqli_real_escape_string($conn, trim($_POST['entry_date'])) : date('Y-m-d');
$entry_type = isset($_POST['entry_type']) ? strtolower(trim($_POST['entry_type'])) : '';
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
$payment_method = isset($_POST['payment_method']) ? strtolower(trim($_POST['payment_method'])) : 'adjustment';
$bank_account_id = ($payment_method === 'bank' && isset($_POST['bank_account_id'])) ? intval($_POST['bank_account_id']) : NULL;
$reference_no = isset($_POST['reference_no']) ? mysqli_real_escape_string($conn, trim($_POST['reference_no'])) : '';
$remarks = isset($_POST['remarks']) ? mysqli_real_escape_string($conn, trim($_POST['remarks'])) : '';

// Validation
if ($customer_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid customer selected.']);
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

// Fetch customer
$cust_query = "SELECT * FROM customers WHERE id = $customer_id";
$cust_res = mysqli_query($conn, $cust_query);
if (!$cust_res || mysqli_num_rows($cust_res) === 0) {
    echo json_encode(['success' => false, 'message' => 'Customer not found.']);
    exit();
}
$customer = mysqli_fetch_assoc($cust_res);
$current_balance = floatval($customer['current_balance']);

// Start Database Transaction
mysqli_begin_transaction($conn);

try {
    // Determine debit/credit values and new balance
    // For customer (Receivable / Asset):
    // Debit increases receivable (customer owes more)
    // Credit decreases receivable (customer owes less)
    if ($entry_type === 'debit') {
        $debit = $amount;
        $credit = 0;
        $new_balance = $current_balance + $amount;
        $type_tag = 'Manual Debit';
    } else {
        $debit = 0;
        $credit = $amount;
        $new_balance = $current_balance - $amount;
        $type_tag = 'Manual Credit';
    }

    $description = "[$type_tag] $remarks";
    if (!empty($reference_no)) {
        $description .= " (Ref: $reference_no)";
    }

    // 1. Insert into customer_ledger
    $ledger_query = "INSERT INTO customer_ledger (date, customer_id, reference_type, reference_id, description, debit, credit, balance, created_at) 
                     VALUES ('$entry_date', $customer_id, 'ADJUSTMENT', 0, '$description', $debit, $credit, $new_balance, NOW())";
    if (!mysqli_query($conn, $ledger_query)) {
        throw new Exception("Failed to insert customer ledger record: " . mysqli_error($conn));
    }
    $ledger_entry_id = mysqli_insert_id($conn);

    // 2. Update customer current_balance
    $update_customer = "UPDATE customers SET current_balance = $new_balance WHERE id = $customer_id";
    if (!mysqli_query($conn, $update_customer)) {
        throw new Exception("Failed to update customer balance: " . mysqli_error($conn));
    }

    // 3. Optional Cash / Bank Book handling
    if ($payment_method === 'cash') {
        $cash_bal_q = mysqli_query($conn, "SELECT balance FROM cash_book ORDER BY id DESC LIMIT 1");
        $cash_balance = 0;
        if ($cash_bal_q && mysqli_num_rows($cash_bal_q) > 0) {
            $cash_balance = floatval(mysqli_fetch_assoc($cash_bal_q)['balance']);
        }

        if ($entry_type === 'credit') {
            // Customer credit with cash = Payment received from customer (Cash IN / Debit)
            $new_cash_balance = $cash_balance + $amount;
            $cash_desc = "Payment received from {$customer['customer_name']} (Manual Credit) - $remarks";
            $cash_sql = "INSERT INTO cash_book (date, reference_type, reference_id, description, debit, credit, balance, created_at) 
                         VALUES ('$entry_date', 'CUSTOMER_MANUAL', $ledger_entry_id, '$cash_desc', $amount, 0, $new_cash_balance, NOW())";
        } else {
            // Customer debit with cash = Cash given/refunded to customer (Cash OUT / Credit)
            $new_cash_balance = $cash_balance - $amount;
            $cash_desc = "Cash paid/refunded to {$customer['customer_name']} (Manual Debit) - $remarks";
            $cash_sql = "INSERT INTO cash_book (date, reference_type, reference_id, description, debit, credit, balance, created_at) 
                         VALUES ('$entry_date', 'CUSTOMER_MANUAL', $ledger_entry_id, '$cash_desc', 0, $amount, $new_cash_balance, NOW())";
        }

        if (!mysqli_query($conn, $cash_sql)) {
            throw new Exception("Failed to update cash book: " . mysqli_error($conn));
        }
    } elseif ($payment_method === 'bank') {
        // Fetch current bank balance
        $bank_q = mysqli_query($conn, "SELECT * FROM bank_accounts WHERE id = $bank_account_id");
        if (!$bank_q || mysqli_num_rows($bank_q) === 0) {
            throw new Exception("Selected bank account not found.");
        }
        $bank_acc = mysqli_fetch_assoc($bank_q);
        $bank_curr_bal = floatval($bank_acc['current_balance']);

        if ($entry_type === 'credit') {
            // Customer credit with bank = Received in bank (Inflow / Debit)
            $new_bank_balance = $bank_curr_bal + $amount;
            $bank_desc = "Bank received from {$customer['customer_name']} (Manual Credit) - $remarks";
            $bank_book_sql = "INSERT INTO bank_book (date, bank_account_id, reference_type, reference_id, description, debit, credit, balance, created_at) 
                              VALUES ('$entry_date', $bank_account_id, 'CUSTOMER_MANUAL', $ledger_entry_id, '$bank_desc', $amount, 0, $new_bank_balance, NOW())";
        } else {
            // Customer debit with bank = Paid out to customer from bank (Outflow / Credit)
            $new_bank_balance = $bank_curr_bal - $amount;
            $bank_desc = "Bank paid to {$customer['customer_name']} (Manual Debit) - $remarks";
            $bank_book_sql = "INSERT INTO bank_book (date, bank_account_id, reference_type, reference_id, description, debit, credit, balance, created_at) 
                              VALUES ('$entry_date', $bank_account_id, 'CUSTOMER_MANUAL', $ledger_entry_id, '$bank_desc', 0, $amount, $new_bank_balance, NOW())";
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
