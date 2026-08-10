<?php
/**
 * Add Customer AJAX Handler - FIXED VERSION
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

if(isset($_POST['customer_name'])) {
    $customer_name = mysqli_real_escape_string($conn, trim($_POST['customer_name']));
    $mobile = mysqli_real_escape_string($conn, trim($_POST['mobile']));
    
    if(empty($customer_name)) {
        $response['message'] = "Customer name is required!";
    } else {
        // Check if customer already exists
        $check = mysqli_query($conn, "SELECT id, customer_code FROM customers WHERE customer_name = '$customer_name'");
        if(mysqli_num_rows($check) > 0) {
            $row = mysqli_fetch_assoc($check);
            $response['success'] = true;
            $response['message'] = "Customer already exists!";
            $response['customer_id'] = $row['id'];
            $response['customer_code'] = $row['customer_code'];
            $response['already_exists'] = true;
            echo json_encode($response);
            exit();
        }
        
        // Generate unique customer code
        $prefix = "CUS";
        $code_query = "SELECT customer_code FROM customers WHERE customer_code LIKE '{$prefix}%' ORDER BY id DESC LIMIT 1";
        $code_result = mysqli_query($conn, $code_query);
        if(mysqli_num_rows($code_result) > 0) {
            $row = mysqli_fetch_assoc($code_result);
            $last_code = $row['customer_code'];
            $number = intval(substr($last_code, 4)) + 1;
            $customer_code = $prefix . "-" . str_pad($number, 4, '0', STR_PAD_LEFT);
        } else {
            $customer_code = $prefix . "-0001";
        }
        
        if(empty($mobile)) {
            $mobile = '0000000000';
        }
        
        $insert = "INSERT INTO customers (customer_code, customer_name, mobile, opening_balance, current_balance, balance_type, status, notes) 
                   VALUES ('$customer_code', '$customer_name', '$mobile', 0, 0, 'receivable', 1, 'Added from quotation form')";
        
        if(mysqli_query($conn, $insert)) {
            $response['success'] = true;
            $response['message'] = "Customer added successfully!";
            $response['customer_id'] = mysqli_insert_id($conn);
            $response['customer_code'] = $customer_code;
        } else {
            $response['message'] = "Database error: " . mysqli_error($conn);
        }
    }
}

echo json_encode($response);
mysqli_close($conn);
?>