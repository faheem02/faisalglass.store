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
    
    $check_customer = "SELECT id, customer_name FROM customers WHERE id = $customer_id";
    $customer_result = mysqli_query($conn, $check_customer);
    
    if(mysqli_num_rows($customer_result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Customer not found!']);
        exit();
    }
    
    $customer = mysqli_fetch_assoc($customer_result);
    $customer_name = $customer['customer_name'];
    
    $delete_query = "DELETE FROM customers WHERE id = $customer_id";
    
    if(mysqli_query($conn, $delete_query)) {
        echo json_encode(['success' => true, 'message' => "Customer '$customer_name' has been successfully deleted! All data (sales, payments, ledger) is safe."]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

mysqli_close($conn);
?>