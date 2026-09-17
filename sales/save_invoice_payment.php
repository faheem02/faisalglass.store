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
    $payment_date = mysqli_real_escape_string($conn, trim($_POST['payment_date'] ?? date('Y-m-d')));
    $payment_method = mysqli_real_escape_string($conn, trim($_POST['payment_method'] ?? 'cash'));
    $bank_account_id = ($payment_method === 'bank') ? intval($_POST['bank_account_id'] ?? 0) : 0;
    $amount = floatval($_POST['amount'] ?? 0);
    $reference_no = mysqli_real_escape_string($conn, trim($_POST['reference_no'] ?? ''));
    $remarks = mysqli_real_escape_string($conn, trim($_POST['remarks'] ?? ''));
    $user_id = $_SESSION['user_id'] ?? 0;

    if($sale_id <= 0) {
        $response['message'] = 'Invalid sale invoice selected.';
    } elseif($amount <= 0) {
        $response['message'] = 'Payment amount must be greater than 0.';
    } elseif($payment_method === 'bank' && $bank_account_id <= 0) {
        $response['message'] = 'Please select a bank account for bank transfer.';
    } else {
        $sale_q = mysqli_query($conn, "SELECT s.*, c.customer_name FROM sale_master s LEFT JOIN customers c ON s.customer_id = c.id WHERE s.id = $sale_id LIMIT 1");
        if(!$sale_q || mysqli_num_rows($sale_q) === 0) {
            $response['message'] = 'Sale invoice not found.';
        } else {
            $sale = mysqli_fetch_assoc($sale_q);
            $current_remaining = floatval($sale['remaining_amount']);
            $current_received = floatval($sale['received_amount']);
            $customer_id = intval($sale['customer_id']);
            $invoice_no = $sale['invoice_no'];

            if($amount > ($current_remaining + 0.01)) {
                $response['message'] = "Amount cannot exceed remaining balance of " . formatCurrency($current_remaining);
            } else {
                mysqli_begin_transaction($conn);
                try {
                    $new_received = $current_received + $amount;
                    $new_remaining = max(0, $current_remaining - $amount);

                    // Update payment_type: If fully paid, update to payment_method (cash/bank); if partial, mark 'partial'
                    $status_sql = "";
                    if($new_remaining <= 0) {
                        $status_sql = ", payment_type = '$payment_method'";
                        if($payment_method === 'bank' && $bank_account_id > 0) {
                            $status_sql .= ", bank_account_id = $bank_account_id";
                        }
                    } else {
                        $status_sql = ", payment_type = 'partial'";
                    }

                    // Update sale_master
                    $update_sale = "UPDATE sale_master SET received_amount = $new_received, remaining_amount = $new_remaining $status_sql WHERE id = $sale_id";
                    if(!mysqli_query($conn, $update_sale)) {
                        throw new Exception("Failed to update sale invoice: " . mysqli_error($conn));
                    }

                    // Log in customer_receipts if table exists
                    $check_rec_tbl = mysqli_query($conn, "SHOW TABLES LIKE 'customer_receipts'");
                    if($check_rec_tbl && mysqli_num_rows($check_rec_tbl) > 0) {
                        $insert_rec = "INSERT INTO customer_receipts (receipt_date, customer_id, payment_method, bank_account_id, reference_no, amount, remarks, created_by, created_at) 
                                       VALUES ('$payment_date', $customer_id, '$payment_method', " . ($bank_account_id > 0 ? $bank_account_id : "NULL") . ", '$reference_no', $amount, 'Payment for Invoice $invoice_no" . ($remarks ? " - $remarks" : "") . "', $user_id, NOW())";
                        @mysqli_query($conn, $insert_rec);
                    }

                    // Update customer current_balance and customer_ledger
                    if($customer_id > 0) {
                        $cust_q = mysqli_query($conn, "SELECT customer_code, current_balance FROM customers WHERE id = $customer_id");
                        if($cust_q && mysqli_num_rows($cust_q) > 0) {
                            $cust_row = mysqli_fetch_assoc($cust_q);
                            $is_walkin_cust = (($cust_row['customer_code'] ?? '') === 'WALK-IN' || !empty($sale['walk_in_customer_name']));
                            $new_cust_balance = $is_walkin_cust ? 0 : (floatval($cust_row['current_balance']) - $amount);
                            mysqli_query($conn, "UPDATE customers SET current_balance = $new_cust_balance WHERE id = $customer_id");

                            // Insert into customer_ledger
                            $ledger_sql = "INSERT INTO customer_ledger (date, customer_id, reference_type, reference_id, description, debit, credit, balance, created_at) 
                                           VALUES ('$payment_date', $customer_id, 'PAYMENT', $sale_id, 'Payment received for Invoice $invoice_no" . ($remarks ? " - $remarks" : "") . "', 0, $amount, $new_cust_balance, NOW())";
                            @mysqli_query($conn, $ledger_sql);
                        }
                    }

                    // Update cash_book or bank_book
                    if($payment_method === 'cash') {
                        $cash_bal_q = mysqli_query($conn, "SELECT SUM(debit) - SUM(credit) as balance FROM cash_book");
                        $cur_cash = 0;
                        if($cash_bal_q && mysqli_num_rows($cash_bal_q) > 0) {
                            $cur_cash = floatval(mysqli_fetch_assoc($cash_bal_q)['balance']);
                        }
                        $new_cash = $cur_cash + $amount;
                        $cash_sql = "INSERT INTO cash_book (date, reference_type, reference_id, description, debit, credit, balance, created_at) 
                                     VALUES ('$payment_date', 'SALE_PAYMENT', $sale_id, 'Payment received for Invoice $invoice_no" . ($remarks ? " - $remarks" : "") . "', $amount, 0, $new_cash, NOW())";
                        if(!mysqli_query($conn, $cash_sql)) {
                            throw new Exception("Failed to update cash book: " . mysqli_error($conn));
                        }
                    } elseif($payment_method === 'bank' && $bank_account_id > 0) {
                        $bank_bal_q = mysqli_query($conn, "SELECT SUM(debit) - SUM(credit) as balance FROM bank_book WHERE bank_account_id = $bank_account_id");
                        $cur_bank = 0;
                        if($bank_bal_q && mysqli_num_rows($bank_bal_q) > 0) {
                            $cur_bank = floatval(mysqli_fetch_assoc($bank_bal_q)['balance']);
                        }
                        $new_bank = $cur_bank + $amount;
                        $bank_sql = "INSERT INTO bank_book (date, bank_account_id, reference_type, reference_id, description, debit, credit, balance, created_at) 
                                     VALUES ('$payment_date', $bank_account_id, 'SALE_PAYMENT', $sale_id, 'Payment received for Invoice $invoice_no" . ($remarks ? " - $remarks" : "") . "', $amount, 0, $new_bank, NOW())";
                        if(!mysqli_query($conn, $bank_sql)) {
                            throw new Exception("Failed to update bank book: " . mysqli_error($conn));
                        }
                    }

                    mysqli_commit($conn);
                    $response['success'] = true;
                    $response['message'] = "Payment of " . formatCurrency($amount) . " received successfully for Invoice $invoice_no!";
                } catch(Exception $e) {
                    mysqli_rollback($conn);
                    $response['message'] = "Error saving payment: " . $e->getMessage();
                }
            }
        }
    }
}

while (ob_get_level()) { ob_end_clean(); }
echo json_encode($response);
exit();
