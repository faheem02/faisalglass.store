<?php
/**
 * Refund Purchase AJAX Handler - FIXED VERSION
 * Faysal Glass And Aluminium Centre
 * 
 * Handles both cash and credit purchase refunds with proper accounting
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

if(isset($_POST['refund_purchase'])) {
    $purchase_id = intval($_POST['purchase_id']);
    $refund_date = mysqli_real_escape_string($conn, $_POST['refund_date']);
    $refund_reason = mysqli_real_escape_string($conn, trim($_POST['refund_reason']));
    $refund_items = isset($_POST['refund_items']) ? $_POST['refund_items'] : array();
    $refund_quantities = isset($_POST['refund_quantities']) ? $_POST['refund_quantities'] : array();
    $refund_amounts = isset($_POST['refund_amounts']) ? $_POST['refund_amounts'] : array();
    
    // Fetch original purchase details
    $purchase_query = "SELECT * FROM purchase_master WHERE id = $purchase_id";
    $purchase_result = mysqli_query($conn, $purchase_query);
    
    if(!$purchase_result || mysqli_num_rows($purchase_result) == 0) {
        $response['message'] = "Purchase not found!";
        echo json_encode($response);
        exit();
    }
    
    $purchase = mysqli_fetch_assoc($purchase_result);
    $payment_type = $purchase['payment_type'];
    
    // Validation
    if(empty($refund_date)) {
        $response['message'] = "Refund date is required!";
    } elseif(empty($refund_items) || count($refund_items) == 0) {
        $response['message'] = "Please select at least one item to refund!";
    } else {
        mysqli_begin_transaction($conn);
        
        try {
            $total_refund_amount = 0;
            
            // Process each refund item
            for($i = 0; $i < count($refund_items); $i++) {
                $detail_id = intval($refund_items[$i]);
                $refund_qty = floatval($refund_quantities[$i]);
                $refund_amt = floatval($refund_amounts[$i]);
                
                // Get original purchase detail
                $detail_query = "SELECT * FROM purchase_details WHERE id = $detail_id AND purchase_id = $purchase_id";
                $detail_result = mysqli_query($conn, $detail_query);
                
                if(!$detail_result || mysqli_num_rows($detail_result) == 0) {
                    throw new Exception("Purchase detail not found for ID: $detail_id");
                }
                
                $detail = mysqli_fetch_assoc($detail_result);
                $original_qty = floatval($detail['quantity']);
                
                // FIX: Calculate unit price correctly based on net_amount / quantity
                // Use net_amount which includes any discounts properly
                $original_net_amount = floatval($detail['net_amount']);
                $unit_price = $original_net_amount / $original_qty;
                
                $refunded_qty_sofar = floatval($detail['refunded_qty'] ?? 0);
                $available_qty = $original_qty - $refunded_qty_sofar;
                
                // Calculate refund amount based on unit price if not provided
                if($refund_amt <= 0 || $refund_amt == $refund_qty * $detail['unit_price']) {
                    // Use the correct unit price that includes area calculation
                    $refund_amt = $refund_qty * $unit_price;
                }
                
                // Check if refund quantity exceeds available quantity
                if($refund_qty > $available_qty) {
                    throw new Exception("Refund quantity cannot exceed available quantity! Available: $available_qty");
                }
                
                $total_refund_amount += $refund_amt;
                
                // Calculate new values
                $new_qty = $original_qty - $refund_qty;
                $new_net_amount = $original_net_amount - $refund_amt;
                $new_refunded_qty = $refunded_qty_sofar + $refund_qty;
                $new_refunded_amount = floatval($detail['refunded_amount'] ?? 0) + $refund_amt;
                
                // Update purchase_details
                $update_detail = "UPDATE purchase_details SET 
                                  quantity = $new_qty, 
                                  net_amount = $new_net_amount,
                                  amount = $new_net_amount,
                                  refunded_qty = $new_refunded_qty,
                                  refunded_amount = $new_refunded_amount
                                  WHERE id = $detail_id";
                
                if(!mysqli_query($conn, $update_detail)) {
                    throw new Exception("Failed to update purchase details");
                }
                
                // Update inventory - FIX: Use correct unit price for inventory
                $product_id = $detail['product_id'];
                $stock_query = "SELECT balance_qty FROM inventory_ledger WHERE product_id = $product_id ORDER BY id DESC LIMIT 1";
                $stock_result = mysqli_query($conn, $stock_query);
                $current_stock = 0;
                if($stock_result && mysqli_num_rows($stock_result) > 0) {
                    $stock_data = mysqli_fetch_assoc($stock_result);
                    $current_stock = floatval($stock_data['balance_qty']);
                }
                
                $new_stock = $current_stock - $refund_qty;
                
                $inventory_query = "INSERT INTO inventory_ledger (date, product_id, reference_type, reference_id, 
                                    qty_in, qty_out, balance_qty, unit_price, total_amount, remarks) 
                                    VALUES ('$refund_date', '$product_id', 'ADJUSTMENT', $purchase_id, 
                                    0, $refund_qty, $new_stock, $unit_price, $refund_amt, 
                                    'Refund from Purchase Invoice: {$purchase['invoice_no']}')";
                
                if(!mysqli_query($conn, $inventory_query)) {
                    throw new Exception("Failed to update inventory ledger: " . mysqli_error($conn));
                }
            }
            
            // Get updated total from purchase_details
            $total_details_query = "SELECT SUM(net_amount) as total FROM purchase_details WHERE purchase_id = $purchase_id";
            $total_details_result = mysqli_query($conn, $total_details_query);
            $total_details = mysqli_fetch_assoc($total_details_result);
            $new_grand_total = floatval($total_details['total']);
            
            // Calculate new paid amount - FIX: Paid amount shouldn't change for credit purchases
            $new_paid_amount = $purchase['paid_amount'];
            $new_remaining_amount = $new_grand_total - $new_paid_amount;
            
            // Determine refund status
            $refund_status = 'partial';
            if($new_grand_total <= 0) {
                $refund_status = 'full';
            }
            
            // Get current refund amount from purchase_master
            $current_refund_amount = floatval($purchase['refund_amount'] ?? 0);
            $new_refund_amount = $current_refund_amount + $total_refund_amount;
            
            // Update purchase_master
            $update_purchase = "UPDATE purchase_master SET 
                                grand_total = $new_grand_total,
                                subtotal = $new_grand_total,
                                remaining_amount = $new_remaining_amount,
                                refund_status = '$refund_status',
                                refund_amount = $new_refund_amount,
                                refund_date = '$refund_date',
                                refund_reason = CONCAT(COALESCE(refund_reason, ''), '\n', '$refund_reason')
                                WHERE id = $purchase_id";
            
            if(!mysqli_query($conn, $update_purchase)) {
                throw new Exception("Failed to update purchase master: " . mysqli_error($conn));
            }
            
            // =====================================================
            // SUPPLIER LEDGER UPDATE FOR REFUND
            // =====================================================
            // Get current supplier balance
            $supplier_balance_query = "SELECT SUM(credit) - SUM(debit) as balance FROM supplier_ledger WHERE supplier_id = {$purchase['supplier_id']}";
            $supplier_balance_result = mysqli_query($conn, $supplier_balance_query);
            $current_supplier_balance = 0;
            if($supplier_balance_result && mysqli_num_rows($supplier_balance_result) > 0) {
                $bal_data = mysqli_fetch_assoc($supplier_balance_result);
                $current_supplier_balance = floatval($bal_data['balance']);
            }
            
            // Calculate new supplier balance (Refund DEBIT reduces payable)
            $new_supplier_balance = $current_supplier_balance - $total_refund_amount;
            
            $refund_description = "Refund - Purchase Invoice: {$purchase['invoice_no']} - Reason: $refund_reason";
            
            // Insert DEBIT entry in supplier_ledger
            $supplier_ledger_query = "INSERT INTO supplier_ledger (date, supplier_id, reference_type, reference_id, 
                                      description, debit, credit, balance) 
                                      VALUES ('$refund_date', '{$purchase['supplier_id']}', 'ADJUSTMENT', $purchase_id, 
                                      '$refund_description', $total_refund_amount, 0, $new_supplier_balance)";
            
            if(!mysqli_query($conn, $supplier_ledger_query)) {
                throw new Exception("Failed to update supplier ledger: " . mysqli_error($conn));
            }
            
            // Update supplier's current_balance
            $update_supplier = "UPDATE suppliers SET current_balance = $new_supplier_balance WHERE id = {$purchase['supplier_id']}";
            mysqli_query($conn, $update_supplier);
            
            // =====================================================
            // CASH/BANK BOOK UPDATE FOR REFUND (FOR CASH/PAID PURCHASES)
            // =====================================================
            // FIX: Only update cash/bank if money was actually paid
            if($purchase['payment_type'] == 'cash' && $purchase['paid_amount'] > 0) {
                $refund_from_paid = min($total_refund_amount, $purchase['paid_amount']);
                
                if($refund_from_paid > 0) {
                    $cash_bal_query = "SELECT SUM(debit) - SUM(credit) as balance FROM cash_book";
                    $cash_bal_result = mysqli_query($conn, $cash_bal_query);
                    $current_cash_balance = 0;
                    if($cash_bal_result && mysqli_num_rows($cash_bal_result) > 0) {
                        $cash_bal_data = mysqli_fetch_assoc($cash_bal_result);
                        $current_cash_balance = floatval($cash_bal_data['balance']);
                    }
                    
                    $new_cash_balance = $current_cash_balance + $refund_from_paid;
                    
                    $cash_query = "INSERT INTO cash_book (date, reference_type, reference_id, description, 
                                  debit, credit, balance) 
                                  VALUES ('$refund_date', 'REFUND', $purchase_id, 
                                  'Refund for Purchase Invoice: {$purchase['invoice_no']}', $refund_from_paid, 0, $new_cash_balance)";
                    mysqli_query($conn, $cash_query);
                }
            }
            
            mysqli_commit($conn);
            
            $response['success'] = true;
            $response['message'] = "Refund processed successfully! Total refund amount: " . formatCurrency($total_refund_amount);
            $response['refund_amount'] = $total_refund_amount;
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $response['message'] = $e->getMessage();
            error_log("Purchase Refund Error: " . $e->getMessage());
        }
    }
}

echo json_encode($response);
mysqli_close($conn);
exit();
?>