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

$response = ['success' => false, 'message' => ''];

if(isset($_POST['customer_name'])) {
    $customer_name = mysqli_real_escape_string($conn, trim($_POST['customer_name']));
    $mobile = mysqli_real_escape_string($conn, trim($_POST['mobile']));
    
    if(empty($customer_name)) {
        $response['message'] = "Customer name is required!";
    } else {
        // Check if customer with same name already exists
        $check_query = "SELECT id, customer_code FROM customers WHERE customer_name = '$customer_name'";
        $check_result = mysqli_query($conn, $check_query);
        
        if(mysqli_num_rows($check_result) > 0) {
            $existing = mysqli_fetch_assoc($check_result);
            $response['success'] = true;
            $response['message'] = "Customer already exists!";
            $response['customer_id'] = $existing['id'];
            $response['customer_code'] = $existing['customer_code'];
            $response['already_exists'] = true;
            echo json_encode($response);
            exit();
        }
        
        // Generate unique customer code - FIXED: Handle existing codes properly
        $prefix = "CUS";
        $max_code_query = "SELECT customer_code FROM customers WHERE customer_code REGEXP '^{$prefix}-[0-9]+$' ORDER BY CAST(SUBSTRING(customer_code, 5) AS UNSIGNED) DESC LIMIT 1";
        $code_result = mysqli_query($conn, $max_code_query);
        
        $max_number = 0;
        if($code_result && mysqli_num_rows($code_result) > 0) {
            $row = mysqli_fetch_assoc($code_result);
            $last_code = $row['customer_code'];
            // Extract number from code (format: CUS-0001)
            $number_part = substr($last_code, 4);
            $max_number = intval($number_part);
        }
        
        $new_number = $max_number + 1;
        $customer_code = $prefix . "-" . str_pad($new_number, 4, '0', STR_PAD_LEFT);
        
        // Also check for TMP prefix codes
        $tmp_check = "SELECT customer_code FROM customers WHERE customer_code LIKE 'TMP%' ORDER BY id DESC LIMIT 1";
        $tmp_result = mysqli_query($conn, $tmp_check);
        if($tmp_result && mysqli_num_rows($tmp_result) > 0) {
            $tmp_row = mysqli_fetch_assoc($tmp_result);
            // Don't use TMP for new customers, only for walk-in
        }
        
        if(empty($mobile)) {
            $mobile = '0000000000';
        }
        
        $insert_query = "INSERT INTO customers (customer_code, customer_name, mobile, opening_balance, current_balance, balance_type, status, notes) 
                         VALUES ('$customer_code', '$customer_name', '$mobile', 0, 0, 'receivable', 1, 'Customer added from sale form')";
        
        if(mysqli_query($conn, $insert_query)) {
            $response['success'] = true;
            $response['message'] = "Customer added successfully!";
            $response['customer_id'] = mysqli_insert_id($conn);
            $response['customer_code'] = $customer_code;
        } else {
            $response['message'] = "Failed to add customer: " . mysqli_error($conn);
        }
    }
}

echo json_encode($response);
mysqli_close($conn);
?>