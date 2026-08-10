<?php
/**
 * Save Sale AJAX Handler - FIXED: Inventory reduction uses Total Area (sq ft)
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

if(isset($_POST['save_sale'])) {
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
    
    // Calculate remaining amount based on payment type
    if($payment_type == 'credit') {
        $remaining_amount = $grand_total;
        $received_amount = 0;
    } elseif($payment_type == 'cash' || $payment_type == 'bank') {
        $remaining_amount = 0;
        $received_amount = $grand_total;
    } else { // partial
        $remaining_amount = $grand_total - $received_amount;
    }
    
    // Get product data from JSON
    $product_data_json = $_POST['product_data'];
    $product_data = json_decode($product_data_json, true);
    
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
            
            if(!mysqli_query($conn, $insert_master)) {
                throw new Exception("Database error: " . mysqli_error($conn));
            }
            
            $sale_id = mysqli_insert_id($conn);
            
            // Insert sale details and update inventory
            foreach($product_data as $item) {
                $product_id = intval($item['product_id']);
                $client_height = floatval($item['client_height']);
                $client_width = floatval($item['client_width']);
                $client_size = mysqli_real_escape_string($conn, $item['client_size']);
                $multiple = intval($item['multiple_of']);
                $std_height = mysqli_real_escape_string($conn, $item['std_height']);
                $std_width = mysqli_real_escape_string($conn, $item['std_width']);
                $uom = mysqli_real_escape_string($conn, $item['uom']);
                $quantity_pieces = floatval($item['quantity']); // number of pieces
                $total_area = floatval($item['total_area']);   // total sq ft (area * quantity)
                $rate = floatval($item['rate']);
                $discount_percent = floatval($item['discount_percent']);
                $amount = floatval($item['amount']);
                
                $insert_detail = "INSERT INTO sale_details (sale_id, product_id, client_height, client_width, client_size,
                                  multiple_of, std_height, std_width, uom, quantity, area, rate, 
                                  discount_percentage, amount) 
                                  VALUES ('$sale_id', '$product_id', '$client_height', '$client_width', '$client_size',
                                  '$multiple', '$std_height', '$std_width', '$uom', '$quantity_pieces', '$total_area', 
                                  '$rate', '$discount_percent', '$amount')";
                
                if(!mysqli_query($conn, $insert_detail)) {
                    throw new Exception("Failed to save sale details: " . mysqli_error($conn));
                }
                
                // Update inventory (reduce stock) using TOTAL AREA (sq ft)
                $stock_query = "SELECT balance_qty FROM inventory_ledger WHERE product_id = $product_id ORDER BY id DESC LIMIT 1";
                $stock_result = mysqli_query($conn, $stock_query);
                $current_stock = 0;
                if($stock_result && mysqli_num_rows($stock_result) > 0) {
                    $stock_data = mysqli_fetch_assoc($stock_result);
                    $current_stock = floatval($stock_data['balance_qty']);
                }
                
                // Stock is stored in sq ft, so reduce by total_area
                $new_stock = $current_stock - $total_area;
                
                if($new_stock < 0) {
                    throw new Exception("Insufficient stock! Available: $current_stock sq ft, Requested: $total_area sq ft");
                }
                
                $inventory_query = "INSERT INTO inventory_ledger (date, product_id, reference_type, reference_id, 
                                    qty_in, qty_out, balance_qty, unit_price, total_amount, remarks) 
                                    VALUES ('$sale_date', '$product_id', 'SALE', '$sale_id', 
                                    0, '$total_area', '$new_stock', '$rate', '$amount', 'Sale Invoice: $invoice_no - Total Area: $total_area sq ft')";
                
                if(!mysqli_query($conn, $inventory_query)) {
                    throw new Exception("Failed to update inventory ledger");
                }
            }
            
            // Customer ledger entry for remaining amount (credit/partial)
            $ledger_debit_amount = $remaining_amount;
            
            if($ledger_debit_amount > 0) {
                $customer_balance_query = "SELECT SUM(debit) - SUM(credit) as balance FROM customer_ledger WHERE customer_id = $customer_id";
                $customer_balance_result = mysqli_query($conn, $customer_balance_query);
                $current_customer_balance = 0;
                
                if($customer_balance_result && mysqli_num_rows($customer_balance_result) > 0) {
                    $bal_data = mysqli_fetch_assoc($customer_balance_result);
                    $current_customer_balance = floatval($bal_data['balance']);
                }
                
                $new_customer_balance = $current_customer_balance + $ledger_debit_amount;
                
                $product_names = [];
                foreach($product_data as $item) {
                    $product_names[] = $item['product_name'] . ' (' . $item['client_size'] . ')';
                }
                $description = "Sale Invoice: $invoice_no - " . implode(', ', array_slice($product_names, 0, 3));
                if(count($product_names) > 3) {
                    $description .= " and " . (count($product_names) - 3) . " more items";
                }
                
                $customer_ledger_query = "INSERT INTO customer_ledger (date, customer_id, reference_type, reference_id, 
                                          description, debit, credit, balance) 
                                          VALUES ('$sale_date', '$customer_id', 'SALE', '$sale_id', 
                                          '" . mysqli_real_escape_string($conn, $description) . "', '$ledger_debit_amount', 0, '$new_customer_balance')";
                
                if(!mysqli_query($conn, $customer_ledger_query)) {
                    throw new Exception("Failed to update customer ledger: " . mysqli_error($conn));
                }
                
                $update_customer = "UPDATE customers SET current_balance = $new_customer_balance WHERE id = $customer_id";
                mysqli_query($conn, $update_customer);
            }
            
            // Update cash or bank book for received amount
            if($received_amount > 0) {
                if($payment_type == 'cash') {
                    $cash_bal_query = "SELECT SUM(debit) - SUM(credit) as balance FROM cash_book";
                    $cash_bal_result = mysqli_query($conn, $cash_bal_query);
                    $current_cash_balance = 0;
                    if($cash_bal_result && mysqli_num_rows($cash_bal_result) > 0) {
                        $cash_bal_data = mysqli_fetch_assoc($cash_bal_result);
                        $current_cash_balance = floatval($cash_bal_data['balance']);
                    }
                    
                    $new_cash_balance = $current_cash_balance + $received_amount;
                    
                    $cash_query = "INSERT INTO cash_book (date, reference_type, reference_id, description, 
                                  debit, credit, balance) 
                                  VALUES ('$sale_date', 'SALE', '$sale_id', 
                                  'Sale Payment: $invoice_no', '$received_amount', 0, '$new_cash_balance')";
                    mysqli_query($conn, $cash_query);
                } 
                elseif($payment_type == 'bank' && $bank_account_id > 0) {
                    $bank_bal_query = "SELECT SUM(debit) - SUM(credit) as balance FROM bank_book WHERE bank_account_id = $bank_account_id";
                    $bank_bal_result = mysqli_query($conn, $bank_bal_query);
                    $current_bank_balance = 0;
                    if($bank_bal_result && mysqli_num_rows($bank_bal_result) > 0) {
                        $bank_bal_data = mysqli_fetch_assoc($bank_bal_result);
                        $current_bank_balance = floatval($bank_bal_data['balance']);
                    }
                    
                    $new_bank_balance = $current_bank_balance + $received_amount;
                    
                    $bank_query = "INSERT INTO bank_book (date, bank_account_id, reference_type, reference_id, 
                                  description, debit, credit, balance) 
                                  VALUES ('$sale_date', '$bank_account_id', 'SALE', '$sale_id', 
                                  'Sale Payment: $invoice_no', '$received_amount', 0, '$new_bank_balance')";
                    mysqli_query($conn, $bank_query);
                }
            }
            
            mysqli_commit($conn);
            // After successful sale, if this sale was loaded from a hold bill, remove/update hold
            if(isset($_POST['hold_id']) && !empty($_POST['hold_id'])) {
                $hold_id = intval($_POST['hold_id']);
                // Option 1: Delete the hold bill
                mysqli_query($conn, "DELETE FROM hold_sales_master WHERE id = $hold_id");
                // Option 2: Mark as converted (if you prefer to keep history)
                // mysqli_query($conn, "UPDATE hold_sales_master SET status = 'converted' WHERE id = $hold_id");
            }
            $response['success'] = true;
            $response['message'] = "Sale invoice created successfully!";
            $response['invoice_no'] = $invoice_no;
            $response['customer_id'] = $customer_id;
            $response['remaining_amount'] = $remaining_amount;
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $response['message'] = $e->getMessage();
            error_log("Sale Error: " . $e->getMessage());
        }
    }
}

while (ob_get_level()) {
    ob_end_clean();
}
echo json_encode($response);
mysqli_close($conn);
exit();
?>