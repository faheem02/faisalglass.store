<?php
/**
 * Get Sale Details for Refund AJAX
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

if(isset($_GET['sale_id'])) {
    $sale_id = intval($_GET['sale_id']);
    
    // Fetch sale master
    $query = "SELECT s.*, c.customer_name, c.customer_code 
              FROM sale_master s
              LEFT JOIN customers c ON s.customer_id = c.id
              WHERE s.id = $sale_id";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Sale not found!']);
        mysqli_close($conn);
        exit();
    }
    
    $sale = mysqli_fetch_assoc($result);
    
    // Fetch sale details
    $details_query = "SELECT sd.*, p.product_name, p.product_code 
                     FROM sale_details sd
                     LEFT JOIN products p ON sd.product_id = p.id
                     WHERE sd.sale_id = $sale_id";
    $details_result = mysqli_query($conn, $details_query);
    
    $items = [];
    while($detail = mysqli_fetch_assoc($details_result)) {
        // Only show items that have remaining quantity (not fully refunded)
        $remaining_qty = $detail['quantity'] - ($detail['refunded_qty'] ?? 0);
        if($remaining_qty > 0) {
            $items[] = [
                'id' => $detail['id'],
                'product_id' => $detail['product_id'],
                'product_name' => $detail['product_name'],
                'product_code' => $detail['product_code'],
                'client_size' => $detail['client_size'],
                'quantity' => $remaining_qty,
                'rate' => $detail['rate'],
                'amount' => $detail['amount']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'sale_id' => $sale_id,
        'invoice_no' => $sale['invoice_no'],
        'customer_name' => $sale['customer_name'],
        'sale_date' => date('d-m-Y', strtotime($sale['sale_date'])),
        'items' => $items
    ]);
}

mysqli_close($conn);
?>