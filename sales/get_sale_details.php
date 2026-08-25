<?php
/**
 * Get Sale Details (AJAX) - for the View popup modal
 * Faysal Glass And Aluminium Centre
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

error_reporting(0);
header('Content-Type: application/json');

include('../includes/database.php');
include('../includes/txt.php');

while(ob_get_level() > 0) {
    ob_end_clean();
}

$sale_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($sale_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid sale id']);
    exit;
}

$query = "SELECT s.*, c.customer_name, c.customer_code, c.mobile, c.address
          FROM sale_master s
          LEFT JOIN customers c ON s.customer_id = c.id
          WHERE s.id = $sale_id";
$result = mysqli_query($conn, $query);

if(!$result || mysqli_num_rows($result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Sale not found']);
    exit;
}

$sale = mysqli_fetch_assoc($result);

$details_query = "SELECT sd.*, p.product_name, p.product_code
                 FROM sale_details sd
                 LEFT JOIN products p ON sd.product_id = p.id
                 WHERE sd.sale_id = $sale_id";
$details_result = mysqli_query($conn, $details_query);

$items = [];
if($details_result) {
    while($detail = mysqli_fetch_assoc($details_result)) {
        $items[] = $detail;
    }
}

$payment_method_display = ucfirst($sale['payment_type']);

$response = [
    'success' => true,
    'sale' => [
        'id' => $sale['id'],
        'invoice_no' => $sale['invoice_no'],
        'sale_date' => date('d-m-Y', strtotime($sale['sale_date'])),
        'payment_type' => $payment_method_display,
        'reference_no' => $sale['reference_no'] ?? '',
        'remarks' => $sale['remarks'] ?? '',
        'status' => $sale['refund_status'] ?? 'none',
        'subtotal' => floatval($sale['subtotal']),
        'discount_percentage' => floatval($sale['discount_percentage']),
        'discount_amount' => floatval($sale['discount_amount']),
        'other_charges' => floatval($sale['other_charges']),
        'grand_total' => floatval($sale['grand_total']),
        'received_amount' => floatval($sale['received_amount']),
        'remaining_amount' => floatval($sale['remaining_amount']),
    ],
    'customer' => [
        'customer_name' => $sale['customer_name'] ?? 'Walk-In',
        'customer_code' => $sale['customer_code'] ?? '',
        'mobile' => $sale['mobile'] ?? '',
        'address' => $sale['address'] ?? '',
    ],
    'items' => $items
];

echo json_encode($response);
mysqli_close($conn);
