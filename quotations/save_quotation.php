<?php
/**
 * Save Quotation Handler - WITH HOLD STATUS
 * Faysal Glass And Aluminium Centre
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

header('Content-Type: application/json');

if(isset($_POST['save_quotation'])) {
    $quotation_date = mysqli_real_escape_string($conn, $_POST['quotation_date']);
    $quotation_no = mysqli_real_escape_string($conn, $_POST['quotation_no']);
    $customer_id = intval($_POST['customer_id']);
    $valid_until = !empty($_POST['valid_until']) ? "'" . mysqli_real_escape_string($conn, $_POST['valid_until']) . "'" : "NULL";
    $reference_no = mysqli_real_escape_string($conn, $_POST['reference_no']);
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
    $subtotal = floatval($_POST['subtotal']);
    $discount_percentage = floatval($_POST['discount_percentage']);
    $discount_amount = floatval($_POST['discount_amount']);
    $other_charges = floatval($_POST['other_charges']);
    $grand_total = floatval($_POST['grand_total']);
    $created_by = $_SESSION['user_id'];
    
    // NEW: Get status from POST, default 'draft'
    $status = isset($_POST['quotation_status']) ? mysqli_real_escape_string($conn, $_POST['quotation_status']) : 'draft';
    // Validate allowed statuses
    $allowed_statuses = ['draft', 'hold', 'pending', 'approved', 'rejected', 'converted'];
    if(!in_array($status, $allowed_statuses)) {
        $status = 'draft';
    }
    
    // If this quotation was loaded from a hold quotation, the hold is removed after a successful save
    $hold_id = isset($_POST['hold_id']) ? intval($_POST['hold_id']) : 0;
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Insert into quotation_master with status
        $insert_master = "INSERT INTO quotation_master (quotation_no, quotation_date, customer_id, valid_until, 
                          subtotal, discount_percentage, discount_amount, other_charges, grand_total, 
                          reference_no, remarks, status, created_by, created_at) 
                          VALUES ('$quotation_no', '$quotation_date', $customer_id, $valid_until, 
                          $subtotal, $discount_percentage, $discount_amount, $other_charges, $grand_total, 
                          '$reference_no', '$remarks', '$status', $created_by, NOW())";
        
        if(!mysqli_query($conn, $insert_master)) {
            throw new Exception("Failed to save quotation: " . mysqli_error($conn));
        }
        
        $quotation_id = mysqli_insert_id($conn);
        
        // Get arrays from POST
        $product_ids = $_POST['product_id'];
        $client_heights = $_POST['client_height'];
        $client_widths = $_POST['client_width'];
        $std_heights = $_POST['std_height'];
        $std_widths = $_POST['std_width'];
        $quantities = $_POST['quantity'];
        $unit_prices = $_POST['unit_price'];
        $areas = $_POST['area'];
        $amounts = $_POST['amount'];
        $discount_percents = $_POST['discount_percent'];
        $net_amounts = $_POST['net_amount'];
        
        for($i = 0; $i < count($product_ids); $i++) {
            if(!empty($product_ids[$i])) {
                $product_id = intval($product_ids[$i]);
                $client_height = floatval($client_heights[$i]);
                $client_width = floatval($client_widths[$i]);
                $std_height = floatval($std_heights[$i]);
                $std_width = floatval($std_widths[$i]);
                $uom = 'Inch';
                $quantity = floatval($quantities[$i]);
                $unit_price = floatval($unit_prices[$i]);
                $area = floatval($areas[$i]);
                $amount = floatval($amounts[$i]);
                $discount_percent = floatval($discount_percents[$i]);
                $discount_amount_row = $amount * ($discount_percent / 100);
                $net_amount = floatval($net_amounts[$i]);
                
                $insert_detail = "INSERT INTO quotation_details (quotation_id, product_id, client_height, client_width, 
                                  std_height, std_width, uom, quantity, unit_price, area, amount, 
                                  discount_percentage, discount_amount, net_amount) 
                                  VALUES ($quotation_id, $product_id, $client_height, $client_width, 
                                  $std_height, $std_width, '$uom', $quantity, $unit_price, $area, $amount, 
                                  $discount_percent, $discount_amount_row, $net_amount)";
                
                if(!mysqli_query($conn, $insert_detail)) {
                    throw new Exception("Failed to save product details: " . mysqli_error($conn));
                }
                
                // Reduce stock (total area = per-unit area x quantity), like a sale
                $total_area = $area * $quantity;
                $stock_query = "SELECT balance_qty FROM inventory_ledger WHERE product_id = $product_id ORDER BY id DESC LIMIT 1";
                $stock_result = mysqli_query($conn, $stock_query);
                $current_stock = 0;
                if($stock_result && mysqli_num_rows($stock_result) > 0) {
                    $stock_data = mysqli_fetch_assoc($stock_result);
                    $current_stock = floatval($stock_data['balance_qty']);
                }
                
                $new_stock = $current_stock - $total_area;
                
                if($new_stock < 0) {
                    throw new Exception("Insufficient stock! Available: $current_stock sq ft, Requested: $total_area sq ft");
                }
                
                $inventory_query = "INSERT INTO inventory_ledger (date, product_id, reference_type, reference_id, 
                                    qty_in, qty_out, balance_qty, unit_price, total_amount, remarks) 
                                    VALUES ('$quotation_date', '$product_id', 'QUOTATION', '$quotation_id', 
                                    0, '$total_area', '$new_stock', '$unit_price', '$net_amount', 'Quotation: $quotation_no - Total Area: $total_area sq ft')";
                
                if(!mysqli_query($conn, $inventory_query)) {
                    throw new Exception("Failed to update inventory ledger: " . mysqli_error($conn));
                }
            }
        }
        
        // Post to customer ledger (quotation amount = debit, no payment received)
        $desc_q = "SELECT GROUP_CONCAT(CONCAT(p.product_name, ' (', IFNULL(qd.client_height,'0'), ' x ', IFNULL(qd.client_width,'0'), ')') SEPARATOR ', ') as items 
                   FROM quotation_details qd 
                   LEFT JOIN products p ON qd.product_id = p.id 
                   WHERE qd.quotation_id = $quotation_id";
        $desc_r = mysqli_query($conn, $desc_q);
        $items_text = '';
        if($desc_r && mysqli_num_rows($desc_r) > 0) {
            $items_text = mysqli_fetch_assoc($desc_r)['items'] ?? '';
        }
        $description = "Quotation: $quotation_no" . ($items_text ? " - $items_text" : "");
        
        $customer_balance_query = "SELECT SUM(debit) - SUM(credit) as balance FROM customer_ledger WHERE customer_id = $customer_id";
        $customer_balance_result = mysqli_query($conn, $customer_balance_query);
        $current_customer_balance = 0;
        if($customer_balance_result && mysqli_num_rows($customer_balance_result) > 0) {
            $bal_data = mysqli_fetch_assoc($customer_balance_result);
            $current_customer_balance = floatval($bal_data['balance']);
        }
        
        $new_customer_balance = $current_customer_balance + $grand_total;
        
        $customer_ledger_query = "INSERT INTO customer_ledger (date, customer_id, reference_type, reference_id, 
                                  description, debit, credit, balance) 
                                  VALUES ('$quotation_date', '$customer_id', 'QUOTATION', '$quotation_id', 
                                  '" . mysqli_real_escape_string($conn, $description) . "', '$grand_total', 0, '$new_customer_balance')";
        
        if(!mysqli_query($conn, $customer_ledger_query)) {
            throw new Exception("Failed to update customer ledger: " . mysqli_error($conn));
        }
        
        $update_customer = "UPDATE customers SET current_balance = $new_customer_balance WHERE id = $customer_id";
        if(!mysqli_query($conn, $update_customer)) {
            throw new Exception("Failed to update customer balance: " . mysqli_error($conn));
        }
        
        // If this quotation was loaded from a hold quotation, remove the hold
        // (inside the transaction so it commits/rolls back with the quotation)
        if($hold_id > 0) {
            $hold_check = mysqli_query($conn, "SELECT status FROM hold_quotations_master WHERE id = $hold_id");
            if($hold_check && mysqli_num_rows($hold_check) > 0) {
                $hold_status = mysqli_fetch_assoc($hold_check)['status'];
                if($hold_status == 'hold') {
                    if(!mysqli_query($conn, "DELETE FROM hold_quotations_details WHERE hold_id = $hold_id") ||
                       !mysqli_query($conn, "DELETE FROM hold_quotations_master WHERE id = $hold_id")) {
                        throw new Exception("Failed to remove hold quotation: " . mysqli_error($conn));
                    }
                }
            }
        }
        
        mysqli_commit($conn);
        
        echo json_encode([
            'success' => true,
            'message' => 'Quotation saved successfully with status: ' . ucfirst($status),
            'quotation_id' => $quotation_id,
            'quotation_no' => $quotation_no,
            'status' => $status
        ]);
        
    } catch(Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

mysqli_close($conn);
?>