<?php
/**
 * Delete Product Endpoint
 * Faysal Glass And Aluminium Centre
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please login.']);
    exit();
}

include_once('../includes/database.php');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $product_id = intval($_POST['id']);

    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
        exit();
    }

    // Check if product exists
    $check_query = "SELECT id, product_name, product_code FROM products WHERE id = $product_id";
    $result = mysqli_query($conn, $check_query);

    if (!$result || mysqli_num_rows($result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found or already deleted!']);
        exit();
    }

    $product = mysqli_fetch_assoc($result);
    $product_name = $product['product_name'];

    mysqli_begin_transaction($conn);

    try {
        // Clean up linked product tables
        mysqli_query($conn, "DELETE FROM product_sizes WHERE product_id = $product_id");
        mysqli_query($conn, "DELETE FROM opening_stock WHERE product_id = $product_id");
        mysqli_query($conn, "DELETE FROM inventory_ledger WHERE product_id = $product_id");
        mysqli_query($conn, "DELETE FROM hold_sales_details WHERE product_id = $product_id");
        mysqli_query($conn, "DELETE FROM hold_quotations_details WHERE product_id = $product_id");

        // Delete product
        $delete_query = "DELETE FROM products WHERE id = $product_id";
        if (!mysqli_query($conn, $delete_query)) {
            throw new Exception("Failed to delete product: " . mysqli_error($conn));
        }

        mysqli_commit($conn);

        echo json_encode([
            'success' => true,
            'message' => "Product '{$product_name}' has been successfully deleted!"
        ]);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters.']);
}

mysqli_close($conn);
