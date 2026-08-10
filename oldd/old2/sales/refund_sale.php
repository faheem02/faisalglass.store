<?php
/**
 * Refund Sale AJAX Handler
 * Faysal Glass And Aluminium Centre
 * 
 * Handles both cash and credit sale refunds with proper accounting
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

if(isset($_POST['refund_sale'])) {
    $sale_id = intval($_POST['sale_id']);
    $refund_date = mysqli_real_escape_string($conn, $_POST['refund_date']);
    $refund_reason = mysqli_real_escape_string($conn, trim($_POST['refund_reason']));
    $refund_items = isset($_POST['refund_items']) ? $_POST['refund_items'] : array();
    $refund_quantities = isset($_POST['refund_quantities']) ? $_POST['refund_quantities'] : array();
    $refund_amounts = isset($_POST['refund_amounts']) ? $_POST['refund_amounts'] : array();
    
    // Fetch original sale details
    $sale_query = "SELECT * FROM sale_master WHERE id = $sale_id";
    $sale_result = mysqli_query($conn, $sale_query);
    
    if(!$sale_result || mysqli_num_rows($sale_result) == 0) {
        $response['message'] = "Sale not found!";
        echo json_encode($response);
        exit();
    }
    
    $sale = mysqli_fetch_assoc($sale_result);
    $payment_type = $sale['payment_type'];
    
    // Validation
    if(empty($refund_date)) {
        $response['message'] = "Refund date is required!";
    } elseif(empty($refund_items) || count($refund_items) == 0) {
        $response['message'] = "Please select at least one item to refund!";
    } else {
        mysqli_begin_transaction($conn);
        
        try {
            $total_refund_amount = 0;
            $refund_details = [];
            
            // Process each refund item
            for($i = 0; $i < count($refund_items); $i++) {
                $detail_id = intval($refund_items[$i]);
                $refund_qty = floatval($refund_quantities[$i]);
                $refund_amt = floatval($refund_amounts[$i]);
                
                // Get original sale detail
                $detail_query = "SELECT * FROM sale_details WHERE id = $detail_id AND sale_id = $sale_id";
                $detail_result = mysqli_query($conn, $detail_query);
                
                if(!$detail_result || mysqli_num_rows($detail_result) == 0) {
                    throw new Exception("Sale detail not found for ID: $detail_id");
                }
                
                $detail = mysqli_fetch_assoc($detail_result);
                $original_qty = floatval($detail['quantity']);
                $original_amount = floatval($detail['amount']);
                $unit_rate = $original_amount / $original_qty;
                $refunded_qty_sofar = floatval($detail['refunded_qty'] ?? 0);
                $available_qty = $original_qty - $refunded_qty_sofar;
                
                // Calculate refund amount if not provided
                if($refund_amt <= 0) {
                    $refund_amt = $refund_qty * $unit_rate;
                }
                
                // Check if refund quantity exceeds available quantity
                if($refund_qty > $available_qty) {
                    throw new Exception("Refund quantity cannot exceed available quantity! Available: $available_qty");
                }
                
                $total_refund_amount += $refund_amt;
                
                // Update sale_details - mark refunded quantity
                $new_qty = $original_qty - $refund_qty;
                $new_amount = $original_amount - $refund_amt;
                $new_refunded_qty = $refunded_qty_sofar + $refund_qty;
                $new_refunded_amount = floatval($detail['refunded_amount'] ?? 0) + $refund_amt;
                
                $update_detail = "UPDATE sale_details SET 
                                  quantity = $new_qty, 
                                  amount = $new_amount,
                                  refunded_qty = $new_refunded_qty,
                                  refunded_amount = $new_refunded_amount
                                  WHERE id = $detail_id";
                mysqli_query($conn, $update_detail);
                
                // Update inventory (add back stock)
                $product_id = $detail['product_id'];
                $stock_query = "SELECT balance_qty FROM inventory_ledger WHERE product_id = $product_id ORDER BY id DESC LIMIT 1";
                $stock_result = mysqli_query($conn, $stock_query);
                $current_stock = 0;
                if($stock_result && mysqli_num_rows($stock_result) > 0) {
                    $stock_data = mysqli_fetch_assoc($stock_result);
                    $current_stock = floatval($stock_data['balance_qty']);
                }
                
                $new_stock = $current_stock + $refund_qty;
                
                $inventory_query = "INSERT INTO inventory_ledger (date, product_id, reference_type, reference_id, 
                                    qty_in, qty_out, balance_qty, unit_price, total_amount, remarks) 
                                    VALUES ('$refund_date', '$product_id', 'ADJUSTMENT', $sale_id, 
                                    $refund_qty, 0, $new_stock, $unit_rate, $refund_amt, 
                                    'Refund from Sale Invoice: {$sale['invoice_no']}')";
                
                if(!mysqli_query($conn, $inventory_query)) {
                    throw new Exception("Failed to update inventory ledger");
                }
                
                $refund_details[] = [
                    'product_id' => $product_id,
                    'quantity' => $refund_qty,
                    'amount' => $refund_amt
                ];
            }
            
            // Update sale_master with refund information
            $new_grand_total = $sale['grand_total'] - $total_refund_amount;
            $new_received_amount = $sale['received_amount'] - $total_refund_amount;
            $new_remaining_amount = $sale['remaining_amount'];
            
            // Determine refund status
            $refund_status = ($new_grand_total <= 0) ? 'full' : 'partial';
            
            $update_sale = "UPDATE sale_master SET 
                            grand_total = $new_grand_total,
                            received_amount = $new_received_amount,
                            refund_status = '$refund_status',
                            refund_amount = refund_amount + $total_refund_amount,
                            refund_date = '$refund_date',
                            refund_reason = CONCAT(COALESCE(refund_reason, ''), '\n', '$refund_reason')
                            WHERE id = $sale_id";
            mysqli_query($conn, $update_sale);
            
            // =====================================================
            // CUSTOMER LEDGER UPDATE FOR REFUND
            // =====================================================
            // For credit sale: Refund reduces customer's receivable balance
            // So we need to add a CREDIT entry in customer_ledger
            // For cash sale: Refund also reduces customer balance (if any outstanding)
            
            if($total_refund_amount > 0) {
                // Get current customer balance from customer_ledger
                $customer_balance_query = "SELECT SUM(debit) - SUM(credit) as balance FROM customer_ledger WHERE customer_id = {$sale['customer_id']}";
                $customer_balance_result = mysqli_query($conn, $customer_balance_query);
                $current_customer_balance = 0;
                if($customer_balance_result && mysqli_num_rows($customer_balance_result) > 0) {
                    $bal_data = mysqli_fetch_assoc($customer_balance_result);
                    $current_customer_balance = floatval($bal_data['balance']);
                }
                
                // Calculate new balance (Refund CREDIT reduces receivable)
                $new_customer_balance = $current_customer_balance - $total_refund_amount;
                
                $refund_description = "Refund - Sale Invoice: {$sale['invoice_no']} - Reason: $refund_reason";
                
                // Insert CREDIT entry in customer_ledger (reduces receivable)
                $customer_ledger_query = "INSERT INTO customer_ledger (date, customer_id, reference_type, reference_id, 
                                          description, debit, credit, balance) 
                                          VALUES ('$refund_date', '{$sale['customer_id']}', 'ADJUSTMENT', $sale_id, 
                                          '$refund_description', 0, $total_refund_amount, $new_customer_balance)";
                
                if(!mysqli_query($conn, $customer_ledger_query)) {
                    throw new Exception("Failed to update customer ledger for refund");
                }
                
                // Update customer's current_balance in customers table
                $update_customer = "UPDATE customers SET current_balance = $new_customer_balance WHERE id = {$sale['customer_id']}";
                mysqli_query($conn, $update_customer);
                
                // =====================================================
                // CASH/BANK BOOK UPDATE FOR REFUND (ONLY FOR CASH/PAID SALES)
                // =====================================================
                // For cash sales or partial payments where money was received,
                // refund requires updating cash/bank book (CREDIT entry - money going out)
                if($sale['received_amount'] > 0 && $total_refund_amount > 0) {
                    // Calculate how much of this refund is from received amount
                    $refund_from_received = min($total_refund_amount, $sale['received_amount']);
                    
                    if($refund_from_received > 0) {
                        // Get current cash balance
                        $cash_bal_query = "SELECT SUM(debit) - SUM(credit) as balance FROM cash_book";
                        $cash_bal_result = mysqli_query($conn, $cash_bal_query);
                        $current_cash_balance = 0;
                        if($cash_bal_result && mysqli_num_rows($cash_bal_result) > 0) {
                            $cash_bal_data = mysqli_fetch_assoc($cash_bal_result);
                            $current_cash_balance = floatval($cash_bal_data['balance']);
                        }
                        
                        $new_cash_balance = $current_cash_balance - $refund_from_received;
                        
                        $cash_query = "INSERT INTO cash_book (date, reference_type, reference_id, description, 
                                      debit, credit, balance) 
                                      VALUES ('$refund_date', 'REFUND', $sale_id, 
                                      'Refund for Sale Invoice: {$sale['invoice_no']}', 0, $refund_from_received, $new_cash_balance)";
                        mysqli_query($conn, $cash_query);
                    }
                }
            }
            
            mysqli_commit($conn);
            
            $response['success'] = true;
            $response['message'] = "Refund processed successfully! Total refund amount: " . formatCurrency($total_refund_amount);
            $response['refund_amount'] = $total_refund_amount;
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $response['message'] = $e->getMessage();
            error_log("Refund Error: " . $e->getMessage());
        }
    }
}

echo json_encode($response);
mysqli_close($conn);
exit();
?>