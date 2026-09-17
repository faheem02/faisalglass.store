<?php
session_start();
header('Content-Type: application/json');
ob_start();

if(!isset($_SESSION['user_id'])) {
    while (ob_get_level()) { ob_end_clean(); }
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit();
}

include(__DIR__ . '/../includes/database.php');
include(__DIR__ . '/../includes/txt.php');

$response = ['success' => false, 'message' => ''];

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sale_id = intval($_POST['sale_id'] ?? 0);
    $new_status = strtolower(trim($_POST['payment_type'] ?? ''));
    $bank_account_id = intval($_POST['bank_account_id'] ?? 0);
    $received_input = isset($_POST['received_amount']) ? floatval($_POST['received_amount']) : null;
    $user_id = $_SESSION['user_id'] ?? 0;

    if($sale_id <= 0) {
        $response['message'] = 'Invalid sale invoice selected.';
    } elseif(!in_array($new_status, ['cash', 'bank', 'credit', 'partial'])) {
        $response['message'] = 'Invalid payment status selected.';
    } elseif($new_status === 'bank' && $bank_account_id <= 0) {
        $response['message'] = 'Please select a bank account for bank payment.';
    } else {
        $sale_q = mysqli_query($conn, "SELECT s.*, c.customer_code, c.customer_name FROM sale_master s LEFT JOIN customers c ON s.customer_id = c.id WHERE s.id = $sale_id LIMIT 1");
        if(!$sale_q || mysqli_num_rows($sale_q) === 0) {
            $response['message'] = 'Sale invoice not found.';
        } else {
            $sale = mysqli_fetch_assoc($sale_q);
            $grand_total = floatval($sale['grand_total']);
            $sale_date = $sale['sale_date'];
            $invoice_no = $sale['invoice_no'];
            $customer_id = intval($sale['customer_id']);
            $is_walkin = (($sale['customer_code'] ?? '') === 'WALK-IN' || !empty($sale['walk_in_customer_name']));

            // Determine received and remaining amounts
            if($new_status === 'cash' || $new_status === 'bank') {
                $received_amount = $grand_total;
                $remaining_amount = 0.00;
            } elseif($new_status === 'credit') {
                $received_amount = 0.00;
                $remaining_amount = $grand_total;
                $bank_account_id = 0;
            } else { // partial
                if($received_input === null || $received_input <= 0) {
                    while (ob_get_level()) { ob_end_clean(); }
                    echo json_encode(['success' => false, 'message' => 'Please enter a valid received amount for partial payment.']);
                    exit();
                }
                if($received_input >= $grand_total) {
                    $new_status = ($bank_account_id > 0) ? 'bank' : 'cash';
                    $received_amount = $grand_total;
                    $remaining_amount = 0.00;
                } else {
                    $received_amount = $received_input;
                    $remaining_amount = $grand_total - $received_amount;
                }
            }

            mysqli_begin_transaction($conn);
            try {
                // 1. Remove old financial entries for this sale
                mysqli_query($conn, "DELETE FROM customer_ledger WHERE reference_type = 'SALE' AND reference_id = $sale_id");
                mysqli_query($conn, "DELETE FROM cash_book WHERE reference_type IN ('SALE', 'SALE_PAYMENT') AND reference_id = $sale_id");
                mysqli_query($conn, "DELETE FROM bank_book WHERE reference_type IN ('SALE', 'SALE_PAYMENT') AND reference_id = $sale_id");

                // 2. Update sale_master
                $update_master = "UPDATE sale_master SET 
                                  payment_type = '$new_status', 
                                  received_amount = $received_amount, 
                                  remaining_amount = $remaining_amount, 
                                  bank_account_id = " . ($bank_account_id > 0 ? $bank_account_id : "NULL") . " 
                                  WHERE id = $sale_id";
                if(!mysqli_query($conn, $update_master)) {
                    throw new Exception("Failed to update sale invoice: " . mysqli_error($conn));
                }

                // 3. Customer ledger entry
                if($customer_id > 0) {
                    $bal_q = mysqli_query($conn, "SELECT COALESCE(SUM(debit) - SUM(credit), 0) as balance FROM customer_ledger WHERE customer_id = $customer_id");
                    $cur_bal = 0;
                    if($bal_q && mysqli_num_rows($bal_q) > 0) {
                        $cur_bal = floatval(mysqli_fetch_assoc($bal_q)['balance']);
                    }
                    $new_bal = $cur_bal + $grand_total - $received_amount;

                    $ledger_sql = "INSERT INTO customer_ledger (date, customer_id, reference_type, reference_id, description, debit, credit, balance, created_at) 
                                   VALUES ('$sale_date', $customer_id, 'SALE', $sale_id, 'Sale Invoice: $invoice_no (Status: " . ucfirst($new_status) . ")', $grand_total, $received_amount, $new_bal, NOW())";
                    if(!mysqli_query($conn, $ledger_sql)) {
                        throw new Exception("Failed to update customer ledger: " . mysqli_error($conn));
                    }

                    // Update customers table (walkin stays 0)
                    $cust_balance_to_set = $is_walkin ? 0 : $new_bal;
                    mysqli_query($conn, "UPDATE customers SET current_balance = $cust_balance_to_set WHERE id = $customer_id");
                }

                // 4. Update cash_book or bank_book if received_amount > 0
                if($received_amount > 0) {
                    if($new_status === 'bank' || ($new_status === 'partial' && $bank_account_id > 0)) {
                        $bank_bal_q = mysqli_query($conn, "SELECT COALESCE(SUM(debit) - SUM(credit), 0) as balance FROM bank_book WHERE bank_account_id = $bank_account_id");
                        $cur_bank = 0;
                        if($bank_bal_q && mysqli_num_rows($bank_bal_q) > 0) {
                            $cur_bank = floatval(mysqli_fetch_assoc($bank_bal_q)['balance']);
                        }
                        $new_bank = $cur_bank + $received_amount;
                        $bank_sql = "INSERT INTO bank_book (date, bank_account_id, reference_type, reference_id, description, debit, credit, balance, created_at) 
                                     VALUES ('$sale_date', $bank_account_id, 'SALE', $sale_id, 'Sale Payment: $invoice_no', $received_amount, 0, $new_bank, NOW())";
                        if(!mysqli_query($conn, $bank_sql)) {
                            throw new Exception("Failed to update bank book: " . mysqli_error($conn));
                        }
                    } else {
                        // Cash payment
                        $cash_bal_q = mysqli_query($conn, "SELECT COALESCE(SUM(debit) - SUM(credit), 0) as balance FROM cash_book");
                        $cur_cash = 0;
                        if($cash_bal_q && mysqli_num_rows($cash_bal_q) > 0) {
                            $cur_cash = floatval(mysqli_fetch_assoc($cash_bal_q)['balance']);
                        }
                        $new_cash = $cur_cash + $received_amount;
                        $cash_sql = "INSERT INTO cash_book (date, reference_type, reference_id, description, debit, credit, balance, created_at) 
                                     VALUES ('$sale_date', 'SALE', $sale_id, 'Sale Payment: $invoice_no', $received_amount, 0, $new_cash, NOW())";
                        if(!mysqli_query($conn, $cash_sql)) {
                            throw new Exception("Failed to update cash book: " . mysqli_error($conn));
                        }
                    }
                }

                mysqli_commit($conn);
                $response['success'] = true;
                $response['message'] = "Invoice $invoice_no status successfully updated to " . ucfirst($new_status) . "!";
                $response['payment_type'] = $new_status;
                $response['received_amount'] = $received_amount;
                $response['remaining_amount'] = $remaining_amount;
            } catch(Exception $e) {
                mysqli_rollback($conn);
                $response['message'] = "Error updating status: " . $e->getMessage();
            }
        }
    }
}

while (ob_get_level()) { ob_end_clean(); }
echo json_encode($response);
exit();
