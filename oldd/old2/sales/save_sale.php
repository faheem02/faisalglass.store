<?php
/**
 * Save Sale AJAX Handler - FIXED: Correct bind_param for edit/update
 * Faysal Glass And Aluminium Centre
 */

session_start();
header('Content-Type: application/json');

error_reporting(0);
ini_set('display_errors', 0);

if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access!']);
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$response = ['success' => false, 'message' => ''];

// Check if this is an update (edit) or new sale
$is_update = isset($_POST['update_sale']) && $_POST['update_sale'] == '1';
$sale_id = isset($_POST['sale_id']) ? intval($_POST['sale_id']) : 0;

if($is_update && $sale_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid sale ID for update']);
    exit;
}

// Common data
$sale_date = mysqli_real_escape_string($conn, $_POST['sale_date']);
$customer_id = intval($_POST['customer_id']);
$subtotal = floatval($_POST['subtotal']);
$other_charges = floatval($_POST['other_charges']);
$grand_total = floatval($_POST['grand_total']);
$received_amount = floatval($_POST['received_amount']);
$payment_type = mysqli_real_escape_string($conn, $_POST['payment_type']);
$bank_account_id = isset($_POST['bank_account_id']) ? intval($_POST['bank_account_id']) : 0;
$reference_no = mysqli_real_escape_string($conn, trim($_POST['reference_no']));
$remarks = mysqli_real_escape_string($conn, trim($_POST['remarks']));
$product_data = json_decode($_POST['product_data'], true);

// Calculate remaining
if($payment_type == 'credit') {
    $remaining_amount = $grand_total;
    $received_amount = 0;
} elseif($payment_type == 'cash' || $payment_type == 'bank') {
    $remaining_amount = 0;
    $received_amount = $grand_total;
} else { // partial
    $remaining_amount = $grand_total - $received_amount;
}

// Validation
if(empty($sale_date)) {
    $response['message'] = "Sale date is required!";
} elseif($customer_id <= 0) {
    $response['message'] = "Please select a customer!";
} elseif(empty($product_data) || count($product_data) == 0) {
    $response['message'] = "Please add at least one product!";
} elseif($grand_total <= 0) {
    $response['message'] = "Grand total must be greater than 0!";
} else {
    mysqli_begin_transaction($conn);
    try {
        // ---------- IF UPDATE, REVERSE OLD DATA ----------
        if($is_update) {
            // Restore inventory from old sale details
            $old_details = mysqli_query($conn, "SELECT * FROM sale_details WHERE sale_id = $sale_id");
            while($old = mysqli_fetch_assoc($old_details)) {
                $product_id = $old['product_id'];
                $qty_out = floatval($old['quantity']); // This is total_area (sq ft)
                // Get current stock
                $stock_q = mysqli_query($conn, "SELECT balance_qty FROM inventory_ledger WHERE product_id = $product_id ORDER BY id DESC LIMIT 1");
                $stock_row = mysqli_fetch_assoc($stock_q);
                $current_stock = $stock_row ? floatval($stock_row['balance_qty']) : 0;
                $new_stock = $current_stock + $qty_out;
                // Insert adjustment to add back stock
                $inv_insert = "INSERT INTO inventory_ledger (date, product_id, reference_type, reference_id, qty_in, qty_out, balance_qty, unit_price, total_amount, remarks) 
                               VALUES (CURDATE(), $product_id, 'ADJUSTMENT', $sale_id, $qty_out, 0, $new_stock, 0, 0, 'Restored from edit of invoice')";
                mysqli_query($conn, $inv_insert);
            }
            // Delete old sale details, customer ledger, cash/bank entries
            mysqli_query($conn, "DELETE FROM sale_details WHERE sale_id = $sale_id");
            mysqli_query($conn, "DELETE FROM customer_ledger WHERE reference_type = 'SALE' AND reference_id = $sale_id");
            mysqli_query($conn, "DELETE FROM cash_book WHERE reference_type = 'SALE' AND reference_id = $sale_id");
            mysqli_query($conn, "DELETE FROM bank_book WHERE reference_type = 'SALE' AND reference_id = $sale_id");
            // Update sale_master record with new totals (keep invoice_no)
            $update_master = "UPDATE sale_master SET 
                              sale_date = '$sale_date',
                              customer_id = $customer_id,
                              subtotal = $subtotal,
                              discount_percentage = 0,
                              discount_amount = 0,
                              other_charges = $other_charges,
                              grand_total = $grand_total,
                              received_amount = $received_amount,
                              remaining_amount = $remaining_amount,
                              payment_type = '$payment_type',
                              bank_account_id = $bank_account_id,
                              reference_no = '$reference_no',
                              remarks = '$remarks',
                              created_by = '{$_SESSION['user_id']}'
                              WHERE id = $sale_id";
            mysqli_query($conn, $update_master);
            $new_sale_id = $sale_id;
            $invoice_no = mysqli_fetch_assoc(mysqli_query($conn, "SELECT invoice_no FROM sale_master WHERE id = $sale_id"))['invoice_no'];
        } else {
            // ---------- NEW SALE ----------
            // Generate invoice number
            $prefix = "SAL";
            $inv_query = "SELECT invoice_no FROM sale_master WHERE invoice_no LIKE '{$prefix}%' ORDER BY id DESC LIMIT 1";
            $inv_result = mysqli_query($conn, $inv_query);
            $invoice_no = $prefix . "-00001";
            if($inv_result && mysqli_num_rows($inv_result) > 0) {
                $row = mysqli_fetch_assoc($inv_result);
                $last_no = $row['invoice_no'];
                $number = intval(substr($last_no, 4)) + 1;
                $invoice_no = $prefix . "-" . str_pad($number, 5, '0', STR_PAD_LEFT);
            }
            // Insert into sale_master
            $insert_master = "INSERT INTO sale_master (invoice_no, sale_date, customer_id, subtotal, 
                              discount_percentage, discount_amount, other_charges, grand_total, received_amount, 
                              remaining_amount, payment_type, bank_account_id, reference_no, remarks, created_by) 
                              VALUES ('$invoice_no', '$sale_date', '$customer_id', '$subtotal', 
                              0, 0, '$other_charges', '$grand_total', '$received_amount', 
                              '$remaining_amount', '$payment_type', '$bank_account_id', 
                              '$reference_no', '$remarks', '{$_SESSION['user_id']}')";
            mysqli_query($conn, $insert_master);
            $new_sale_id = mysqli_insert_id($conn);
        }

        // ---------- INSERT NEW SALE DETAILS ----------
        $stmt_detail = mysqli_prepare($conn, "INSERT INTO sale_details (sale_id, product_id, client_height, client_width, client_size, multiple_of, std_height, std_width, uom, quantity, area, raw_area, total_area, rate, discount_percentage, amount) 
                                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach($product_data as $item) {
            $product_id = intval($item['product_id']);
            $client_height = floatval($item['client_height']);
            $client_width = floatval($item['client_width']);
            $client_size = mysqli_real_escape_string($conn, $item['client_size']);
            $multiple_of = intval($item['multiple_of']);
            $std_height = floatval($item['std_height']);
            $std_width = floatval($item['std_width']);
            $uom = isset($item['uom']) ? mysqli_real_escape_string($conn, $item['uom']) : 'Inch';
            $quantity = floatval($item['quantity']);
            $area = floatval($item['area']); // area per piece
            $raw_area = isset($item['raw_area']) ? floatval($item['raw_area']) : 0;
            $total_area = floatval($item['total_area']); // area * quantity
            $rate = floatval($item['rate']);
            $discount_percent = floatval($item['discount_percent']);
            $amount = floatval($item['amount']);

            // ✅ CORRECT bind_param: 16 parameters with correct types
            mysqli_stmt_bind_param($stmt_detail, "iiddsiddsddddddd", 
                $new_sale_id, $product_id, $client_height, $client_width, $client_size, $multiple_of, $std_height, $std_width, $uom, $quantity, $area, $raw_area, $total_area, $rate, $discount_percent, $amount
            );
            if(!mysqli_stmt_execute($stmt_detail)) {
                throw new Exception("Failed to insert sale detail: " . mysqli_stmt_error($stmt_detail));
            }

            // ---------- INVENTORY LEDGER (reduce stock by total_area) ----------
            $stock_q = mysqli_query($conn, "SELECT balance_qty FROM inventory_ledger WHERE product_id = $product_id ORDER BY id DESC LIMIT 1");
            $stock_row = mysqli_fetch_assoc($stock_q);
            $current_stock = $stock_row ? floatval($stock_row['balance_qty']) : 0;
            $new_stock = $current_stock - $total_area;
            if($new_stock < 0) {
                throw new Exception("Insufficient stock! Available: $current_stock sq ft, Requested: $total_area sq ft");
            }
            $inv_insert = "INSERT INTO inventory_ledger (date, product_id, reference_type, reference_id, qty_in, qty_out, balance_qty, unit_price, total_amount, remarks) 
                           VALUES ('$sale_date', $product_id, 'SALE', $new_sale_id, 0, $total_area, $new_stock, $rate, $amount, 'Sale Invoice: $invoice_no - Total Area: $total_area sq ft')";
            mysqli_query($conn, $inv_insert);
        }

        // ---------- CUSTOMER LEDGER ----------
        $ledger_debit = $remaining_amount;
        if($ledger_debit > 0) {
            $cust_bal_q = mysqli_query($conn, "SELECT SUM(debit) - SUM(credit) as balance FROM customer_ledger WHERE customer_id = $customer_id");
            $cust_bal = mysqli_fetch_assoc($cust_bal_q);
            $current_cust_bal = $cust_bal ? floatval($cust_bal['balance']) : 0;
            $new_cust_bal = $current_cust_bal + $ledger_debit;
            // description
            $product_names = array_slice(array_column($product_data, 'product_name'), 0, 3);
            $desc = "Sale Invoice: $invoice_no - " . implode(', ', $product_names);
            if(count($product_data) > 3) $desc .= " and " . (count($product_data)-3) . " more items";
            $ledger_insert = "INSERT INTO customer_ledger (date, customer_id, reference_type, reference_id, description, debit, credit, balance) 
                              VALUES ('$sale_date', $customer_id, 'SALE', $new_sale_id, '" . mysqli_real_escape_string($conn, $desc) . "', $ledger_debit, 0, $new_cust_bal)";
            mysqli_query($conn, $ledger_insert);
            mysqli_query($conn, "UPDATE customers SET current_balance = $new_cust_bal WHERE id = $customer_id");
        }

        // ---------- CASH / BANK BOOK ----------
        if($received_amount > 0) {
            if($payment_type == 'cash') {
                $cash_bal_q = mysqli_query($conn, "SELECT SUM(debit) - SUM(credit) as balance FROM cash_book");
                $cash_bal = mysqli_fetch_assoc($cash_bal_q);
                $current_cash = $cash_bal ? floatval($cash_bal['balance']) : 0;
                $new_cash = $current_cash + $received_amount;
                $cash_insert = "INSERT INTO cash_book (date, reference_type, reference_id, description, debit, credit, balance) 
                                VALUES ('$sale_date', 'SALE', $new_sale_id, 'Sale Payment: $invoice_no', $received_amount, 0, $new_cash)";
                mysqli_query($conn, $cash_insert);
            } elseif($payment_type == 'bank' && $bank_account_id > 0) {
                $bank_bal_q = mysqli_query($conn, "SELECT SUM(debit) - SUM(credit) as balance FROM bank_book WHERE bank_account_id = $bank_account_id");
                $bank_bal = mysqli_fetch_assoc($bank_bal_q);
                $current_bank = $bank_bal ? floatval($bank_bal['balance']) : 0;
                $new_bank = $current_bank + $received_amount;
                $bank_insert = "INSERT INTO bank_book (date, bank_account_id, reference_type, reference_id, description, debit, credit, balance) 
                                VALUES ('$sale_date', $bank_account_id, 'SALE', $new_sale_id, 'Sale Payment: $invoice_no', $received_amount, 0, $new_bank)";
                mysqli_query($conn, $bank_insert);
            }
        }

        // If this sale was loaded from a hold bill, delete the hold record
        if(isset($_POST['hold_id']) && !empty($_POST['hold_id'])) {
            $hold_id = intval($_POST['hold_id']);
            mysqli_query($conn, "DELETE FROM hold_sales_master WHERE id = $hold_id");
        }

        mysqli_commit($conn);
        $response['success'] = true;
        $response['message'] = $is_update ? "Sale updated successfully!" : "Sale saved successfully!";
        $response['invoice_no'] = $invoice_no;
        $response['sale_id'] = $new_sale_id;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $response['message'] = "Error: " . $e->getMessage();
        error_log("Sale Save Error: " . $e->getMessage());
    }
}

while (ob_get_level()) { ob_end_clean(); }
echo json_encode($response);
mysqli_close($conn);
exit();
?>