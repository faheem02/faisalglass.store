<?php
/**
 * Save Quotation AJAX Handler - Matching Save Sale Pattern with Payment Methods
 * Faysal Glass And Aluminium Centre
 */

session_start();
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

$response = ['success' => false, 'message' => ''];
$conn = null;

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

if(isset($_POST['save_quotation'])) {
    $quotation_date = mysqli_real_escape_string($conn, $_POST['quotation_date'] ?? date('Y-m-d'));
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $valid_until = !empty($_POST['valid_until']) ? "'" . mysqli_real_escape_string($conn, $_POST['valid_until']) . "'" : "NULL";
    $reference_no = mysqli_real_escape_string($conn, trim($_POST['reference_no'] ?? ''));
    $remarks = mysqli_real_escape_string($conn, trim($_POST['remarks'] ?? ''));
    $subtotal = floatval($_POST['subtotal'] ?? 0);
    $discount_amount = floatval($_POST['discount_amount'] ?? 0);
    $other_charges = floatval($_POST['other_charges'] ?? 0);
    $grand_total = floatval($_POST['grand_total'] ?? 0);
    $received_amount = floatval($_POST['received_amount'] ?? 0);
    $payment_type = mysqli_real_escape_string($conn, $_POST['payment_type'] ?? 'credit');
    $bank_account_id = isset($_POST['bank_account_id']) && !empty($_POST['bank_account_id']) ? intval($_POST['bank_account_id']) : 0;
    $status = isset($_POST['quotation_status']) ? mysqli_real_escape_string($conn, $_POST['quotation_status']) : 'draft';
    $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
    $hold_id = isset($_POST['hold_id']) ? intval($_POST['hold_id']) : 0;
    $created_by = $_SESSION['user_id'];
    
    // Calculate overall discount percentage
    $gross_subtotal = $subtotal + $discount_amount;
    $discount_percentage = $gross_subtotal > 0 ? ($discount_amount / $gross_subtotal * 100) : 0;
    
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
    
    // Parse product data
    $product_data = [];
    if(isset($_POST['product_data']) && !empty($_POST['product_data'])) {
        $decoded = json_decode($_POST['product_data'], true);
        if(is_array($decoded)) {
            $product_data = $decoded;
        }
    }
    
    // Fallback if product data sent via standard arrays (backward compatibility)
    if(empty($product_data) && isset($_POST['product_id']) && is_array($_POST['product_id'])) {
        for($i = 0; $i < count($_POST['product_id']); $i++) {
            if(!empty($_POST['product_id'][$i])) {
                $pid = intval($_POST['product_id'][$i]);
                $cH = floatval($_POST['client_height'][$i] ?? 0);
                $cW = floatval($_POST['client_width'][$i] ?? 0);
                $sH = $_POST['std_height'][$i] ?? 0;
                $sW = $_POST['std_width'][$i] ?? 0;
                $qty = floatval($_POST['quantity'][$i] ?? 1);
                $uPrice = floatval($_POST['unit_price'][$i] ?? 0);
                $ar = floatval($_POST['area'][$i] ?? 0);
                $totAr = floatval($_POST['total_area'][$i] ?? ($ar * $qty));
                $dPct = floatval($_POST['discount_percent'][$i] ?? 0);
                $amt = floatval($_POST['amount'][$i] ?? ($totAr * $uPrice));
                $netAmt = floatval($_POST['net_amount'][$i] ?? ($amt - ($amt * ($dPct / 100))));
                
                $product_data[] = [
                    'product_id' => $pid,
                    'client_height' => $cH,
                    'client_width' => $cW,
                    'client_size' => $cH . ' x ' . $cW,
                    'multiple_of' => 6,
                    'std_height' => $sH,
                    'std_width' => $sW,
                    'quantity' => $qty,
                    'area' => $ar,
                    'total_area' => $totAr,
                    'rate' => $uPrice,
                    'unit_price' => $uPrice,
                    'discount_percentage' => $dPct,
                    'amount' => $amt,
                    'net_amount' => $netAmt
                ];
            }
        }
    }
    
    // Validation
    if(empty($quotation_date)) {
        $response['message'] = "Quotation date is required!";
    } elseif($customer_id <= 0) {
        $response['message'] = "Please select a customer!";
    } elseif(count($product_data) == 0) {
        $response['message'] = "Please add at least one product!";
    } elseif($grand_total <= 0) {
        $response['message'] = "Grand total must be greater than 0!";
    } else {
        mysqli_begin_transaction($conn);
        
        try {
            // EDIT MODE: reverse previous stock + ledger + cash/bank entries
            if($edit_id > 0) {
                $old_master_q = mysqli_query($conn, "SELECT * FROM quotation_master WHERE id = $edit_id");
                $old_master = ($old_master_q && mysqli_num_rows($old_master_q) > 0) ? mysqli_fetch_assoc($old_master_q) : null;
                if(!$old_master) {
                    throw new Exception("Quotation to edit not found");
                }
                
                // 1. Restore stock consumed by old quotation
                $old_det_q = mysqli_query($conn, "SELECT product_id, area, quantity FROM quotation_details WHERE quotation_id = $edit_id");
                if($old_det_q) {
                    while($old_det = mysqli_fetch_assoc($old_det_q)) {
                        $pid = intval($old_det['product_id']);
                        $tot_area = floatval($old_det['area']) * floatval($old_det['quantity']);
                        
                        $posted_q = mysqli_query($conn, "SELECT id FROM inventory_ledger WHERE reference_type = 'QUOTATION' AND reference_id = $edit_id AND product_id = $pid LIMIT 1");
                        if($posted_q && mysqli_num_rows($posted_q) > 0) {
                            $stock_q = mysqli_query($conn, "SELECT balance_qty FROM inventory_ledger WHERE product_id = $pid ORDER BY id DESC LIMIT 1");
                            $cur = 0;
                            if($stock_q && mysqli_num_rows($stock_q) > 0) {
                                $cur = floatval(mysqli_fetch_assoc($stock_q)['balance_qty']);
                            }
                            $new_stock = $cur + $tot_area;
                            if(!mysqli_query($conn, "INSERT INTO inventory_ledger (date, product_id, reference_type, reference_id, qty_in, qty_out, balance_qty, unit_price, total_amount, remarks) VALUES (CURDATE(), $pid, 'ADJUSTMENT', $edit_id, $tot_area, 0, $new_stock, 0, 0, 'Quotation Edit Stock Restore')")) {
                                throw new Exception("Failed to restore stock: " . mysqli_error($conn));
                            }
                            mysqli_query($conn, "DELETE FROM inventory_ledger WHERE reference_type = 'QUOTATION' AND reference_id = $edit_id AND product_id = $pid");
                        }
                    }
                }
                
                // 2. Remove old customer ledger entry + recompute customer balance
                $old_cust_id = intval($old_master['customer_id']);
                mysqli_query($conn, "DELETE FROM customer_ledger WHERE reference_type = 'QUOTATION' AND reference_id = $edit_id");
                $bal_q = mysqli_query($conn, "SELECT COALESCE(SUM(debit) - SUM(credit), 0) as balance FROM customer_ledger WHERE customer_id = $old_cust_id");
                $old_bal = ($bal_q && mysqli_num_rows($bal_q) > 0) ? floatval(mysqli_fetch_assoc($bal_q)['balance']) : 0;
                mysqli_query($conn, "UPDATE customers SET current_balance = $old_bal WHERE id = $old_cust_id");
                
                // 3. Remove old cash/bank book entries
                mysqli_query($conn, "DELETE FROM cash_book WHERE reference_type = 'QUOTATION' AND reference_id = $edit_id");
                mysqli_query($conn, "DELETE FROM bank_book WHERE reference_type = 'QUOTATION' AND reference_id = $edit_id");
                
                // 4. Remove old details
                mysqli_query($conn, "DELETE FROM quotation_details WHERE quotation_id = $edit_id");
                
                // 5. Update quotation_master
                $quotation_no = mysqli_real_escape_string($conn, $_POST['quotation_no'] ?? $old_master['quotation_no']);
                $bank_acc_sql = ($bank_account_id > 0) ? $bank_account_id : "NULL";
                $update_master = "UPDATE quotation_master SET 
                    quotation_no = '$quotation_no',
                    quotation_date = '$quotation_date',
                    customer_id = $customer_id,
                    valid_until = $valid_until,
                    subtotal = $subtotal,
                    discount_percentage = $discount_percentage,
                    discount_amount = $discount_amount,
                    other_charges = $other_charges,
                    grand_total = $grand_total,
                    received_amount = $received_amount,
                    remaining_amount = $remaining_amount,
                    payment_type = '$payment_type',
                    bank_account_id = $bank_acc_sql,
                    reference_no = '$reference_no',
                    remarks = '$remarks',
                    status = '$status'
                    WHERE id = $edit_id";
                
                if(!mysqli_query($conn, $update_master)) {
                    throw new Exception("Failed to update quotation: " . mysqli_error($conn));
                }
                $quotation_id = $edit_id;
            } else {
                // NEW QUOTATION MODE
                $prefix = "QTN";
                $inv_query = "SELECT quotation_no FROM quotation_master WHERE quotation_no LIKE '{$prefix}-%' ORDER BY id DESC LIMIT 1";
                $inv_result = mysqli_query($conn, $inv_query);
                $quotation_no = $prefix . "-00001";
                if($inv_result && mysqli_num_rows($inv_result) > 0) {
                    $row = mysqli_fetch_assoc($inv_result);
                    $last_no = $row['quotation_no'];
                    $number = intval(substr($last_no, 4)) + 1;
                    $quotation_no = $prefix . "-" . str_pad($number, 5, '0', STR_PAD_LEFT);
                }
                
                // Override if explicitly provided in POST and not empty
                if(!empty($_POST['quotation_no'])) {
                    $quotation_no = mysqli_real_escape_string($conn, $_POST['quotation_no']);
                }
                
                $bank_acc_sql = ($bank_account_id > 0) ? $bank_account_id : "NULL";
                $insert_master = "INSERT INTO quotation_master 
                    (quotation_no, quotation_date, customer_id, valid_until, subtotal, discount_percentage, discount_amount, other_charges, grand_total, received_amount, remaining_amount, payment_type, bank_account_id, reference_no, remarks, status, created_by, created_at) 
                    VALUES ('$quotation_no', '$quotation_date', $customer_id, $valid_until, $subtotal, $discount_percentage, $discount_amount, $other_charges, $grand_total, $received_amount, $remaining_amount, '$payment_type', $bank_acc_sql, '$reference_no', '$remarks', '$status', $created_by, NOW())";
                
                if(!mysqli_query($conn, $insert_master)) {
                    throw new Exception("Failed to save quotation: " . mysqli_error($conn));
                }
                $quotation_id = mysqli_insert_id($conn);
            }
            
            // Insert product details and update inventory
            foreach($product_data as $item) {
                $product_id = intval($item['product_id'] ?? 0);
                $client_height = floatval($item['client_height'] ?? 0);
                $client_width = floatval($item['client_width'] ?? 0);
                $client_size = mysqli_real_escape_string($conn, strval($item['client_size'] ?? ($client_height . ' x ' . $client_width)));
                $multiple = intval($item['multiple_of'] ?? 6);
                $std_height = mysqli_real_escape_string($conn, strval($item['std_height'] ?? 0));
                $std_width = mysqli_real_escape_string($conn, strval($item['std_width'] ?? 0));
                $uom = mysqli_real_escape_string($conn, strval($item['uom'] ?? 'Inch'));
                $quantity = floatval($item['quantity'] ?? 1);
                $area = floatval($item['area'] ?? 0);
                $total_area = floatval($item['total_area'] ?? ($area * $quantity));
                $rate = floatval($item['rate'] ?? ($item['unit_price'] ?? 0));
                $disc_pct = floatval($item['discount_percentage'] ?? 0);
                $amt = floatval($item['amount'] ?? ($total_area * $rate));
                $disc_amount_row = $amt * ($disc_pct / 100);
                $net_amount = floatval($item['net_amount'] ?? ($amt - $disc_amount_row));
                
                if($product_id <= 0) {
                    throw new Exception("Invalid product in quotation!");
                }
                
                $insert_detail = "INSERT INTO quotation_details 
                    (quotation_id, product_id, client_height, client_width, client_size, multiple_of, std_height, std_width, uom, quantity, unit_price, rate, area, amount, discount_percentage, discount_amount, net_amount) 
                    VALUES ($quotation_id, $product_id, $client_height, $client_width, '$client_size', $multiple, '$std_height', '$std_width', '$uom', $quantity, $rate, $rate, $area, $amt, $disc_pct, $disc_amount_row, $net_amount)";
                
                if(!mysqli_query($conn, $insert_detail)) {
                    throw new Exception("Failed to save quotation details: " . mysqli_error($conn));
                }
                
                // Deduct inventory (Total Area sq ft)
                $stock_query = "SELECT balance_qty FROM inventory_ledger WHERE product_id = $product_id ORDER BY id DESC LIMIT 1";
                $stock_result = mysqli_query($conn, $stock_query);
                $current_stock = 0;
                if($stock_result && mysqli_num_rows($stock_result) > 0) {
                    $current_stock = floatval(mysqli_fetch_assoc($stock_result)['balance_qty']);
                }
                
                $new_stock = $current_stock - $total_area;
                if($new_stock < 0) {
                    throw new Exception("Insufficient stock! Available: $current_stock sq ft, Requested: $total_area sq ft");
                }
                
                $inventory_query = "INSERT INTO inventory_ledger 
                    (date, product_id, reference_type, reference_id, qty_in, qty_out, balance_qty, unit_price, total_amount, remarks) 
                    VALUES ('$quotation_date', $product_id, 'QUOTATION', $quotation_id, 0, '$total_area', '$new_stock', '$rate', '$net_amount', 'Quotation: $quotation_no - Total Area: $total_area sq ft')";
                
                if(!mysqli_query($conn, $inventory_query)) {
                    throw new Exception("Failed to update inventory ledger: " . mysqli_error($conn));
                }
            }
            
            // Post to customer ledger (debit = grand_total, credit = received_amount)
            $product_names = [];
            foreach($product_data as $item) {
                $product_names[] = ($item['product_name'] ?? '') . ' (' . ($item['client_size'] ?? '') . ')';
            }
            $description = "Quotation: $quotation_no - " . implode(', ', array_slice($product_names, 0, 3));
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
            
            $customer_ledger_query = "INSERT INTO customer_ledger 
                (date, customer_id, reference_type, reference_id, description, debit, credit, balance) 
                VALUES ('$quotation_date', $customer_id, 'QUOTATION', $quotation_id, '" . mysqli_real_escape_string($conn, $description) . "', '$grand_total', '$received_amount', '$new_customer_balance')";
            
            if(!mysqli_query($conn, $customer_ledger_query)) {
                throw new Exception("Failed to update customer ledger: " . mysqli_error($conn));
            }
            
            mysqli_query($conn, "UPDATE customers SET current_balance = $new_customer_balance WHERE id = $customer_id");
            
            // Update cash or bank book for received amount (advance/payment)
            if($received_amount > 0) {
                if($payment_type == 'cash') {
                    $cash_bal_query = "SELECT SUM(debit) - SUM(credit) as balance FROM cash_book";
                    $cash_bal_result = mysqli_query($conn, $cash_bal_query);
                    $current_cash_balance = 0;
                    if($cash_bal_result && mysqli_num_rows($cash_bal_result) > 0) {
                        $current_cash_balance = floatval(mysqli_fetch_assoc($cash_bal_result)['balance']);
                    }
                    $new_cash_balance = $current_cash_balance + $received_amount;
                    $cash_query = "INSERT INTO cash_book (date, reference_type, reference_id, description, debit, credit, balance) 
                                   VALUES ('$quotation_date', 'QUOTATION', $quotation_id, 'Quotation Payment: $quotation_no', '$received_amount', 0, '$new_cash_balance')";
                    mysqli_query($conn, $cash_query);
                } elseif($payment_type == 'bank' && $bank_account_id > 0) {
                    $bank_bal_query = "SELECT SUM(debit) - SUM(credit) as balance FROM bank_book WHERE bank_account_id = $bank_account_id";
                    $bank_bal_result = mysqli_query($conn, $bank_bal_query);
                    $current_bank_balance = 0;
                    if($bank_bal_result && mysqli_num_rows($bank_bal_result) > 0) {
                        $current_bank_balance = floatval(mysqli_fetch_assoc($bank_bal_result)['balance']);
                    }
                    $new_bank_balance = $current_bank_balance + $received_amount;
                    $bank_query = "INSERT INTO bank_book (date, bank_account_id, reference_type, reference_id, description, debit, credit, balance) 
                                   VALUES ('$quotation_date', $bank_account_id, 'QUOTATION', $quotation_id, 'Quotation Payment: $quotation_no', '$received_amount', 0, '$new_bank_balance')";
                    mysqli_query($conn, $bank_query);
                }
            }
            
            // Remove hold quotation if loaded from hold
            if($hold_id > 0) {
                mysqli_query($conn, "DELETE FROM hold_quotations_details WHERE hold_id = $hold_id");
                mysqli_query($conn, "DELETE FROM hold_quotations_master WHERE id = $hold_id");
            }
            
            mysqli_commit($conn);
            
            $response = [
                'success' => true,
                'message' => ($edit_id > 0 ? 'Quotation updated' : 'Quotation saved') . ' successfully!',
                'quotation_id' => $quotation_id,
                'quotation_no' => $quotation_no,
                'remaining_amount' => $remaining_amount
            ];
            
        } catch(Throwable $e) {
            mysqli_rollback($conn);
            $response = ['success' => false, 'message' => $e->getMessage()];
            error_log("Quotation Error: " . $e->getMessage());
        }
    }
} else {
    $response = ['success' => false, 'message' => 'Invalid request'];
}

while (ob_get_level()) { ob_end_clean(); }
echo json_encode($response);
mysqli_close($conn);
?>