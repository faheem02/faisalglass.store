<?php
/**
 * Save Sale AJAX Handler - FIXED: Inventory reduction uses Total Area (sq ft)
 * Faysal Glass And Aluminium Centre
 *
 * FIXED VERSION - bugs corrected (see chat notes):
 * 1. ROOT CAUSE of "Failed to save sale invoice!": catch(Exception) does NOT
 *    catch TypeError/Error (e.g. mysqli_fetch_assoc() called on a failed/false
 *    query result in the edit-mode reversal block). That escaped the try/catch
 *    as an uncaught fatal. With error_reporting(0)/display_errors=0 this produced
 *    a completely BLANK HTTP response, which is not valid JSON, so jQuery's
 *    dataType:'json' parse failed -> triggered the generic error: callback.
 *    Verified by reproducing it: mysqli_fetch_assoc(false) throws a TypeError,
 *    and catch(Exception) does not catch it, but catch(Throwable) does.
 * 2. Widened catch(Exception) -> catch(Throwable) everywhere.
 * 3. Added register_shutdown_function as a safety net for any fatal that
 *    happens outside the try block entirely (e.g. inside database.php/txt.php
 *    includes) - it now always returns valid JSON with the real PHP error
 *    message + line instead of a blank response.
 * 4. Fixed discount key mismatch: was reading $item['discount_percent'], but
 *    add_sale.php's collectProductData() now sends 'discount_percentage'
 *    (matches the DB column name and the hold-bill handler). Was silently
 *    saving discount as 0 for every sale.
 * 5. Defaulted $item['uom'] (frontend never sends this field - "UOM Removed"
 *    per file header) so it no longer relies on a suppressed undefined-index.
 * 6. Guarded the unguarded mysqli_fetch_assoc($old_details) loop in edit mode
 *    with a truthy check on the query result.
 * 7. Added null-coalescing defaults on $_POST reads and validated the
 *    json_decode() result explicitly.
 * 8. Wrapped the whole script in ob_start()/ob_end_clean() so any stray
 *    warning/notice output from included files can never corrupt the JSON body.
 */

session_start();
header('Content-Type: application/json');

// Keep errors OUT of the response body (so JSON never gets corrupted), but
// keep them ENABLED and LOGGED so real bugs are visible in the PHP error log
// and can also be surfaced via the shutdown handler below.
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Buffer all output so any stray echo/warning from included files never
// leaks into the JSON body.
ob_start();

$response = ['success' => false, 'message' => ''];
$conn = null;

// Safety net: if a fatal error happens ANYWHERE in this script (including
// inside included files, or anything that escapes try/catch), still return
// valid JSON instead of a blank/broken response.
register_shutdown_function(function() use (&$response) {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        global $conn;
        if (isset($conn) && $conn) {
            @mysqli_rollback($conn);
        }
        while (ob_get_level()) { ob_end_clean(); }
        echo json_encode([
            'success' => false,
            'message' => 'Server error: ' . $error['message'] . ' (line ' . $error['line'] . ')'
        ]);
    }
});

if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    while (ob_get_level()) { ob_end_clean(); }
    echo json_encode(['success' => false, 'message' => 'Unauthorized access!']);
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

if(isset($_POST['save_sale'])) {
    $sale_date = mysqli_real_escape_string($conn, $_POST['sale_date'] ?? date('Y-m-d'));
    $customer_id = intval($_POST['customer_id'] ?? 0);
    if($customer_id <= 0) {
        $walkin_q = mysqli_query($conn, "SELECT id FROM customers WHERE customer_code = 'WALK-IN' OR customer_name = 'Walk-in Customer' LIMIT 1");
        if($walkin_q && mysqli_num_rows($walkin_q) > 0) {
            $customer_id = intval(mysqli_fetch_assoc($walkin_q)['id']);
        }
    }
    $subtotal = floatval($_POST['subtotal'] ?? 0);
    $other_charges = floatval($_POST['other_charges'] ?? 0);
    $grand_total = floatval($_POST['grand_total'] ?? 0);
    $received_amount = floatval($_POST['received_amount'] ?? 0);
    $payment_type = mysqli_real_escape_string($conn, $_POST['payment_type'] ?? 'cash');
    $bank_account_id = isset($_POST['bank_account_id']) ? intval($_POST['bank_account_id']) : 0;
    $reference_no = mysqli_real_escape_string($conn, trim($_POST['reference_no'] ?? ''));
    $remarks = mysqli_real_escape_string($conn, trim($_POST['remarks'] ?? ''));
    $walk_in_customer_name = mysqli_real_escape_string($conn, trim($_POST['walk_in_customer_name'] ?? ''));
    $walk_in_customer_phone = mysqli_real_escape_string($conn, trim($_POST['walk_in_customer_phone'] ?? ''));
    $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
    
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
    $product_data_json = $_POST['product_data'] ?? '';
    $product_data = json_decode($product_data_json, true);
    $product_data_valid = (json_last_error() === JSON_ERROR_NONE) && is_array($product_data);
    
    // Validation
    if(empty($sale_date)) {
        $response['message'] = "Sale date is required!";
    } elseif($customer_id <= 0) {
        $response['message'] = "Please select a customer!";
    } elseif(!$product_data_valid || count($product_data) == 0) {
        $response['message'] = "Please add at least one product!";
    } elseif($grand_total <= 0) {
        $response['message'] = "Grand total must be greater than 0!";
    } else {
        mysqli_begin_transaction($conn);
        
        try {
            if ($edit_id > 0) {
                // EDIT MODE: Reverse old entries, then apply new ones
                $sale_id = $edit_id;
                $invoice_no = mysqli_real_escape_string($conn, $_POST['invoice_no'] ?? '');
                
                // Reverse inventory: add back stock from old sale details
                $old_details = mysqli_query($conn, "SELECT * FROM sale_details WHERE sale_id = $sale_id");
                if ($old_details === false) {
                    throw new Exception("Database error reading old sale details: " . mysqli_error($conn));
                }
                while ($old = mysqli_fetch_assoc($old_details)) {
                    $pid = $old['product_id'];
                    $old_area = floatval($old['area']);
                    $stock_q = "SELECT balance_qty FROM inventory_ledger WHERE product_id = $pid ORDER BY id DESC LIMIT 1";
                    $stock_r = mysqli_query($conn, $stock_q);
                    $cur = 0;
                    if ($stock_r && mysqli_num_rows($stock_r) > 0) {
                        $cur = floatval(mysqli_fetch_assoc($stock_r)['balance_qty']);
                    }
                    $new_stock = $cur + $old_area;
                    mysqli_query($conn, "INSERT INTO inventory_ledger (date, product_id, reference_type, reference_id, qty_in, qty_out, balance_qty, unit_price, total_amount, remarks) VALUES (CURDATE(), $pid, 'ADJUSTMENT', $sale_id, $old_area, 0, $new_stock, 0, 0, 'Sale Edit Reversal: $invoice_no')");
                }
                
                // Remove old ledger/cash/bank entries
                mysqli_query($conn, "DELETE FROM customer_ledger WHERE reference_type = 'SALE' AND reference_id = $sale_id");
                mysqli_query($conn, "DELETE FROM cash_book WHERE reference_type = 'SALE' AND reference_id = $sale_id");
                mysqli_query($conn, "DELETE FROM bank_book WHERE reference_type = 'SALE' AND reference_id = $sale_id");
                
                // Delete old sale details
                mysqli_query($conn, "DELETE FROM sale_details WHERE sale_id = $sale_id");
                
                // Update sale_master (keep same record)
                $update_master = "UPDATE sale_master SET 
                    sale_date = '$sale_date', customer_id = '$customer_id', 
                    walk_in_customer_name = '$walk_in_customer_name', walk_in_customer_phone = '$walk_in_customer_phone',
                    subtotal = '$subtotal', discount_percentage = 0, discount_amount = 0, 
                    other_charges = '$other_charges', grand_total = '$grand_total', 
                    received_amount = '$received_amount', remaining_amount = '$remaining_amount', 
                    payment_type = '$payment_type', bank_account_id = '$bank_account_id', 
                    reference_no = '$reference_no', remarks = '$remarks' 
                    WHERE id = $sale_id";
                if (!mysqli_query($conn, $update_master)) {
                    throw new Exception("Database error: " . mysqli_error($conn));
                }
            } else {
                // NEW SALE MODE
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
                $insert_master = "INSERT INTO sale_master (invoice_no, sale_date, customer_id, walk_in_customer_name, walk_in_customer_phone, subtotal, 
                                  discount_percentage, discount_amount, other_charges, grand_total, received_amount, 
                                  remaining_amount, payment_type, bank_account_id, reference_no, remarks, created_by) 
                                  VALUES ('$invoice_no', '$sale_date', '$customer_id', '$walk_in_customer_name', '$walk_in_customer_phone', '$subtotal', 
                                  0, 0, '$other_charges', '$grand_total', '$received_amount', 
                                  '$remaining_amount', '$payment_type', '$bank_account_id', 
                                  '$reference_no', '$remarks', '{$_SESSION['user_id']}')";
                
                if(!mysqli_query($conn, $insert_master)) {
                    throw new Exception("Database error: " . mysqli_error($conn));
                }
                
                $sale_id = mysqli_insert_id($conn);
            }
            
            // Insert sale details and update inventory
            foreach($product_data as $item) {
                $product_id = intval($item['product_id'] ?? 0);
                $client_height = floatval($item['client_height'] ?? 0);
                $client_width = floatval($item['client_width'] ?? 0);
                $client_size = mysqli_real_escape_string($conn, $item['client_size'] ?? '');
                $multiple = ($item['multiple_of'] === 'manual') ? 0 : intval($item['multiple_of'] ?? 0);
                $std_height = mysqli_real_escape_string($conn, (string)($item['std_height'] ?? 0));
                $std_width = mysqli_real_escape_string($conn, (string)($item['std_width'] ?? 0));
                $uom = mysqli_real_escape_string($conn, $item['uom'] ?? '');
                $quantity_pieces = floatval($item['quantity'] ?? 0); // number of pieces
                $total_area = floatval($item['total_area'] ?? 0);   // total sq ft (area * quantity)
                $rate = floatval($item['rate'] ?? 0);
                $discount_percentage = floatval($item['discount_percentage'] ?? 0);
                $amount = floatval($item['amount'] ?? 0);
                
                if ($product_id <= 0) {
                    throw new Exception("Invalid product in cart - please re-select the product and try again.");
                }
                
                $insert_detail = "INSERT INTO sale_details (sale_id, product_id, client_height, client_width, client_size,
                                  multiple_of, std_height, std_width, uom, quantity, area, rate, 
                                  discount_percentage, amount) 
                                  VALUES ('$sale_id', '$product_id', '$client_height', '$client_width', '$client_size',
                                  '$multiple', '$std_height', '$std_width', '$uom', '$quantity_pieces', '$total_area', 
                                  '$rate', '$discount_percentage', '$amount')";
                
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
                    throw new Exception("Failed to update inventory ledger: " . mysqli_error($conn));
                }
            }
            
            // Customer ledger entry - always record the sale (debit = grand_total, credit = received_amount)
            $product_names = [];
            foreach($product_data as $item) {
                $product_names[] = ($item['product_name'] ?? '') . ' (' . ($item['client_size'] ?? '') . ')';
            }
            $description = "Sale Invoice: $invoice_no - " . implode(', ', array_slice($product_names, 0, 3));
            if(count($product_names) > 3) {
                $description .= " and " . (count($product_names) - 3) . " more items";
            }
            
            $customer_balance_query = "SELECT SUM(debit) - SUM(credit) as balance FROM customer_ledger WHERE customer_id = $customer_id";
            $customer_balance_result = mysqli_query($conn, $customer_balance_query);
            $current_customer_balance = 0;
            
            if($customer_balance_result && mysqli_num_rows($customer_balance_result) > 0) {
                $bal_data = mysqli_fetch_assoc($customer_balance_result);
                $current_customer_balance = floatval($bal_data['balance']);
            }
            
            $new_customer_balance = $current_customer_balance + $grand_total - $received_amount;
            
            $customer_ledger_query = "INSERT INTO customer_ledger (date, customer_id, reference_type, reference_id, 
                                      description, debit, credit, balance) 
                                      VALUES ('$sale_date', '$customer_id', 'SALE', '$sale_id', 
                                      '" . mysqli_real_escape_string($conn, $description) . "', '$grand_total', '$received_amount', '$new_customer_balance')";
            
            if(!mysqli_query($conn, $customer_ledger_query)) {
                throw new Exception("Failed to update customer ledger: " . mysqli_error($conn));
            }
            
            // Check if this is a walk-in customer
            $check_walkin = mysqli_query($conn, "SELECT customer_code FROM customers WHERE id = $customer_id LIMIT 1");
            $is_walkin_cust = false;
            if($check_walkin && mysqli_num_rows($check_walkin) > 0) {
                $c_row = mysqli_fetch_assoc($check_walkin);
                if(($c_row['customer_code'] ?? '') === 'WALK-IN' || !empty($walk_in_customer_name)) {
                    $is_walkin_cust = true;
                }
            }
            
            // For walk-in customer, current_balance must always remain 0
            if($is_walkin_cust) {
                $update_customer = "UPDATE customers SET current_balance = 0 WHERE id = $customer_id";
            } else {
                $update_customer = "UPDATE customers SET current_balance = $new_customer_balance WHERE id = $customer_id";
            }
            mysqli_query($conn, $update_customer);
            
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
            
            // If this sale was loaded from a hold bill, remove the hold bill
            // (inside the transaction so it commits/rolls back with the sale)
            if(isset($_POST['hold_id']) && !empty($_POST['hold_id'])) {
                $hold_id = intval($_POST['hold_id']);
                $hold_check = mysqli_query($conn, "SELECT status FROM hold_sales_master WHERE id = $hold_id");
                if($hold_check && mysqli_num_rows($hold_check) > 0) {
                    $hold_status = mysqli_fetch_assoc($hold_check)['status'];
                    if($hold_status == 'hold') {
                        if(!mysqli_query($conn, "DELETE FROM hold_sales_details WHERE hold_id = $hold_id") ||
                           !mysqli_query($conn, "DELETE FROM hold_sales_master WHERE id = $hold_id")) {
                            throw new Exception("Failed to remove hold bill: " . mysqli_error($conn));
                        }
                    }
                }
            }
            
            mysqli_commit($conn);
            $response['success'] = true;
            $response['message'] = $edit_id > 0 ? "Sale invoice updated successfully!" : "Sale invoice created successfully!";
            $response['invoice_no'] = $invoice_no;
            $response['customer_id'] = $customer_id;
            $response['remaining_amount'] = $remaining_amount;
            
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $response['message'] = $e->getMessage();
            error_log("Sale Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
        }
    }
}

while (ob_get_level()) {
    ob_end_clean();
}
echo json_encode($response);
if ($conn) {
    mysqli_close($conn);
}
exit(); 
?> 