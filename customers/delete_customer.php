<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $customer_id = intval($_POST['id']);
    
    // Check if customer exists
    $check_customer = "SELECT id, customer_name FROM customers WHERE id = $customer_id";
    $customer_result = mysqli_query($conn, $check_customer);
    
    if(mysqli_num_rows($customer_result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Customer not found!']);
        exit();
    }
    
    $customer = mysqli_fetch_assoc($customer_result);
    $customer_name = $customer['customer_name'];
    
    // Check if customer has any actual transactions (sales, payments, quotations, or ledger entries other than OPENING)
    $sales_cnt = 0;
    $pay_cnt = 0;
    $quote_cnt = 0;
    $ledger_cnt = 0;
    
    $res = mysqli_query($conn, "SELECT COUNT(*) as c FROM sale_master WHERE customer_id = $customer_id");
    if($res) { $sales_cnt = intval(mysqli_fetch_assoc($res)['c'] ?? 0); }
    
    $res = mysqli_query($conn, "SELECT COUNT(*) as c FROM customer_payments WHERE customer_id = $customer_id");
    if($res) { $pay_cnt = intval(mysqli_fetch_assoc($res)['c'] ?? 0); }
    
    $res = mysqli_query($conn, "SELECT COUNT(*) as c FROM quotation_master WHERE customer_id = $customer_id");
    if($res) { $quote_cnt = intval(mysqli_fetch_assoc($res)['c'] ?? 0); }
    
    $res = mysqli_query($conn, "SELECT COUNT(*) as c FROM customer_ledger WHERE customer_id = $customer_id AND reference_type != 'OPENING'");
    if($res) { $ledger_cnt = intval(mysqli_fetch_assoc($res)['c'] ?? 0); }
    
    if($sales_cnt > 0 || $pay_cnt > 0 || $quote_cnt > 0 || $ledger_cnt > 0) {
        $details = [];
        if($sales_cnt > 0) $details[] = "$sales_cnt Sales Invoices";
        if($pay_cnt > 0) $details[] = "$pay_cnt Payments";
        if($quote_cnt > 0) $details[] = "$quote_cnt Quotations";
        if($ledger_cnt > 0) $details[] = "$ledger_cnt Ledger Entries";
        
        $msg = "Yeh customer delete nahi ho sakta kyunke iske sath " . implode(', ', $details) . " linked hain! Agar aap record rakhna chahte hain to isko Edit me ja kar Inactive kar dein.";
        echo json_encode(['success' => false, 'message' => $msg]);
        exit();
    }
    
    // If no active transactions exist, delete opening ledger records and the customer
    mysqli_query($conn, "DELETE FROM customer_ledger WHERE customer_id = $customer_id");
    $delete_query = "DELETE FROM customers WHERE id = $customer_id";
    
    if(mysqli_query($conn, $delete_query)) {
        echo json_encode(['success' => true, 'message' => "Customer '$customer_name' successfully delete ho gaya hai!"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

mysqli_close($conn);
?>
