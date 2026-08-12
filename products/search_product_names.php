<?php
/**
 * Search Product Names AJAX - autocomplete for Add Opening Stock
 * Faysal Glass And Aluminium Centre
 * 
 * Returns products whose name starts with the typed word(s).
 * Supports exact match lookup for duplicate detection.
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

error_reporting(0);
header('Content-Type: application/json');
while(ob_get_level() > 0) ob_end_clean();

include('../includes/database.php');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if($q === '') {
    echo json_encode([]);
    exit();
}

$q_esc = mysqli_real_escape_string($conn, $q);
$exact = isset($_GET['exact']) && intval($_GET['exact']) === 1;

if($exact) {
    $query = "SELECT id, product_code, product_name FROM products WHERE product_name = '$q_esc' LIMIT 5";
} else {
    // Match anywhere in the name (type first word -> all matching products)
    $query = "SELECT id, product_code, product_name FROM products WHERE product_name LIKE '%$q_esc%' ORDER BY product_name ASC LIMIT 15";
}

$result = mysqli_query($conn, $query);
$names = [];
if($result) {
    while($row = mysqli_fetch_assoc($result)) {
        $names[] = [
            'id' => intval($row['id']),
            'product_code' => $row['product_code'],
            'product_name' => $row['product_name']
        ];
    }
}

echo json_encode($names);
mysqli_close($conn);
?>
