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