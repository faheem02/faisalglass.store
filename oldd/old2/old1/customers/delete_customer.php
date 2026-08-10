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
    $check_customer = "SELECT id, status FROM customers WHERE id = $customer_id";
    $customer_result = mysqli_query($conn, $check_customer);
    
    if(mysqli_num_rows($customer_result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Customer not found!']);
        exit();
    }
    
    $customer = mysqli_fetch_assoc($customer_result);
    
    // Toggle status: If active -> inactive, If inactive -> active
    $new_status = ($customer['status'] == 1) ? 0 : 1;
    $action = ($new_status == 0) ? 'deactivated' : 'activated';
    
    // Soft delete - just update status
    $update_query = "UPDATE customers SET status = $new_status WHERE id = $customer_id";
    if(mysqli_query($conn, $update_query)) {
        echo json_encode(['success' => true, 'message' => "Customer $action successfully!"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

mysqli_close($conn);
?>