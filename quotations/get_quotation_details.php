<?php
/**
 * Get Quotation Details (AJAX) - for the View popup modal
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

$quotation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($quotation_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid quotation id']);
    exit;
}

// Check if payment columns exist
$col_check = mysqli_query($conn, "SHOW COLUMNS FROM quotation_master LIKE 'bank_account_id'");
$has_payment_cols = ($col_check && mysqli_num_rows($col_check) > 0);

if($has_payment_cols) {
    $query = "SELECT q.*, c.customer_name, c.customer_code, c.mobile, c.address, c.current_balance, b.bank_name, b.account_number
              FROM quotation_master q
              LEFT JOIN customers c ON q.customer_id = c.id
              LEFT JOIN bank_accounts b ON q.bank_account_id = b.id
              WHERE q.id = $quotation_id";
} else {
    $query = "SELECT q.*, c.customer_name, c.customer_code, c.mobile, c.address, c.current_balance
              FROM quotation_master q
              LEFT JOIN customers c ON q.customer_id = c.id
              WHERE q.id = $quotation_id";
}
$result = mysqli_query($conn, $query);

if(!$result || mysqli_num_rows($result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Quotation not found']);
    exit;
}

$quotation = mysqli_fetch_assoc($result);

$details_query = "SELECT qd.*, p.product_name, p.product_code
                 FROM quotation_details qd
                 LEFT JOIN products p ON qd.product_id = p.id
                 WHERE qd.quotation_id = $quotation_id";
$details_result = mysqli_query($conn, $details_query);

$items = [];
if($details_result) {
    while($detail = mysqli_fetch_assoc($details_result)) {
        $items[] = $detail;
    }
}

$response = [
    'success' => true,
    'quotation' => [
        'id' => $quotation['id'],
        'quotation_no' => $quotation['quotation_no'],
        'quotation_date' => date('d-m-Y', strtotime($quotation['quotation_date'])),
        'valid_until' => $quotation['valid_until'] ? date('d-m-Y', strtotime($quotation['valid_until'])) : '',
        'reference_no' => $quotation['reference_no'] ?? '',
        'remarks' => $quotation['remarks'] ?? '',
        'status' => $quotation['status'],
        'subtotal' => floatval($quotation['subtotal']),
        'discount_percentage' => floatval($quotation['discount_percentage']),
        'discount_amount' => floatval($quotation['discount_amount']),
        'other_charges' => floatval($quotation['other_charges']),
        'grand_total' => floatval($quotation['grand_total']),
        'received_amount' => floatval($quotation['received_amount'] ?? 0),
        'remaining_amount' => floatval($quotation['remaining_amount'] ?? 0),
        'payment_type' => $quotation['payment_type'] ?? 'credit',
        'bank_name' => $quotation['bank_name'] ?? '',
        'account_number' => $quotation['account_number'] ?? '',
    ],
    'customer' => [
        'customer_name' => $quotation['customer_name'] ?? 'Walk-In',
        'customer_code' => $quotation['customer_code'] ?? '',
        'mobile' => $quotation['mobile'] ?? '',
        'address' => $quotation['address'] ?? '',
        'current_balance' => floatval($quotation['current_balance'] ?? 0),
    ],
    'items' => $items
];

echo json_encode($response);
mysqli_close($conn);
