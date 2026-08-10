<?php
/**
 * Save Purchase AJAX Handler - FIXED (Stock movement uses total area)
 * Faysal Glass And Aluminium Centre
 */

session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access!']);
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$response = ['success' => false, 'message' => ''];

if(isset($_POST['save_purchase'])) {
    $purchase_date = mysqli_real_escape_string($conn, $_POST['purchase_date']);
    $supplier_id = intval($_POST['supplier_id']);
    $subtotal = floatval($_POST['subtotal']);
    $discount_percentage = floatval($_POST['discount_percentage']);
    $discount_amount = floatval($_POST['discount_amount']);
    $other_charges = floatval($_POST['other_charges']);
    $grand_total = floatval($_POST['grand_total']);
    $paid_amount = floatval($_POST['paid_amount']);
    $remaining_amount = floatval($_POST['remaining_amount']);
    $payment_type = mysqli_real_escape_string($conn, $_POST['payment_type']);
    $bank_account_id = isset($_POST['bank_account_id']) ? intval($_POST['bank_account_id']) : 0;
    $reference_no = mysqli_real_escape_string($conn, trim($_POST['reference_no']));
    $remarks = mysqli_real_escape_string($conn, trim($_POST['remarks']));
    
    // Get products data
    $product_ids = $_POST['product_id'];
    $client_heights = $_POST['client_height'];
    $client_widths = $_POST['client_width'];
    $std_heights = $_POST['std_height'];
    $std_widths = $_POST['std_width'];
    $quantities = $_POST['quantity'];      // number of pieces
    $unit_prices = $_POST['unit_price'];
    $retail_prices = $_POST['retail_price'];
    $areas = $_POST['area'];               // area per piece (sq ft)
    $amounts = $_POST['amount'];
    $discount_percents = $_POST['discount_percent'];
    $net_amounts = $_POST['net_amount'];
    
    // Validation
    if(empty($purchase_date)) {
        $response['message'] = "Purchase date is required!";
    } elseif($supplier_id <= 0) {
        $response['message'] = "Please select a supplier!";
    } elseif(count($product_ids) == 0) {
        $response['message'] = "Please add at least one product!";
    } elseif($grand_total <= 0) {
        $response['message'] = "Grand total must be greater than 0!";
    } elseif($payment_type != 'credit' && $paid_amount <= 0) {
        $response['message'] = "Paid amount is required for cash/bank payment!";
    } elseif($paid_amount > $grand_total) {
        $response['message'] = "Paid amount cannot exceed grand total!";
    } elseif($payment_type == 'bank' && $bank_account_id <= 0) {
        $response['message'] = "Please select a bank account!";
    } else {
        mysqli_begin_transaction($conn);
        
        try {
            // Generate invoice number
            $prefix = "PUR";
            $inv_query = "SELECT invoice_no FROM purchase_master WHERE invoice_no LIKE '{$prefix}%' ORDER BY id DESC LIMIT 1";
            $inv_result = mysqli_query($conn, $inv_query);
            if($inv_result && mysqli_num_rows($inv_result) > 0) {
                $row = mysqli_fetch_assoc($inv_result);
                $last_no = $row['invoice_no'];
                $number = intval(substr($last_no, 4)) + 1;
                $invoice_no = $prefix . "-" . str_pad($number, 5, '0', STR_PAD_LEFT);
            } else {
                $invoice_no = $prefix . "-00001";
            }
            
            // Insert into purchase_master
            $insert_master = "INSERT INTO purchase_master (invoice_no, purchase_date, supplier_id, subtotal, 
                              discount_percentage, discount_amount, other_charges, grand_total, paid_amount, 
                              remaining_amount, payment_type, bank_account_id, reference_no, remarks, created_by) 
                              VALUES ('$invoice_no', '$purchase_date', '$supplier_id', '$subtotal', 
                              '$discount_percentage', '$discount_amount', '$other_charges', '$grand_total', 
                              '$paid_amount', '$remaining_amount', '$payment_type', '$bank_account_id', 
                              '$reference_no', '$remarks', '{$_SESSION['user_id']}')";
            
            if(!mysqli_query($conn, $insert_master)) {
                throw new Exception("Failed to save purchase invoice: " . mysqli_error($conn));
            }
            
            $purchase_id = mysqli_insert_id($conn);
            
            // Insert purchase details and update inventory
            for($i = 0; $i < count($product_ids); $i++) {
                $product_id = intval($product_ids[$i]);
                $client_height = floatval($client_heights[$i]);
                $client_width = floatval($client_widths[$i]);
                $std_height = $std_heights[$i];
                $std_width = $std_widths[$i];
                $quantity = floatval($quantities[$i]);               // pieces
                $unit_price = floatval($unit_prices[$i]);
                $retail_price = floatval($retail_prices[$i]);
                $area = floatval($areas[$i]);                       // area per piece (sq ft)
                $amount = floatval($amounts[$i]);
                $discount_percent = floatval($discount_percents[$i]);
                $discount_amt = $amount * ($discount_percent / 100);
                $net_amount = floatval($net_amounts[$i]);
                
                // Calculate total area (sq ft) for inventory movement
                $total_area = $area * $quantity;
                
                $insert_detail = "INSERT INTO purchase_details (purchase_id, product_id, client_height, client_width,
                                  std_height, std_width, quantity, unit_price, retail_price, area, amount, 
                                  discount_percentage, discount_amount, net_amount) 
                                  VALUES ('$purchase_id', '$product_id', '$client_height', '$client_width',
                                  '$std_height', '$std_width', '$quantity', '$unit_price', '$retail_price', 
                                  '$area', '$amount', '$discount_percent', '$discount_amt', '$net_amount')";
                
                if(!mysqli_query($conn, $insert_detail)) {
                    throw new Exception("Failed to save purchase details: " . mysqli_error($conn));
                }
                
                // ---------- INVENTORY UPDATE USING TOTAL AREA ----------
                $stock_query = "SELECT balance_qty FROM inventory_ledger WHERE product_id = $product_id ORDER BY id DESC LIMIT 1";
                $stock_result = mysqli_query($conn, $stock_query);
                $current_stock = 0;
                if($stock_result && mysqli_num_rows($stock_result) > 0) {
                    $stock_data = mysqli_fetch_assoc($stock_result);
                    $current_stock = floatval($stock_data['balance_qty']);
                }
                
                // Increase stock by total area (sq ft)
                $new_stock = $current_stock + $total_area;
                
                $inventory_query = "INSERT INTO inventory_ledger (date, product_id, reference_type, reference_id, 
                                    qty_in, qty_out, balance_qty, unit_price, total_amount, remarks) 
                                    VALUES ('$purchase_date', '$product_id', 'PURCHASE', '$purchase_id', 
                                    '$total_area', 0, '$new_stock', '$unit_price', '$net_amount', 'Purchase Invoice: $invoice_no')";
                
                if(!mysqli_query($conn, $inventory_query)) {
                    throw new Exception("Failed to update inventory ledger");
                }
                
                // Update sale price if retail price provided
                if($retail_price > 0) {
                    $update_product = "UPDATE products SET sale_price = $retail_price WHERE id = $product_id";
                    mysqli_query($conn, $update_product);
                }
            }
            
            // Supplier ledger (using remaining amount) – unchanged
            $supplier_balance_query = "SELECT SUM(credit) - SUM(debit) as balance FROM supplier_ledger WHERE supplier_id = $supplier_id";
            $supplier_balance_result = mysqli_query($conn, $supplier_balance_query);
            $current_supplier_balance = 0;
            if($supplier_balance_result && mysqli_num_rows($supplier_balance_result) > 0) {
                $bal_data = mysqli_fetch_assoc($supplier_balance_result);
                $current_supplier_balance = floatval($bal_data['balance']);
            }
            $new_supplier_balance = $current_supplier_balance + $remaining_amount;
            $supplier_ledger_query = "INSERT INTO supplier_ledger (date, supplier_id, reference_type, reference_id, 
                                      description, debit, credit, balance) 
                                      VALUES ('$purchase_date', '$supplier_id', 'PURCHASE', '$purchase_id', 
                                      'Purchase Invoice: $invoice_no', 0, '$remaining_amount', '$new_supplier_balance')";
            if(!mysqli_query($conn, $supplier_ledger_query)) {
                throw new Exception("Failed to update supplier ledger");
            }
            $update_supplier = "UPDATE suppliers SET current_balance = $new_supplier_balance WHERE id = $supplier_id";
            mysqli_query($conn, $update_supplier);
            
            // Cash/bank book (payment) – unchanged
            if($paid_amount > 0) {
                if($payment_type == 'cash') {
                    $cash_bal_query = "SELECT SUM(debit) - SUM(credit) as balance FROM cash_book";
                    $cash_bal_result = mysqli_query($conn, $cash_bal_query);
                    $current_cash_balance = 0;
                    if($cash_bal_result && mysqli_num_rows($cash_bal_result) > 0) {
                        $cash_bal_data = mysqli_fetch_assoc($cash_bal_result);
                        $current_cash_balance = floatval($cash_bal_data['balance']);
                    }
                    $new_cash_balance = $current_cash_balance - $paid_amount;
                    $cash_query = "INSERT INTO cash_book (date, reference_type, reference_id, description, 
                                  debit, credit, balance) 
                                  VALUES ('$purchase_date', 'PURCHASE', '$purchase_id', 
                                  'Purchase Payment: $invoice_no', 0, '$paid_amount', '$new_cash_balance')";
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
                    $new_bank_balance = $current_bank_balance - $paid_amount;
                    $bank_query = "INSERT INTO bank_book (date, bank_account_id, reference_type, reference_id, 
                                  description, debit, credit, balance) 
                                  VALUES ('$purchase_date', '$bank_account_id', 'PURCHASE', '$purchase_id', 
                                  'Purchase Payment: $invoice_no', 0, '$paid_amount', '$new_bank_balance')";
                    mysqli_query($conn, $bank_query);
                }
            }
            
            mysqli_commit($conn);
            
            $response['success'] = true;
            $response['message'] = "Purchase invoice created successfully!";
            $response['invoice_no'] = $invoice_no;
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $response['message'] = $e->getMessage();
            error_log("Purchase Error: " . $e->getMessage());
        }
    }
}

echo json_encode($response);
mysqli_close($conn);
?>