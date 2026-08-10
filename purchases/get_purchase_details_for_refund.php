<?php
/**
 * Get Purchase Details for Refund AJAX
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

if(isset($_GET['purchase_id'])) {
    $purchase_id = intval($_GET['purchase_id']);
    
    // Fetch purchase master
    $query = "SELECT p.*, s.supplier_name, s.supplier_code 
              FROM purchase_master p
              LEFT JOIN suppliers s ON p.supplier_id = s.id
              WHERE p.id = $purchase_id";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Purchase not found!']);
        mysqli_close($conn);
        exit();
    }
    
    $purchase = mysqli_fetch_assoc($result);
    
    // Fetch purchase details
    $details_query = "SELECT pd.*, pr.product_name, pr.product_code 
                     FROM purchase_details pd
                     LEFT JOIN products pr ON pd.product_id = pr.id
                     WHERE pd.purchase_id = $purchase_id";
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
                'client_size' => ($detail['client_height'] && $detail['client_width']) ? $detail['client_height'] . ' x ' . $detail['client_width'] : '-',
                'quantity' => $remaining_qty,
                'unit_price' => $detail['unit_price'],
                'amount' => $detail['amount']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'purchase_id' => $purchase_id,
        'invoice_no' => $purchase['invoice_no'],
        'supplier_name' => $purchase['supplier_name'],
        'purchase_date' => date('d-m-Y', strtotime($purchase['purchase_date'])),
        'items' => $items
    ]);
}

mysqli_close($conn);
?>